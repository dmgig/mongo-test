<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Party List</title>
    <style>
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
  </head>
  <body>
    <h1>Parties</h1>
    
    <?php if (empty($parties)): ?>
        <p>No parties found.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>ID</th>
                    <th>Type</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($parties as $party): ?>
                <tr>
                    <td><a href="/party/detail?id=<?= urlencode($party['_id'] ?? '') ?>"><?= htmlspecialchars($party['name'] ?? '') ?></a></td>
                    <td><?= htmlspecialchars($party['_id'] ?? '') ?></td>
                    <td><?= htmlspecialchars($party['type'] ?? '') ?></td>
                    <td><a href="/party/delete?id=<?= urlencode($party['_id'] ?? '') ?>" style="color: red;">Delete</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    
    <p>
        <a href="/">Back to Home</a> | 
        <a href="/party/create">Create New Party</a>
    </p>
  </body>
</html>
