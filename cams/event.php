<html><head><title>Event</title></head><body><table><tr><th>Event</th></tr><tr><td><?php
$fn = $_REQUEST["fn"];
$cam = $_REQUEST["cam"];
$suffix = end(explode(".", $fn));
if ( $suffix == "avi" ) {
print <<<EOF
<video width="640" height="480" controls autoplay loop>
  <source src="cam${cam}/${fn}" type="video/avi">
</video>
<a href="cam${cam}/${fn}">Download</a>
EOF;
} elseif ( $suffix == "swf" ) {
print <<<EOF
<object width="640" height="480"><param name="movie" value="cam${cam}/${fn}"><embed src="cam${cam}/${fn}" width="640" height="480"></embed></object>
<br/>
<a href="cam${cam}/${fn}">Download</a>
EOF;
} else {
print <<<EOF
<video width="640" height="480" controls autoplay loop>
  <source src="cam${cam}/${fn}" type="video/$suffix">
</video>
<a href="cam${cam}/${fn}">Download</a>
EOF;
}
?></td></tr></table><a href="cam<?php print($cam); ?>/events.php?cam=<?php print($cam); ?>">Return</a></body></html>
