<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Delete Party</title>
  </head>
  <body>
    <h1>Delete Party</h1>
    
    <div style="background-color: #ffe6e6; border: 1px solid red; padding: 10px; margin-bottom: 20px;">
        <strong>Warning:</strong> Deleting a party will also delete all relationships associated with it. This action cannot be undone.
    </div>

    <?php if (isset($message)): ?>
        <div style="border: 1px solid green; padding: 10px; margin-bottom: 10px;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="/party/delete" onsubmit="return confirm('Are you sure you want to delete this party and all its relationships?');">
      <div>
        <label for="id">Party ID</label><br />
        <input id="id" name="id" type="text" required placeholder="e.g. 550e8400-e29b-..." style="width: 300px;" value="<?= htmlspecialchars($id ?? '') ?>" />
      </div>

      <div style="margin-top: 0.75rem;">
        <button type="submit" style="background-color: red; color: white;">Delete Party</button>
      </div>
    </form>
    
    <p><a href="/">Back to Home</a> | <a href="/parties">List Parties</a></p>
  </body>
</html>
