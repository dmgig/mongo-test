<!DOCTYPE html>
<html>
<head>
    <title>Breakdown Detail</title>
    <style>
        .section { margin-bottom: 2em; }
        .party, .location, .event { margin-bottom: 1em; padding: 10px; border: 1px solid #eee; }
        .date-info { font-style: italic; color: #666; }
    </style>
</head>
<body>
    <h1>Breakdown Detail</h1>
    <p><strong>ID:</strong> <?= $breakdown->id ?></p>
    <p><strong>Source ID:</strong> <?= $breakdown->sourceId ?></p>
    
    <?php if ($breakdown->result): ?>
        <div class="section">
            <h2>Identified Parties</h2>
            <?php foreach ($breakdown->result->parties as $party): ?>
                <div class="party">
                    <strong><?= htmlspecialchars($party->name) ?></strong>
                    (<?= htmlspecialchars($party->type->value) ?>)
                </div>
            <?php endforeach; ?>
        </div>

        <div class="section">
            <h2>Locations</h2>
            <?php foreach ($breakdown->result->locations as $location): ?>
                <div class="location">
                    <?= htmlspecialchars(json_encode($location)) ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="section">
            <h2>Timeline</h2>
            <?php foreach ($breakdown->result->timeline as $event): ?>
                <div class="event">
                    <h3><?= htmlspecialchars($event->name) ?></h3>
                    <p><?= htmlspecialchars($event->description) ?></p>
                    
                    <?php if ($event->startDate): ?>
                        <div class="date-info">
                            <strong>Start:</strong> <?= htmlspecialchars($event->startDate->humanReadable ?? '') ?>
                            (<?= $event->startDate->dateTime->format('Y-m-d H:i:s') ?> - <?= $event->startDate->precision->value ?>)
                            <?php if ($event->startDate->isCirca): ?>(Circa)<?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($event->endDate): ?>
                        <div class="date-info">
                            <strong>End:</strong> <?= htmlspecialchars($event->endDate->humanReadable ?? '') ?>
                            (<?= $event->endDate->dateTime->format('Y-m-d H:i:s') ?> - <?= $event->endDate->precision->value ?>)
                            <?php if ($event->endDate->isCirca): ?>(Circa)<?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>No result available yet. Breakdown in progress or failed.</p>
        <div class="section">
            <h3>Current Summary</h3>
            <pre><?= htmlspecialchars($breakdown->summary) ?></pre>
        </div>
    <?php endif; ?>

    <a href="/sources">Back to Sources</a>
</body>
</html>
