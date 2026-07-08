<?php

namespace Controllers;

use Models\User;
use Models\ActivityLog;
use Models\Penalty;
use Models\Hall;
use Models\Reservation;
use Helpers\ResponseHelper;
use Middleware\AuthMiddleware;

class AdminController {
    public function indexUsers() {
        AuthMiddleware::checkAdmin();
        $users = User::getAll();
        $formattedUsers = array_map(function($u) {
            return $u->toArray();
        }, $users);
        ResponseHelper::json(true, "Users retrieved successfully.", $formattedUsers);
    }

    public function storeUser() {
        AuthMiddleware::checkAdmin();
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || empty($data['name']) || empty($data['email']) || empty($data['password']) || empty($data['role'])) {
            ResponseHelper::json(false, "Invalid user inputs.", null, 400);
        }

        try {
            $newUser = User::create([
                'name' => htmlspecialchars($data['name']),
                'email' => htmlspecialchars($data['email']),
                'password' => $data['password'],
                'role' => htmlspecialchars($data['role'])
            ]);
            ActivityLog::log(AuthMiddleware::checkAuth()->getId(), "Created user account: " . $data['email']);
            ResponseHelper::json(true, "User created successfully.", $newUser->toArray(), 201);
        } catch (\Exception $e) {
            ResponseHelper::json(false, $e->getMessage(), null, 400);
        }
    }

    public function updateUser(int $id) {
        AuthMiddleware::checkAdmin();
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || $id <= 0) {
            ResponseHelper::json(false, "Invalid update payload.", null, 400);
        }

        $db = \Config\Database::getConnection();
        // Dynamic update user record
        $name = htmlspecialchars($data['name'] ?? '');
        $role = htmlspecialchars($data['role'] ?? '');
        $status = htmlspecialchars($data['status'] ?? 'Active');

        // Resolve role ID from role name
        $roleStmt = $db->prepare("SELECT id FROM roles WHERE name = :role");
        $roleStmt->execute(['role' => $role]);
        $roleRow = $roleStmt->fetch();
        if (!$roleRow) {
            // Map common aliases
            if ($role === 'student' || $role === 'Student Representative') {
                $role = 'Student Representative';
            } elseif ($role === 'lecturer') {
                $role = 'Lecturer';
            } elseif ($role === 'admin') {
                $role = 'Admin';
            }
            $roleStmt->execute(['role' => $role]);
            $roleRow = $roleStmt->fetch();
        }
        $roleId = $roleRow ? (int)$roleRow['id'] : 1;

        $stmt = $db->prepare("
            UPDATE users 
            SET name = :name, role_id = :role_id, status = :status 
            WHERE id = :id
        ");
        $stmt->execute([
            'id' => $id,
            'name' => $name,
            'role_id' => $roleId,
            'status' => $status
        ]);

        ActivityLog::log(AuthMiddleware::checkAuth()->getId(), "Updated user ID {$id}: Name: {$name}, Role: {$role}, Status: {$status}");
        ResponseHelper::json(true, "User updated successfully.", null);
    }

    public function destroyUser(int $id) {
        AuthMiddleware::checkAdmin();
        if ($id <= 0) {
            ResponseHelper::json(false, "Invalid user ID.", null, 400);
        }

        $db = \Config\Database::getConnection();
        $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute(['id' => $id]);

        ActivityLog::log(AuthMiddleware::checkAuth()->getId(), "Deleted user ID {$id}");
        ResponseHelper::json(true, "User deleted successfully.", null);
    }

    public function indexLogs() {
        AuthMiddleware::checkAdmin();
        $logs = ActivityLog::getAll();
        ResponseHelper::json(true, "Logs retrieved.", $logs);
    }

    public function indexPenalties() {
        AuthMiddleware::checkAdmin();
        $penalties = Penalty::getAll();
        ResponseHelper::json(true, "Penalties retrieved.", $penalties);
    }

    public function storePenalty() {
        AuthMiddleware::checkAdmin();
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || empty($data['userId']) || empty($data['type']) || !isset($data['pointsAdded'])) {
            ResponseHelper::json(false, "Invalid penalty details.", null, 400);
        }

        $success = Penalty::create([
            'user_id' => (int)$data['userId'],
            'reservation_id' => isset($data['reservationId']) ? (int)$data['reservationId'] : null,
            'type' => htmlspecialchars($data['type']),
            'points_added' => (int)$data['pointsAdded'],
            'action_taken' => htmlspecialchars($data['actionTaken'] ?? 'Penalty Points'),
            'details' => htmlspecialchars($data['details'] ?? '')
        ]);

        if ($success) {
            ActivityLog::log(AuthMiddleware::checkAuth()->getId(), "Assigned penalty points to user ID: " . $data['userId']);
            ResponseHelper::json(true, "Penalty applied successfully.", null);
        } else {
            ResponseHelper::json(false, "Failed to apply penalty.", null, 500);
        }
    }

    public function utilizationReport() {
        AuthMiddleware::checkAdmin();
        $db = \Config\Database::getConnection();

        // Calculate utilization analytics
        // 1. Most Booked Halls
        $mostBooked = $db->query("
            SELECT h.name, COUNT(r.id) as booking_count 
            FROM halls h
            LEFT JOIN reservations r ON h.id = r.hall_id AND r.status = 'Approved'
            GROUP BY h.id ORDER BY booking_count DESC
        ")->fetchAll();

        // 2. Event Types Popularity
        $events = $db->query("
            SELECT event_type, COUNT(*) as count 
            FROM reservations 
            GROUP BY event_type ORDER BY count DESC
        ")->fetchAll();

        // 3. Peak Hours
        $peakHours = $db->query("
            SELECT HOUR(time) as hour, COUNT(*) as count 
            FROM reservations 
            GROUP BY hour ORDER BY count DESC LIMIT 5
        ")->fetchAll();

        ResponseHelper::json(true, "Analytics retrieved.", [
            'mostBooked' => $mostBooked,
            'events' => $events,
            'peakHours' => $peakHours
        ]);
    }

    public function exportReport() {
        AuthMiddleware::checkAdmin();
        
        $db = \Config\Database::getConnection();
        $stmt = $db->query("
            SELECT r.id, u.name as user_name, h.name as hall_name, r.event_type, r.participant_count, r.date, r.time, r.status
            FROM reservations r
            JOIN users u ON r.user_id = u.id
            JOIN halls h ON r.hall_id = h.id
            ORDER BY r.id DESC
        ");
        $rows = $stmt->fetchAll();

        // Generate CSV output stream
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="reservations_report_' . date('Ymd') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'User Name', 'Hall Name', 'Event Type', 'Participants', 'Date', 'Time', 'Status']);
        
        foreach ($rows as $row) {
            fputcsv($output, [
                $row['id'],
                $row['user_name'],
                $row['hall_name'],
                $row['event_type'],
                $row['participant_count'],
                $row['date'],
                $row['time'],
                $row['status']
            ]);
        }
        fclose($output);
        exit;
    }
}
