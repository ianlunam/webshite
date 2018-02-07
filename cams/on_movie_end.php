#!/usr/bin/php
#
#
#
#array (
#  0 => '/usr/local/bin/on_movie_end.php',
#  1 => '4',
#  2 => '02',
#  3 => '/var/www/html/mellor-secure/home/cam4/20141029110547-02.swf',
#)
#
#
<?php

$cameras = array( 1 => "Sunroom Window", 2 => "Janets Office", 3 => "Front Door" );

$camera = $argv[1];
$event = $argv[2];
$file = $argv[3];

$location = $cameras[$camera];

$pathinfo = pathinfo($file);
$newFile = $pathinfo['dirname'] . "/" . $pathinfo['filename'] . ".ogg";

$cmd = sprintf("/usr/bin/ffmpeg -i %s %s", $file, $newFile);
system($cmd, $res);
unlink($file);

$size = round(filesize($newFile) / 1024);
$P = 'K';
if ($size > 1024) {
  $size = round($size / 1024);
  $P = 'M';
}
$newFile = basename($newFile);
# We're on the second server, so add 3
# $camera = $camera + 3;
mail("ian@lunam.org", "Event on camera ${location} complete", "Movie here:\n\nhttps://www.mellorfamily.org/home/event.php?fn=${newFile}&cam=${camera}\nSize: $size$P\n\nRegards,\nThe Server");

?>
