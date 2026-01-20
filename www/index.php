<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use App\Domain\Party\Party;
use App\Domain\Party\PartyType;
use App\Domain\Party\PartyRelationship;
use App\Domain\Party\PartyRelationshipType;
use App\Domain\Party\PartyRelationshipStatus;
use App\Domain\Party\PartyId;
use App\Domain\Party\PartyService;
use App\Infrastructure\Mongo\MongoConnector;
use App\Infrastructure\Mongo\MongoPartyRepository;
use App\Infrastructure\Mongo\MongoPartyRelationshipRepository;

require_once dirname(__DIR__) . '/settings.php';

$app = AppFactory::create();

// Add Error Middleware
$app->addErrorMiddleware(true, true, true);

// Home - Links to create pages
$app->get('/', function (Request $request, Response $response) {
    $html = '<h1>Home</h1><ul>
    <li><a href="/parties">List Parties</a></li>
    <li><a href="/party/create">Create Party</a></li>
    <li><a href="/party/relationship/create">Create Relationship</a></li>
    <li><a href="/party/delete" style="color: red;">Delete Party</a></li>
    </ul>';
    $response->getBody()->write($html);
    return $response;
});

// GET /party/detail - Show Party Details
$app->get('/party/detail', function (Request $request, Response $response) {
    $idStr = $request->getQueryParams()['id'] ?? '';
    
    if (!$idStr) {
        $response->getBody()->write("Party ID is required.");
        return $response->withStatus(400);
    }

    try {
        $connector = MongoConnector::fromEnvironment();
        $partyRepo = new MongoPartyRepository($connector);
        $relRepo = new MongoPartyRelationshipRepository($connector);
        $service = new PartyService($partyRepo, $relRepo);

        $result = $service->getPartyWithRelationships(PartyId::fromString($idStr));
        
        // Extract variables for the template
        $party = $result['party'];
        $relationships = $result['relationships'];

        ob_start();
        require __DIR__ . '/../templates/party_detail.php';
        $html = ob_get_clean();
        $response->getBody()->write($html);
        return $response;

    } catch (\Exception $e) {
        $response->getBody()->write("Error: " . htmlspecialchars($e->getMessage()));
        return $response->withStatus(404);
    }
});

// GET /parties - List Parties
$app->get('/parties', function (Request $request, Response $response) {
    try {
        $connector = MongoConnector::fromEnvironment();
        $db = $connector->database();
        $collection = $db->selectCollection('submissions');
        // Fetch parties, sorted by newest first
        $cursor = $collection->find([], [
            'limit' => 100,
            'sort' => ['created_at' => -1]
        ]);
        $parties = $cursor->toArray();
    } catch (\Exception $e) {
        $parties = [];
        // Ideally log this error
    }

    ob_start();
    require __DIR__ . '/../templates/party_list.php';
    $html = ob_get_clean();
    $response->getBody()->write($html);
    return $response;
});

// GET /party/create - Show Form
$app->get('/party/create', function (Request $request, Response $response) {
    ob_start();
    require __DIR__ . '/../templates/party_create.php';
    $html = ob_get_clean();
    $response->getBody()->write($html);
    return $response;
});

// POST /party/create - Handle Submission
$app->post('/party/create', function (Request $request, Response $response) {
    $data = (array)$request->getParsedBody();
    $name = $data['name'] ?? '';
    $typeStr = $data['type'] ?? '';
    $message = '';

    if ($name && $typeStr) {
        try {
            $party = Party::create($name, PartyType::from($typeStr));
            
            $connector = MongoConnector::fromEnvironment();
            $db = $connector->database();
            $collection = $db->selectCollection('submissions'); // Keeping 'submissions' as per earlier tasks
            $collection->insertOne($party->toArray());
            
            $message = "Created Party with ID: " . $party->id;
        } catch (\Exception $e) {
            $message = "Error: " . $e->getMessage();
        }
    } else {
        $message = "Name and Type are required.";
    }

    // Render form again with message
    ob_start();
    require __DIR__ . '/../templates/party_create.php';
    $html = ob_get_clean();
    $response->getBody()->write($html);
    return $response;
});


// GET /party/relationship/create - Show Form
$app->get('/party/relationship/create', function (Request $request, Response $response) {
    ob_start();
    require __DIR__ . '/../templates/relationship_create.php';
    $html = ob_get_clean();
    $response->getBody()->write($html);
    return $response;
});

// POST /party/relationship/create - Handle Submission
$app->post('/party/relationship/create', function (Request $request, Response $response) {
    $data = (array)$request->getParsedBody();
    $fromId = $data['from_party_id'] ?? '';
    $toId = $data['to_party_id'] ?? '';
    $typeStr = $data['type'] ?? '';
    $statusStr = $data['status'] ?? '';
    $message = '';

    if ($fromId && $toId && $typeStr && $statusStr) {
        try {
            $p1 = PartyId::fromString($fromId);
            $p2 = PartyId::fromString($toId);
            $type = PartyRelationshipType::from($typeStr);
            $status = PartyRelationshipStatus::from($statusStr);
            
            $rel = PartyRelationship::create($p1, $p2, $type);
            // Manually set status if not active (default is active)
            if ($status === PartyRelationshipStatus::INACTIVE) {
                $rel->deactivate();
            }

            $connector = MongoConnector::fromEnvironment();
            $db = $connector->database();
            $collection = $db->selectCollection('relationships');
            $collection->insertOne($rel->toArray());
            
            $message = "Created Relationship with ID: " . $rel->id;
        } catch (\Exception $e) {
            $message = "Error: " . $e->getMessage();
        }
    } else {
        $message = "All fields are required.";
    }

    // Render form again with message
    ob_start();
    require __DIR__ . '/../templates/relationship_create.php';
    $html = ob_get_clean();
    $response->getBody()->write($html);
    return $response;
});

// GET /party/delete - Show Form
$app->get('/party/delete', function (Request $request, Response $response) {
    $id = $request->getQueryParams()['id'] ?? '';
    ob_start();
    require __DIR__ . '/../templates/party_delete.php';
    $html = ob_get_clean();
    $response->getBody()->write($html);
    return $response;
});

// POST /party/delete - Handle Submission
$app->post('/party/delete', function (Request $request, Response $response) {
    $data = (array)$request->getParsedBody();
    $idStr = $data['id'] ?? '';
    $message = '';

    if ($idStr) {
        try {
            $connector = MongoConnector::fromEnvironment();
            // Wiring up the dependencies manually
            $partyRepo = new MongoPartyRepository($connector);
            $relRepo = new MongoPartyRelationshipRepository($connector);
            $service = new PartyService($partyRepo, $relRepo);

            $service->deleteParty(PartyId::fromString($idStr));
            $message = "Party and its relationships deleted successfully.";
        } catch (\Exception $e) {
            $message = "Error: " . $e->getMessage();
        }
    } else {
        $message = "ID is required.";
    }

    ob_start();
    require __DIR__ . '/../templates/party_delete.php';
    $html = ob_get_clean();
    $response->getBody()->write($html);
    return $response;
});

$app->run();
