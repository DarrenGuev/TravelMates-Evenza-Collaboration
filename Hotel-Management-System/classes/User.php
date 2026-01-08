<?php
require_once __DIR__ . '/Model.php';

//User Model - Handles all user-related database operations

class User extends Model
{
    protected string $table = 'users';
    protected string $primaryKey = 'userID';

    public function findByUsername(string $username): ?array
    {
        return $this->findOneBy('username', $username);
    }

    public function findByEmail(string $email): ?array
    {
        return $this->findOneBy('email', $email);
    }

    public function authenticate(string $username, string $password): ?array
    {
        $hashedPassword = md5($password);
        
        $query = "SELECT * FROM `{$this->table}` WHERE `username` = ? AND `password` = ?";
        $result = $this->executeStatement($query, 'ss', [$username, $hashedPassword]);
        
        if ($result && $result->num_rows === 1) {
            return $this->db->fetchOne($result);
        }
        
        return null;
    }

    public function register(array $data): array
    {
        // Check if email already exists
        if ($this->exists('email', $data['email'])) {
            return ['success' => false, 'message' => 'Email already registered', 'userID' => null];
        }

        // Check if username already exists
        if ($this->exists('username', $data['username'])) {
            return ['success' => false, 'message' => 'Username already taken', 'userID' => null];
        }

        // Hash password
        $data['password'] = md5($data['password']);
        
        // Set default role
        if (!isset($data['role'])) {
            $data['role'] = 'user';
        }

        $userID = $this->insert($data);
        
        if ($userID) {
            return ['success' => true, 'message' => 'Registration successful', 'userID' => $userID];
        }
        
        return ['success' => false, 'message' => 'Registration failed', 'userID' => null];
    }

    public function getByRole(string $role, string $orderBy = 'created_at', string $direction = 'DESC'): array
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $query = "SELECT * FROM `{$this->table}` WHERE `role` = ? ORDER BY `{$orderBy}` {$direction}";
        $result = $this->executeStatement($query, 's', [$role]);
        
        return $result ? $this->db->fetchAll($result) : [];
    }

    public function getAllCustomers(): array
    {
        return $this->getByRole('user');
    }

    public function getAllAdmins(): array
    {
        return $this->getByRole('admin');
    }

    public function updateRole(int $userID, string $role): bool
    {
        $validRoles = ['user', 'admin'];
        if (!in_array($role, $validRoles)) {
            return false;
        }
        
        return $this->update($userID, ['role' => $role]);
    }

    public function updatePassword(int $userID, string $newPassword): bool
    {
        $hashedPassword = md5($newPassword);
        return $this->update($userID, ['password' => $hashedPassword]);
    }

    public function getUserWithBookingCount(int $userID): ?array
    {
        $query = "SELECT u.*, COUNT(b.bookingID) as bookingCount 
                  FROM `{$this->table}` u 
                  LEFT JOIN bookings b ON u.userID = b.userID 
                  WHERE u.userID = ? 
                  GROUP BY u.userID";
        $result = $this->executeStatement($query, 'i', [$userID]);
        
        if ($result && $result->num_rows > 0) {
            return $this->db->fetchOne($result);
        }
        
        return null;
    }

    public function getFullName(int $userID): string
    {
        $user = $this->find($userID);
        if ($user) {
            return trim($user['firstName'] . ' ' . $user['lastName']);
        }
        return '';
    }

    public function emailExists(string $email, ?int $excludeUserID = null): bool
    {
        if ($excludeUserID === null) {
            return $this->exists('email', $email);
        }
        
        $query = "SELECT COUNT(*) as count FROM `{$this->table}` WHERE `email` = ? AND `userID` != ?";
        $result = $this->executeStatement($query, 'si', [$email, $excludeUserID]);
        
        if ($result) {
            $row = $this->db->fetchOne($result);
            return (int)$row['count'] > 0;
        }
        
        return false;
    }

    public function usernameExists(string $username, ?int $excludeUserID = null): bool
    {
        if ($excludeUserID === null) {
            return $this->exists('username', $username);
        }
        
        $query = "SELECT COUNT(*) as count FROM `{$this->table}` WHERE `username` = ? AND `userID` != ?";
        $result = $this->executeStatement($query, 'si', [$username, $excludeUserID]);
        
        if ($result) {
            $row = $this->db->fetchOne($result);
            return (int)$row['count'] > 0;
        }
        
        return false;
    }
}
