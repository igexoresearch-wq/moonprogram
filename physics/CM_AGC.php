<?php
include_once 'physics/functions.php';
$query = "SELECT seconds FROM mission_clock WHERE id = 1";
$stmt = $conn->query($query);
$result = $stmt ? $stmt->fetch_assoc() : null;

if ($result) { 
    $seconds = intval($result['seconds']);

    if ($seconds != -900) {
        // Increment the seconds
        $seconds += 1;

        // Update the mission clock in the database
        $updateQuery = "UPDATE mission_clock SET seconds = $seconds WHERE id = 1";
        if (!$conn->query($updateQuery)) {
            // Log error if query fails
            error_log("Error updating mission_clock: " . $conn->error);
        }
    }
}


if ($Guidance_Computer_SWITCH == 1) {
   

    // Process register ID 1
    $register1 = $conn->query("SELECT program_number, seconds FROM CMprogram_register WHERE id = 1");
    if ($register1 && $row1 = $register1->fetch_assoc()) {
        $program_number = intval($row1['program_number']);
        $seconds = intval($row1['seconds']);
        if ($program_number != 0) {
            $programQuery = $conn->query("SELECT program FROM CMguidance WHERE id = $program_number");
            if ($programQuery && $programRow = $programQuery->fetch_assoc()) {
                $programCode = $programRow['program'];
                eval("?> $programCode <?php ");
            }
            if ($seconds != -1) {
                if ($seconds == 1) {
                    $conn->query("UPDATE CMprogram_register SET program_number = 0, seconds = NULL WHERE id = 1");
                } else {
                    $conn->query("UPDATE CMprogram_register SET seconds = seconds - 1 WHERE id = 1");
                }
            }
        }
    }

    // Process register ID 2
    $register2 = $conn->query("SELECT program_number, seconds FROM CMprogram_register WHERE id = 2");
    if ($register2 && $row2 = $register2->fetch_assoc()) {
        $program_number = intval($row2['program_number']);
        $seconds = intval($row2['seconds']);
        if ($program_number != 0) {
            $programQuery = $conn->query("SELECT program FROM CMguidance WHERE id = $program_number");
            if ($programQuery && $programRow = $programQuery->fetch_assoc()) {
                $programCode = $programRow['program'];
                eval("?> $programCode <?php ");
            }
            if ($seconds != -1) {
                if ($seconds == 1) {
                    $conn->query("UPDATE CMprogram_register SET program_number = 0, seconds = NULL WHERE id = 2");
                } else {
                    $conn->query("UPDATE CMprogram_register SET seconds = seconds - 1 WHERE id = 2");
                }
            }
        }
    }

    // Process register ID 3
    $register3 = $conn->query("SELECT program_number, seconds FROM CMprogram_register WHERE id = 3");
    if ($register3 && $row3 = $register3->fetch_assoc()) {
        $program_number = intval($row3['program_number']);
        $seconds = intval($row3['seconds']);
        if ($program_number != 0) {
            $programQuery = $conn->query("SELECT program FROM CMguidance WHERE id = $program_number");
            if ($programQuery && $programRow = $programQuery->fetch_assoc()) {
                $programCode = $programRow['program'];
                eval("?> $programCode <?php ");
            }
            if ($seconds != -1) {
                if ($seconds == 1) {
                    $conn->query("UPDATE CMprogram_register SET program_number = 0, seconds = NULL WHERE id = 3");
                } else {
                    $conn->query("UPDATE CMprogram_register SET seconds = seconds - 1 WHERE id = 3");
                }
            }
        }
    }

    // Process register ID 4
    $register4 = $conn->query("SELECT program_number, seconds FROM CMprogram_register WHERE id = 4");
    if ($register4 && $row4 = $register4->fetch_assoc()) {
        $program_number = intval($row4['program_number']);
        $seconds = intval($row4['seconds']);
        if ($program_number != 0) {
            $programQuery = $conn->query("SELECT program FROM CMguidance WHERE id = $program_number");
            if ($programQuery && $programRow = $programQuery->fetch_assoc()) {
                $programCode = $programRow['program'];
                eval("?> $programCode <?php ");
            }
            if ($seconds != -1) {
                if ($seconds == 1) {
                    $conn->query("UPDATE CMprogram_register SET program_number = 0, seconds = NULL WHERE id = 4");
                } else {
                    $conn->query("UPDATE CMprogram_register SET seconds = seconds - 1 WHERE id = 4");
                }
            }
        }
    }

    // Process register ID 5
    $register5 = $conn->query("SELECT program_number, seconds FROM CMprogram_register WHERE id = 5");
    if ($register5 && $row5 = $register5->fetch_assoc()) {
        $program_number = intval($row5['program_number']);
        $seconds = intval($row5['seconds']);
        if ($program_number != 0) {
            $programQuery = $conn->query("SELECT program FROM CMguidance WHERE id = $program_number");
            if ($programQuery && $programRow = $programQuery->fetch_assoc()) {
                $programCode = $programRow['program'];
                eval("?> $programCode <?php ");
            }
            if ($seconds != -1) {
                if ($seconds == 1) {
                    $conn->query("UPDATE CMprogram_register SET program_number = 0, seconds = NULL WHERE id = 5");
                } else {
                    $conn->query("UPDATE CMprogram_register SET seconds = seconds - 1 WHERE id = 5");
                }
            }
        }
    }
     $result = $conn->query("SELECT COUNT(*) AS count FROM CMguidance WHERE execute = 1");
    $count = ($result && $row = $result->fetch_assoc()) ? intval($row['count']) : 0;

    if ($count === 1) {
        $programQuery = $conn->query("SELECT program FROM CMguidance WHERE execute = 1 LIMIT 1");
        if ($programQuery && $programRow = $programQuery->fetch_assoc()) {
            $programCode = $programRow['program'];
            eval("?> $programCode <?php ");
        }
    }
    
    
    
    
    
    
}


