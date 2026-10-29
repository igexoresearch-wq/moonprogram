<?php
header('Content-Type: application/json');
require_once 'db.php';

require_once 'variables.php';
require_once 'constants.php';
// Assume $dockCM is already defined in your environment
echo json_encode(["dockCM" => $dockCM]);
?>
