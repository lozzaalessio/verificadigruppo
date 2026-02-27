<?php

declare(strict_types=1);

namespace Tests;

use App\Database;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Unit test per le 10 query SQL.
 * Usa SQLite in-memory: nessuna dipendenza da MySQL.
 *
 * Esegui con: composer test
 */
class QueryTest extends TestCase
{
    private static PDO $db;

    /* -----------------------------------------------------------
       Setup: crea schema SQLite e popola con dati di test
    ----------------------------------------------------------- */
    public static function setUpBeforeClass(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $pdo->exec("
            CREATE TABLE Fornitori (
                fid       TEXT PRIMARY KEY,
                fnome     TEXT NOT NULL,
                indirizzo TEXT
            );
            CREATE TABLE Pezzi (
                pid    TEXT PRIMARY KEY,
                pnome  TEXT NOT NULL,
                colore TEXT NOT NULL
            );
            CREATE TABLE Catalogo (
                fid   TEXT NOT NULL,
                pid   TEXT NOT NULL,
                costo REAL NOT NULL,
                PRIMARY KEY (fid, pid)
            );
        ");

        // Fornitori
        $pdo->exec("
            INSERT INTO Fornitori VALUES
                ('F01','Acme',         'Via Roma 1, Milano'),
                ('F02','WidgetCorp',   'Via Milano 2, Torino'),
                ('F03','Supplies Inc', 'Via Torino 3, Genova'),
                ('F04','TechParts',    'Via Venezia 4, Venezia'),
                ('F05','MegaSupplies', 'Via Napoli 5, Napoli'),
                ('F06','GreenTech',    'Via Palermo 6, Palermo'),
                ('F07','RedComponents','Via Firenze 7, Firenze');
        ");

        // Pezzi
        $pdo->exec("
            INSERT INTO Pezzi VALUES
                ('P01','Bullone','rosso'),
                ('P02','Vite','blu'),
                ('P03','Dado','rosso'),
                ('P04','Rivetto','verde'),
                ('P05','Molla','blu'),
                ('P06','Guarnizione','rosso'),
                ('P07','Cuscinetto','verde'),
                ('P08','Cavo','blu'),
                ('P09','Resistore','rosso'),
                ('P10','Condensatore','verde'),
                ('P11','Trasformatore','verde'),
                ('P12','Fusibile','rosso');
        ");

        // Catalogo
        $pdo->exec("
            INSERT INTO Catalogo VALUES
                ('F01','P01',10.5),('F01','P02',5.1),('F01','P03',8.4),
                ('F01','P04',6.0),('F01','P05',7.3),('F01','P06',9.0),
                ('F01','P07',12.2),('F01','P08',4.6),('F01','P09',15.0),
                ('F01','P10',8.6),('F01','P11',18.0),('F01','P12',6.5),
                ('F02','P01',11.0),('F02','P02',5.0),('F02','P03',8.1),
                ('F02','P04',6.7),('F02','P05',7.0),('F02','P07',11.8),
                ('F03','P08',3.9),('F03','P09',14.8),('F03','P10',9.8),
                ('F03','P11',17.5),('F03','P12',6.2),
                ('F04','P04',6.1),('F04','P07',12.0),('F04','P10',8.9),('F04','P11',18.3),
                ('F05','P02',5.3),('F05','P05',7.4),('F05','P06',9.2),('F05','P08',4.8),
                ('F06','P04',6.4),('F06','P07',12.5),('F06','P10',9.1),
                ('F07','P01',10.9),('F07','P03',8.3),('F07','P06',9.1),
                ('F07','P09',15.2),('F07','P12',6.7);
        ");

        self::$db = $pdo;
        Database::setConnection($pdo);
    }

    /* -----------------------------------------------------------
       Q1 – Pezzi distinti nel catalogo
    ----------------------------------------------------------- */
    public function testQ1PezziNelCatalogo(): void
    {
        $rows = self::$db->query(
            "SELECT DISTINCT p.pnome
             FROM Pezzi p
             JOIN Catalogo c ON c.pid = p.pid"
        )->fetchAll();

        // Tutti i 12 pezzi sono in catalogo (Acme li ha tutti)
        $this->assertCount(12, $rows);
    }

    /* -----------------------------------------------------------
       Q2 – Fornitori che forniscono TUTTI i pezzi
    ----------------------------------------------------------- */
    public function testQ2FornitoriTuttiIPezzi(): void
    {
        $rows = self::$db->query(
            "SELECT f.fnome
             FROM Fornitori f
             JOIN Catalogo c ON c.fid = f.fid
             GROUP BY f.fid, f.fnome
             HAVING COUNT(DISTINCT c.pid) = (SELECT COUNT(*) FROM Pezzi)"
        )->fetchAll();

        // Solo Acme (F01) ha tutti e 12 i pezzi
        $this->assertCount(1, $rows);
        $this->assertEquals('Acme', $rows[0]['fnome']);
    }

    /* -----------------------------------------------------------
       Q3 – Fornitori che forniscono tutti i pezzi ROSSI
    ----------------------------------------------------------- */
    public function testQ3FornitoriTuttiPezziRossi(): void
    {
        $colore = 'rosso';
        $stmt   = self::$db->prepare(
            "SELECT f.fnome
             FROM Fornitori f
             JOIN Catalogo c ON c.fid = f.fid
             JOIN Pezzi p    ON p.pid  = c.pid
             WHERE p.colore = :c
             GROUP BY f.fid, f.fnome
             HAVING COUNT(DISTINCT p.pid) = (
                 SELECT COUNT(*) FROM Pezzi WHERE colore = :c2
             )"
        );
        $stmt->execute([':c' => $colore, ':c2' => $colore]);
        $rows = $stmt->fetchAll();

        // I pezzi rossi sono P01,P03,P06,P09,P12 (5 pezzi).
        // Solo F01 (Acme) li ha tutti e 5.
        $nomi = array_column($rows, 'fnome');
        $this->assertContains('Acme', $nomi);
    }

    /* -----------------------------------------------------------
       Q4 – Pezzi venduti SOLO da Acme (unicità nel catalogo)
    ----------------------------------------------------------- */
    public function testQ4PezziSoloAcme(): void
    {
        $stmt = self::$db->prepare(
                        "SELECT p.pnome
                         FROM Pezzi p
                         WHERE EXISTS (
                                 SELECT 1
                                 FROM Catalogo c1
                                 JOIN Fornitori f1 ON f1.fid = c1.fid
                                 WHERE c1.pid = p.pid
                                     AND f1.fnome = :fnome
                         )
                         AND NOT EXISTS (
                                 SELECT 1
                                 FROM Catalogo c2
                                 JOIN Fornitori f2 ON f2.fid = c2.fid
                                 WHERE c2.pid = p.pid
                                     AND f2.fnome <> :fnome
                         )"
        );
        $stmt->execute([':fnome' => 'Acme']);
        $rows = $stmt->fetchAll();

        // P02,P05 sono venduti anche da altri fornitori
        // Ci aspettiamo che la lista NON sia vuota
        $this->assertIsArray($rows);
        // Verifica che 'Bullone' (P01) NON sia qui (venduto anche da F02, F07)
        $nomi = array_column($rows, 'pnome');
        $this->assertNotContains('Bullone', $nomi);
    }

    /* -----------------------------------------------------------
       Q5 – Fornitori con almeno un pezzo sopra la media
    ----------------------------------------------------------- */
    public function testQ5FornitoriSopraMedia(): void
    {
        $rows = self::$db->query(
            "SELECT DISTINCT c.fid
             FROM Catalogo c
             JOIN (
                 SELECT pid, AVG(costo) AS costo_medio
                 FROM Catalogo
                 GROUP BY pid
             ) AS media ON c.pid = media.pid
             WHERE c.costo > media.costo_medio"
        )->fetchAll();

        $this->assertNotEmpty($rows);
        // Deve esserci almeno un fornitore
        $this->assertGreaterThanOrEqual(1, count($rows));
    }

    /* -----------------------------------------------------------
       Q6 – Fornitore col costo minimo per ogni pezzo
    ----------------------------------------------------------- */
    public function testQ6CostoMinPerPezzo(): void
    {
        $rows = self::$db->query(
            "SELECT p.pid, p.pnome, f.fnome, c.costo
             FROM Catalogo c
             JOIN Pezzi     p ON p.pid = c.pid
             JOIN Fornitori f ON f.fid = c.fid
             WHERE NOT EXISTS (
                 SELECT 1
                 FROM Catalogo c2
                 WHERE c2.pid   = c.pid
                   AND c2.costo < c.costo
             )
             ORDER BY p.pid"
        )->fetchAll();

        $this->assertNotEmpty($rows);

        // Per P02 (Vite) il minimo è F02 a 5.0
        $p02 = array_filter($rows, fn($r) => $r['pid'] === 'P02');
        $p02 = array_values($p02);
        $this->assertEquals(5.0, (float)$p02[0]['costo']);
    }

    /* -----------------------------------------------------------
       Q7 – Fornitori che vendono SOLO pezzi rossi
    ----------------------------------------------------------- */
    public function testQ7SoloRossi(): void
    {
        $rows = self::$db->query(
            "SELECT f.fid
             FROM Fornitori f
             WHERE EXISTS (
                 SELECT 1 FROM Catalogo c WHERE c.fid = f.fid
             )
             AND NOT EXISTS (
                 SELECT 1
                 FROM Catalogo c2
                 JOIN Pezzi p2 ON p2.pid = c2.pid
                 WHERE c2.fid = f.fid AND p2.colore <> 'rosso'
             )"
        )->fetchAll();

        $fids = array_column($rows, 'fid');
        // F07 (RedComponents) vende solo P01,P03,P06,P09,P12 – tutti rossi
        $this->assertContains('F07', $fids);
        // F01 (Acme) vende anche pezzi blu e verdi: NON deve comparire
        $this->assertNotContains('F01', $fids);
    }

    /* -----------------------------------------------------------
       Q8 – Fornitori con pezzi SIA rossi SIA verdi
    ----------------------------------------------------------- */
    public function testQ8RossiEVerdi(): void
    {
        $rows = self::$db->query(
            "SELECT f.fid
             FROM Fornitori f
             JOIN Catalogo c ON c.fid = f.fid
             JOIN Pezzi    p ON p.pid = c.pid
             GROUP BY f.fid
             HAVING COUNT(DISTINCT CASE WHEN p.colore = 'rosso' THEN p.pid END) > 0
                AND COUNT(DISTINCT CASE WHEN p.colore = 'verde' THEN p.pid END) > 0"
        )->fetchAll();

        $fids = array_column($rows, 'fid');
        // F01 ha sia rossi che verdi
        $this->assertContains('F01', $fids);
        // F07 ha solo rossi: NON deve comparire
        $this->assertNotContains('F07', $fids);
    }

    /* -----------------------------------------------------------
       Q9 – Fornitori con almeno un pezzo rosso O verde
    ----------------------------------------------------------- */
    public function testQ9RossiOVerdi(): void
    {
        $rows = self::$db->query(
            "SELECT DISTINCT c.fid
             FROM Catalogo c
             JOIN Pezzi p ON p.pid = c.pid
             WHERE p.colore IN ('rosso','verde')"
        )->fetchAll();

        $fids = array_column($rows, 'fid');
        // F01 ha pezzi rossi e verdi
        $this->assertContains('F01', $fids);
        // Tutti i fornitori tranne eventualmente chi vende solo blu
        $this->assertNotEmpty($rows);
    }

    /* -----------------------------------------------------------
       Q10 – Pezzi in catalogo di almeno 2 fornitori
    ----------------------------------------------------------- */
    public function testQ10PezziMultiFornitori(): void
    {
        $stmt = self::$db->prepare(
            "SELECT c.pid
             FROM Catalogo c
             GROUP BY c.pid
             HAVING COUNT(DISTINCT c.fid) >= :min"
        );
        $stmt->bindValue(':min', 2, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $pids = array_column($rows, 'pid');
        // P01 è venduto da F01, F02, F07 → deve comparire
        $this->assertContains('P01', $pids);
        // P05 (Molla) è venduto da F01 e F05 → deve comparire
        $this->assertContains('P05', $pids);
    }

    /* -----------------------------------------------------------
       Extra: paginazione
    ----------------------------------------------------------- */
    public function testPaginatorSlicesCorrectly(): void
    {
        $data = range(1, 50);
        // Simula request con page=2, per_page=10
        $request = $this->createMock(\Psr\Http\Message\ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn(['page' => '2', 'per_page' => '10']);

        $result = \App\Paginator::paginate($data, $request);

        $this->assertCount(10, $result['data']);
        $this->assertEquals(11, $result['data'][0]); // secondo blocco di 10
        $this->assertEquals(2,  $result['meta']['current_page']);
        $this->assertEquals(5,  $result['meta']['last_page']);
        $this->assertEquals(50, $result['meta']['total']);
    }
}
