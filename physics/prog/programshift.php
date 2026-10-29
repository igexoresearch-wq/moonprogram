<?php
// Check if program is in the program register
$programQuery = $conn->query("SELECT execute FROM CMguidance WHERE id = $prog");

if ($programQuery && $row = $programQuery->fetch_assoc()) {
    if ($row['execute'] == 1) {
        // If 32 is not in the program register, update id 10 in CMguidance
        $conn->query("UPDATE CMguidance SET execute = 0 WHERE id = $prog");
        $conn->query("UPDATE CMguidance SET hold = $prog, execute = 1 WHERE id = 10");
        $conn->query("UPDATE CMguidance SET seconds = -1 WHERE id = 2");
    }
} 
?>

