<?php
declare(strict_types=1);
// Authenticated cron entry point: no unauthenticated HTTP fetch, fork,
// shell command, or legacy random-motion physics runner.
require_once __DIR__.'/shared/operator_security.php';
moonSimRequireWorker();
require __DIR__.'/physicsupdate.php';
