<?php
	/* 	Torrent HTTP Scraper
		v1.0
		
		2010 by Johannes Zinnau
		johannes@johnimedia.de
		
		Licensed under a Creative Commons Attribution-ShareAlike 3.0 Unported License
		http://creativecommons.org/licenses/by-sa/3.0/
		
		It would be very nice if you send me your changes on this class, so that i can include them if they are improve it.
		Thanks!
		
		Usage:
		try{
			$timeout = 2;
			//Read only 4MiB of the scrape response
			$maxread = 1024 * 4;
			
			$scraper = new httptscraper($timeout,$maxread);
			$ret = $scraper->scrape('http://tracker.tld:port/announce',array('0000000000000000000000000000000000000000'));
			
			print_r($ret);
		}catch(ScraperException $e){
			echo('Error: ' . $e->getMessage() . "<br />\n");
			echo('Connection error: ' . ($e->isConnectionError() ? 'yes' : 'no') . "<br />\n");
		}
	*/
	
	require_once(dirname(__FILE__) . '/tscraper.php');
	require_once(dirname(__FILE__) . '/lightbenc.php');
	
	class httptscraper extends tscraper{
		protected $maxreadsize;
		protected $lastError = '';
		
		public function __construct($timeout=2,$maxreadsize=4096){
			$this->maxreadsize = $maxreadsize;
			parent::__construct($timeout);
		}
		
		/* 	$url: Tracker url like: http://tracker.tld:port/announce or http://tracker.tld:port/scrape
			$infohash: Infohash string or array. 40 char long infohash. 
			*/
		public function scrape($url,$infohash){
			if(!is_array($infohash)){ $infohash = array($infohash); }
			foreach($infohash as $hash){
				if(!preg_match('#^[a-f0-9]{40}$#i',$hash)){ throw new ScraperException('Invalid infohash: ' . $hash . '.'); }
			}
			$url = trim($url);
			if (!preg_match('%^https?://%i', $url)){
				throw new ScraperException('Invalid tracker url.');
			}

			$path = (string)parse_url($url, PHP_URL_PATH);
			if (preg_match('%/(ann)$%i', $path)) {
				return $this->announceRequest($url, $infohash, new ScraperException('No scrape endpoint for /ann tracker.'));
			}

			$scrapeurl = $url;
			if (preg_match('%(https?://.*?/)(announce)([^/]*)$%i', $url, $m)){
				$scrapeurl = $m[1] . 'scrape' . $m[3];
			}

			try {
				return $this->scrapeRequest($scrapeurl, $infohash);
			} catch (ScraperException $e) {
				return $this->announceRequest($url, $infohash, $e);
			}
		}

		protected function readUrl($requesturl){
			$this->lastError = '';
			if (function_exists('curl_init')) {
				$ch = curl_init($requesturl);
				$options = array(
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_FOLLOWLOCATION => true,
					CURLOPT_CONNECTTIMEOUT => min(5, $this->timeout),
					CURLOPT_TIMEOUT => $this->timeout,
					CURLOPT_USERAGENT => 'kinozal-multitracker/1.0',
					CURLOPT_HTTPHEADER => array('Accept: */*'),
				);
				if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
					$options[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
				}
				curl_setopt_array($ch, $options);
				$return = curl_exec($ch);
				$code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
				$error = curl_error($ch);
				if (PHP_VERSION_ID < 80500) {
					curl_close($ch);
				}
				if (is_string($return) && $return !== '' && $code >= 200 && $code < 300) {
					return substr($return, 0, $this->maxreadsize);
				}
				$this->lastError = $error !== '' ? $error : ($code > 0 ? 'HTTP ' . $code : 'Could not open HTTP connection.');
				$cli = $this->readUrlWithCurlCli($requesturl);
				if ($cli !== '') {
					return $cli;
				}
				throw new ScraperException($this->lastError, 0, true);
			}

			ini_set('default_socket_timeout',$this->timeout);
			$rh = @fopen($requesturl,'r');
			if(!$rh){ throw new ScraperException('Could not open HTTP connection.',0,true); }
			stream_set_timeout($rh, $this->timeout);

			$return = '';
			$pos = 0;
			while (!feof($rh) && $pos < $this->maxreadsize){
				$chunk = fread($rh,1024);
				if ($chunk === false || $chunk === '') { break; }
				$return .= $chunk;
				$pos += strlen($chunk);
			}
			fclose($rh);
			return $return;
		}

		protected function readUrlWithCurlCli($requesturl){
			if (!function_exists('shell_exec')) {
				return '';
			}
			$curl = trim((string)@shell_exec('command -v curl 2>/dev/null'));
			if ($curl === '') {
				return '';
			}
			$cmd = escapeshellcmd($curl)
				. ' -4 -L -sS --connect-timeout ' . (int)min(5, $this->timeout)
				. ' --max-time ' . (int)$this->timeout
				. ' --user-agent ' . escapeshellarg('kinozal-multitracker/1.0')
				. ' ' . escapeshellarg($requesturl) . ' 2>/dev/null';
			$return = @shell_exec($cmd);
			return is_string($return) ? substr($return, 0, $this->maxreadsize) : '';
		}

		protected function scrapeRequest($url, $infohash){
			$sep = preg_match ('/\?.{1,}?/i', $url) ? '&' : '?';
			$requesturl = $url;
			foreach($infohash as $hash){
				$requesturl .= $sep . 'info_hash=' . rawurlencode(pack('H*', $hash));
				$sep = '&';
			}

			$return = $this->readUrl($requesturl);
			if(!substr($return, 0, 1) == 'd'){ throw new ScraperException('Invalid scrape response.'); }
			$arr_scrape_data = lightbenc::bdecode($return);
			
			$torrents = array();
			foreach($infohash as $hash){
				$ehash = pack('H*', $hash);
				if (isset($arr_scrape_data['files'][$ehash])){
					$torrents[$hash] = array(	'infohash'=>$hash,
												'seeders'=>(int) $arr_scrape_data['files'][$ehash]['complete'],
												'completed'=>(int) $arr_scrape_data['files'][$ehash]['downloaded'],
												'leechers'=>(int) $arr_scrape_data['files'][$ehash]['incomplete']
												);
				}else{
					$torrents[$hash] = false;
				}
			}
			
			return($torrents);
		}

		protected function announceRequest($url, $infohash, ScraperException $previous){
			$torrents = array();
			foreach($infohash as $hash){
				$sep = preg_match ('/\?.{1,}?/i', $url) ? '&' : '?';
				$peerid = '-KZ0001-' . substr(md5($hash . microtime(true)), 0, 12);
				$requesturl = $url
					. $sep . 'info_hash=' . rawurlencode(pack('H*', $hash))
					. '&peer_id=' . rawurlencode($peerid)
					. '&port=6881&uploaded=0&downloaded=0&left=0&compact=1&numwant=0&event=stopped';
				$return = $this->readUrl($requesturl);
				if(!substr($return, 0, 1) == 'd'){ throw $previous; }
				$data = lightbenc::bdecode($return);
				if (!is_array($data) || (!isset($data['complete']) && !isset($data['incomplete']))){
					throw $previous;
				}
				$torrents[$hash] = array(
					'infohash'=>$hash,
					'seeders'=>(int) (isset($data['complete']) ? $data['complete'] : 0),
					'completed'=>(int) (isset($data['downloaded']) ? $data['downloaded'] : 0),
					'leechers'=>(int) (isset($data['incomplete']) ? $data['incomplete'] : 0)
				);
			}
			return($torrents);
		}
	}
?>
