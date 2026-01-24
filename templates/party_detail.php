<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Party Detail</title>
    <style>
        .section { margin-bottom: 30px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
  </head>
  <body>
    <a href="/">Back to Home</a> | 
    <a href="/parties">List Parties</a>
    <h1>Party Details</h1>
    
    <div class="section">
        <h2><?= htmlspecialchars($party->name) ?></h2>
        <dl>
            <dt><strong>ID:</strong></dt> <dd><?= htmlspecialchars($party->id->value) ?></dd>
            <dt><strong>Type:</strong></dt> <dd><?= htmlspecialchars($party->type->value) ?></dd>
            <dt><strong>Created:</strong></dt> <dd><?= htmlspecialchars($party->createdAt->format('Y-m-d H:i:s')) ?></dd>
        </dl>
    </div>

    <div class="section">
        <h3>Relationships</h3>
        <?php if (empty($relationships)): ?>
            <p>No relationships found.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Relationship ID</th>
                        <th>Type</th>
                        <th>Related Party</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($relationships as $rel): ?>
                    <?php 
                        // Determine the "other" party in the relationship
                        $isFrom = $rel->fromPartyId->equals($party->id);
                        $otherId = $isFrom ? $rel->toPartyId->value : $rel->fromPartyId->value;
                        
                        // Look up name in relatedParties map
                        $otherName = isset($relatedParties[$otherId]) ? $relatedParties[$otherId]->name : 'Unknown Party';
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($rel->id->value) ?></td>
                        <td><?= htmlspecialchars($rel->type->value) ?></td>
                        <td>
                            <a href="/party/detail/<?= urlencode($otherId) ?>"><?= htmlspecialchars($otherName) ?></a>
                        </td>
                        <td><?= htmlspecialchars($rel->status->value) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <p>
        <a href="/">Back to Home</a> | 
        <a href="/parties">List Parties</a> | 
        <a href="/party/delete/<?= urlencode($party->id->value) ?>" style="color: red;">Delete this Party</a>
    </p>
  </body>
</html>
