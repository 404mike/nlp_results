<?php 
  include_once 'database/queue.php';
  $queue = new AllItems();
  $items = $queue->getAllItems();
?>
<!doctype html>

<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>A Basic HTML5 Template</title>
  <meta name="description" content="A simple HTML5 Template for new projects.">
  <meta name="author" content="SitePoint">
  <link rel="stylesheet" href="assets/style.css?v=1.0">

</head>

<body>

  <div id="container">
    <?php
      if(isset($_GET['id'])) {
        echo $_GET['id'] . " created";
      }

      foreach($items as $k => $v) {
        echo '<div class="items">';

          if($v['status'] == 0) $status = '<span class="pending">pending</span>';
          if($v['status'] == 9) $status = '<span class="error">error</span>';
          if($v['status'] == 1) $status = '<span class="complete">complete</span>';

          echo '<p>Project: <a href="view_project?id='.$v['project'].'">' . $v['project'] . '</a> - ' . $status . '</p>';
        echo '</div>';  
      }
    ?>
  </div>

  <!-- your content here... -->
  <script src="assets/main.js"></script>
</body>
</html>