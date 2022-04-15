<?php 
  include_once 'database/search.php';
  $search = new ItemSearch();
  $project = $_GET['id'];
  $items = $search->viewItem($project);
?>

<?php include_once 'includes/header.php'; ?>

  <div id="container">
    <h1>Project Title: <?php echo $items['project_title']; ?></h1>
    <h2>Query type: <?php echo $items['query_type']; ?></h2>
    <textarea><?php echo $items['query']; ?></textarea>
    <a class="delete_button" href="database/delete.php?id=<?php echo $items['project'];?>">Delete</a>
  </div>

<?php include_once 'includes/footer.php'; ?>