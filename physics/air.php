<?php

// Speed of sound at sea level (m/s)
$speed_of_sound = 343;
$magnitude1 = sqrt(pow($delta_xCM, 2) + pow($delta_yCM, 2) + pow($delta_zCM, 2));

// Compute Mach number
$mach = $magnitude1 / $speed_of_sound;

// Adjust drag coefficient based on Mach number
if ($mach < 0.8) { 
    $drag_coefficient = 2.2; // Subsonic
} elseif ($mach < 1.2) { 
    $drag_coefficient = 1.6; // Transonic (lowered from 1.8)
} elseif ($mach < 3) { 
    $drag_coefficient = 1.2; // Supersonic (lowered from 1.4)
} else { 
    $drag_coefficient = 1.0; // Hypersonic (lowered from 1.1)
}

// Improved atmospheric density model (faster drop at high altitudes)
if ($altitude > 0 && $altitude < 100000) {
    $air_density = $air_density_at_sea_level * exp(-$altitude / ($scale_height * 1.5)); // 1.5 factor speeds up density drop
} else {
    $air_density = 0; // No air resistance above 100km
}

// Determine drag coefficient based on configuration
if ($service_moduleCM == 1) {
    $drag_coefficient = 0.7; // Saturn V configuration
    $reference_area = 10;
} else {
    $reference_area = 4; // Reentry vehicle
}

// Compute lift magnitude
$lift_coefficient = 13.2;
$yaw_rad = deg2rad($yawCM);
$roll_rad = deg2rad($rollCM);

$lift_magnitude = (0.5 * $air_density * $lift_coefficient * $reference_area) * pow($delta_xCM, 2) / $totalSaturnV;

if ($service_moduleCM == 0) {
    // Compute lift direction
    $lift_acc_x = $lift_magnitude * -sin($yaw_rad) * cos($roll_rad);
    $lift_acc_y = $lift_magnitude * -sin($roll_rad);
    $lift_acc_z = $lift_magnitude * cos($yaw_rad) * cos($roll_rad);

    if ($magnitude1 < 2500) {
        $lift_acc_x = 0;
        $lift_acc_y = 0;
        $lift_acc_z = 0;
    }
} else {
    $lift_acc_x = 0;
    $lift_acc_y = 0;
    $lift_acc_z = 0;
}

// Compute drag acceleration components
$drag_magnitude = (0.5 * $air_density * $drag_coefficient * $reference_area) / ($totalSaturnV + 0.0001);

// **Limit Drag Growth at High Speeds (Prevents v² Explosion)**
$velocity_limit = 800; // Drag scaling starts at 800 m/s
if ($magnitude1 > $velocity_limit) {
    $drag_magnitude *= ($velocity_limit / $magnitude1); // Gradual force reduction above 800 m/s
}

// Apply drag forces
$drag_acc_x = $drag_magnitude * pow($delta_xCM, 2);
$drag_acc_y = $drag_magnitude * pow($delta_yCM, 2);
$drag_acc_z = $drag_magnitude * pow($delta_zCM, 2);

// Apply proper drag force direction (Oppose motion)
$drag_acc_x *= ($delta_xCM < 0) ? 1 : -1;
$drag_acc_y *= ($delta_yCM < 0) ? 1 : -1;
$drag_acc_z *= ($delta_zCM < 0) ? 1 : -1;

// Compute net drag acceleration
$drag_acc_net = sqrt(pow($drag_acc_x, 2) + pow($drag_acc_y, 2) + pow($drag_acc_z, 2));

// **Prevent Terminal Velocity From Triggering Too Fast**
if ($magnitude1 < 500) {
    $drag_magnitude *= 0.8; // Reduce drag force by 20% at low speeds
}

$fact = 1;

// Adjust for parachutes
if ($parachutes_deployedCM == 1) {
    if ($altitude > 3000 && $altitude < 7300) {
        if ($magnitude1 > 60) {
            $fact = 0.836;
        }
    }
    if ($altitude > 1 && $altitude < 3000) {
        if ($magnitude1 > 10) {
            $fact = 0.44;
        }
    }
    if ($altitude < 15 && $altitude > 0) {
        $fact = 0.025;
    }

    // **Prevent Parachutes from Applying at High Speeds**
    if ($magnitude1 > 250) {
        $fact = 1.0; // No parachute effect above 250 m/s
    }

    // Zero out drag when parachutes are deployed
    $drag_acc_x = 0;
    $drag_acc_y = 0;
    $drag_acc_z = 0;

    // Apply parachute deceleration factor smoothly
    $delta_xCM *= $fact;
    $delta_yCM *= $fact;
    $delta_zCM *= $fact;
}

// **Convert Final Outputs to BCMath for Further Processing**
bcscale(10);
$drag_acc_x = number_format(-$drag_acc_x, 10, '.', '');
$drag_acc_y = number_format(-$drag_acc_y, 10, '.', '');
$drag_acc_z = number_format(-$drag_acc_z, 10, '.', '');
$lift_acc_x = number_format($lift_acc_x, 10, '.', '');
$lift_acc_y = number_format($lift_acc_y, 10, '.', '');
$lift_acc_z = number_format($lift_acc_z, 10, '.', '');
$drag_acc_net = number_format($drag_acc_net, 10, '.', '');

?>
