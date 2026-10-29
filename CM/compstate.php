<?php
// Fetch the row where execute = 1
$result = $conn->query("SELECT nonmonic FROM CMguidance WHERE execute = 1 LIMIT 1");
$row = $result ? $result->fetch_assoc() : null;

// Check if there is a row with execute = 1
$has_execute = !is_null($row);
$nonmonic_display = $has_execute ? $row['nonmonic'] : ''; // Display the nonmonic if found
?><table><tr>
<td><div class="compstate-container">
    <div class="status-box <?php echo $has_execute ? 'active' : ''; ?>">
        <span class="status-text"><?php echo 'ACTIVE'; ?></span>
    </div></td><td>
    <div class="nonmonic-display"><?php if ($Guidance_Computer_SWITCH == 1) { echo $has_execute ? htmlspecialchars($nonmonic_display): htmlspecialchars('STBY') ; }    else {echo '&nbsp;';}?></div>
</div></td></tr></table>

<style>
    .compstate-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin: 20px 0;
    }

    /* Box for ACTIVE status */
    .status-box {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 90px;
        height: 40px;
        border-radius: 10px;
        background-color: #d3d3d3; /* Light grey by default */
        box-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        border: 2px solid #999999;
        font-family: Arial, sans-serif;
        font-size: 1.5rem;
        font-weight: bold;
        color: #000000; /* Black text */
        transition: background-color 0.3s ease;
    }

    .status-box.active {
        background-color: #FFFF00; /* Yellow when active */
    }

    .status-text {
        font-size: 1rem;
    }

    /* Nonmonic display */
    .nonmonic-display {
        font-family: 'DigitTech', monospace;
        font-size: 2rem;
        font-weight: bold;
        color: #FFFF00; /* Yellow text */
        background-color: #000000; /* Black background */
        padding: 5px 5px;
        border-radius: 5px;
        margin-top: 10px;
        width:8ch; /* Ensure display holds 20 characters */
        text-align: right;
    }
</style>
