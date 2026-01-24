<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Create Relationship</title>
  </head>
  <body>
    <a href="/">Back to Home</a>
    <h1>Create Relationship</h1>

    <?php if (isset($message)): ?>
        <div style="border: 1px solid green; padding: 10px; margin-bottom: 10px;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="/party/relationship/create">
      <div>
        <label for="from_party_id">From Party ID</label><br />
        <input id="from_party_id" name="from_party_id" type="text" required />
      </div>

      <div style="margin-top: 0.75rem;">
        <label for="to_party_id">To Party ID</label><br />
        <input id="to_party_id" name="to_party_id" type="text" required />
      </div>

      <div style="margin-top: 0.75rem;">
        <label for="type">Relationship Type</label><br />
        <select id="type" name="type">
          <option value="employment">Employment</option>
          <option value="membership">Membership</option>
          <option value="association">Association</option>
        </select>
      </div>
      
      <div style="margin-top: 0.75rem;">
        <label for="status">Status</label><br />
        <select id="status" name="status">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
      </div>

      <div style="margin-top: 0.75rem;">
        <button type="submit">Create Relationship</button>
      </div>
    </form>
    
    <p><a href="/">Back to Home</a></p>
  </body>
</html>
