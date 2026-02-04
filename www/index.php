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
use App\Domain\Party\PartyRelationshipId;
use App\Domain\Source\SourceService;
use App\Domain\Source\SourceId;
use App\Domain\Source\Source;
use App\Infrastructure\Mongo\MongoConnector;
use App\Infrastructure\Mongo\MongoPartyRepository;
use App\Infrastructure\Mongo\MongoPartyRelationshipRepository;
use App\Infrastructure\Mongo\MongoSourceRepository;
use App\Infrastructure\Mongo\MongoSourceRelationshipRepository;
use App\Infrastructure\Mongo\MongoBreakdownRepository;
use App\Infrastructure\Mongo\MongoEventRepository;
use App\Domain\Breakdown\BreakdownService;
use App\Domain\Breakdown\BreakdownId;
use App\Domain\Event\EventService;

require_once dirname(__DIR__) . '/settings.php';

$app = AppFactory::create();

// Add Body Parsing Middleware
$app->addBodyParsingMiddleware();

// Add Error Middleware
$app->addErrorMiddleware(true, true, true);

// Home - Links to create pages
$app->get('/', function (Request $request, Response $response) {
    $html = '<h1>Home</h1><ul>
    <li><a href="/parties">List Parties</a></li>
    <li><a href="/sources">List Sources</a></li>
    <li><a href="/timeline">Master Timeline</a></li>
    <li><a href="/party/create">Create Party</a></li>
    <li><a href="/party/relationship/create">Create Relationship</a></li>
    <li><a href="/party/delete" style="color: red;">Delete Party</a></li>
    </ul>';
    $response->getBody()->write($html);
    return $response;
});

// GET /timeline - Master Timeline
$app->get('/timeline', function (Request $request, Response $response) {
    try {
        $connector = MongoConnector::fromEnvironment();
        $eventRepo = new MongoEventRepository($connector);
        $sourceRelRepo = new MongoSourceRelationshipRepository($connector);
        $eventService = new EventService($eventRepo, $sourceRelRepo);
        
        $events = $eventService->getAllEvents();
        
        ob_start();
        require __DIR__ . '/../templates/timeline.php';
        $html = ob_get_clean();
        $response->getBody()->write($html);
        return $response;

    } catch (\Exception $e) {
        $response->getBody()->write("Error: " . htmlspecialchars($e->getMessage()));
        return $response->withStatus(500);
    }
});


// GET /sources - List Sources
$app->get('/sources', function (Request $request, Response $response) {
    try {
        $connector = MongoConnector::fromEnvironment();
        $sourceRepo = new MongoSourceRepository($connector);
        $breakdownRepo = new MongoBreakdownRepository($connector);
        $breakdownService = new BreakdownService($breakdownRepo);
        
        $sources = $sourceRepo->findAll();
        
        ob_start();
        require __DIR__ . '/../templates/source_list.php';
        $html = ob_get_clean();
        $response->getBody()->write($html);
        return $response;

    } catch (\Exception $e) {
        $response->getBody()->write("Error: " . htmlspecialchars($e->getMessage()));
        return $response->withStatus(500);
    }
});

// GET /source/detail/{id} - Show Source Details
$app->get('/source/detail/{id}', function (Request $request, Response $response, array $args) {
    $idStr = $args['id'] ?? '';
    
    if (!$idStr) {
        $response->getBody()->write("Source ID is required.");
        return $response->withStatus(400);
    }

    try {
        $connector = MongoConnector::fromEnvironment();
        $sourceRepo = new MongoSourceRepository($connector);
        
        $source = $sourceRepo->findById(SourceId::fromString($idStr));

        ob_start();
        require __DIR__ . '/../templates/source_detail.php';
        $html = ob_get_clean();
        $response->getBody()->write($html);
        return $response;

    } catch (\Exception $e) {
        $response->getBody()->write("Error: " . htmlspecialchars($e->getMessage()));
        return $response->withStatus(404);
    }
});

