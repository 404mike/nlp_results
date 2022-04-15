<?php include_once 'includes/header.php'; ?>

  <div id="container">
    <form action="process.php" method="post">

      <label>Query type:</label>
      <select name="query_type">
        <option value="single">Single</option>
        <option value="sparql">SPARQL</option>
      </select>

      <label>Project title</lable><input name="project_title" type="text" />

      <label>Query:</label><textarea name="sparql"></textarea>

      <button type="submit">Submit</button>
    </form>
  </div>

<?php include_once 'includes/footer.php'; ?>