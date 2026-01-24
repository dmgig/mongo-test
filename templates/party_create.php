<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Create Party</title>
  </head>
  <body>
    <a href="/">Back to Home</a>
    <h1>Create Party</h1>
    
    <?php if (isset($message)): ?>
        <div style="border: 1px solid green; padding: 10px; margin-bottom: 10px;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="/party/create">
      <div>
        <label for="name">Name</label><br />
        <input id="name" name="name" type="text" required />
      </div>

      <div style="margin-top: 0.75rem;">
        <label for="type">Type</label><br />
        <select id="type" name="type">
          <option value="individual">individual</option>
          <option value="organization">organization</option>
        </select>
      </div>

      <div style="margin-top: 0.75rem;">
        <button type="submit">Submit</button>
      </div>
    </form>
    
    <p><a href="/">Back to Home</a></p>
  </body>
</html>
