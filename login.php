<?php
declare(strict_types=1);
require_once __DIR__.'/shared/operator_security.php';
moonSimRequireHttps();
// SWC's existing login is the only place that asks for credentials.
header('Cache-Control: no-store');
header('Location: '.moonSimLoginUrl(),true,303);
exit;
