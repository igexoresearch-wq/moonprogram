<?php
include 'db.php';

$attribute_name = "factor"; // Change this to match the actual attribute

if (isset($_POST['change'])) {
    $change = floatval($_POST['change']);

    // Fetch the current value
    $query = "SELECT value FROM command_module WHERE attribute = '$attribute_name'";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $current_value = isset($row['value']) ? floatval($row['value']) : 0.0000;

    // Update the value
    $new_value = $current_value + $change;
    $update_query = "UPDATE command_module SET value = $new_value WHERE attribute = '$attribute_name'";
    $conn->query($update_query);

    // Return the updated value
    echo number_format($new_value, 4);
}
?>
