<html><head><title>Events</title></head><body><a href="../">Return</a><br/><table><tr><td><table width="300"><tr><th width="80%">Events</th><th width="20%">Size</th></tr><?php

$cam = $_REQUEST['cam'];
$dir = "/var/www/html/cam${cam}/";
if ($handle = opendir($dir)) {

  while (false !== ($file = readdir($handle))) {
    $suffix = pathinfo($dir . $file, PATHINFO_EXTENSION);
    if ( $suffix == 'php' || $file == '.' || $file == '..') {
      continue;
    }
    if ( strpos($file, "snapshot") === false && strpos($file, "timelapse") === false && strpos($file, "lastsnap") === false ) {
      $list[] = $file;
    }
    if ( strpos($file, "-timelapse") !== false ) {
      $list2[] = $file;
    }
  }

  closedir($handle);

  arsort($list);

  foreach ($list as $file) {
    $DT = substr($file, 6, 2) . "/" . substr($file, 4, 2) . "/" .  substr($file, 0, 4) . " " . substr($file, 8, 2) . ":" . substr($file, 10, 2) . ":" . substr($file, 12, 2);
    $NUM = substr($file, (strpos($file, "-")+1), (strpos($file, ".") - (strpos($file, "-")+1)));
    $size = round(filesize($dir . $file) / 1024);
    $P = 'K';
    if ($size > 1024) {
      $size = round($size / 1024);
      $P = 'M';
    }
    print <<<EOF
<tr><td><a href="../event.php?fn=$file&cam=$cam">$DT ($NUM)</a></td><td>$size$P</td></tr>
EOF;
  }
?></table></td><td valign="top">
<table width="300"><tr><th width="80%">Timelapse</th><th width="20%">Size</th></tr><?php
  arsort($list2);

  foreach ($list2 as $file) {
    $DT = substr($file, 6, 2) . "/" . substr($file, 4, 2) . "/" .  substr($file, 0, 4);
    $size = round(filesize($dir . $file) / 1024);
    $P = 'K';
    if ($size > 1024) {
      $size = round($size / 1024);
      $P = 'M';
    }
    print <<<EOF
<tr><td><a href="../event.php?fn=$file&cam=$cam">$DT</a></td><td>$size$P</td></tr>
EOF;
  }
}
?></tr></table></body></html>
