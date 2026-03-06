<?php
<<<<<<< HEAD

=======
>>>>>>> 13b6648 (finale)
declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use App\Database;
use App\Paginator;
<<<<<<< HEAD
use App\Auth;
use App\Middleware\AuthMiddleware;
use App\Middleware\AdminMiddleware;
=======
>>>>>>> 13b6648 (finale)

require __DIR__ . '/../vendor/autoload.php';

/* ---------------------------------------------------------------
<<<<<<< HEAD
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
=======
   Carica .env
--------------------------------------------------------------- */
$envCandidates = [
    __DIR__ . '/../.env',
    __DIR__ . '/../env.txt',
    __DIR__ . '/../env.example',
];
foreach ($envCandidates as $envFile) {
    if (!file_exists($envFile)) continue;
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        if (!isset($_ENV[$k]) || $_ENV[$k] === '') $_ENV[$k] = $v;
    }
    break;
}

/* ---------------------------------------------------------------
   Slim app + middleware CORS
--------------------------------------------------------------- */
$app = AppFactory::create();
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$basePath   = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
if (($_SERVER['PATH_INFO'] ?? '') === '' && $basePath !== '' && $basePath !== '/') {
    $app->setBasePath($basePath);
}

>>>>>>> 13b6648 (finale)
$app->add(function (Request $request, $handler) {
    $pathInfo = $_SERVER['PATH_INFO'] ?? '';
    if ($pathInfo !== '') {
        $request = $request->withUri($request->getUri()->withPath($pathInfo));
    }
<<<<<<< HEAD

    return $handler->handle($request);
});
$app->addErrorMiddleware(true, true, true);

