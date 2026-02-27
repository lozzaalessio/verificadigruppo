<?php
/**
 * Connessione al database MySQL usando PDO
 */

// Parametri di connessione
$host = 'localhost';
$dbname = 'FornitoriPezziDB';
$username = 'utente1';
$password = 'password123';
$port = '3306';
$charset = 'utf8mb4';

// DSN (Data Source Name)
$dsn = "mysql:host=$host;dbname=$dbname;port=$port;charset=$charset";

// Opzioni PDO per sicurezza e gestione errori
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // Lancia eccezioni per gli errori
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // Fetch associativo di default
    PDO::ATTR_EMULATE_PREPARES   => false,                    // Usa prepared statements nativi
];

try {
    // Crea la connessione PDO
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Messaggio di successo (opzionale, puoi commentare in produzione)
    // echo "Connessione al database riuscita!";
    
} catch (PDOException $e) {
    // Gestione errori di connessione
    die("Errore di connessione al database: " . $e->getMessage());
}

// Ora puoi usare $pdo per le query
// Esempio: $stmt = $pdo->query("SELECT * FROM Fornitori");
?>
