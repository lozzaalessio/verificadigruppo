<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use App\Database;
use App\Paginator;
use App\Auth;
use App\Middleware\AuthMiddleware;
use App\Middleware\AdminMiddleware;

require __DIR__ . '/../vendor/autoload.php';

/* ---------------------------------------------------------------
   Carica .env se presente (chiave=valore, una per riga)
--------------------------------------------------------------- */
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        $_ENV[$k] = $v;
    }
}

/* ---------------------------------------------------------------
   Slim app
--------------------------------------------------------------- */
$app = AppFactory::create();
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
if (($_SERVER['PATH_INFO'] ?? '') === '' && $basePath !== '' && $basePath !== '/') {
    $app->setBasePath($basePath);
}
$app->add(function (Request $request, $handler) {
    $pathInfo = $_SERVER['PATH_INFO'] ?? '';
    if ($pathInfo !== '') {
        $request = $request->withUri($request->getUri()->withPath($pathInfo));
    }

    return $handler->handle($request);
});
$app->addErrorMiddleware(true, true, true);

/* ---------------------------------------------------------------
   Helper: scrive la risposta JSON
--------------------------------------------------------------- */
function jsonResponse(Response $response, mixed $payload, int $status = 200): Response
{
    $response->getBody()->write(
        json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus($status);
}

/* ===============================================================
   ENDPOINT 1 – GET /1
   Nomi distinti dei pezzi presenti in almeno un catalogo.
   Query parametri: ?page=1&per_page=20
   =============================================================== */
$app->get('/1', function (Request $request, Response $response): Response {
    $pdo = Database::getConnection();

    $rows = $pdo->query(
        "SELECT DISTINCT p.pid, p.pnome, p.colore
         FROM Pezzi p
         JOIN Catalogo c ON c.pid = p.pid
         ORDER BY p.pnome"
    )->fetchAll();

    return jsonResponse($response, Paginator::paginate($rows, $request));
});

/* ===============================================================
   ENDPOINT 2 – GET /2
   Fornitori che forniscono TUTTI i pezzi esistenti.
   (nessun parametro aggiuntivo: risposta unica)
   =============================================================== */
$app->get('/2', function (Request $request, Response $response): Response {
    $pdo = Database::getConnection();

    $rows = $pdo->query(
        "SELECT f.fid, f.fnome, f.indirizzo
         FROM Fornitori f
         JOIN Catalogo c ON c.fid = f.fid
         GROUP BY f.fid, f.fnome, f.indirizzo
         HAVING COUNT(DISTINCT c.pid) = (
             SELECT COUNT(*) FROM Pezzi
         )
         ORDER BY f.fid"
    )->fetchAll();

    return jsonResponse($response, ['data' => $rows]);
});

/* ===============================================================
   ENDPOINT 3 – GET /3
   Fornitori che forniscono TUTTI i pezzi di uno specifico colore.
   Query param: ?colore=rosso  (default: rosso)
   =============================================================== */
$app->get('/3', function (Request $request, Response $response): Response {
    $params = $request->getQueryParams();
    $colore = trim($params['colore'] ?? 'rosso');

    $pdo  = Database::getConnection();
    $stmt = $pdo->prepare(
        "SELECT f.fid, f.fnome, f.indirizzo
         FROM Fornitori f
         JOIN Catalogo c ON c.fid = f.fid
         JOIN Pezzi p    ON p.pid  = c.pid
         WHERE p.colore = :colore
         GROUP BY f.fid, f.fnome, f.indirizzo
         HAVING COUNT(DISTINCT p.pid) = (
             SELECT COUNT(*) FROM Pezzi WHERE colore = :colore2
         )
         ORDER BY f.fid"
    );
    $stmt->execute([':colore' => $colore, ':colore2' => $colore]);
    $rows = $stmt->fetchAll();

    return jsonResponse($response, [
        'filtro' => ['colore' => $colore],
        'data'   => $rows,
    ]);
});

/* ===============================================================
   ENDPOINT 4 – GET /4
   Pezzi venduti SOLO da un determinato fornitore (unicità).
   Query param: ?fnome=Acme  (default: Acme)
   =============================================================== */
$app->get('/4', function (Request $request, Response $response): Response {
    $params = $request->getQueryParams();
    $fnome  = trim($params['fnome'] ?? 'Acme');

    $pdo  = Database::getConnection();
    $stmt = $pdo->prepare(
                "SELECT p.pid, p.pnome, p.colore
                 FROM Pezzi p
                 WHERE EXISTS (
                         SELECT 1
                         FROM Catalogo c1
                         JOIN Fornitori f1 ON f1.fid = c1.fid
                         WHERE c1.pid = p.pid
                            AND f1.fnome = :fnome_eq
                 )
                 AND NOT EXISTS (
                         SELECT 1
                         FROM Catalogo c2
                         JOIN Fornitori f2 ON f2.fid = c2.fid
                         WHERE c2.pid = p.pid
                            AND f2.fnome <> :fnome_ne
                 )
                 ORDER BY p.pid"
    );
    $stmt->execute([
        ':fnome_eq' => $fnome,
        ':fnome_ne' => $fnome,
    ]);
    $rows = $stmt->fetchAll();

    return jsonResponse($response, [
        'filtro' => ['fnome' => $fnome],
        'data'   => $rows,
    ]);
});

/* ===============================================================
   ENDPOINT 5 – GET /5
   Fornitori che offrono almeno un pezzo a un costo SUPERIORE
   alla media di quel pezzo tra tutti i fornitori.
   Query param: ?page=1&per_page=20
   =============================================================== */
$app->get('/5', function (Request $request, Response $response): Response {
    $pdo = Database::getConnection();

    $rows = $pdo->query(
        "SELECT DISTINCT f.fid, f.fnome, f.indirizzo
         FROM Catalogo c
         JOIN (
             SELECT pid, AVG(costo) AS costo_medio
             FROM Catalogo
             GROUP BY pid
         ) AS media ON c.pid = media.pid
         JOIN Fornitori f ON f.fid = c.fid
         WHERE c.costo > media.costo_medio
         ORDER BY f.fid"
    )->fetchAll();

    return jsonResponse($response, Paginator::paginate($rows, $request));
});

/* ===============================================================
   ENDPOINT 6 – GET /6
   Per ogni pezzo: il fornitore che offre il costo MINIMO
   (tutti i fornitori a parimerito se stesso minimo).
   Query param: ?page=1&per_page=20
   =============================================================== */
$app->get('/6', function (Request $request, Response $response): Response {
    $pdo = Database::getConnection();

    $rows = $pdo->query(
        "SELECT p.pid, p.pnome, p.colore, f.fid, f.fnome, c.costo
         FROM Catalogo c
         JOIN Pezzi     p ON p.pid = c.pid
         JOIN Fornitori f ON f.fid = c.fid
         WHERE NOT EXISTS (
             SELECT 1
             FROM Catalogo c2
             WHERE c2.pid   = c.pid
               AND c2.costo < c.costo
         )
         ORDER BY p.pid, c.costo"
    )->fetchAll();

    return jsonResponse($response, Paginator::paginate($rows, $request));
});

/* ===============================================================
   ENDPOINT 7 – GET /7
   Fornitori che vendono SOLO pezzi rossi (e almeno uno).
   Nessun parametro aggiuntivo.
   =============================================================== */
$app->get('/7', function (Request $request, Response $response): Response {
    $pdo = Database::getConnection();

    $rows = $pdo->query(
        "SELECT f.fid, f.fnome, f.indirizzo
         FROM Fornitori f
         WHERE EXISTS (
             SELECT 1
             FROM Catalogo c
             WHERE c.fid = f.fid
         )
         AND NOT EXISTS (
             SELECT 1
             FROM Catalogo c2
             JOIN Pezzi p2 ON p2.pid = c2.pid
             WHERE c2.fid      = f.fid
               AND p2.colore  <> 'rosso'
         )
         ORDER BY f.fid"
    )->fetchAll();

    return jsonResponse($response, ['data' => $rows]);
});

/* ===============================================================
   ENDPOINT 8 – GET /8
   Fornitori che hanno nel catalogo sia pezzi rossi SIA pezzi verdi.
   Query param: ?page=1&per_page=20
   =============================================================== */
$app->get('/8', function (Request $request, Response $response): Response {
    $pdo = Database::getConnection();

    $rows = $pdo->query(
        "SELECT f.fid, f.fnome, f.indirizzo,
                COUNT(DISTINCT CASE WHEN p.colore = 'rosso' THEN p.pid END) AS pezzi_rossi,
                COUNT(DISTINCT CASE WHEN p.colore = 'verde' THEN p.pid END) AS pezzi_verdi
         FROM Fornitori f
         JOIN Catalogo c ON c.fid = f.fid
         JOIN Pezzi    p ON p.pid = c.pid
         GROUP BY f.fid, f.fnome, f.indirizzo
         HAVING COUNT(DISTINCT CASE WHEN p.colore = 'rosso' THEN p.pid END) > 0
            AND COUNT(DISTINCT CASE WHEN p.colore = 'verde' THEN p.pid END) > 0
         ORDER BY f.fid"
    )->fetchAll();

    return jsonResponse($response, Paginator::paginate($rows, $request));
});

/* ===============================================================
   ENDPOINT 9 – GET /9
   Fornitori che vendono almeno un pezzo rosso O verde.
   Query param: ?colori=rosso,verde  (lista separata da virgola)
                ?page=1&per_page=20
   =============================================================== */
$app->get('/9', function (Request $request, Response $response): Response {
    $params = $request->getQueryParams();

    // Colori accettati (whitelist)
    $coloriValidi = ['rosso', 'verde', 'blu'];
    $coloriInput  = array_filter(
        array_map('trim', explode(',', $params['colori'] ?? 'rosso,verde')),
        fn($c) => in_array($c, $coloriValidi, true)
    );

    if (empty($coloriInput)) {
        return jsonResponse($response, ['error' => 'Nessun colore valido specificato. Usa: rosso, verde, blu'], 400);
    }

    $pdo         = Database::getConnection();
    $placeholders = implode(',', array_fill(0, count($coloriInput), '?'));

    $stmt = $pdo->prepare(
        "SELECT DISTINCT f.fid, f.fnome, f.indirizzo
         FROM Catalogo c
         JOIN Pezzi     p ON p.pid = c.pid
         JOIN Fornitori f ON f.fid = c.fid
         WHERE p.colore IN ({$placeholders})
         ORDER BY f.fid"
    );
    $stmt->execute(array_values($coloriInput));
    $rows = $stmt->fetchAll();

    return jsonResponse($response, array_merge(
        ['filtro' => ['colori' => array_values($coloriInput)]],
        Paginator::paginate($rows, $request)
    ));
});

/* ===============================================================
   ENDPOINT 10 – GET /10
   Pezzi presenti nel catalogo di almeno N fornitori distinti.
   Query param: ?min_fornitori=2  (default: 2)
                ?page=1&per_page=20
   =============================================================== */
$app->get('/10', function (Request $request, Response $response): Response {
    $params       = $request->getQueryParams();
    $minFornitori = max(1, (int)($params['min_fornitori'] ?? 2));

    $pdo  = Database::getConnection();
    $stmt = $pdo->prepare(
        "SELECT p.pid, p.pnome, p.colore,
                COUNT(DISTINCT c.fid) AS num_fornitori
         FROM Catalogo c
         JOIN Pezzi p ON p.pid = c.pid
         GROUP BY c.pid, p.pnome, p.colore
         HAVING COUNT(DISTINCT c.fid) >= :min
         ORDER BY num_fornitori DESC, p.pid"
    );
    $stmt->bindValue(':min', $minFornitori, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    return jsonResponse($response, array_merge(
        ['filtro' => ['min_fornitori' => $minFornitori]],
        Paginator::paginate($rows, $request)
    ));
});

/* ===============================================================
   NUOVE API - AUTENTICAZIONE E GESTIONE
   =============================================================== */

/* ---------------------------------------------------------------
   POST /api/auth/login - Login utente
--------------------------------------------------------------- */
$app->post('/api/auth/login', function (Request $request, Response $response): Response {
    $body = $request->getParsedBody();
    $username = trim($body['username'] ?? '');
    $password = trim($body['password'] ?? '');
    
    if (empty($username) || empty($password)) {
        return jsonResponse($response, [
            'error' => 'Parametri mancanti',
            'message' => 'Username e password sono obbligatori'
        ], 400);
    }
    
    $user = Auth::authenticate($username, $password);
    
    if (!$user) {
        return jsonResponse($response, [
            'error' => 'Credenziali non valide',
            'message' => 'Username o password errati'
        ], 401);
    }
    
    Auth::login($user);
    
    // Se è un fornitore, aggiungi anche l'ID fornitore
    if ($user['role'] === 'fornitore') {
        $fornitoreId = Auth::getFornitoreId();
        $user['fornitore_id'] = $fornitoreId;
    }
    
    return jsonResponse($response, [
        'message' => 'Login effettuato con successo',
        'user' => $user
    ]);
});

/* ---------------------------------------------------------------
   POST /api/auth/logout - Logout utente
--------------------------------------------------------------- */
$app->post('/api/auth/logout', function (Request $request, Response $response): Response {
    Auth::logout();
    
    return jsonResponse($response, [
        'message' => 'Logout effettuato con successo'
    ]);
});

/* ---------------------------------------------------------------
   GET /api/auth/me - Info utente corrente
--------------------------------------------------------------- */
$app->get('/api/auth/me', function (Request $request, Response $response): Response {
    if (!Auth::check()) {
        return jsonResponse($response, [
            'authenticated' => false
        ]);
    }
    
    $user = Auth::user();
    
    // Se è un fornitore, aggiungi anche l'ID fornitore
    if ($user['role'] === 'fornitore') {
        $fornitoreId = Auth::getFornitoreId();
        $user['fornitore_id'] = $fornitoreId;
    }
    
    return jsonResponse($response, [
        'authenticated' => true,
        'user' => $user
    ]);
});

/* ===============================================================
   API FORNITORI
   =============================================================== */

/* ---------------------------------------------------------------
   GET /api/fornitori - Lista fornitori (paginata)
--------------------------------------------------------------- */
$app->get('/api/fornitori', function (Request $request, Response $response): Response {
    $pdo = Database::getConnection();
    
    $stmt = $pdo->query(
        "SELECT f.fid, f.fnome, f.indirizzo, f.user_id,
                u.username, u.email,
                COUNT(c.pid) as num_pezzi,
                f.created_at, f.updated_at
         FROM Fornitori f
         LEFT JOIN Users u ON f.user_id = u.id
         LEFT JOIN Catalogo c ON c.fid = f.fid
         GROUP BY f.fid, f.fnome, f.indirizzo, f.user_id, 
                  u.username, u.email, f.created_at, f.updated_at
         ORDER BY f.fnome"
    );
    $rows = $stmt->fetchAll();
    
    return jsonResponse($response, Paginator::paginate($rows, $request));
})->add(new AuthMiddleware());

/* ---------------------------------------------------------------
   GET /api/fornitori/{fid} - Dettagli fornitore
--------------------------------------------------------------- */
$app->get('/api/fornitori/{fid}', function (Request $request, Response $response, array $args): Response {
    $pdo = Database::getConnection();
    $fid = $args['fid'];
    
    $stmt = $pdo->prepare(
        "SELECT f.fid, f.fnome, f.indirizzo, f.user_id,
                u.username, u.email, u.role,
                f.created_at, f.updated_at
         FROM Fornitori f
         LEFT JOIN Users u ON f.user_id = u.id
         WHERE f.fid = :fid"
    );
    $stmt->execute(['fid' => $fid]);
    $fornitore = $stmt->fetch();
    
    if (!$fornitore) {
        return jsonResponse($response, [
            'error' => 'Fornitore non trovato'
        ], 404);
    }
    
    // Recupera i pezzi nel catalogo
    $stmt = $pdo->prepare(
        "SELECT p.pid, p.pnome, p.colore, p.descrizione,
                c.costo, c.quantita, c.note,
                c.created_at as aggiunto_il, c.updated_at as modificato_il
         FROM Catalogo c
         JOIN Pezzi p ON p.pid = c.pid
         WHERE c.fid = :fid
         ORDER BY p.pnome"
    );
    $stmt->execute(['fid' => $fid]);
    $fornitore['catalogo'] = $stmt->fetchAll();
    
    return jsonResponse($response, ['data' => $fornitore]);
})->add(new AuthMiddleware());

/* ---------------------------------------------------------------
   POST /api/fornitori - Crea fornitore (solo admin)
--------------------------------------------------------------- */
$app->post('/api/fornitori', function (Request $request, Response $response): Response {
    $body = $request->getParsedBody();
    $fid = trim($body['fid'] ?? '');
    $fnome = trim($body['fnome'] ?? '');
    $indirizzo = trim($body['indirizzo'] ?? '');
    $userId = !empty($body['user_id']) ? (int)$body['user_id'] : null;
    
    if (empty($fid) || empty($fnome)) {
        return jsonResponse($response, [
            'error' => 'Parametri mancanti',
            'message' => 'fid e fnome sono obbligatori'
        ], 400);
    }
    
    $pdo = Database::getConnection();
    
    // Verifica se esiste già
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Fornitori WHERE fid = :fid");
    $stmt->execute(['fid' => $fid]);
    if ($stmt->fetchColumn() > 0) {
        return jsonResponse($response, [
            'error' => 'Fornitore già esistente',
            'message' => "Un fornitore con ID '{$fid}' esiste già"
        ], 409);
    }
    
    $stmt = $pdo->prepare(
        "INSERT INTO Fornitori (fid, fnome, indirizzo, user_id) 
         VALUES (:fid, :fnome, :indirizzo, :user_id)"
    );
    
    $stmt->execute([
        'fid' => $fid,
        'fnome' => $fnome,
        'indirizzo' => $indirizzo,
        'user_id' => $userId
    ]);
    
    return jsonResponse($response, [
        'message' => 'Fornitore creato con successo',
        'data' => [
            'fid' => $fid,
            'fnome' => $fnome,
            'indirizzo' => $indirizzo,
            'user_id' => $userId
        ]
    ], 201);
})->add(new AdminMiddleware())->add(new AuthMiddleware());

/* ---------------------------------------------------------------
   PUT /api/fornitori/{fid} - Aggiorna fornitore (solo admin)
--------------------------------------------------------------- */
$app->put('/api/fornitori/{fid}', function (Request $request, Response $response, array $args): Response {
    $pdo = Database::getConnection();
    $fid = $args['fid'];
    $body = $request->getParsedBody();
    
    // Verifica se esiste
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Fornitori WHERE fid = :fid");
    $stmt->execute(['fid' => $fid]);
    if ($stmt->fetchColumn() == 0) {
        return jsonResponse($response, [
            'error' => 'Fornitore non trovato'
        ], 404);
    }
    
    $fnome = trim($body['fnome'] ?? '');
    $indirizzo = trim($body['indirizzo'] ?? '');
    $userId = isset($body['user_id']) ? (empty($body['user_id']) ? null : (int)$body['user_id']) : null;
    
    if (empty($fnome)) {
        return jsonResponse($response, [
            'error' => 'Parametri mancanti',
            'message' => 'fnome è obbligatorio'
        ], 400);
    }
    
    $stmt = $pdo->prepare(
        "UPDATE Fornitori 
         SET fnome = :fnome, indirizzo = :indirizzo, user_id = :user_id
         WHERE fid = :fid"
    );
    
    $stmt->execute([
        'fid' => $fid,
        'fnome' => $fnome,
        'indirizzo' => $indirizzo,
        'user_id' => $userId
    ]);
    
    return jsonResponse($response, [
        'message' => 'Fornitore aggiornato con successo',
        'data' => [
            'fid' => $fid,
            'fnome' => $fnome,
            'indirizzo' => $indirizzo,
            'user_id' => $userId
        ]
    ]);
})->add(new AdminMiddleware())->add(new AuthMiddleware());

/* ---------------------------------------------------------------
   DELETE /api/fornitori/{fid} - Elimina fornitore (solo admin)
--------------------------------------------------------------- */
$app->delete('/api/fornitori/{fid}', function (Request $request, Response $response, array $args): Response {
    $pdo = Database::getConnection();
    $fid = $args['fid'];
    
    $stmt = $pdo->prepare("DELETE FROM Fornitori WHERE fid = :fid");
    $stmt->execute(['fid' => $fid]);
    
    if ($stmt->rowCount() == 0) {
        return jsonResponse($response, [
            'error' => 'Fornitore non trovato'
        ], 404);
    }
    
    return jsonResponse($response, [
        'message' => 'Fornitore eliminato con successo'
    ]);
})->add(new AdminMiddleware())->add(new AuthMiddleware());

/* ===============================================================
   API PEZZI
   =============================================================== */

/* ---------------------------------------------------------------
   GET /api/pezzi - Lista pezzi (paginata)
--------------------------------------------------------------- */
$app->get('/api/pezzi', function (Request $request, Response $response): Response {
    $pdo = Database::getConnection();
    
    $stmt = $pdo->query(
        "SELECT p.pid, p.pnome, p.colore, p.descrizione,
                COUNT(DISTINCT c.fid) as num_fornitori,
                MIN(c.costo) as costo_minimo,
                MAX(c.costo) as costo_massimo,
                AVG(c.costo) as costo_medio,
                p.created_at, p.updated_at
         FROM Pezzi p
         LEFT JOIN Catalogo c ON c.pid = p.pid
         GROUP BY p.pid, p.pnome, p.colore, p.descrizione, 
                  p.created_at, p.updated_at
         ORDER BY p.pnome"
    );
    $rows = $stmt->fetchAll();
    
    return jsonResponse($response, Paginator::paginate($rows, $request));
})->add(new AuthMiddleware());

/* ---------------------------------------------------------------
   GET /api/pezzi/{pid} - Dettagli pezzo
--------------------------------------------------------------- */
$app->get('/api/pezzi/{pid}', function (Request $request, Response $response, array $args): Response {
    $pdo = Database::getConnection();
    $pid = $args['pid'];
    
    $stmt = $pdo->prepare(
        "SELECT p.pid, p.pnome, p.colore, p.descrizione,
                p.created_at, p.updated_at
         FROM Pezzi p
         WHERE p.pid = :pid"
    );
    $stmt->execute(['pid' => $pid]);
    $pezzo = $stmt->fetch();
    
    if (!$pezzo) {
        return jsonResponse($response, [
            'error' => 'Pezzo non trovato'
        ], 404);
    }
    
    // Recupera i fornitori che lo vendono
    $stmt = $pdo->prepare(
        "SELECT f.fid, f.fnome, f.indirizzo,
                c.costo, c.quantita, c.note,
                c.created_at as aggiunto_il, c.updated_at as modificato_il
         FROM Catalogo c
         JOIN Fornitori f ON f.fid = c.fid
         WHERE c.pid = :pid
         ORDER BY c.costo"
    );
    $stmt->execute(['pid' => $pid]);
    $pezzo['fornitori'] = $stmt->fetchAll();
    
    return jsonResponse($response, ['data' => $pezzo]);
})->add(new AuthMiddleware());

/* ---------------------------------------------------------------
   POST /api/pezzi - Crea pezzo (solo admin)
--------------------------------------------------------------- */
$app->post('/api/pezzi', function (Request $request, Response $response): Response {
    $body = $request->getParsedBody();
    $pid = trim($body['pid'] ?? '');
    $pnome = trim($body['pnome'] ?? '');
    $colore = trim($body['colore'] ?? '');
    $descrizione = trim($body['descrizione'] ?? '');
    
    if (empty($pid) || empty($pnome) || empty($colore)) {
        return jsonResponse($response, [
            'error' => 'Parametri mancanti',
            'message' => 'pid, pnome e colore sono obbligatori'
        ], 400);
    }
    
    $pdo = Database::getConnection();
    
    // Verifica se esiste già
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Pezzi WHERE pid = :pid");
    $stmt->execute(['pid' => $pid]);
    if ($stmt->fetchColumn() > 0) {
        return jsonResponse($response, [
            'error' => 'Pezzo già esistente',
            'message' => "Un pezzo con ID '{$pid}' esiste già"
        ], 409);
    }
    
    $stmt = $pdo->prepare(
        "INSERT INTO Pezzi (pid, pnome, colore, descrizione) 
         VALUES (:pid, :pnome, :colore, :descrizione)"
    );
    
    $stmt->execute([
        'pid' => $pid,
        'pnome' => $pnome,
        'colore' => $colore,
        'descrizione' => $descrizione
    ]);
    
    return jsonResponse($response, [
        'message' => 'Pezzo creato con successo',
        'data' => [
            'pid' => $pid,
            'pnome' => $pnome,
            'colore' => $colore,
            'descrizione' => $descrizione
        ]
    ], 201);
})->add(new AdminMiddleware())->add(new AuthMiddleware());

/* ---------------------------------------------------------------
   PUT /api/pezzi/{pid} - Aggiorna pezzo (solo admin)
--------------------------------------------------------------- */
$app->put('/api/pezzi/{pid}', function (Request $request, Response $response, array $args): Response {
    $pdo = Database::getConnection();
    $pid = $args['pid'];
    $body = $request->getParsedBody();
    
    // Verifica se esiste
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Pezzi WHERE pid = :pid");
    $stmt->execute(['pid' => $pid]);
    if ($stmt->fetchColumn() == 0) {
        return jsonResponse($response, [
            'error' => 'Pezzo non trovato'
        ], 404);
    }
    
    $pnome = trim($body['pnome'] ?? '');
    $colore = trim($body['colore'] ?? '');
    $descrizione = trim($body['descrizione'] ?? '');
    
    if (empty($pnome) || empty($colore)) {
        return jsonResponse($response, [
            'error' => 'Parametri mancanti',
            'message' => 'pnome e colore sono obbligatori'
        ], 400);
    }
    
    $stmt = $pdo->prepare(
        "UPDATE Pezzi 
         SET pnome = :pnome, colore = :colore, descrizione = :descrizione
         WHERE pid = :pid"
    );
    
    $stmt->execute([
        'pid' => $pid,
        'pnome' => $pnome,
        'colore' => $colore,
        'descrizione' => $descrizione
    ]);
    
    return jsonResponse($response, [
        'message' => 'Pezzo aggiornato con successo',
        'data' => [
            'pid' => $pid,
            'pnome' => $pnome,
            'colore' => $colore,
            'descrizione' => $descrizione
        ]
    ]);
})->add(new AdminMiddleware())->add(new AuthMiddleware());

/* ---------------------------------------------------------------
   DELETE /api/pezzi/{pid} - Elimina pezzo (solo admin)
--------------------------------------------------------------- */
$app->delete('/api/pezzi/{pid}', function (Request $request, Response $response, array $args): Response {
    $pdo = Database::getConnection();
    $pid = $args['pid'];
    
    $stmt = $pdo->prepare("DELETE FROM Pezzi WHERE pid = :pid");
    $stmt->execute(['pid' => $pid]);
    
    if ($stmt->rowCount() == 0) {
        return jsonResponse($response, [
            'error' => 'Pezzo non trovato'
        ], 404);
    }
    
    return jsonResponse($response, [
        'message' => 'Pezzo eliminato con successo'
    ]);
})->add(new AdminMiddleware())->add(new AuthMiddleware());

/* ===============================================================
   API CATALOGO
   =============================================================== */

/* ---------------------------------------------------------------
   GET /api/catalogo - Lista completa catalogo (paginata)
--------------------------------------------------------------- */
$app->get('/api/catalogo', function (Request $request, Response $response): Response {
    $pdo = Database::getConnection();
    $user = Auth::user();
    
    // Se è un fornitore, mostra solo il suo catalogo
    if ($user['role'] === 'fornitore') {
        $fornitoreId = Auth::getFornitoreId();
        if (!$fornitoreId) {
            return jsonResponse($response, [
                'error' => 'Fornitore non trovato',
                'message' => 'Nessun fornitore associato a questo utente'
            ], 404);
        }
        
        $stmt = $pdo->prepare(
            "SELECT c.fid, c.pid, c.costo, c.quantita, c.note,
                    f.fnome, f.indirizzo,
                    p.pnome, p.colore, p.descrizione,
                    c.created_at, c.updated_at
             FROM Catalogo c
             JOIN Fornitori f ON f.fid = c.fid
             JOIN Pezzi p ON p.pid = c.pid
             WHERE c.fid = :fid
             ORDER BY p.pnome"
        );
        $stmt->execute(['fid' => $fornitoreId]);
    } else {
        // Admin: mostra tutto
        $stmt = $pdo->query(
            "SELECT c.fid, c.pid, c.costo, c.quantita, c.note,
                    f.fnome, f.indirizzo,
                    p.pnome, p.colore, p.descrizione,
                    c.created_at, c.updated_at
             FROM Catalogo c
             JOIN Fornitori f ON f.fid = c.fid
             JOIN Pezzi p ON p.pid = c.pid
             ORDER BY f.fnome, p.pnome"
        );
    }
    
    $rows = $stmt->fetchAll();
    
    return jsonResponse($response, Paginator::paginate($rows, $request));
})->add(new AuthMiddleware());

/* ---------------------------------------------------------------
   POST /api/catalogo - Aggiungi pezzo al catalogo
   Admin: può aggiungere per qualsiasi fornitore
   Fornitore: può aggiungere solo al proprio catalogo
--------------------------------------------------------------- */
$app->post('/api/catalogo', function (Request $request, Response $response): Response {
    $body = $request->getParsedBody();
    $fid = trim($body['fid'] ?? '');
    $pid = trim($body['pid'] ?? '');
    $costo = floatval($body['costo'] ?? 0);
    $quantita = intval($body['quantita'] ?? 0);
    $note = trim($body['note'] ?? '');
    
    if (empty($fid) || empty($pid) || $costo <= 0) {
        return jsonResponse($response, [
            'error' => 'Parametri mancanti o non validi',
            'message' => 'fid, pid e costo (> 0) sono obbligatori'
        ], 400);
    }
    
    $user = Auth::user();
    
    // Se è un fornitore, può aggiungere solo al proprio catalogo
    if ($user['role'] === 'fornitore') {
        $fornitoreId = Auth::getFornitoreId();
        if ($fid !== $fornitoreId) {
            return jsonResponse($response, [
                'error' => 'Accesso negato',
                'message' => 'Puoi gestire solo il tuo catalogo'
            ], 403);
        }
    }
    
    $pdo = Database::getConnection();
    
    // Verifica se esiste già
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Catalogo WHERE fid = :fid AND pid = :pid");
    $stmt->execute(['fid' => $fid, 'pid' => $pid]);
    if ($stmt->fetchColumn() > 0) {
        return jsonResponse($response, [
            'error' => 'Voce già esistente',
            'message' => 'Questo pezzo è già nel catalogo del fornitore. Usa PUT per aggiornare.'
        ], 409);
    }
    
    $stmt = $pdo->prepare(
        "INSERT INTO Catalogo (fid, pid, costo, quantita, note) 
         VALUES (:fid, :pid, :costo, :quantita, :note)"
    );
    
    $stmt->execute([
        'fid' => $fid,
        'pid' => $pid,
        'costo' => $costo,
        'quantita' => $quantita,
        'note' => $note
    ]);
    
    return jsonResponse($response, [
        'message' => 'Pezzo aggiunto al catalogo con successo',
        'data' => [
            'fid' => $fid,
            'pid' => $pid,
            'costo' => $costo,
            'quantita' => $quantita,
            'note' => $note
        ]
    ], 201);
})->add(new AuthMiddleware());

/* ---------------------------------------------------------------
   PUT /api/catalogo/{fid}/{pid} - Aggiorna voce catalogo
   Admin: può aggiornare qualsiasi voce
   Fornitore: può aggiornare solo il proprio catalogo
--------------------------------------------------------------- */
$app->put('/api/catalogo/{fid}/{pid}', function (Request $request, Response $response, array $args): Response {
    $fid = $args['fid'];
    $pid = $args['pid'];
    $body = $request->getParsedBody();
    
    $user = Auth::user();
    
    // Se è un fornitore, può modificare solo il proprio catalogo
    if ($user['role'] === 'fornitore') {
        $fornitoreId = Auth::getFornitoreId();
        if ($fid !== $fornitoreId) {
            return jsonResponse($response, [
                'error' => 'Accesso negato',
                'message' => 'Puoi gestire solo il tuo catalogo'
            ], 403);
        }
    }
    
    $pdo = Database::getConnection();
    
    // Verifica se esiste
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Catalogo WHERE fid = :fid AND pid = :pid");
    $stmt->execute(['fid' => $fid, 'pid' => $pid]);
    if ($stmt->fetchColumn() == 0) {
        return jsonResponse($response, [
            'error' => 'Voce non trovata'
        ], 404);
    }
    
    $costo = floatval($body['costo'] ?? 0);
    $quantita = intval($body['quantita'] ?? 0);
    $note = trim($body['note'] ?? '');
    
    if ($costo <= 0) {
        return jsonResponse($response, [
            'error' => 'Parametri non validi',
            'message' => 'Il costo deve essere maggiore di 0'
        ], 400);
    }
    
    $stmt = $pdo->prepare(
        "UPDATE Catalogo 
         SET costo = :costo, quantita = :quantita, note = :note
         WHERE fid = :fid AND pid = :pid"
    );
    
    $stmt->execute([
        'fid' => $fid,
        'pid' => $pid,
        'costo' => $costo,
        'quantita' => $quantita,
        'note' => $note
    ]);
    
    return jsonResponse($response, [
        'message' => 'Voce catalogo aggiornata con successo',
        'data' => [
            'fid' => $fid,
            'pid' => $pid,
            'costo' => $costo,
            'quantita' => $quantita,
            'note' => $note
        ]
    ]);
})->add(new AuthMiddleware());

/* ---------------------------------------------------------------
   DELETE /api/catalogo/{fid}/{pid} - Rimuovi pezzo dal catalogo
   Admin: può rimuovere qualsiasi voce
   Fornitore: può rimuovere solo dal proprio catalogo
--------------------------------------------------------------- */
$app->delete('/api/catalogo/{fid}/{pid}', function (Request $request, Response $response, array $args): Response {
    $fid = $args['fid'];
    $pid = $args['pid'];
    
    $user = Auth::user();
    
    // Se è un fornitore, può eliminare solo dal proprio catalogo
    if ($user['role'] === 'fornitore') {
        $fornitoreId = Auth::getFornitoreId();
        if ($fid !== $fornitoreId) {
            return jsonResponse($response, [
                'error' => 'Accesso negato',
                'message' => 'Puoi gestire solo il tuo catalogo'
            ], 403);
        }
    }
    
    $pdo = Database::getConnection();
    
    $stmt = $pdo->prepare("DELETE FROM Catalogo WHERE fid = :fid AND pid = :pid");
    $stmt->execute(['fid' => $fid, 'pid' => $pid]);
    
    if ($stmt->rowCount() == 0) {
        return jsonResponse($response, [
            'error' => 'Voce non trovata'
        ], 404);
    }
    
    return jsonResponse($response, [
        'message' => 'Pezzo rimosso dal catalogo con successo'
    ]);
})->add(new AuthMiddleware());

$homeHandler = function (Request $request, Response $response): Response {
    return jsonResponse($response, [
        'message' => 'API FornitoriPezziDB attiva',
        'endpoints' => [
            '/1', '/2', '/3', '/4', '/5',
            '/6', '/7', '/8', '/9', '/10',
        ],
    ]);
};

$app->get('/', $homeHandler);
$app->get('/index.php', $homeHandler);

$app->run();
