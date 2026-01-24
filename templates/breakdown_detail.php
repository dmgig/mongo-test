<!DOCTYPE html>
<html>
<head>
    <title>Breakdown Detail</title>
    <style>
        .section { margin-bottom: 2em; }
        pre { background-color: #f4f4f4; padding: 1em; border: 1px solid #ddd; white-space: pre-wrap; word-break: break-all; }
    </style>
</head>
<body>
    <a href="/sources">Back to Sources</a>
    <h1>Breakdown Detail</h1>
    <p><strong>ID:</strong> <?= $breakdown->id ?></p>
    <p><strong>Source ID:</strong> <?= $breakdown->sourceId ?></p>
    
    <div class="section">
        <h2>Summary</h2>
        <pre><?= htmlspecialchars($breakdown->summary) ?></pre>
    </div>

    <?php if ($breakdown->partiesYaml): ?>
        <div class="section">
            <h2>Identified Parties (YAML)</h2>
            <pre><?= htmlspecialchars($breakdown->partiesYaml) ?></pre>
        </div>
    <?php endif; ?>

    <?php if ($breakdown->locationsYaml): ?>
        <div class="section">
            <h2>Locations (YAML)</h2>
            <pre><?= htmlspecialchars($breakdown->locationsYaml) ?></pre>
        </div>
    <?php endif; ?>

    <?php if ($breakdown->timelineYaml): ?>
        <div class="section">
            <h2>Timeline (YAML)</h2>
            <pre><?= htmlspecialchars($breakdown->timelineYaml) ?></pre>
        </div>
    <?php endif; ?>

    <a href="/sources">Back to Sources</a>
</body>
</html>
