<?php 
$audioFile = 'staging.mp3';?>
 <audio id="audioPlayer" src="<?php echo $audioFile; ?>" preload="auto"></audio>
<?php
$thr = $thrustCM;
$stag = $stagingCM;
if ($stagingCM == 1) {
   // Display/audio only: physics owns staging state.
   ?> 
<script>
        // Play the sound once when the page loads
        window.addEventListener('load', () => {
            const audioPlayer = document.getElementById('audioPlayer');
            audioPlayer.play();
        });
    </script>    
    <?php  
} else {
$volumex = min(100, max(0, $thr)); // Ensure volume is between 0 and 100
$volumeree = min(3000, max(0, $temp)); // Ensure volume is between 0 and 3000
?>

<script>
    // Script for thrust sound
    const contextThrust = new (window.AudioContext || window.webkitAudioContext)();
    const gainNodeThrust = contextThrust.createGain();
    gainNodeThrust.gain.value = <?php echo $volumex / 100; ?>; // Set volume based on thrust
    gainNodeThrust.connect(contextThrust.destination);

    fetch("thrust.mp3")
        .then(response => response.arrayBuffer())
        .then(data => contextThrust.decodeAudioData(data))
        .then(buffer => {
            const sourceThrust = contextThrust.createBufferSource();
            sourceThrust.buffer = buffer;
            sourceThrust.loop = true; // Enable looping
            sourceThrust.connect(gainNodeThrust);
            sourceThrust.start(0); // Play immediately
        })
        .catch(error => console.error("Thrust audio playback error:", error));
</script>
<?php }?>
<script>
    // Script for reentry sound
    const contextReentry = new (window.AudioContext || window.webkitAudioContext)();
    const gainNodeReentry = contextReentry.createGain();
    gainNodeReentry.gain.value = <?php echo $volumeree / 3000; ?>; // Set volume based on temperature
    gainNodeReentry.connect(contextReentry.destination);

    fetch("reentry.mp3")
        .then(response => response.arrayBuffer())
        .then(data => contextReentry.decodeAudioData(data))
        .then(buffer => {
            const sourceReentry = contextReentry.createBufferSource();
            sourceReentry.buffer = buffer;
            sourceReentry.loop = true; // Enable looping
            sourceReentry.connect(gainNodeReentry);
            sourceReentry.start(0); // Play immediately
        })
        .catch(error => console.error("Reentry audio playback error:", error));
</script>
