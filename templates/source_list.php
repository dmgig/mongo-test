<!DOCTYPE html>
<html>
<head>
    <title>Sources</title>
</head>
<body>
    <a href="/">Back to Home</a>
    <h1>Sources</h1>
    <ul>
        <?php foreach ($sources as $source): ?>
            <li>
                <strong>ID:</strong> <?= $source->id ?><br>
                <a href="<?= htmlspecialchars($source->url) ?>" target="_blank"><?= htmlspecialchars($source->url) ?></a>
                (Retrieved: <?= $source->accessedAt->setTimezone(new DateTimeZone('America/New_York'))->format('Y-m-d h:i:s A') ?>)
                <ul>
                    <?php 
                    $breakdowns = $breakdownService->getBreakdownsForSource($source->id);
                    if (empty($breakdowns)): ?>
                        <li>No breakdowns yet.</li>
                    <?php else:
                        foreach ($breakdowns as $breakdown): ?>
                            <li>
                                <a href="/breakdown/<?= $breakdown->id ?>"><?= $breakdown->id ?></a> 
                                (Created: <?= $breakdown->createdAt->setTimezone(new DateTimeZone('America/New_York'))->format('Y-m-d h:i:s A') ?>)
                            </li>
                        <?php endforeach;
                    endif; ?>
                </ul>
            </li>
        <?php endforeach; ?>
    </ul>
    <a href="/">Back to Home</a>
</body>
</html>
