<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $value = $_POST['value'] ?? null;

    switch ($action) {
        case 'noun':
            $conn->query("UPDATE CMguidance SET hold = 1 WHERE id = 2");
            break;
        case 'verb':
            $conn->query("UPDATE CMguidance SET hold = 2 WHERE id = 2");
            break;
        case 'key':
            $conn->query("UPDATE CMguidance SET hold = CONCAT(hold, '$value') WHERE id = 6");
            break;
        case 'clear':
            $conn->query("UPDATE CMguidance SET hold = 0 WHERE id IN (2,4,5,6)");
            break;
        case 'enter':
            $conn->query("UPDATE CMguidance SET execute = 1 WHERE id = 2");
            break;
        case 'execute':
            $conn->query("UPDATE CMguidance SET execute = 1 WHERE id = (SELECT hold FROM CMguidance WHERE id = 4)");
            break;
        case 'terminate':
            $conn->query("UPDATE CMguidance SET execute = 0");
            break;
        case 'reset':
            $conn->query("UPDATE CMguidance SET hold = 0 WHERE id IN (2,4,5,6)");
            break;
    }

    echo json_encode(["status" => "success"]);
}
?>
