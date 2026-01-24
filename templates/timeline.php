<!DOCTYPE html>
<html>
<head>
    <title>Master Timeline</title>
    <style>
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <a href="/">Back to Home</a>
    <h1>Master Timeline</h1>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Event</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($events as $event): ?>
                <tr>
                    <td><?= htmlspecialchars($event->startDate ? ($event->startDate->humanReadable ?? $event->startDate->dateTime->format('Y-m-d')) : 'N/A') ?></td>
                    <td><?= htmlspecialchars($event->name) ?></td>
                    <td><?= htmlspecialchars($event->description) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <a href="/">Back to Home</a>
</body>
</html>