// GET /breakdown/{id} - Show Breakdown Details
$app->get('/breakdown/{id}', function (Request $request, Response $response, array $args) {
    $idStr = $args['id'] ?? '';
    
    if (!$idStr) {
        $response->getBody()->write("Breakdown ID is required.");
        return $response->withStatus(400);
    }

    try {
        $connector = MongoConnector::fromEnvironment();
        $breakdownRepo = new MongoBreakdownRepository($connector);
        
        $breakdown = $breakdownRepo->find(BreakdownId::fromString($idStr));

        ob_start();
        require __DIR__ . '/../templates/breakdown_detail.php';
        $html = ob_get_clean();
        $response->getBody()->write($html);
        return $response;

    } catch (\Exception $e) {
        $response->getBody()->write("Error: " . htmlspecialchars($e->getMessage()));
        return $response->withStatus(404);
    }
});


    // GET /party/detail/{id} - Show Party Details
$app->get('/party/detail/{id}', function (Request $request, Response $response, array $args) {
    $idStr = $args['id'] ?? '';
    
    if (!$idStr) {
        $response->getBody()->write("Party ID is required.");
        return $response->withStatus(400);
    }

    try {
        $connector = MongoConnector::fromEnvironment();
        $partyRepo = new MongoPartyRepository($connector);
        $relRepo = new MongoPartyRelationshipRepository($connector);
        $sourceRelRepo = new MongoSourceRelationshipRepository($connector);
        $service = new PartyService($partyRepo, $relRepo, $sourceRelRepo);

        $result = $service->getPartyWithRelationships(PartyId::fromString($idStr));
        
        // Extract variables for the template
        $party = $result['party'];
        $relationships = $result['relationships'];
        $relatedParties = $result['relatedParties'];

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
        $partyRepo = new MongoPartyRepository($connector);
        
        $parties = $partyRepo->findAll();
        
        // Convert party objects to array for the template if needed, or update template to use objects.
        // Looking at templates/party_list.php, it expects an array of arrays (like MongoDB result).
        // Let's adapt it to use objects, or map here.
        // Based on previous reads of party_list.php, it iterates and accesses via array index (e.g. $party['_id']).
        // But `findAll()` now returns `Party` objects.
        // We should update the template or map here. Updating the template is cleaner but let's map for minimal changes first.
        // Wait, I updated `MongoPartyRepository::findAll` to return `Party` objects.
        // The previous implementation returned raw arrays.
        // I need to check `templates/party_list.php`.
        // Let's map to array here to be safe and compatible with existing template if it wasn't updated.
        
        $partiesArray = array_map(fn(Party $p) => $p->toArray(), $parties);
        
        // The `toArray` method adds `_id` back now.
        
        // Pass to template as `parties`
        $parties = $partiesArray;

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
            $partyRepo = new MongoPartyRepository($connector);
            $partyRepo->save($party);
            
            $message = "Created Party with ID: " . $party->id->value;
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
    $fromId = trim($data['from_party_id'] ?? '');
    $toId = trim($data['to_party_id'] ?? '');
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
            $relRepo = new MongoPartyRelationshipRepository($connector);
            $relRepo->save($rel);
            
            $message = "Created Relationship with ID: " . $rel->id->value;
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

// GET /party/delete/{id} - Show Form
$app->get('/party/delete/{id}', function (Request $request, Response $response, array $args) {
    $id = $args['id'] ?? '';
    ob_start();
    require __DIR__ . '/../templates/party_delete.php';
    $html = ob_get_clean();
    $response->getBody()->write($html);
    return $response;
});

// POST /party/delete/{id} - Handle Submission
$app->post('/party/delete/{id}', function (Request $request, Response $response, array $args) {
    // ID comes from the URL path now, not just the body
    $idStr = $args['id'] ?? '';
    $message = '';

    if ($idStr) {
        try {
            $connector = MongoConnector::fromEnvironment();
            // Wiring up the dependencies manually
            $partyRepo = new MongoPartyRepository($connector);
            $relRepo = new MongoPartyRelationshipRepository($connector);
            $sourceRelRepo = new MongoSourceRelationshipRepository($connector);
            $service = new PartyService($partyRepo, $relRepo, $sourceRelRepo);

            $service->deleteParty(PartyId::fromString($idStr));
            $message = "Party and its relationships deleted successfully.";
        } catch (\Exception $e) {
            $message = "Error: " . $e->getMessage();
        }
    } else {
        $message = "ID is required.";
    }

    // For the template to render correctly if we re-display it
    $id = $idStr;
    ob_start();
    require __DIR__ . '/../templates/party_delete.php';
    $html = ob_get_clean();
    $response->getBody()->write($html);
    return $response;
});

// --- API Routes ---

$app->group('/api/v1', function ($group) {
    
    // SOURCES

    // GET /api/v1/sources - List all sources
    $group->get('/sources', function (Request $request, Response $response) {
        $connector = MongoConnector::fromEnvironment();
        $sourceRepo = new MongoSourceRepository($connector);
        
        $sources = $sourceRepo->findAll();
        
        $data = array_map(fn(Source $s) => $s->toArray(), $sources);

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // POST /api/v1/sources - Create source
    $group->post('/sources', function (Request $request, Response $response) {
        $data = (array)$request->getParsedBody();
        $url = $data['url'] ?? '';

        if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
            $response->getBody()->write(json_encode(['error' => 'Valid URL is required']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $connector = MongoConnector::fromEnvironment();
            $sourceRepo = new MongoSourceRepository($connector);
            $sourceRelRepo = new MongoSourceRelationshipRepository($connector);
            $service = new SourceService($sourceRepo, $sourceRelRepo);

            $source = $service->createSource($url);

            $response->getBody()->write(json_encode([
                'id' => $source->id->value,
                'status' => 'created',
                'http_code' => $source->httpCode
            ]));
            return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    // GET /api/v1/sources/{id} - Get source details
    $group->get('/sources/{id}', function (Request $request, Response $response, array $args) {
        $idStr = $args['id'];
        
        try {
            $connector = MongoConnector::fromEnvironment();
            $sourceRepo = new MongoSourceRepository($connector);
            $sourceRelRepo = new MongoSourceRelationshipRepository($connector);
            $service = new SourceService($sourceRepo, $sourceRelRepo);

            $source = $service->getSource(SourceId::fromString($idStr));
            
            $response->getBody()->write(json_encode($source->toArray()));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
    });

    // DELETE /api/v1/sources/{id} - Delete source
    $group->delete('/sources/{id}', function (Request $request, Response $response, array $args) {
        $idStr = $args['id'];
        
        try {
            $connector = MongoConnector::fromEnvironment();
            $sourceRepo = new MongoSourceRepository($connector);
            $sourceRelRepo = new MongoSourceRelationshipRepository($connector);
            $service = new SourceService($sourceRepo, $sourceRelRepo);

            $service->deleteSource(SourceId::fromString($idStr));

            $response->getBody()->write(json_encode(['status' => 'deleted']));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
    });

    // PARTIES

    // GET /api/v1/parties - List all parties
    $group->get('/parties', function (Request $request, Response $response) {
        $connector = MongoConnector::fromEnvironment();
        $partyRepo = new MongoPartyRepository($connector);
        
        $parties = $partyRepo->findAll();
        
        $data = array_map(function (Party $p) {
            return $p->toArray();
        }, $parties);

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // POST /api/v1/parties - Create party
    $group->post('/parties', function (Request $request, Response $response) {
        $data = (array)$request->getParsedBody();
        $name = $data['name'] ?? '';
        $typeStr = $data['type'] ?? '';

        if (!$name || !$typeStr) {
            $response->getBody()->write(json_encode(['error' => 'Name and Type are required']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $party = Party::create($name, PartyType::from($typeStr));
            
            $connector = MongoConnector::fromEnvironment();
            $partyRepo = new MongoPartyRepository($connector);
            $partyRepo->save($party);

            $response->getBody()->write(json_encode([
                'id' => $party->id->value,
                'status' => 'created'
            ]));
            return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    });

    // GET /api/v1/parties/{id} - Get party details
    $group->get('/parties/{id}', function (Request $request, Response $response, array $args) {
        $idStr = $args['id'];
        
        try {
            $connector = MongoConnector::fromEnvironment();
            $partyRepo = new MongoPartyRepository($connector);
            $relRepo = new MongoPartyRelationshipRepository($connector);
            $sourceRelRepo = new MongoSourceRelationshipRepository($connector);
            $service = new PartyService($partyRepo, $relRepo, $sourceRelRepo);

            $result = $service->getPartyWithRelationships(PartyId::fromString($idStr));
            
            $partyData = $result['party']->toArray();
            $relationshipsData = array_map(fn($r) => $r->toArray(), $result['relationships']);
            
            $response->getBody()->write(json_encode([
                'party' => $partyData,
                'relationships' => $relationshipsData
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
    });

    // PATCH /api/v1/parties/{id} - Update party
    $group->patch('/parties/{id}', function (Request $request, Response $response, array $args) {
        $idStr = $args['id'];
        $data = (array)$request->getParsedBody();
        
        try {
            $connector = MongoConnector::fromEnvironment();
            $partyRepo = new MongoPartyRepository($connector);
            
            $party = $partyRepo->findById(PartyId::fromString($idStr));
            if (!$party) {
                throw new \Exception("Party not found");
            }

            // Update fields if provided
            if (isset($data['name'])) {
                // Party name is mutable via constructor in a new instance or we need a rename method.
                // But wait, Party class defines name as public string.
                $party->name = $data['name'];
            }
            
            $partyRepo->save($party);

            $response->getBody()->write(json_encode(['status' => 'updated']));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    });

    // DELETE /api/v1/parties/{id} - Delete party
    $group->delete('/parties/{id}', function (Request $request, Response $response, array $args) {
        $idStr = $args['id'];
        
        try {
            $connector = MongoConnector::fromEnvironment();
            $partyRepo = new MongoPartyRepository($connector);
            $relRepo = new MongoPartyRelationshipRepository($connector);
            $sourceRelRepo = new MongoSourceRelationshipRepository($connector);
            $service = new PartyService($partyRepo, $relRepo, $sourceRelRepo);

            $service->deleteParty(PartyId::fromString($idStr));

            $response->getBody()->write(json_encode(['status' => 'deleted']));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
    });

    // RELATIONSHIPS

    // POST /api/v1/party-relationships - Create relationship
    $group->post('/party-relationships', function (Request $request, Response $response) {
        $data = (array)$request->getParsedBody();
        $fromId = $data['from_party_id'] ?? '';
        $toId = $data['to_party_id'] ?? '';
        $typeStr = $data['type'] ?? '';
        
        if (!$fromId || !$toId || !$typeStr) {
            $response->getBody()->write(json_encode(['error' => 'from_party_id, to_party_id, and type are required']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $rel = PartyRelationship::create(
                PartyId::fromString($fromId),
                PartyId::fromString($toId),
                PartyRelationshipType::from($typeStr)
            );
            
            // Check status if provided
            if (isset($data['status'])) {
                $status = PartyRelationshipStatus::from($data['status']);
                if ($status === PartyRelationshipStatus::INACTIVE) {
                    $rel->deactivate();
                }
            }

            $connector = MongoConnector::fromEnvironment();
            $relRepo = new MongoPartyRelationshipRepository($connector);
            $relRepo->save($rel);

            $response->getBody()->write(json_encode([
                'id' => $rel->id->value,
                'status' => 'created'
            ]));
            return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    });

    // PATCH /api/v1/party-relationships/{id} - Update relationship
    $group->patch('/party-relationships/{id}', function (Request $request, Response $response, array $args) {
        $idStr = $args['id'];
        $data = (array)$request->getParsedBody();
        
        try {
            $connector = MongoConnector::fromEnvironment();
            $relRepo = new MongoPartyRelationshipRepository($connector);
            
            $rel = $relRepo->findById(PartyRelationshipId::fromString($idStr));
            if (!$rel) {
                throw new \Exception("Relationship not found");
            }

            if (isset($data['status'])) {
                $status = PartyRelationshipStatus::from($data['status']);
                if ($status === PartyRelationshipStatus::ACTIVE) {
                    $rel->activate();
                } else {
                    $rel->deactivate();
                }
            }
            
            $relRepo->save($rel);

            $response->getBody()->write(json_encode(['status' => 'updated']));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    });

});

$app->run();
