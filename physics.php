<?php
// Retired legacy physics entry point. The supported simulation worker is
// physicsupdate.php with an authenticated cron trigger or CLI invocation.
http_response_code(410);
exit('Retired engine. Use the production Moon physics worker.');
