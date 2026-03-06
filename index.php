<?php
declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use App\Database;
use App\Paginator;

require __DIR__ . '/../vendor/autoload.php';

/* ---------------------------------------------------------------
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

$app->add(function (Request $request, $handler) {
    $pathInfo = $_SERVER['PATH_INFO'] ?? '';
    if ($pathInfo !== '') {
        $request = $request->withUri($request->getUri()->withPath($pathInfo));
    }
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
--------------------------------------------------------------- */
function jsonResponse(Response $response, mixed $payload, int $status = 200): Response
{
    $response->getBody()->write(
        json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );
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
$app->run();
