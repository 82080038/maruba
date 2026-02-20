<?php
namespace App\Controllers;

class ApiController
{
    public function members(): void
    {
        // API tidak butuh session
        session_write_close();
        header('Content-Type: application/json');
        $pdo = \App\Database::getConnection();
        $stmt = $pdo->query('SELECT id, name, lat, lng, status FROM members WHERE lat IS NOT NULL AND lng IS NOT NULL ORDER BY name');
        echo json_encode($stmt->fetchAll());
    }

    public function surveys(): void
    {
        session_write_close();
        header('Content-Type: application/json');
        $pdo = \App\Database::getConnection();
        $stmt = $pdo->prepare('
            SELECT s.id, s.loan_id, s.geo_lat AS lat, s.geo_lng AS lng, s.score, s.result, m.name AS member_name
            FROM surveys s
            JOIN loans l ON s.loan_id = l.id
            JOIN members m ON l.member_id = m.id
            WHERE s.geo_lat IS NOT NULL AND s.geo_lng IS NOT NULL
            ORDER BY s.created_at DESC
        ');
        $stmt->execute();
        echo json_encode($stmt->fetchAll());
    }

    public function updateMemberGeo(): void
    {
        require_login();
        header('Content-Type: application/json');
        $id = (int)($_POST['id'] ?? 0);
        $lat = (float)($_POST['lat'] ?? 0);
        $lng = (float)($_POST['lng'] ?? 0);
        if (!$id || !($lat >= -90 && $lat <= 90) || !($lng >= -180 && $lng <= 180)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid input']);
            return;
        }
        $pdo = \App\Database::getConnection();
        $stmt = $pdo->prepare('UPDATE members SET lat = ?, lng = ? WHERE id = ?');
        $stmt->execute([$lat, $lng, $id]);
        echo json_encode(['success' => true]);
    }

    public function updateSurveyGeo(): void
    {
        require_login();
        header('Content-Type: application/json');
        $id = (int)($_POST['id'] ?? 0);
        $lat = (float)($_POST['lat'] ?? 0);
        $lng = (float)($_POST['lng'] ?? 0);
        if (!$id || !($lat >= -90 && $lat <= 90) || !($lng >= -180 && $lng <= 180)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid input']);
            return;
        }
        $pdo = \App\Database::getConnection();
        $stmt = $pdo->prepare('UPDATE surveys SET geo_lat = ?, geo_lng = ? WHERE id = ?');
        $stmt->execute([$lat, $lng, $id]);
        echo json_encode(['success' => true]);
    }
}
