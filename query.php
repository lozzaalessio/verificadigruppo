<?php
/**
 * Esecuzione delle 10 query sul database FornitoriPezziDB
 */

// Includi la connessione al database
require_once 'database.php';

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Risultati Query - FornitoriPezziDB</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .query-section {
            background: white;
            margin-bottom: 30px;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h2 {
            color: #333;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th {
            background-color: #4CAF50;
            color: white;
            padding: 12px;
            text-align: left;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .sql-code {
            background-color: #f4f4f4;
            padding: 10px;
            border-left: 4px solid #4CAF50;
            font-family: monospace;
            font-size: 12px;
            overflow-x: auto;
            margin: 10px 0;
        }
        .no-results {
            color: #999;
            font-style: italic;
        }
    </style>
</head>
<body>
    <h1>Risultati Query - Database Fornitori e Pezzi</h1>

    <?php
    // ========== QUERY 1 ==========
    echo '<div class="query-section">';
    echo '<h2>Query 1: Nomi dei pezzi presenti nel catalogo</h2>';
    echo '<div class="sql-code">SELECT DISTINCT p.pnome FROM Pezzi p JOIN Catalogo c ON c.pid = p.pid;</div>';
    
    $query1 = "SELECT DISTINCT p.pnome FROM Pezzi p JOIN Catalogo c ON c.pid = p.pid";
    $stmt1 = $pdo->query($query1);
    $results1 = $stmt1->fetchAll();
    
    if ($results1) {
        echo '<table><tr><th>Nome Pezzo</th></tr>';
        foreach ($results1 as $row) {
            echo '<tr><td>' . htmlspecialchars($row['pnome']) . '</td></tr>';
        }
        echo '</table>';
    } else {
        echo '<p class="no-results">Nessun risultato trovato</p>';
    }
    echo '</div>';

    // ========== QUERY 2 ==========
    echo '<div class="query-section">';
    echo '<h2>Query 2: Fornitori che vendono tutti i pezzi</h2>';
    echo '<div class="sql-code">SELECT f.fnome FROM Fornitori f JOIN Catalogo c ON c.fid = f.fid GROUP BY f.fid, f.fnome HAVING COUNT(DISTINCT c.pid) = (SELECT COUNT(*) FROM Pezzi);</div>';
    
    $query2 = "SELECT f.fnome
FROM Fornitori f
JOIN Catalogo c ON c.fid = f.fid
GROUP BY f.fid, f.fnome
HAVING COUNT(DISTINCT c.pid) = (SELECT COUNT(*) FROM Pezzi)";
    $stmt2 = $pdo->query($query2);
    $results2 = $stmt2->fetchAll();
    
    if ($results2) {
        echo '<table><tr><th>Nome Fornitore</th></tr>';
        foreach ($results2 as $row) {
            echo '<tr><td>' . htmlspecialchars($row['fnome']) . '</td></tr>';
        }
        echo '</table>';
    } else {
        echo '<p class="no-results">Nessun risultato trovato</p>';
    }
    echo '</div>';

    // ========== QUERY 3 ==========
    echo '<div class="query-section">';
    echo '<h2>Query 3: Fornitori che vendono tutti i pezzi rossi</h2>';
    echo '<div class="sql-code">SELECT f.fnome FROM Fornitori f JOIN Catalogo c ON c.fid = f.fid JOIN Pezzi p ON p.pid = c.pid WHERE p.colore = \'rosso\' GROUP BY f.fid, f.fnome HAVING COUNT(DISTINCT p.pid) = (SELECT COUNT(*) FROM Pezzi WHERE colore = \'rosso\');</div>';
    
    $query3 = "SELECT f.fnome
FROM Fornitori f
JOIN Catalogo c ON c.fid = f.fid
JOIN Pezzi p ON p.pid = c.pid
WHERE p.colore = 'rosso'
GROUP BY f.fid, f.fnome
HAVING COUNT(DISTINCT p.pid) = (SELECT COUNT(*) FROM Pezzi WHERE colore = 'rosso')";
    $stmt3 = $pdo->query($query3);
    $results3 = $stmt3->fetchAll();
    
    if ($results3) {
        echo '<table><tr><th>Nome Fornitore</th></tr>';
        foreach ($results3 as $row) {
            echo '<tr><td>' . htmlspecialchars($row['fnome']) . '</td></tr>';
        }
        echo '</table>';
    } else {
        echo '<p class="no-results">Nessun risultato trovato</p>';
    }
    echo '</div>';

    // ========== QUERY 4 ==========
    echo '<div class="query-section">';
    echo '<h2>Query 4: Pezzi venduti esclusivamente da Acme</h2>';
    echo '<div class="sql-code">SELECT p.pnome FROM Pezzi p JOIN Catalogo c ON c.pid = p.pid JOIN Fornitori f ON f.fid = c.fid WHERE f.fnome = \'Acme\' GROUP BY p.pid, p.pnome HAVING COUNT(*) = 1;</div>';
    
    $query4 = "SELECT p.pnome
FROM Pezzi p
JOIN Catalogo c ON c.pid = p.pid
JOIN Fornitori f ON f.fid = c.fid
WHERE f.fnome = 'Acme'
GROUP BY p.pid, p.pnome
HAVING COUNT(*) = 1";
    $stmt4 = $pdo->query($query4);
    $results4 = $stmt4->fetchAll();
    
    if ($results4) {
        echo '<table><tr><th>Nome Pezzo</th></tr>';
        foreach ($results4 as $row) {
            echo '<tr><td>' . htmlspecialchars($row['pnome']) . '</td></tr>';
        }
        echo '</table>';
    } else {
        echo '<p class="no-results">Nessun risultato trovato</p>';
    }
    echo '</div>';

    // ========== QUERY 5 ==========
    echo '<div class="query-section">';
    echo '<h2>Query 5: Fornitori che vendono almeno un pezzo sopra la media</h2>';
    echo '<div class="sql-code">SELECT DISTINCT c.fid FROM Catalogo c JOIN (SELECT pid, AVG(costo) AS costo_medio FROM Catalogo GROUP BY pid) AS media ON c.pid = media.pid WHERE c.costo > media.costo_medio;</div>';
    
    $query5 = "SELECT DISTINCT c.fid
FROM Catalogo c
JOIN (SELECT pid, AVG(costo) AS costo_medio FROM Catalogo GROUP BY pid) AS media
ON c.pid = media.pid
WHERE c.costo > media.costo_medio";
    $stmt5 = $pdo->query($query5);
    $results5 = $stmt5->fetchAll();
    
    if ($results5) {
        echo '<table><tr><th>ID Fornitore</th></tr>';
        foreach ($results5 as $row) {
            echo '<tr><td>' . htmlspecialchars($row['fid']) . '</td></tr>';
        }
        echo '</table>';
    } else {
        echo '<p class="no-results">Nessun risultato trovato</p>';
    }
    echo '</div>';

    // ========== QUERY 6 ==========
    echo '<div class="query-section">';
    echo '<h2>Query 6: Pezzi con il costo più basso per fornitore</h2>';
    echo '<div class="sql-code">SELECT p.pid, p.pnome, f.fnome, c.costo FROM Catalogo c JOIN Pezzi p ON p.pid = c.pid JOIN Fornitori f ON f.fid = c.fid WHERE NOT EXISTS (SELECT 1 FROM Catalogo c2 WHERE c2.pid = c.pid AND c2.costo > c.costo);</div>';
    
    $query6 = "SELECT p.pid, p.pnome, f.fnome, c.costo
FROM Catalogo c
JOIN Pezzi p ON p.pid = c.pid
JOIN Fornitori f ON f.fid = c.fid
WHERE NOT EXISTS (
    SELECT 1 FROM Catalogo c2 
    WHERE c2.pid = c.pid AND c2.costo > c.costo
)";
    $stmt6 = $pdo->query($query6);
    $results6 = $stmt6->fetchAll();
    
    if ($results6) {
        echo '<table><tr><th>ID Pezzo</th><th>Nome Pezzo</th><th>Fornitore</th><th>Costo</th></tr>';
        foreach ($results6 as $row) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['pid']) . '</td>';
            echo '<td>' . htmlspecialchars($row['pnome']) . '</td>';
            echo '<td>' . htmlspecialchars($row['fnome']) . '</td>';
            echo '<td>€ ' . number_format($row['costo'], 2) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<p class="no-results">Nessun risultato trovato</p>';
    }
    echo '</div>';

    // ========== QUERY 7 ==========
    echo '<div class="query-section">';
    echo '<h2>Query 7: Fornitori che vendono solo pezzi rossi</h2>';
    echo '<div class="sql-code">SELECT f.fid FROM Fornitori f WHERE EXISTS (SELECT 1 FROM Catalogo c WHERE c.fid = f.fid) AND NOT EXISTS (SELECT 1 FROM Catalogo c2 JOIN Pezzi p2 ON p2.pid = c2.pid WHERE c2.fid = f.fid AND p2.colore <> \'rosso\');</div>';
    
    $query7 = "SELECT f.fid
FROM Fornitori f
WHERE EXISTS (SELECT 1 FROM Catalogo c WHERE c.fid = f.fid)
AND NOT EXISTS (
    SELECT 1 FROM Catalogo c2 
    JOIN Pezzi p2 ON p2.pid = c2.pid 
    WHERE c2.fid = f.fid AND p2.colore <> 'rosso'
)";
    $stmt7 = $pdo->query($query7);
    $results7 = $stmt7->fetchAll();
    
    if ($results7) {
        echo '<table><tr><th>ID Fornitore</th></tr>';
        foreach ($results7 as $row) {
            echo '<tr><td>' . htmlspecialchars($row['fid']) . '</td></tr>';
        }
        echo '</table>';
    } else {
        echo '<p class="no-results">Nessun risultato trovato</p>';
    }
    echo '</div>';

    // ========== QUERY 8 ==========
    echo '<div class="query-section">';
    echo '<h2>Query 8: Fornitori che vendono almeno un pezzo rosso e un pezzo verde</h2>';
    echo '<div class="sql-code">SELECT f.fid FROM Fornitori f JOIN Catalogo c ON c.fid = f.fid JOIN Pezzi p ON p.pid = c.pid GROUP BY f.fid HAVING COUNT(DISTINCT CASE WHEN p.colore = \'rosso\' THEN p.pid END) > 0 AND COUNT(DISTINCT CASE WHEN p.colore = \'verde\' THEN p.pid END) > 0;</div>';
    
    $query8 = "SELECT f.fid
FROM Fornitori f
JOIN Catalogo c ON c.fid = f.fid
JOIN Pezzi p ON p.pid = c.pid
GROUP BY f.fid
HAVING COUNT(DISTINCT CASE WHEN p.colore = 'rosso' THEN p.pid END) > 0
AND COUNT(DISTINCT CASE WHEN p.colore = 'verde' THEN p.pid END) > 0";
    $stmt8 = $pdo->query($query8);
    $results8 = $stmt8->fetchAll();
    
    if ($results8) {
        echo '<table><tr><th>ID Fornitore</th></tr>';
        foreach ($results8 as $row) {
            echo '<tr><td>' . htmlspecialchars($row['fid']) . '</td></tr>';
        }
        echo '</table>';
    } else {
        echo '<p class="no-results">Nessun risultato trovato</p>';
    }
    echo '</div>';

    // ========== QUERY 9 ==========
    echo '<div class="query-section">';
    echo '<h2>Query 9: Fornitori che vendono pezzi rossi o verdi</h2>';
    echo '<div class="sql-code">SELECT DISTINCT c.fid FROM Catalogo c JOIN Pezzi p ON p.pid = c.pid WHERE p.colore IN (\'rosso\', \'verde\');</div>';
    
    $query9 = "SELECT DISTINCT c.fid
FROM Catalogo c
JOIN Pezzi p ON p.pid = c.pid
WHERE p.colore IN ('rosso', 'verde')";
    $stmt9 = $pdo->query($query9);
    $results9 = $stmt9->fetchAll();
    
    if ($results9) {
        echo '<table><tr><th>ID Fornitore</th></tr>';
        foreach ($results9 as $row) {
            echo '<tr><td>' . htmlspecialchars($row['fid']) . '</td></tr>';
        }
        echo '</table>';
    } else {
        echo '<p class="no-results">Nessun risultato trovato</p>';
    }
    echo '</div>';

    // ========== QUERY 10 ==========
    echo '<div class="query-section">';
    echo '<h2>Query 10: Pezzi venduti da almeno 2 fornitori</h2>';
    echo '<div class="sql-code">SELECT c.pid FROM Catalogo c GROUP BY c.pid HAVING COUNT(DISTINCT c.fid) >= 2;</div>';
    
    $query10 = "SELECT c.pid
FROM Catalogo c
GROUP BY c.pid
HAVING COUNT(DISTINCT c.fid) >= 2";
    $stmt10 = $pdo->query($query10);
    $results10 = $stmt10->fetchAll();
    
    if ($results10) {
        echo '<table><tr><th>ID Pezzo</th></tr>';
        foreach ($results10 as $row) {
            echo '<tr><td>' . htmlspecialchars($row['pid']) . '</td></tr>';
        }
        echo '</table>';
    } else {
        echo '<p class="no-results">Nessun risultato trovato</p>';
    }
    echo '</div>';
    ?>

</body>
</html>
