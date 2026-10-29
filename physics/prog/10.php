<?php
// Fetch the seconds value from id = 2 in internal
$resultSeconds = $conn->query("SELECT seconds FROM CMguidance WHERE id = 2");
$resultHold = $conn->query("SELECT hold FROM CMguidance WHERE id = 10");

if ($resultSeconds && $resultHold) {
    $rowSeconds = $resultSeconds->fetch_assoc();
    $rowHold = $resultHold->fetch_assoc();

    $seconds = isset($rowSeconds['seconds']) ? $rowSeconds['seconds'] : null;
    $hold = isset($rowHold['hold']) ? $rowHold['hold'] : null;

    // Validate that we have valid data
    if (!is_null($seconds) && !is_null($hold)) {
        // Find the lowest id in CMprogram_register where program_number = 0 and seconds is NULL
        $availableSlotQuery = $conn->query("SELECT id FROM CMprogram_register WHERE program_number = 0 AND seconds IS NULL ORDER BY id ASC LIMIT 1");

        if ($availableSlotQuery && $slot = $availableSlotQuery->fetch_assoc()) {
            $targetId = $slot['id'];

            // Update the available slot with the new program number and seconds
            $updateQuery = $conn->prepare("UPDATE CMprogram_register SET program_number = ?, seconds = ? WHERE id = ?");
            $updateQuery->bind_param('sii', $hold, $seconds, $targetId);
            $updateQuery->execute();

            if ($updateQuery->affected_rows > 0) {
                // Set execute to 0 in id = 10
                $conn->query("UPDATE CMguidance SET execute = 0 WHERE id = 10");
            }
        }
    }
}
?>
