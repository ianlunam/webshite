#!/usr/bin/php
<?php
exit;

$cameras = array( 1 => "Sunroom Window", 2 => "Janets Office", 3 => "Front Door" );

$camera = $argv[1];
$event = $argv[2];

$location = $cameras[$camera];

mail("ian@lunam.org", "Event on camera ${location} triggered", "Check here:\n\nhttps://www.mellorfamily.org/home/index.php\n\nRegards,\nThe Server");

?>
