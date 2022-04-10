<!doctype html>

<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>A Basic HTML5 Template</title>
  <meta name="description" content="A simple HTML5 Template for new projects.">
  <meta name="author" content="SitePoint">

  <meta property="og:title" content="A Basic HTML5 Template">
  <meta property="og:type" content="website">
  <meta property="og:url" content="https://www.sitepoint.com/a-basic-html5-template/">
  <meta property="og:description" content="A simple HTML5 Template for new projects.">
  <meta property="og:image" content="image.png">

  <link rel="icon" href="/favicon.ico">
  <link rel="icon" href="/favicon.svg" type="image/svg+xml">
  <link rel="apple-touch-icon" href="/apple-touch-icon.png">

  <link rel="stylesheet" href="assets/style.css?v=1.0">

</head>

<body>

  <div id="container">
    <form action="process.php" method="post">

      <select name="query_type">
        <option value="single">Single</option>
        <option value="sparql">SPARQL</option>
      </select>

      <textarea name="sparql"></textarea>

      <button type="submit">Submit</button>
    </form>
  </div>

  <!-- your content here... -->
  <script src="assets/main.js"></script>
</body>
</html>