// Handle abort sequence
$malf_query = $conn->query("SELECT COUNT(*) AS malf_count FROM EngineStatus WHERE component = 'Combustion Chamber' AND operational_status = 'FAILED'");
if ($malf_query && $malf_data = $malf_query->fetch_assoc()) {
    $malf_count = intval($malf_data['malf_count']);
} else {
    $malf_count = 0;
}

$status_70_query = $conn->query("SELECT status FROM CMswitches WHERE id = 70");
$status_70 = ($status_70_query && $status_70_row = $status_70_query->fetch_assoc()) ? intval($status_70_row['status']) : 0;

if ($malf_count > 0 && $status_70 === 1 && $escapeCM == 1) {
    $conn->query("UPDATE CMswitches SET status = 1 WHERE id = 76");
    $conn->query("UPDATE command_module SET value = 0 WHERE attribute IN ('stage1', 'stage2', 'stage3', 'service_module')");
    if ($escape_fuelCM <= 0 && $altitude < 3000) {
        $conn->query("UPDATE command_module SET value = 0 WHERE attribute = 'escape'");
    }
}
include 'physics/IMU.php';
// Abort sequence handled once above.

// Update navigation deltas and relative velocity
if ($altitudemoon < $altitude) {
    // Calculate deltas relative to the moon
    $delta_x_rel = $delta_xCM - $delta_xMOON;
    $delta_y_rel = $delta_yCM - $delta_yMOON;
    $delta_z_rel = $delta_zCM - $delta_zMOON;

    // Calculate total delta_V relative to the moon
    $delta_V = sqrt(pow($delta_x_rel, 2) + pow($delta_y_rel, 2) + pow($delta_z_rel, 2));

    // Update CMguidance for id = 1 with moon-relative deltas
    $conn->query("UPDATE CMguidance 
                  SET delta_x = $delta_x_rel, delta_y = $delta_y_rel, delta_z = $delta_z_rel, delta_V = $delta_V, altitude = $altitudemoon 
                  WHERE id = 1");
} else {
    // Calculate total delta_V as-is
    $delta_V = sqrt(pow($delta_xCM, 2) + pow($delta_yCM, 2) + pow($delta_zCM, 2));

    // Update CMguidance for id = 1
    $conn->query("UPDATE CMguidance 
                  SET delta_x = $delta_xCM, delta_y = $delta_yCM, delta_z = $delta_zCM, delta_V = $delta_V, altitude = $altitude 
                  WHERE id = 1");
}



// Autodeploy chute
if ($altitude < 10000 && $escapeCM == 0 && $service_moduleCM == 0 && $Main_Parachutes_SWITCH == 1 && $Drogue_Parachutes_SWITCH == 1) {
    $conn->query("UPDATE command_module SET value = 1 WHERE attribute = 'parachutes_deployed'");
}

// Let jettison escape tower
if (isset($LES_Jettison_SWITCH) && $LES_Jettison_SWITCH == 1) {
    $conn->query("UPDATE command_module SET value = 0 WHERE attribute = 'escape'");
}
?>