/* ---------------------------------------------------------------
   Helper: scrive la risposta JSON
=======
    return $handler->handle($request);
});

$app->options('/{routes:.+}', function (Request $request, Response $response): Response {
    return $response;
});

$app->add(function (Request $request, $handler): Response {
    if ($request->getMethod() === 'OPTIONS') {
        $response = new \Slim\Psr7\Response();
    } else {
        $response = $handler->handle($request);
    }
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
});

$app->addErrorMiddleware(true, true, true);

/* ---------------------------------------------------------------
   Helpers
>>>>>>> 13b6648 (finale)
--------------------------------------------------------------- */
function jsonResponse(Response $response, mixed $payload, int $status = 200): Response
{
    $response->getBody()->write(
        json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );
<<<<<<< HEAD
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

    return jsonResponse($response, Paginator::paginate($rows, $request));
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

    return jsonResponse($response, array_merge(
        ['filtro' => ['colore' => $colore]],
        Paginator::paginate($rows, $request)
    ));
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

    return jsonResponse($response, array_merge(
        ['filtro' => ['fnome' => $fnome]],
        Paginator::paginate($rows, $request)
    ));
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

    return jsonResponse($response, Paginator::paginate($rows, $request));
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
    $params = $request->getQueryParams();
    $search = trim((string)($params['search'] ?? ''));
    
    $sql = "SELECT f.fid, f.fnome, f.indirizzo, f.user_id,
                   u.username, u.email,
                   COUNT(c.pid) as num_pezzi,
                   f.created_at, f.updated_at
            FROM Fornitori f
            LEFT JOIN Users u ON f.user_id = u.id
            LEFT JOIN Catalogo c ON c.fid = f.fid";

    $bind = [];
    if ($search !== '') {
        $sql .= " WHERE (
                    f.fid LIKE :search
                    OR f.fnome LIKE :search
                    OR COALESCE(f.indirizzo, '') LIKE :search
                    OR COALESCE(u.username, '') LIKE :search
                    OR COALESCE(u.email, '') LIKE :search
                 )";
        $bind['search'] = '%' . $search . '%';
    }

    $sql .= " GROUP BY f.fid, f.fnome, f.indirizzo, f.user_id,
                      u.username, u.email, f.created_at, f.updated_at
              ORDER BY f.fnome";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($bind);
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
    $catalogoRows = $stmt->fetchAll();
    $catalogoPaginato = Paginator::paginate($catalogoRows, $request);
    $fornitore['catalogo'] = $catalogoPaginato['data'];
    $fornitore['catalogo_meta'] = $catalogoPaginato['meta'];
    
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
    $registerUser = filter_var($body['register_user'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $username = trim($body['username'] ?? '');
    $email = trim($body['email'] ?? '');
    $password = trim($body['password'] ?? '');
    
    if (empty($fid) || empty($fnome)) {
        return jsonResponse($response, [
            'error' => 'Parametri mancanti',
            'message' => 'fid e fnome sono obbligatori'
        ], 400);
    }
    
    $pdo = Database::getConnection();

    if ($registerUser && $userId !== null) {
        return jsonResponse($response, [
            'error' => 'Parametri non validi',
            'message' => 'Specifica user_id oppure register_user, non entrambi'
        ], 400);
    }

    if ($registerUser) {
        if ($username === '' || $email === '' || $password === '') {
            return jsonResponse($response, [
                'error' => 'Parametri mancanti',
                'message' => 'Per register_user servono username, email e password'
            ], 400);
        }

        $newUserId = Auth::register($username, $password, $email, 'fornitore');
        if ($newUserId === null) {
            return jsonResponse($response, [
                'error' => 'Utente non creato',
                'message' => 'Username o email gia in uso'
            ], 409);
        }
        $userId = $newUserId;
    } elseif ($userId !== null) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM Users WHERE id = :id');
        $stmt->execute(['id' => $userId]);
        if ((int)$stmt->fetchColumn() === 0) {
            return jsonResponse($response, [
                'error' => 'Utente non trovato',
                'message' => 'L\'utente associato non esiste'
            ], 404);
        }
    }
    
    // Verifica se esiste già
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Fornitori WHERE fid = :fid");
    $stmt->execute(['fid' => $fid]);
    if ($stmt->fetchColumn() > 0) {
        return jsonResponse($response, [
            'error' => 'Fornitore già esistente',
            'message' => "Un fornitore con ID '{$fid}' esiste già"
        ], 409);
    }

    if ($userId !== null) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM Fornitori WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
        if ((int)$stmt->fetchColumn() > 0) {
            return jsonResponse($response, [
                'error' => 'Utente gia associato',
                'message' => 'Questo utente e gia collegato a un altro fornitore'
            ], 409);
        }
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
    $registerUser = filter_var($body['register_user'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $username = trim($body['username'] ?? '');
    $email = trim($body['email'] ?? '');
    $password = trim($body['password'] ?? '');
    
    if (empty($fnome)) {
        return jsonResponse($response, [
            'error' => 'Parametri mancanti',
            'message' => 'fnome è obbligatorio'
        ], 400);
    }

    if ($registerUser && $userId !== null) {
        return jsonResponse($response, [
            'error' => 'Parametri non validi',
            'message' => 'Specifica user_id oppure register_user, non entrambi'
        ], 400);
    }

    if ($registerUser) {
        if ($username === '' || $email === '' || $password === '') {
            return jsonResponse($response, [
                'error' => 'Parametri mancanti',
                'message' => 'Per register_user servono username, email e password'
            ], 400);
        }

        $newUserId = Auth::register($username, $password, $email, 'fornitore');
        if ($newUserId === null) {
            return jsonResponse($response, [
                'error' => 'Utente non creato',
                'message' => 'Username o email gia in uso'
            ], 409);
        }
        $userId = $newUserId;
    } elseif ($userId !== null) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM Users WHERE id = :id');
        $stmt->execute(['id' => $userId]);
        if ((int)$stmt->fetchColumn() === 0) {
            return jsonResponse($response, [
                'error' => 'Utente non trovato',
                'message' => 'L\'utente associato non esiste'
            ], 404);
        }
    }

    if ($userId !== null) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM Fornitori WHERE user_id = :user_id AND fid <> :fid');
        $stmt->execute([
            'user_id' => $userId,
            'fid' => $fid,
        ]);

        if ((int)$stmt->fetchColumn() > 0) {
            return jsonResponse($response, [
                'error' => 'Utente gia associato',
                'message' => 'Questo utente e gia collegato a un altro fornitore'
            ], 409);
        }
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
    $params = $request->getQueryParams();
    $search = trim((string)($params['search'] ?? ''));
    
    $sql = "SELECT p.pid, p.pnome, p.colore, p.descrizione,
                   COUNT(DISTINCT c.fid) as num_fornitori,
                   MIN(c.costo) as costo_minimo,
                   MAX(c.costo) as costo_massimo,
                   AVG(c.costo) as costo_medio,
                   p.created_at, p.updated_at
            FROM Pezzi p
            LEFT JOIN Catalogo c ON c.pid = p.pid";

    $bind = [];
    if ($search !== '') {
        $sql .= " WHERE (
                    p.pid LIKE :search
                    OR p.pnome LIKE :search
                    OR p.colore LIKE :search
                    OR COALESCE(p.descrizione, '') LIKE :search
                 )";
        $bind['search'] = '%' . $search . '%';
    }

    $sql .= " GROUP BY p.pid, p.pnome, p.colore, p.descrizione,
                     p.created_at, p.updated_at
              ORDER BY p.pnome";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($bind);
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
    $fornitoriRows = $stmt->fetchAll();
    $fornitoriPaginati = Paginator::paginate($fornitoriRows, $request);
    $pezzo['fornitori'] = $fornitoriPaginati['data'];
    $pezzo['fornitori_meta'] = $fornitoriPaginati['meta'];
    
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
    $params = $request->getQueryParams();
    $search = trim((string)($params['search'] ?? ''));
    
    // Se è un fornitore, mostra solo il suo catalogo
    if ($user['role'] === 'fornitore') {
        $fornitoreId = Auth::getFornitoreId();
        if (!$fornitoreId) {
            return jsonResponse($response, [
                'error' => 'Fornitore non trovato',
                'message' => 'Nessun fornitore associato a questo utente'
            ], 404);
        }
        
        $sql =
            "SELECT c.fid, c.pid, c.costo, c.quantita, c.note,
                    f.fnome, f.indirizzo,
                    p.pnome, p.colore, p.descrizione,
                    c.created_at, c.updated_at
             FROM Catalogo c
             JOIN Fornitori f ON f.fid = c.fid
             JOIN Pezzi p ON p.pid = c.pid
             WHERE c.fid = :fid";

        $bind = ['fid' => $fornitoreId];
        if ($search !== '') {
            $sql .= " AND (
                        c.pid LIKE :search
                        OR p.pnome LIKE :search
                        OR p.colore LIKE :search
                        OR COALESCE(c.note, '') LIKE :search
                     )";
            $bind['search'] = '%' . $search . '%';
        }

        $sql .= ' ORDER BY p.pnome';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($bind);
    } else {
        // Admin: mostra tutto
        $sql =
            "SELECT c.fid, c.pid, c.costo, c.quantita, c.note,
                    f.fnome, f.indirizzo,
                    p.pnome, p.colore, p.descrizione,
                    c.created_at, c.updated_at
             FROM Catalogo c
             JOIN Fornitori f ON f.fid = c.fid
             JOIN Pezzi p ON p.pid = c.pid
             WHERE 1=1";

        $bind = [];
        if ($search !== '') {
            $sql .= " AND (
                        c.fid LIKE :search
                        OR f.fnome LIKE :search
                        OR c.pid LIKE :search
                        OR p.pnome LIKE :search
                        OR p.colore LIKE :search
                        OR COALESCE(c.note, '') LIKE :search
                     )";
            $bind['search'] = '%' . $search . '%';
        }

        $sql .= ' ORDER BY f.fnome, p.pnome';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($bind);
    }
    
    $rows = $stmt->fetchAll();
    
    return jsonResponse($response, Paginator::paginate($rows, $request));
})->add(new AuthMiddleware());

