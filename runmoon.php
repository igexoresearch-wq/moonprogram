<?php
// Trigger the secondary script in the background
$url2 = 'http://rodriguez0524.com/moon/physicsupdate.php';
exec("curl -s $url2 > /dev/null 2>&1 &");
?>