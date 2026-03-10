<?php
require_once 'includes/functions.php';

header('Content-Type: application/json');

// Get action
$action = $_GET['action'] ?? 'dashboard';

if ($action === 'dashboard') {
    // Check login
    if (!isLoggedIn()) {
        // For development/testing without login, return dummy data that matches the DB structure
        // Remove this in production and un-comment requireLogin() or return 401
        $user = [
            'id' => 1,
            'full_name' => 'Player One',
            'current_rank' => 'E',
            'current_streak' => 5,
            'best_streak' => 12,
            'total_workouts' => 24,
            'shadow_coins' => 150,
            'total_xp' => 1250
        ];
        $armyCount = 2;
        $progress = ['next_rank' => 'D', 'current' => 5, 'needed' => 20, 'percentage' => 25];
        $recentWorkouts = [
            ['workout_date' => date('Y-m-d', strtotime('-1 days')), 'completion_percentage' => 100, 'xp_earned' => 150, 'shadow_coins_earned' => 15],
            ['workout_date' => date('Y-m-d', strtotime('-2 days')), 'completion_percentage' => 100, 'xp_earned' => 150, 'shadow_coins_earned' => 15]
        ];
        $todayWorkout = null;
        
        echo json_encode([
            'status' => 'success',
            'data' => [
                'user' => $user,
                'progress' => $progress,
                'armyCount' => $armyCount,
                'recentWorkouts' => $recentWorkouts,
                'todayWorkout' => $todayWorkout
            ],
            'message' => 'Logged in as mock user. Use actual login in production.'
        ]);
        exit;
    }

    $user = getCurrentUser();
    $progress = calculateProgressToNextRank($user['current_rank'], $user['current_streak']);

    // Get today's workout status
    $db = new Database();
    $conn = $db->getConnection();

    $today = date('Y-m-d');
    $workoutQuery = "SELECT * FROM workout_logs WHERE user_id = :user_id AND workout_date = :date";
    $workoutStmt = $conn->prepare($workoutQuery);
    $workoutStmt->execute([':user_id' => $user['id'], ':date' => $today]);
    $todayWorkout = $workoutStmt->fetch(PDO::FETCH_ASSOC);

    // Get recent workouts
    $recentQuery = "SELECT * FROM workout_logs WHERE user_id = :user_id ORDER BY workout_date DESC LIMIT 7";
    $recentStmt = $conn->prepare($recentQuery);
    $recentStmt->execute([':user_id' => $user['id']]);
    $recentWorkouts = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get shadow army count
    $armyQuery = "SELECT COUNT(*) as count FROM shadow_army WHERE user_id = :user_id";
    $armyStmt = $conn->prepare($armyQuery);
    $armyStmt->execute([':user_id' => $user['id']]);
    $armyCount = $armyStmt->fetch(PDO::FETCH_ASSOC)['count'];

    echo json_encode([
        'status' => 'success',
        'data' => [
            'user' => $user,
            'progress' => $progress,
            'armyCount' => $armyCount,
            'recentWorkouts' => $recentWorkouts,
            'todayWorkout' => $todayWorkout
        ]
    ]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Unknown action']);
