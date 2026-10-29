<?php
require 'db.php';

$query = $conn->query("SELECT attribute, value FROM command_module WHERE attribute IN ('pitch_rate', 'yaw_rate', 'roll_rate', 'thrust')");
$values = [];

while ($row = $query->fetch_assoc()) {
    $values[$row['attribute']] = round($row['value'], 1);
}

echo json_encode($values);
?>
