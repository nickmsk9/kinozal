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
    $caption = trim((string)$caption);
    $padding = (int)$padding;

    if ($padding < 0) {
        $padding = 0;
    }

    echo '<div class="clr"></div>' . "\n";
    echo '<div class="frame-wrap">' . "\n";

    if ($caption !== '') {
        echo '<div class="pad0x0x5x0">';
        echo '<ul class="lis">';
        echo '<li class="mn">' . htmlspecialchars($caption, ENT_QUOTES, 'UTF-8') . '</li>';
        echo '</ul>';
        echo '</div>' . "\n";
    }

    echo '<div class="bx1_0">' . "\n";

    $classes = array();
    $classes[] = 'pad' . $padding . 'x' . $padding;

    if ($center) {
        $classes[] = 'center';
    }

    echo '<div class="' . implode(' ', $classes) . '">' . "\n";
}

function attach_frame($padding = 10)
{
    $padding = (int)$padding;

    if ($padding < 0) {
        $padding = 0;
    }

    echo '</div>' . "\n";
    echo '<div class="clr"></div>' . "\n";
    echo '<div class="pad' . $padding . 'x' . $padding . '">' . "\n";
}

function end_frame()
{
    echo '</div>' . "\n";
    echo '</div>' . "\n";
    echo '</div>' . "\n";
    echo '<div class="clr"></div>' . "\n";
}

	// ---------------------------------------------------------------------------------------------------------
  
  //-------- Inserts a smilies frame
  //         (move to globals)

  function insert_smilies_frame()
  {
    global $smilies, $DEFAULTBASEURL;

    begin_frame("Смайлы", true);

    begin_table(false, 5);

    print("<tr><td class=\"colhead\">Написание</td><td class=\"colhead\">Смайл</td></tr>\n");

    while (list($code, $url) = each($smilies))
      print("<tr><td>$code</td><td><img src=\"$DEFAULTBASEURL/pic/smilies/$url\"></td>\n");

    end_table();

    end_frame();
  }

  // Block menu function
  // Print out menu block!

function blok_menu($title, $content , $width="155") {
	global $ss_uri;
	$thefile = addslashes(file_get_contents('themes/'.$ss_uri.'/html/block-left.html'));
	$thefile = "\$r_file=\"".$thefile."\";";
	eval($thefile);
	echo $r_file;
}

?>