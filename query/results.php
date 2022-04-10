<?php 
  include_once 'database/queue.php';
  $queue = new AllItems();
  $items = $queue->getAllItems();
?>

<?php include_once 'includes/header.php'; ?>

  <div id="container">
    <?php
      if(isset($_GET['id'])) {
        $last_id = $_GET['id'];
      }else{
        $last_id = '';
      }

      foreach($items as $k => $v) {
        $id = $v['id'];

        if($id == $last_id) $recent = 'recent';
        else $recent = '';
        
        echo '<div class="items '. $recent .'">';

          if($v['status'] == 0) $status = '<span class="pending">pending</span>';
          if($v['status'] == 9) $status = '<span class="error">error</span>';
          if($v['status'] == 1) $status = '<span class="complete">complete</span>';

          echo '<p>Project: <a href="view_project.php?id='.$v['project'].'">' . $v['project'] . '</a> - ' . $status . '</p>';
        echo '</div>';  
      }
    ?>
  </div>

<?php include_once 'includes/footer.php'; ?>