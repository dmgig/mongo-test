<!DOCTYPE html>
<html>
<head>
    <title>Source Detail</title>
</head>
<body>
    <a href="/sources">Back to Sources</a>
    <h1>Source Detail</h1>
    <?php if (isset($source)): ?>
        <p><strong>ID:</strong> <?= htmlspecialchars($source->id->value) ?></p>
        <p><strong>URL:</strong> <a href="<?= htmlspecialchars($source->url) ?>" target="_blank"><?= htmlspecialchars($source->url) ?></a></p>
        <p><strong>Accessed At:</strong> <?= $source->accessedAt->setTimezone(new DateTimeZone('America/New_York'))->format('Y-m-d H:i:s') ?></p>
        <hr>
        <h2>Content</h2>
        <pre><?= htmlspecialchars($source->content) ?></pre>
    <?php else: ?>
        <p>Source not found.</p>
    <?php endif; ?>
    <a href="/sources">Back to Sources</a>
</body>
</html>