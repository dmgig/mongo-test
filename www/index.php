<?php require_once dirname(__DIR__) . '/settings.php'; ?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Basic Webform</title>
  </head>
  <body>
    <h1>Basic Webform</h1>

    <form method="post" action="results.php">
      <div>
        <label for="name">Name</label><br />
        <input id="name" name="name" type="text" />
      </div>

      <div style="margin-top: 0.75rem;">
        <label for="type">Type</label><br />
        <select id="type" name="type">
          <option value="organization">organization</option>
          <option value="person">person</option>
        </select>
      </div>

      <div style="margin-top: 0.75rem;">
        <button type="submit">Submit</button>
      </div>
    </form>
  </body>
</html>
