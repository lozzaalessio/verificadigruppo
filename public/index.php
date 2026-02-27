<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use App\Database;
use App\Paginator;

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
