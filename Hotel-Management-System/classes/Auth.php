<?php
//Auth Class - Handles authentication and session management
class Auth
{
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function isLoggedIn(): bool
    {
        self::startSession();
        return isset($_SESSION['userID']);
    }

    public static function isAdmin(): bool
    {
        self::startSession();
        return isset($_SESSION['userID']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }

    public static function getUserId(): ?int
    {
        self::startSession();
        return isset($_SESSION['userID']) ? (int)$_SESSION['userID'] : null;
    }

    public static function getRole(): ?string
    {
        self::startSession();
        return $_SESSION['role'] ?? null;
    }

    public static function getUsername(): string
    {
        self::startSession();
        return $_SESSION['username'] ?? '';
    }

    public static function getFirstName(): string
    {
        self::startSession();
        return $_SESSION['firstName'] ?? '';
    }

    public static function getDisplayName(): string
    {
        self::startSession();
        return $_SESSION['firstName'] ?? $_SESSION['username'] ?? 'Guest';
    }

    public static function getCurrentUserName(): string
    {
        self::startSession();
        return $_SESSION['firstName'] ?? $_SESSION['username'] ?? 'Admin';
    }

    public static function get(string $key, $default = null)
    {
        self::startSession();
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, $value): void
    {
        self::startSession();
        $_SESSION[$key] = $value;
    }

    public static function login(array $user): void
    {
        self::startSession();
        $_SESSION['userID'] = $user['userID'];
        $_SESSION['firstName'] = $user['firstName'];
        $_SESSION['lastName'] = $user['lastName'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
    }

    public static function logout(): void
    {
        self::startSession();
        session_unset();
        session_destroy();
    }

    public static function requireLogin(string $redirectUrl = '../frontend/login.php', string $errorMessage = 'Please login to continue'): void
    {
        if (!self::isLoggedIn()) {
            header("Location: {$redirectUrl}?error=" . urlencode($errorMessage));
            exit();
        }
    }

    public static function requireAdmin(string $redirectUrl = '../frontend/login.php'): void
    {
        if (!self::isAdmin()) {
            header("Location: {$redirectUrl}?error=Access denied");
            exit();
        }
    }

    public static function setAlert(string $type, string $message): void
    {
        self::startSession();
        $_SESSION['alert'] = ['type' => $type, 'message' => $message];
    }

    public static function getAlert(): ?array
    {
        self::startSession();
        if (isset($_SESSION['alert'])) {
            $alert = $_SESSION['alert'];
            unset($_SESSION['alert']);
            return $alert;
        }
        return null;
    }

    public static function hasAlert(): bool
    {
        self::startSession();
        return isset($_SESSION['alert']);
    }
}