/* ---------------------------------------------------------------
   GET /api/catalogo/{fid}/{pid} - Dettaglio voce catalogo
   Admin: qualsiasi voce
   Fornitore: solo voce del proprio catalogo
--------------------------------------------------------------- */
$app->get('/api/catalogo/{fid}/{pid}', function (Request $request, Response $response, array $args): Response {
    $fid = $args['fid'];
    $pid = $args['pid'];
    $user = Auth::user();

    if ($user['role'] === 'fornitore') {
        $fornitoreId = Auth::getFornitoreId();
        if ($fid !== $fornitoreId) {
            return jsonResponse($response, [
                'error' => 'Accesso negato',
                'message' => 'Puoi visualizzare solo il tuo catalogo'
            ], 403);
        }
    }

    $pdo = Database::getConnection();
    $stmt = $pdo->prepare(
        "SELECT c.fid, c.pid, c.costo, c.quantita, c.note,
                f.fnome, f.indirizzo,
                p.pnome, p.colore, p.descrizione,
                c.created_at, c.updated_at
         FROM Catalogo c
         JOIN Fornitori f ON f.fid = c.fid
         JOIN Pezzi p ON p.pid = c.pid
         WHERE c.fid = :fid AND c.pid = :pid"
    );
    $stmt->execute(['fid' => $fid, 'pid' => $pid]);
    $item = $stmt->fetch();

    if (!$item) {
        return jsonResponse($response, [
            'error' => 'Voce non trovata'
        ], 404);
    }

    return jsonResponse($response, ['data' => $item]);
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

=======
    return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
}

function getBearerToken(Request $request): ?string
{
    $auth = $request->getHeaderLine('Authorization');
    if (preg_match('/Bearer\s+(.+)/i', $auth, $m)) return $m[1];
    return null;
}

function authUser(Request $request): ?array
{
    $token = getBearerToken($request);
    if (!$token) return null;
    $secret   = $_ENV['APP_SECRET'] ?? 'changeme';
    $allUsers = Database::getConnection()->query('SELECT * FROM Utenti')->fetchAll();
    foreach ($allUsers as $u) {
        $expected = hash('sha256', $u['uid'] . '|' . $u['username'] . '|' . $secret);
        if (hash_equals($expected, $token)) return $u;
    }
    return null;
}

function requireAuth(Request $request, Response $response, string $role = ''): ?Response
{
    $user = authUser($request);
    if (!$user) return jsonResponse($response, ['error' => 'Non autenticato'], 401);
    if ($role && $user['ruolo'] !== $role) return jsonResponse($response, ['error' => 'Accesso negato'], 403);
    return null;
}

/* ===============================================================
   AUTH
   =============================================================== */
$app->post('/auth/login', function (Request $request, Response $response): Response {
    $b = json_decode((string)$request->getBody(), true) ?? [];
    $username = trim($b['username'] ?? '');
    $password = $b['password'] ?? '';
    if ($username === '' || $password === '')
        return jsonResponse($response, ['error' => 'username e password obbligatori'], 400);

    $stmt = Database::getConnection()->prepare('SELECT * FROM Utenti WHERE username=:u LIMIT 1');
    $stmt->execute([':u' => $username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash']))
        return jsonResponse($response, ['error' => 'Credenziali non valide'], 401);

    $secret = $_ENV['APP_SECRET'] ?? 'changeme';
    $token  = hash('sha256', $user['uid'] . '|' . $user['username'] . '|' . $secret);
    return jsonResponse($response, [
        'token' => $token,
        'user'  => ['uid'=>$user['uid'],'username'=>$user['username'],'ruolo'=>$user['ruolo'],'fid'=>$user['fid']],
    ]);
});

$app->get('/auth/me', function (Request $request, Response $response): Response {
    $user = authUser($request);
    if (!$user) return jsonResponse($response, ['error' => 'Non autenticato'], 401);
    return jsonResponse($response, ['uid'=>$user['uid'],'username'=>$user['username'],'ruolo'=>$user['ruolo'],'fid'=>$user['fid']]);
});

/* ===============================================================
   ADMIN — Utenti
   =============================================================== */
$app->get('/admin/utenti', function (Request $request, Response $response): Response {
    if ($err = requireAuth($request, $response, 'admin')) return $err;
    $rows = Database::getConnection()->query('SELECT uid,username,ruolo,fid,created_at FROM Utenti ORDER BY uid')->fetchAll();
    return jsonResponse($response, Paginator::paginate($rows, $request));
});

$app->get('/admin/utenti/{uid}', function (Request $request, Response $response, array $args): Response {
    if ($err = requireAuth($request, $response, 'admin')) return $err;
    $stmt = Database::getConnection()->prepare('SELECT uid,username,ruolo,fid,created_at FROM Utenti WHERE uid=:u');
    $stmt->execute([':u'=>(int)$args['uid']]);
    $row = $stmt->fetch();
    if (!$row) return jsonResponse($response, ['error'=>'Utente non trovato'], 404);
    return jsonResponse($response, $row);
});

$app->post('/admin/utenti', function (Request $request, Response $response): Response {
    if ($err = requireAuth($request, $response, 'admin')) return $err;
    $b = json_decode((string)$request->getBody(), true) ?? [];
    $username = trim($b['username'] ?? '');
    $password = $b['password'] ?? '';
    $ruolo    = $b['ruolo']    ?? 'fornitore';
    $fid      = $b['fid']      ?? null;
    if ($username===''||strlen($password)<4) return jsonResponse($response, ['error'=>'username e password (min 4) obbligatori'], 400);
    if (!in_array($ruolo, ['admin','fornitore'])) return jsonResponse($response, ['error'=>'ruolo non valido'], 400);
    try {
        $pdo = Database::getConnection();
        $pdo->prepare('INSERT INTO Utenti (username,password_hash,ruolo,fid) VALUES (:u,:h,:r,:f)')
            ->execute([':u'=>$username,':h'=>password_hash($password, PASSWORD_BCRYPT),':r'=>$ruolo,':f'=>$fid]);
        return jsonResponse($response, ['message'=>'Utente creato','uid'=>$pdo->lastInsertId()], 201);
    } catch (\PDOException) { return jsonResponse($response, ['error'=>'Username già esistente o fid non valido'], 409); }
});

$app->put('/admin/utenti/{uid}', function (Request $request, Response $response, array $args): Response {
    if ($err = requireAuth($request, $response, 'admin')) return $err;
    $b = json_decode((string)$request->getBody(), true) ?? [];
    $fields=[]; $params=[':uid'=>(int)$args['uid']];
    if (isset($b['username'])) { $fields[]='username=:u'; $params[':u']=$b['username']; }
    if (isset($b['password'])&&strlen($b['password'])>=4) { $fields[]='password_hash=:h'; $params[':h']=password_hash($b['password'],PASSWORD_BCRYPT); }
    if (isset($b['ruolo']))    { $fields[]='ruolo=:r'; $params[':r']=$b['ruolo']; }
    if (array_key_exists('fid',$b)) { $fields[]='fid=:f'; $params[':f']=$b['fid']?:null; }
    if (empty($fields)) return jsonResponse($response, ['error'=>'Nessun campo'], 400);
    Database::getConnection()->prepare('UPDATE Utenti SET '.implode(',',$fields).' WHERE uid=:uid')->execute($params);
    return jsonResponse($response, ['message'=>'Utente aggiornato']);
});

$app->delete('/admin/utenti/{uid}', function (Request $request, Response $response, array $args): Response {
    if ($err = requireAuth($request, $response, 'admin')) return $err;
    Database::getConnection()->prepare('DELETE FROM Utenti WHERE uid=:u')->execute([':u'=>(int)$args['uid']]);
    return jsonResponse($response, ['message'=>'Utente eliminato']);
});

/* ===============================================================
   ADMIN — Fornitori
   =============================================================== */
$app->get('/admin/fornitori', function (Request $request, Response $response): Response {
    if ($err = requireAuth($request, $response, 'admin')) return $err;
    $rows = Database::getConnection()->query('SELECT * FROM Fornitori ORDER BY fid')->fetchAll();
    return jsonResponse($response, Paginator::paginate($rows, $request));
});

$app->get('/admin/fornitori/{fid}', function (Request $request, Response $response, array $args): Response {
    if ($err = requireAuth($request, $response, 'admin')) return $err;
    $stmt = Database::getConnection()->prepare('SELECT * FROM Fornitori WHERE fid=:f');
    $stmt->execute([':f'=>$args['fid']]);
    $row = $stmt->fetch();
    if (!$row) return jsonResponse($response, ['error'=>'Non trovato'], 404);
    return jsonResponse($response, $row);
});

$app->post('/admin/fornitori', function (Request $request, Response $response): Response {
    if ($err = requireAuth($request, $response, 'admin')) return $err;
    $b = json_decode((string)$request->getBody(), true) ?? [];
    $fid=trim($b['fid']??''); $fnome=trim($b['fnome']??'');
    if (!$fid||!$fnome) return jsonResponse($response, ['error'=>'fid e fnome obbligatori'], 400);
    try {
        Database::getConnection()->prepare('INSERT INTO Fornitori (fid,fnome,indirizzo) VALUES (:f,:n,:i)')
            ->execute([':f'=>$fid,':n'=>$fnome,':i'=>$b['indirizzo']??null]);
        return jsonResponse($response, ['message'=>'Fornitore creato'], 201);
    } catch (\PDOException) { return jsonResponse($response, ['error'=>'fid già esistente'], 409); }
});

$app->put('/admin/fornitori/{fid}', function (Request $request, Response $response, array $args): Response {
    if ($err = requireAuth($request, $response, 'admin')) return $err;
    $b = json_decode((string)$request->getBody(), true) ?? [];
    $fields=[]; $params=[':fid'=>$args['fid']];
    if (isset($b['fnome']))     { $fields[]='fnome=:n';     $params[':n']=$b['fnome']; }
    if (isset($b['indirizzo'])) { $fields[]='indirizzo=:i'; $params[':i']=$b['indirizzo']; }
    if (empty($fields)) return jsonResponse($response, ['error'=>'Nessun campo'], 400);
    Database::getConnection()->prepare('UPDATE Fornitori SET '.implode(',',$fields).' WHERE fid=:fid')->execute($params);
    return jsonResponse($response, ['message'=>'Fornitore aggiornato']);
});

$app->delete('/admin/fornitori/{fid}', function (Request $request, Response $response, array $args): Response {
    if ($err = requireAuth($request, $response, 'admin')) return $err;
    Database::getConnection()->prepare('DELETE FROM Fornitori WHERE fid=:f')->execute([':f'=>$args['fid']]);
    return jsonResponse($response, ['message'=>'Fornitore eliminato']);
});

/* ===============================================================
   ADMIN — Pezzi
   =============================================================== */
$app->get('/admin/pezzi', function (Request $request, Response $response): Response {
    if ($err = requireAuth($request, $response, 'admin')) return $err;
    $rows = Database::getConnection()->query('SELECT * FROM Pezzi ORDER BY pid')->fetchAll();
    return jsonResponse($response, Paginator::paginate($rows, $request));
});

$app->get('/admin/pezzi/{pid}', function (Request $request, Response $response, array $args): Response {
    if ($err = requireAuth($request, $response, 'admin')) return $err;
    $stmt = Database::getConnection()->prepare('SELECT * FROM Pezzi WHERE pid=:p');
    $stmt->execute([':p'=>$args['pid']]);
    $row = $stmt->fetch();
    if (!$row) return jsonResponse($response, ['error'=>'Non trovato'], 404);
    return jsonResponse($response, $row);
});

$app->post('/admin/pezzi', function (Request $request, Response $response): Response {
    if ($err = requireAuth($request, $response, 'admin')) return $err;
    $b = json_decode((string)$request->getBody(), true) ?? [];
    $pid=trim($b['pid']??''); $pnome=trim($b['pnome']??''); $colore=trim($b['colore']??'');
    if (!$pid||!$pnome||!$colore) return jsonResponse($response, ['error'=>'pid, pnome, colore obbligatori'], 400);
    try {
        Database::getConnection()->prepare('INSERT INTO Pezzi (pid,pnome,colore) VALUES (:p,:n,:c)')
            ->execute([':p'=>$pid,':n'=>$pnome,':c'=>$colore]);
        return jsonResponse($response, ['message'=>'Pezzo creato'], 201);
    } catch (\PDOException) { return jsonResponse($response, ['error'=>'pid già esistente'], 409); }
});

$app->put('/admin/pezzi/{pid}', function (Request $request, Response $response, array $args): Response {
    if ($err = requireAuth($request, $response, 'admin')) return $err;
    $b = json_decode((string)$request->getBody(), true) ?? [];
    $fields=[]; $params=[':pid'=>$args['pid']];
    if (isset($b['pnome']))  { $fields[]='pnome=:n';  $params[':n']=$b['pnome']; }
    if (isset($b['colore'])) { $fields[]='colore=:c'; $params[':c']=$b['colore']; }
    if (empty($fields)) return jsonResponse($response, ['error'=>'Nessun campo'], 400);
    Database::getConnection()->prepare('UPDATE Pezzi SET '.implode(',',$fields).' WHERE pid=:pid')->execute($params);
    return jsonResponse($response, ['message'=>'Pezzo aggiornato']);
});

$app->delete('/admin/pezzi/{pid}', function (Request $request, Response $response, array $args): Response {
    if ($err = requireAuth($request, $response, 'admin')) return $err;
    Database::getConnection()->prepare('DELETE FROM Pezzi WHERE pid=:p')->execute([':p'=>$args['pid']]);
    return jsonResponse($response, ['message'=>'Pezzo eliminato']);
});

/* ===============================================================
   ADMIN — Catalogo
   =============================================================== */
$app->get('/admin/catalogo', function (Request $request, Response $response): Response {
    if ($err = requireAuth($request, $response, 'admin')) return $err;
    $rows = Database::getConnection()->query(
        'SELECT c.fid,f.fnome,c.pid,p.pnome,p.colore,c.costo
         FROM Catalogo c JOIN Fornitori f ON f.fid=c.fid JOIN Pezzi p ON p.pid=c.pid
         ORDER BY c.fid,c.pid')->fetchAll();
    return jsonResponse($response, Paginator::paginate($rows, $request));
});

$app->get('/admin/catalogo/{fid}/{pid}', function (Request $request, Response $response, array $args): Response {
    if ($err = requireAuth($request, $response, 'admin')) return $err;
    $stmt = Database::getConnection()->prepare(
        'SELECT c.*,f.fnome,p.pnome,p.colore FROM Catalogo c
         JOIN Fornitori f ON f.fid=c.fid JOIN Pezzi p ON p.pid=c.pid
         WHERE c.fid=:f AND c.pid=:p');
    $stmt->execute([':f'=>$args['fid'],':p'=>$args['pid']]);
    $row = $stmt->fetch();
    if (!$row) return jsonResponse($response, ['error'=>'Non trovato'], 404);
    return jsonResponse($response, $row);
});

$app->post('/admin/catalogo', function (Request $request, Response $response): Response {
    if ($err = requireAuth($request, $response, 'admin')) return $err;
    $b = json_decode((string)$request->getBody(), true) ?? [];
    $fid=trim($b['fid']??''); $pid=trim($b['pid']??''); $costo=(float)($b['costo']??0);
    if (!$fid||!$pid||$costo<=0) return jsonResponse($response, ['error'=>'fid, pid e costo>0 obbligatori'], 400);
    try {
        Database::getConnection()->prepare('INSERT INTO Catalogo (fid,pid,costo) VALUES (:f,:p,:c)')
            ->execute([':f'=>$fid,':p'=>$pid,':c'=>$costo]);
        return jsonResponse($response, ['message'=>'Voce creata'], 201);
    } catch (\PDOException) { return jsonResponse($response, ['error'=>'Già esistente o FK non valida'], 409); }
});

$app->put('/admin/catalogo/{fid}/{pid}', function (Request $request, Response $response, array $args): Response {
    if ($err = requireAuth($request, $response, 'admin')) return $err;
    $costo = (float)(json_decode((string)$request->getBody(), true)['costo'] ?? 0);
    if ($costo<=0) return jsonResponse($response, ['error'=>'costo>0 richiesto'], 400);
    Database::getConnection()->prepare('UPDATE Catalogo SET costo=:c WHERE fid=:f AND pid=:p')
        ->execute([':c'=>$costo,':f'=>$args['fid'],':p'=>$args['pid']]);
    return jsonResponse($response, ['message'=>'Costo aggiornato']);
});

$app->delete('/admin/catalogo/{fid}/{pid}', function (Request $request, Response $response, array $args): Response {
    if ($err = requireAuth($request, $response, 'admin')) return $err;
    Database::getConnection()->prepare('DELETE FROM Catalogo WHERE fid=:f AND pid=:p')
        ->execute([':f'=>$args['fid'],':p'=>$args['pid']]);
    return jsonResponse($response, ['message'=>'Voce eliminata']);
});

/* ===============================================================
   FORNITORE — gestione solo del proprio catalogo
   =============================================================== */
$app->get('/fornitore/catalogo', function (Request $request, Response $response): Response {
    $user = authUser($request);
    if (!$user||$user['ruolo']!=='fornitore'||!$user['fid']) return jsonResponse($response, ['error'=>'Accesso negato'], 403);
    $stmt = Database::getConnection()->prepare(
        'SELECT c.pid,p.pnome,p.colore,c.costo FROM Catalogo c JOIN Pezzi p ON p.pid=c.pid
         WHERE c.fid=:f ORDER BY c.pid');
    $stmt->execute([':f'=>$user['fid']]);
    return jsonResponse($response, Paginator::paginate($stmt->fetchAll(), $request));
});

$app->get('/fornitore/catalogo/{pid}', function (Request $request, Response $response, array $args): Response {
    $user = authUser($request);
    if (!$user||$user['ruolo']!=='fornitore'||!$user['fid']) return jsonResponse($response, ['error'=>'Accesso negato'], 403);
    $stmt = Database::getConnection()->prepare(
        'SELECT c.fid,c.pid,p.pnome,p.colore,c.costo FROM Catalogo c JOIN Pezzi p ON p.pid=c.pid
         WHERE c.fid=:f AND c.pid=:p');
    $stmt->execute([':f'=>$user['fid'],':p'=>$args['pid']]);
    $row = $stmt->fetch();
    if (!$row) return jsonResponse($response, ['error'=>'Non nel tuo catalogo'], 404);
    return jsonResponse($response, $row);
});

$app->post('/fornitore/catalogo', function (Request $request, Response $response): Response {
    $user = authUser($request);
    if (!$user||$user['ruolo']!=='fornitore'||!$user['fid']) return jsonResponse($response, ['error'=>'Accesso negato'], 403);
    $b = json_decode((string)$request->getBody(), true) ?? [];
    $pid=trim($b['pid']??''); $costo=(float)($b['costo']??0);
    if (!$pid||$costo<=0) return jsonResponse($response, ['error'=>'pid e costo>0 obbligatori'], 400);
    try {
        Database::getConnection()->prepare('INSERT INTO Catalogo (fid,pid,costo) VALUES (:f,:p,:c)')
            ->execute([':f'=>$user['fid'],':p'=>$pid,':c'=>$costo]);
        return jsonResponse($response, ['message'=>'Aggiunto al catalogo'], 201);
    } catch (\PDOException) { return jsonResponse($response, ['error'=>'Già presente o pid non valido'], 409); }
});

$app->put('/fornitore/catalogo/{pid}', function (Request $request, Response $response, array $args): Response {
    $user = authUser($request);
    if (!$user||$user['ruolo']!=='fornitore'||!$user['fid']) return jsonResponse($response, ['error'=>'Accesso negato'], 403);
    $costo = (float)(json_decode((string)$request->getBody(), true)['costo'] ?? 0);
    if ($costo<=0) return jsonResponse($response, ['error'=>'costo>0 richiesto'], 400);
    Database::getConnection()->prepare('UPDATE Catalogo SET costo=:c WHERE fid=:f AND pid=:p')
        ->execute([':c'=>$costo,':f'=>$user['fid'],':p'=>$args['pid']]);
    return jsonResponse($response, ['message'=>'Costo aggiornato']);
});

$app->delete('/fornitore/catalogo/{pid}', function (Request $request, Response $response, array $args): Response {
    $user = authUser($request);
    if (!$user||$user['ruolo']!=='fornitore'||!$user['fid']) return jsonResponse($response, ['error'=>'Accesso negato'], 403);
    Database::getConnection()->prepare('DELETE FROM Catalogo WHERE fid=:f AND pid=:p')
        ->execute([':f'=>$user['fid'],':p'=>$args['pid']]);
    return jsonResponse($response, ['message'=>'Rimosso dal catalogo']);
});

$app->get('/fornitore/pezzi-disponibili', function (Request $request, Response $response): Response {
    $user = authUser($request);
    if (!$user||$user['ruolo']!=='fornitore'||!$user['fid']) return jsonResponse($response, ['error'=>'Accesso negato'], 403);
    $stmt = Database::getConnection()->prepare(
        'SELECT pid,pnome,colore FROM Pezzi WHERE pid NOT IN
         (SELECT pid FROM Catalogo WHERE fid=:f) ORDER BY pid');
    $stmt->execute([':f'=>$user['fid']]);
    return jsonResponse($response, Paginator::paginate($stmt->fetchAll(), $request));
});

/* ===============================================================
   ENDPOINT QUERY 1-10
   =============================================================== */
$app->get('/1', function (Request $request, Response $response): Response {
    $rows = Database::getConnection()->query("SELECT DISTINCT p.pid,p.pnome,p.colore FROM Pezzi p JOIN Catalogo c ON c.pid=p.pid ORDER BY p.pnome")->fetchAll();
    return jsonResponse($response, Paginator::paginate($rows, $request));
});
$app->get('/2', function (Request $request, Response $response): Response {
    $rows = Database::getConnection()->query("SELECT f.fid,f.fnome,f.indirizzo FROM Fornitori f JOIN Catalogo c ON c.fid=f.fid GROUP BY f.fid,f.fnome,f.indirizzo HAVING COUNT(DISTINCT c.pid)=(SELECT COUNT(*) FROM Pezzi) ORDER BY f.fid")->fetchAll();
    return jsonResponse($response, ['data'=>$rows]);
});
$app->get('/3', function (Request $request, Response $response): Response {
    $colore = trim($request->getQueryParams()['colore'] ?? 'rosso');
    $stmt = Database::getConnection()->prepare("SELECT f.fid,f.fnome,f.indirizzo FROM Fornitori f JOIN Catalogo c ON c.fid=f.fid JOIN Pezzi p ON p.pid=c.pid WHERE p.colore=:c GROUP BY f.fid,f.fnome,f.indirizzo HAVING COUNT(DISTINCT p.pid)=(SELECT COUNT(*) FROM Pezzi WHERE colore=:c2) ORDER BY f.fid");
    $stmt->execute([':c'=>$colore,':c2'=>$colore]);
    return jsonResponse($response, ['filtro'=>['colore'=>$colore],'data'=>$stmt->fetchAll()]);
});
$app->get('/4', function (Request $request, Response $response): Response {
    $fnome = trim($request->getQueryParams()['fnome'] ?? 'Acme');
    $stmt = Database::getConnection()->prepare("SELECT p.pid,p.pnome,p.colore FROM Pezzi p WHERE EXISTS(SELECT 1 FROM Catalogo c1 JOIN Fornitori f1 ON f1.fid=c1.fid WHERE c1.pid=p.pid AND f1.fnome=:fe) AND NOT EXISTS(SELECT 1 FROM Catalogo c2 JOIN Fornitori f2 ON f2.fid=c2.fid WHERE c2.pid=p.pid AND f2.fnome<>:fn) ORDER BY p.pid");
    $stmt->execute([':fe'=>$fnome,':fn'=>$fnome]);
    return jsonResponse($response, ['filtro'=>['fnome'=>$fnome],'data'=>$stmt->fetchAll()]);
});
$app->get('/5', function (Request $request, Response $response): Response {
    $rows = Database::getConnection()->query("SELECT DISTINCT f.fid,f.fnome,f.indirizzo FROM Catalogo c JOIN (SELECT pid,AVG(costo) AS cm FROM Catalogo GROUP BY pid) m ON c.pid=m.pid JOIN Fornitori f ON f.fid=c.fid WHERE c.costo>m.cm ORDER BY f.fid")->fetchAll();
    return jsonResponse($response, Paginator::paginate($rows, $request));
});
$app->get('/6', function (Request $request, Response $response): Response {
    $rows = Database::getConnection()->query("SELECT p.pid,p.pnome,p.colore,f.fid,f.fnome,c.costo FROM Catalogo c JOIN Pezzi p ON p.pid=c.pid JOIN Fornitori f ON f.fid=c.fid WHERE NOT EXISTS(SELECT 1 FROM Catalogo c2 WHERE c2.pid=c.pid AND c2.costo<c.costo) ORDER BY p.pid,c.costo")->fetchAll();
    return jsonResponse($response, Paginator::paginate($rows, $request));
});
$app->get('/7', function (Request $request, Response $response): Response {
    $rows = Database::getConnection()->query("SELECT f.fid,f.fnome,f.indirizzo FROM Fornitori f WHERE EXISTS(SELECT 1 FROM Catalogo c WHERE c.fid=f.fid) AND NOT EXISTS(SELECT 1 FROM Catalogo c2 JOIN Pezzi p2 ON p2.pid=c2.pid WHERE c2.fid=f.fid AND p2.colore<>'rosso') ORDER BY f.fid")->fetchAll();
    return jsonResponse($response, ['data'=>$rows]);
});
$app->get('/8', function (Request $request, Response $response): Response {
    $rows = Database::getConnection()->query("SELECT f.fid,f.fnome,f.indirizzo,COUNT(DISTINCT CASE WHEN p.colore='rosso' THEN p.pid END) AS pezzi_rossi,COUNT(DISTINCT CASE WHEN p.colore='verde' THEN p.pid END) AS pezzi_verdi FROM Fornitori f JOIN Catalogo c ON c.fid=f.fid JOIN Pezzi p ON p.pid=c.pid GROUP BY f.fid,f.fnome,f.indirizzo HAVING COUNT(DISTINCT CASE WHEN p.colore='rosso' THEN p.pid END)>0 AND COUNT(DISTINCT CASE WHEN p.colore='verde' THEN p.pid END)>0 ORDER BY f.fid")->fetchAll();
    return jsonResponse($response, Paginator::paginate($rows, $request));
});
$app->get('/9', function (Request $request, Response $response): Response {
    $validi=['rosso','verde','blu'];
    $input=array_filter(array_map('trim',explode(',',$request->getQueryParams()['colori']??'rosso,verde')),fn($c)=>in_array($c,$validi,true));
    if(empty($input)) return jsonResponse($response, ['error'=>'Colori non validi'], 400);
    $ph=implode(',',array_fill(0,count($input),'?'));
    $stmt=Database::getConnection()->prepare("SELECT DISTINCT f.fid,f.fnome,f.indirizzo FROM Catalogo c JOIN Pezzi p ON p.pid=c.pid JOIN Fornitori f ON f.fid=c.fid WHERE p.colore IN ({$ph}) ORDER BY f.fid");
    $stmt->execute(array_values($input));
    return jsonResponse($response, array_merge(['filtro'=>['colori'=>array_values($input)]],Paginator::paginate($stmt->fetchAll(),$request)));
});
$app->get('/10', function (Request $request, Response $response): Response {
    $min=max(1,(int)($request->getQueryParams()['min_fornitori']??2));
    $stmt=Database::getConnection()->prepare("SELECT p.pid,p.pnome,p.colore,COUNT(DISTINCT c.fid) AS num_fornitori FROM Catalogo c JOIN Pezzi p ON p.pid=c.pid GROUP BY c.pid,p.pnome,p.colore HAVING COUNT(DISTINCT c.fid)>=:m ORDER BY num_fornitori DESC,p.pid");
    $stmt->bindValue(':m',$min,PDO::PARAM_INT); $stmt->execute();
    return jsonResponse($response, array_merge(['filtro'=>['min_fornitori'=>$min]],Paginator::paginate($stmt->fetchAll(),$request)));
});

/* ---- Home / Dashboard ---- */
$homeHandler = function (Request $request, Response $response): Response {
    return jsonResponse($response, ['message'=>'API FornitoriPezziDB','endpoints'=>['/1','/2','/3','/4','/5','/6','/7','/8','/9','/10'],'auth'=>['POST /auth/login','GET /auth/me'],'admin'=>['GET|POST|PUT|DELETE /admin/fornitori[/{fid}]','GET|POST|PUT|DELETE /admin/pezzi[/{pid}]','GET|POST|PUT|DELETE /admin/catalogo[/{fid}/{pid}]','GET|POST|PUT|DELETE /admin/utenti[/{uid}]'],'fornitore'=>['GET|POST|PUT|DELETE /fornitore/catalogo[/{pid}]','GET /fornitore/pezzi-disponibili']]);
};

$dashboardHandler = function (Request $request, Response $response): Response {
    ob_start();
    include __DIR__ . '/../dashboard.php';
    $html = (string)ob_get_clean();
    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html; charset=UTF-8');
};

$app->get('/',              $homeHandler);
$app->get('/index.php',     $homeHandler);
$app->get('/dashboard',     $dashboardHandler);
$app->get('/dashboard.php', $dashboardHandler);
>>>>>>> 13b6648 (finale)
$app->run();
