<?php
// Fetch the thrust value from the telemetry table
$thrust = 0;
$result = $conn->query("SELECT value FROM telemetry WHERE attribute = 'thrust' LIMIT 1");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $thrust = $row['value'];
}

// Fetch the status of ID 1 in the systems table
$system_status = 0;
$result = $conn->query("SELECT status FROM systems WHERE id = 1 LIMIT 1");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $system_status = $row['status'];
}

// Play sound if thrust > 0 and system ID 1 status is 1
if ($thrust > 0 && $system_status == 1) {
    $volume = min(100, max(0, $thrust)); // Ensure volume is between 0 and 100
    ?>
    <script>
        const context = new (window.AudioContext || window.webkitAudioContext)();
        const gainNode = context.createGain();
        gainNode.gain.value = <?php echo $volume / 100; ?>; // Set volume based on thrust
        gainNode.connect(context.destination);

        fetch("065110_seamless-rocket-booster-roar-amp-crackle-42487.mp3")
            .then(response => response.arrayBuffer())
            .then(data => context.decodeAudioData(data))
            .then(buffer => {
                const source = context.createBufferSource();
                source.buffer = buffer;
                source.loop = true; // Enable looping
                source.connect(gainNode);
                source.start(0); // Play immediately
            })
            .catch(error => console.error("Audio playback error:", error));
    </script>
    <?php
}
?>
