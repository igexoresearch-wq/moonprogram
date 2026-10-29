 <?php include 'includefiles.php';?>
 <style>
             .side-div {
            width: 500px; /* Doubled width */
            height: 500px;
             }
                     .container-text {
            color: #fff;
            text-align: center;
           
        }
 </style>
<div class="container-text"><?php include 'CMViewport.php';?>
            
             <?php if ($CMcrashSYS == 1) {?><img src="crash.png" alt="Overlay" class="overlay-image" width="590"><?php } ?>
            
            <img src="<?php if ($dockCM == 1) { echo 'degreedock.png';} else { echo 'degree.png';} ?>" alt="Overlay" class="overlay-image" width="615" height="600">
            
             <script>
    setTimeout(() => {
        window.location.reload();
    }, 5000); // Refresh every 5000ms (5 seconds)
    </script>