<?php
$servername = "localhost";
$username = "victorywebproductions";
$password = "CHANGE_ME";
$dbname = "moonland";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$startTime = $_POST['startTime'];
$endTime = $_POST['endTime'];
$attributes = $_POST['attributes'];

$attributeList = implode(", ", array_map(function ($attr) {
    return "`$attr`";
}, $attributes));

$query = "SELECT timestamp, $attributeList FROM CMtelemetry WHERE timestamp BETWEEN ? AND ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $startTime, $endTime);
$stmt->execute();
$result = $stmt->get_result();

$data = ["timestamps" => [], "datasets" => []];
$datasets = [];

while ($row = $result->fetch_assoc()) {
    $data["timestamps"][] = $row["timestamp"];
    
    foreach ($attributes as $attr) {
        if (!isset($datasets[$attr])) {
            $datasets[$attr] = ["label" => $attr, "values" => []];
        }
        // Ensure numeric conversion
        $datasets[$attr]["values"][] = is_numeric($row[$attr]) ? floatval($row[$attr]) : null;
    }
}


$data["datasets"] = array_values($datasets);

$stmt->close();
$conn->close();

echo json_encode($data);
?>
