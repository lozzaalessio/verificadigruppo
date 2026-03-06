<?php

declare(strict_types=1);

namespace App;

use PDO;

/**
 * Gestione autenticazione utenti.
 */
class Auth
{
    /**
     * Verifica le credenziali e restituisce i dati utente se validi.
     */
    public static function authenticate(string $username, string $password): ?array
    {
        $pdo = Database::getConnection();
        
        $stmt = $pdo->prepare(
            "SELECT id, username, email, role, active 
             FROM Users 
             WHERE username = :username AND active = TRUE"
        );
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return null;
        }
        
        // Verifica password con bcrypt
        if (!password_verify($password, self::getPasswordHash($user['id']))) {
            return null;
        }
        
        return $user;
    }
    
    /**
     * Ottiene l'hash della password per un utente.
     */
    private static function getPasswordHash(int $userId): string
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT password FROM Users WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        return $stmt->fetchColumn() ?: '';
    }
    
    /**
     * Crea una nuova sessione per l'utente.
     */
    public static function login(array $user): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email'] = $user['email'];
        
        // Rigenerazione ID sessione per sicurezza
        session_regenerate_id(true);
    }
    
    /**
     * Distrugge la sessione corrente.
     */
    public static function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION = [];
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        session_destroy();
    }
    
    /**
     * Verifica se l'utente è autenticato.
     */
    public static function check(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Restituisce i dati dell'utente corrente.
     */
    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role'],
            'email' => $_SESSION['email'],
        ];
    }
    
    /**
     * Verifica se l'utente ha il ruolo specificato.
     */
    public static function hasRole(string $role): bool
    {
        $user = self::user();
        return $user && $user['role'] === $role;
    }
    
    /**
     * Verifica se l'utente è un amministratore.
     */
    public static function isAdmin(): bool
    {
        return self::hasRole('admin');
    }
    
    /**
     * Verifica se l'utente è un fornitore.
     */
    public static function isFornitore(): bool
    {
        return self::hasRole('fornitore');
    }
    
    /**
     * Ottiene il fornitore associato all'utente corrente (se è un fornitore).
     */
    public static function getFornitoreId(): ?string
    {
        if (!self::isFornitore()) {
            return null;
        }
        
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT fid FROM Fornitori WHERE user_id = :user_id"
        );
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        $result = $stmt->fetchColumn();
        
        return $result !== false ? (string)$result : null;
    }
    
    /**
     * Registra un nuovo utente.
     */
    public static function register(string $username, string $password, string $email, string $role = 'fornitore'): ?int
    {
        $pdo = Database::getConnection();
        
        // Verifica se username o email esistono già
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Users WHERE username = :username OR email = :email");
        $stmt->execute(['username' => $username, 'email' => $email]);
        if ($stmt->fetchColumn() > 0) {
            return null;
        }
        
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        
        $stmt = $pdo->prepare(
            "INSERT INTO Users (username, password, email, role) 
             VALUES (:username, :password, :email, :role)"
        );
        
        $stmt->execute([
            'username' => $username,
            'password' => $passwordHash,
            'email' => $email,
            'role' => $role
        ]);
        
        return (int)$pdo->lastInsertId();
    }
}
