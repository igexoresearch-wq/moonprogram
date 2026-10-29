<?php



// Calculate the distance between the Moon and the Earth using bcmath
$distance_to_earth = bcsqrt(
    bcadd(
        bcadd(bcpow(bcsub($xMOON, $earth_center_x, 10), "2", 10), bcpow(bcsub($yMOON, $earth_center_y, 10), "2", 10), 10),
        bcpow(bcsub($zMOON, $earth_center_z, 10), "2", 10), 10
    ), 10
);

// Calculate the gravitational force exerted by Earth on the Moon
$force = bcdiv(
    bcmul($gravitational_constant, bcmul($earth_mass, $moon_mass, 10), 10),
    bcpow($distance_to_earth, "2", 10),
    10
);

// Calculate acceleration components for the Moon
$acceleration_xMOON = bcdiv(
    bcmul($force, bcdiv(bcsub($earth_center_x, $xMOON, 10), $distance_to_earth, 10), 10),
    $moon_mass,
    10
);

$acceleration_yMOON = bcdiv(
    bcmul($force, bcdiv(bcsub($earth_center_y, $yMOON, 10), $distance_to_earth, 10), 10),
    $moon_mass,
    10
);

$acceleration_zMOON = bcdiv(
    bcmul($force, bcdiv(bcsub($earth_center_z, $zMOON, 10), $distance_to_earth, 10), 10),
    $moon_mass,
    10
);

// Update Moon's velocity based on acceleration
$delta_xMOONnew = bcadd($acceleration_xMOON, $delta_xMOON, 10);
$delta_yMOONnew = bcadd($acceleration_yMOON, $delta_yMOON, 10);
$delta_zMOONnew = bcadd($acceleration_zMOON, $delta_zMOON, 10);

// Format acceleration values as strings with 10 decimal places
$acceleration_xMOON = sprintf("%.10f", $acceleration_xMOON);
$acceleration_yMOON = sprintf("%.10f", $acceleration_yMOON);
$acceleration_zMOON = sprintf("%.10f", $acceleration_zMOON);

// Update Moon's position based on velocity, using 0.91 factor like the LM calculations
$newmoonx = bcadd($xMOON, bcadd($delta_xMOON, bcmul(bcdiv($acceleration_xMOON, "2", 10), "0.91", 10), 10), 10);
$newmoony = bcadd($yMOON, bcadd($delta_yMOON, bcmul(bcdiv($acceleration_yMOON, "2", 10), "0.91", 10), 10), 10);
$newmoonz = bcadd($zMOON, bcadd($delta_zMOON, bcmul(bcdiv($acceleration_zMOON, "2", 10), "0.91", 10), 10), 10);











// Update the database with the new position and velocity
$update_query = "UPDATE moon SET value = $newmoonx WHERE attribute = 'x'";
$conn->query($update_query);

$update_query = "UPDATE moon SET value = $newmoony WHERE attribute = 'y'";
$conn->query($update_query);

$update_query = "UPDATE moon SET value = $newmoonz WHERE attribute = 'z'";
$conn->query($update_query);

$update_query = "UPDATE moon SET value = $delta_xMOONnew WHERE attribute = 'delta_x'";
$conn->query($update_query);

$update_query = "UPDATE moon SET value = $delta_yMOONnew WHERE attribute = 'delta_y'";
$conn->query($update_query);

$update_query = "UPDATE moon SET value = $delta_zMOONnew WHERE attribute = 'delta_z'";
$conn->query($update_query);

?>
