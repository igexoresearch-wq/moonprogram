<?php

//new feul level CM
if ($ABORTCM == 1) {
    // Ensure fuel doesn't drop below zero
    $new_fuel = $escape_fuelCM - $fuel_consumption;
    if ($new_fuel < 0) {
        $new_fuel = 0;
          // Update the thrust level in the database
        $update_query = "UPDATE command_module SET value = 0 WHERE attribute = 'thrust'";
        if ($conn->query($update_query) === TRUE) {
        }  
    }
    // Update the fuel level in the database
    $update_query = "UPDATE command_module SET value = $new_fuel WHERE attribute = 'escape_fuel'";
    if ($conn->query($update_query) === TRUE) {
    } 
}
elseif ($stage1CM == 1) {
    // Ensure fuel doesn't drop below zero
    $new_fuel = $s1fuelCM - $fuel_consumption;
    if ($new_fuel < 0) {
        $new_fuel = 0;
          // Update the thrust level in the database
        $update_query = "UPDATE command_module SET value = 0 WHERE attribute = 'thrust'";
        if ($conn->query($update_query) === TRUE) {
        }  
    }
    // Update the fuel level in the database
    $update_query = "UPDATE command_module SET value = $new_fuel WHERE attribute = 's1fuel'";
    if ($conn->query($update_query) === TRUE) {
    } 
}
elseif ($stage2CM == 1) {
    // Ensure fuel doesn't drop below zero
    $new_fuel = $s2fuelCM - $fuel_consumption;
    if ($new_fuel < 0) {
        $new_fuel = 0;
          // Update the thrust level in the database
        $update_query = "UPDATE command_module SET value = 0 WHERE attribute = 'thrust'";
        if ($conn->query($update_query) === TRUE) {
        }  
    }
    // Update the fuel level in the database
    $update_query = "UPDATE command_module SET value = $new_fuel WHERE attribute = 's2fuel'";
    if ($conn->query($update_query) === TRUE) {
    } 
}
elseif ($stage3CM == 1) {
    // Ensure fuel doesn't drop below zero
    $new_fuel = $s3fuelCM - $fuel_consumption;
    if ($new_fuel < 0) {
        $new_fuel = 0;
          // Update the thrust level in the database
        $update_query = "UPDATE command_module SET value = 0 WHERE attribute = 'thrust'";
        if ($conn->query($update_query) === TRUE) {
        }  
    }
    // Update the fuel level in the database
    $update_query = "UPDATE command_module SET value = $new_fuel WHERE attribute = 's3fuel'";
    if ($conn->query($update_query) === TRUE) {
    } 
}
elseif ($service_moduleCM == 1) {
    // Ensure fuel doesn't drop below zero
    $new_fuel = $fuelCM - $fuel_consumption;
    if ($new_fuel < 0) {
        $new_fuel = 0;
          // Update the thrust level in the database
        $update_query = "UPDATE command_module SET value = 0 WHERE attribute = 'thrust'";
        if ($conn->query($update_query) === TRUE) {
        }  
    }
    // Update the fuel level in the database
    $update_query = "UPDATE command_module SET value = $new_fuel WHERE attribute = 'fuel'";
    if ($conn->query($update_query) === TRUE) {
    } 
}
//lunar module update
if ($descentLM == 1) {
    // Ensure fuel doesn't drop below zero
    $new_fuelLM = $fuelLM - $fuel_consumption_LM;
    if ($new_fuelLM < 0) {
        $new_fuelLM = 0;
          // Update the thrust level in the database
        $update_query = "UPDATE telemetry SET value = 0 WHERE attribute = 'thrust'";
        if ($conn->query($update_query) === TRUE) {
        }  
    }
    // Update the fuel level in the database
    $update_query = "UPDATE telemetry SET value = $new_fuelLM WHERE attribute = 'fuel'";
    if ($conn->query($update_query) === TRUE) {
    } 
}
else  {
    // Ensure fuel doesn't drop below zero
    $new_fuelLM = $fuel_ascentLM - $fuel_consumption_LM;
    if ($new_fuelLM < 0) {
        $new_fuelLM = 0;
          // Update the thrust level in the database
        $update_query = "UPDATE telemetry SET value = 0 WHERE attribute = 'thrust'";
        if ($conn->query($update_query) === TRUE) {
        }  
    }
    // Update the fuel level in the database
    $update_query = "UPDATE telemetry SET value = $new_fuelLM WHERE attribute = 'fuel_ascent'";
    if ($conn->query($update_query) === TRUE) {
    } 
} echo $fuel_consumption_LM;
?>
