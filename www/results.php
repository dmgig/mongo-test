<?php
require_once dirname(__DIR__) . '/settings.php';

use App\Domain\Party\Party;
use App\Domain\Party\PartyType;
use App\Infrastructure\Mongo\MongoConnector;
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Results</title>
  </head>
  <body>
    <h1>Results</h1>

    <?php
      // Read form values (supports POST; also allows GET for easy testing).
      $name = $_POST['name'] ?? $_GET['name'] ?? '';
      $type = $_POST['type'] ?? $_GET['type'] ?? '';

      // Save to MongoDB if we have data
      if ($name !== '' || $type !== '') {
          try {
              $connector = MongoConnector::fromEnvironment();
              $db = $connector->database();
              $collection = $db->selectCollection('submissions');

              // Create the Party entity (this validates logic like correct type)
              $party = Party::create($name, PartyType::from($type));
              
              // Save the serialized entity
              $result = $collection->insertOne($party->toArray());
              
              echo '<div style="color: green; margin-bottom: 1em;">Saved to MongoDB with ID: ' . $party->id . '</div>';
          } catch (\ValueError $e) {
              echo '<div style="color: red; margin-bottom: 1em;">Error: Invalid Party Type.</div>';
          } catch (\Exception $e) {
              echo '<div style="color: red; margin-bottom: 1em;">Error saving to MongoDB: ' . htmlspecialchars($e->getMessage()) . '</div>';
          }
      }

      // Escape output to avoid HTML injection.
      $nameEsc = htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
      $typeEsc = htmlspecialchars($type, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    ?>

    <dl>
      <dt>Name</dt>
      <dd><?= $nameEsc ?></dd>

      <dt>Type</dt>
      <dd><?= $typeEsc ?></dd>
    </dl>

    <p><a href="index.php">Back</a></p>
  </body>
</html>
