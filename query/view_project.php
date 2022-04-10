<?php 
  include_once 'database/queue.php';
  $queue = new AllItems();
  $items = $queue->getAllItems();
?>

<?php include_once 'includes/header.php'; ?>

  <div id="container">
    <?php
      echo 'test';
    ?>
  </div>

<?php include_once 'includes/footer.php'; ?>