<?php
$prog = 34;

//#############Initiation
include 'programshift.php';
$xMOOND = $xMOON - 10000000000;
$yMOOND = $yMOON - 10000000000;
$zMOOND = $zMOON - 10000000000;

// Compute distance using Euclidean formula
$moondistance = sqrt(pow($xMOOND, 2) + pow($yMOOND, 2) + pow($zMOOND, 2));

$earthRadius = 6371000; // Earth's radius in meters
$mu = 3.98589196e14; // Gravitational parameter (m^3/s^2)

// Calculate the current distance from Earth's center
$currentRadius = $earthRadius + $altitude; // Current orbital radius

$emass = 5.97e24;
$mmass = 7.35e19;


// Calculate the semi-major axis
$apogeeRadius = 2000000 + ($moondistance * pow(($emass/(3 * $emass)),1/3)); // Apogee orbital radius
$semiMajorAxis = ($currentRadius + $apogeeRadius) / 2;

// Calculate velocity using the vis-viva equation
$velocity = sqrt($mu * (2 / $currentRadius - 1 / $semiMajorAxis));


//appogee calc
// Position vector relative to Earth
$r_x = $xCM - 10000000000;
$r_y = $yCM - 10000000000;
$r_z = $zCM - 10000000000;
$r_mag = sqrt($r_x * $r_x + $r_y * $r_y + $r_z * $r_z);

// Velocity vector
$v_mag = sqrt($delta_xCM * $delta_xCM + $delta_yCM * $delta_yCM + $delta_zCM * $delta_zCM);

// Specific angular momentum vector magnitude
$h_x = $r_y * $delta_zCM - $r_z * $delta_yCM;
$h_y = $r_z * $delta_xCM - $r_x * $delta_zCM;
$h_z = $r_x * $delta_yCM - $r_y * $delta_xCM;
$h_mag = sqrt($h_x * $h_x + $h_y * $h_y + $h_z * $h_z);

// Eccentricity vector magnitude
$e_x = ($v_mag ** 2 - $mu / $r_mag) * $r_x / $mu - (($r_x * $delta_xCM + $r_y * $delta_yCM + $r_z * $delta_zCM) * $delta_xCM) / $mu;
$e_y = ($v_mag ** 2 - $mu / $r_mag) * $r_y / $mu - (($r_x * $delta_xCM + $r_y * $delta_yCM + $r_z * $delta_zCM) * $delta_yCM) / $mu;
$e_z = ($v_mag ** 2 - $mu / $r_mag) * $r_z / $mu - (($r_x * $delta_xCM + $r_y * $delta_yCM + $r_z * $delta_zCM) * $delta_zCM) / $mu;
$e_mag = sqrt($e_x * $e_x + $e_y * $e_y + $e_z * $e_z);

// Semi-major axis
$a = ($h_mag ** 2) / ($mu * (1 - $e_mag ** 2));

// Apogee altitude
$apogee_altitude = round(($a * (1 + $e_mag) - $earthRadius)/1000,0);

$apogeeRadius2 = round($apogeeRadius/1000,0);




$conn->query("UPDATE CMguidance SET delta_V = $velocity WHERE id = 2");
$conn->query("UPDATE CMguidance SET yaw_rate = $apogeeRadius2 WHERE id = 2");
$conn->query("UPDATE CMguidance SET altitude = $apogee_altitude WHERE id = 2");



// Moon's orbital period and angular velocity
$MOON_ORBITAL_PERIOD = 27.32 * 86400; // Moon's orbital period in seconds
$MOON_ANGULAR_VELOCITY = 360 / $MOON_ORBITAL_PERIOD; // Degrees per second

// Compute current angular position of the Moon
$currentMoonAngle1 = atan2($yMOON - $earth_center_y, $xMOON - $earth_center_x) * (180 / M_PI);

// Normalize angle to [0, 360] range
$currentMoonAngle = ($currentMoonAngle1 < 0) ? $currentMoonAngle1 + 360 : $currentMoonAngle1;

// Predict Moon’s angular position at apogee
$futureMoonAngle = ($currentMoonAngle + ($MOON_ANGULAR_VELOCITY * (72 * 60 * 60))) % 360;

// Normalize future angle to [0, 360]
$normalizedFutureMoonAngle = ($futureMoonAngle < 0) ? $futureMoonAngle + 360 : $futureMoonAngle;

// Calculate the antipole (180° opposite of the apogee angle)
$antipoleAngle = ($normalizedFutureMoonAngle + 190) % 360;
$conn->query("UPDATE CMguidance SET orbit_angle = $antipoleAngle WHERE id = 2");
$conn->query("UPDATE CMguidance SET orbit_angle = $prog WHERE id = 3");

//retro vector hold
include '28.php';

//trigger orbit angle
include '32.php';

//#############Initiation
include 'programshift.php';

    

        
//#############Trigger 1
if ($trigger == 1) {
    // TLI restart owns the S-IVB J-2 ignition state. The launch sequence
    // deliberately shut the engine OFF at parking-orbit insertion while
    // leaving stage 3 attached and propellant available.
    if ((int)$stage3CM === 1 && (float)$s3fuelCM > 0.0) {
        $conn->query(
            "UPDATE EngineStatus
             SET status='ON'
             WHERE engine_stage='Third Stage'
               AND operational_status='OK'"
        );
        $conn->query(
            "UPDATE mission_clock
             SET launch_events=3
             WHERE id=1"
        );
    }
            
       
        if ($IMU_delta_V < $IMUHOLD_delta_V || $apogee_altitude < $apogeeRadius2) {
            
$appdiff = $apogeeRadius2 - $apogee_altitude; 
$appdiffa = $apogee_altitude - $IMUHOLD_altitude;
$conn->query("UPDATE CMguidance SET roll = $appdiff WHERE id = 2");
$conn->query("UPDATE CMguidance SET roll = $prog WHERE id = 3");
// Prevent division by zero
if ($appdiffa == 0) {
    $thrust = 100; // If no altitude change, set max thrust to try and change it.
} else {
    $time_to_goal = abs($appdiff / $appdiffa); // Estimate time to goal

    if ($time_to_goal > 10) {
        $thrust = 100; // If it's taking too long, full thrust
    } else {
        $thrust = round(($time_to_goal / 10) * 100, 0);
    }
}

// Ensure thrust stays within valid range
$thrust = max(10, min(100, $thrust));

           


           
      $conn->query("UPDATE command_module SET value = $thrust WHERE attribute = 'thrust'"); } 
         else
     {
       $conn->query("UPDATE command_module SET value = 0 WHERE attribute = 'thrust'");
       $conn->query("UPDATE command_module SET value = 0 WHERE attribute = 'yaw_rate'");
       $conn->query(
           "UPDATE EngineStatus
            SET status='OFF'
            WHERE engine_stage='Third Stage'"
       );
       include 'progclear.php';
    }
    
}


?>