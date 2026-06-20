<?php
  // ---------------------------------------------------------------------------------------------------------

  //-------- Begins a main frame

  function begin_main_frame()
  {
    print("<table class=\"main\" width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">" .
      "<tr><td class=\"embedded\">\n");
  }

  //-------- Ends a main frame

  function end_main_frame()
  {
    print("</td></tr></table>\n");
  }

  // ---------------------------------------------------------------------------------------------------------

  function begin_table($fullwidth = false, $padding = 5)
  {
    $width = "";
    
    if ($fullwidth)
      $width .= " width=\"100%\"";
    print("<table class=\"main\"$width border=\"1\" cellspacing=\"0\" cellpadding=\"$padding\">\n");
  }

  function end_table()
  {
    print("</td></tr></table>\n");
  }
  
  // ---------------------------------------------------------------------------------------------------------

  function begin_frame($caption = "", $center = false, $padding = 10)
  {
    $tdextra = "";
    
    if ($caption)
      print("<h2>$caption</h2>\n");

    if ($center)
      $tdextra .= " align=\"center\"";

    print("<table width=\"100%\" border=\"1\" cellspacing=\"0\" cellpadding=\"$padding\"><tr><td$tdextra>\n");

  }

  function attach_frame($padding = 10)
  {
    print("</td></tr><tr><td style=\"border-top: 0px\">\n");
  }

  function end_frame()
  {
    print("</td></tr></table>\n");
  }

	// ---------------------------------------------------------------------------------------------------------
  
  //-------- Inserts a smilies frame
  //         (move to globals)

  function insert_smilies_frame()
  {
    global $smilies, $DEFAULTBASEURL;

    if ((!isset($smilies) || !is_array($smilies)) && defined('ROOT_PATH')) {
      require_once ROOT_PATH . 'include/global.php';
    }

    begin_frame("Смайлы", true);

    begin_table(false, 5);

    print("<tr><td class=\"colhead\">Написание</td><td class=\"colhead\">Смайл</td></tr>\n");

    foreach ((array)$smilies as $code => $url)
      print("<tr><td>$code</td><td><img src=\"$DEFAULTBASEURL/pic/smilies/$url\"></td>\n");

    end_table();

    end_frame();
  }

  // Block menu function
  // Print out menu block!

function blok_menu($title, $content , $width="155") {
	global $ss_uri;
	$template = file_get_contents('themes/'.$ss_uri.'/html/block-left.html');
	if ($template === false) {
		return;
	}
	echo strtr($template, array(
		'$ss_uri' => $ss_uri,
		'$title' => $title,
		'$content' => $content,
		'$width' => $width,
	));
}

?>
