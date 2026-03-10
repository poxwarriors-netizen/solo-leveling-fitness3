<?php
// =====================================================
// CORE FUNCTIONS - ALL APP LOGIC
// =====================================================

require_once __DIR__ . '/../config/database.php';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =====================================================
// USER AUTHENTICATION FUNCTIONS
// =====================================================

/**
 * Register a new user
 */
function registerUser($username, $email, $password, $full_name) {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Check if username or email exists
    $checkQuery = "SELECT id FROM users WHERE username = :username OR email = :email";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->execute([':username' => $username, ':email' => $email]);
    
    if ($checkStmt->rowCount() > 0) {
        return ['success' => false, 'message' => 'Username or email already exists'];
    }
    
    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    $query = "INSERT INTO users (username, email, password_hash, full_name, join_date, last_login) 
              VALUES (:username, :email, :password, :full_name, NOW(), NOW())";
    
    $stmt = $conn->prepare($query);
    $success = $stmt->execute([
        ':username' => $username,
        ':email' => $email,
        ':password' => $password_hash,
        ':full_name' => $full_name
    ]);
    
    if ($success) {
        $user_id = $conn->lastInsertId();
        
        // Log user in
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['logged_in'] = true;
        
        return ['success' => true, 'user_id' => $user_id];
    }
    
    return ['success' => false, 'message' => 'Registration failed'];
}

/**
 * Login user
 */
function loginUser($username, $password) {
    $db = new Database();
    $conn = $db->getConnection();
    
    $query = "SELECT * FROM users WHERE username = :username AND is_active = TRUE";
    $stmt = $conn->prepare($query);
    $stmt->execute([':username' => $username]);
    
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password_hash'])) {
        // Update last login
        $updateQuery = "UPDATE users SET last_login = NOW() WHERE id = :id";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->execute([':id' => $user['id']]);
        
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_rank'] = $user['current_rank'];
        $_SESSION['is_admin'] = $user['is_admin'];
        $_SESSION['logged_in'] = true;
        
        return ['success' => true, 'user' => $user];
    }
    
    return ['success' => false, 'message' => 'Invalid username or password'];
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Get current user data
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $db = new Database();
    $conn = $db->getConnection();
    
    $query = "SELECT * FROM users WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $_SESSION['user_id']]);
    
    return $stmt->fetch();
}

/**
 * Require login - redirect if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /solo-leveling-fitness/pages/login.php');
        exit();
    }
}

/**
 * Require admin - redirect if not admin
 */
function requireAdmin() {
    requireLogin();
    
    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
        header('Location: /solo-leveling-fitness/pages/dashboard.php');
        exit();
    }
}

// =====================================================
// RANK SYSTEM FUNCTIONS
// =====================================================

/**
 * Get rank requirements
 */
function getRankRequirements() {
    return [
        'E' => ['days' => 0, 'next_rank' => 'D', 'xp_needed' => 1000],
        'D' => ['days' => 20, 'next_rank' => 'C', 'xp_needed' => 5000],
        'C' => ['days' => 30, 'next_rank' => 'B', 'xp_needed' => 15000],
        'B' => ['days' => 40, 'next_rank' => 'A', 'xp_needed' => 30000],
        'A' => ['days' => 50, 'next_rank' => 'S', 'xp_needed' => 50000],
        'S' => ['days' => 60, 'next_rank' => null, 'xp_needed' => 100000]
    ];
}

/**
 * Calculate rank based on streak
 */
function calculateRankFromStreak($streak) {
    if ($streak >= 60) return 'S';
    if ($streak >= 50) return 'A';
    if ($streak >= 40) return 'B';
    if ($streak >= 30) return 'C';
    if ($streak >= 20) return 'D';
    return 'E';
}

/**
 * Update user rank
 */
function updateUserRank($user_id) {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get user's current streak
    $userQuery = "SELECT current_streak, current_rank FROM users WHERE id = :id";
    $userStmt = $conn->prepare($userQuery);
    $userStmt->execute([':id' => $user_id]);
    $user = $userStmt->fetch();
    
    $new_rank = calculateRankFromStreak($user['current_streak']);
    
    if ($new_rank != $user['current_rank']) {
        // Rank up celebration!
        $updateQuery = "UPDATE users SET current_rank = :rank WHERE id = :id";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->execute([':rank' => $new_rank, ':id' => $user_id]);
        
        // Log rank up
        logRankUp($user_id, $user['current_rank'], $new_rank);
        
        return ['rank_up' => true, 'old_rank' => $user['current_rank'], 'new_rank' => $new_rank];
    }
    
    return ['rank_up' => false];
}

/**
 * Log rank up for celebrations
 */
function logRankUp($user_id, $old_rank, $new_rank) {
    // You can store this in a notifications table later
    $_SESSION['rank_up'] = [
        'old' => $old_rank,
        'new' => $new_rank,
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

// =====================================================
// WORKOUT FUNCTIONS
// =====================================================

/**
 * Log a workout
 */
function logWorkout($user_id, $exercises) {
    $db = new Database();
    $conn = $db->getConnection();
    
    $today = date('Y-m-d');
    
    // Check if already worked out today
    $checkQuery = "SELECT id FROM workout_logs WHERE user_id = :user_id AND workout_date = :date";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->execute([':user_id' => $user_id, ':date' => $today]);
    
    if ($checkStmt->rowCount() > 0) {
        return ['success' => false, 'message' => 'You already completed your quest today!'];
    }
    
    // Calculate totals
    $total_exercises = array_sum($exercises);
    $max_total = 240; // 60 * 4 exercises
    $completion_percentage = min(($total_exercises / $max_total) * 100, 100);
    
    // Calculate XP (base 100 + bonus for completion)
    $xp_earned = 100;
    if ($completion_percentage >= 100) {
        $xp_earned += 50; // Perfect completion bonus
    }
    
    // Calculate Shadow Coins
    $shadow_coins = 10;
    if ($completion_percentage >= 100) {
        $shadow_coins += 5; // Perfect completion bonus
    }
    
    // Insert workout log
    $query = "INSERT INTO workout_logs 
              (user_id, workout_date, pushups, situps, squats, running_km, 
               total_exercises, completion_percentage, xp_earned, shadow_coins_earned)
              VALUES 
              (:user_id, :date, :pushups, :situps, :squats, :running,
               :total, :percentage, :xp, :coins)";
    
    $stmt = $conn->prepare($query);
    $success = $stmt->execute([
        ':user_id' => $user_id,
        ':date' => $today,
        ':pushups' => $exercises['pushups'] ?? 0,
        ':situps' => $exercises['situps'] ?? 0,
        ':squats' => $exercises['squats'] ?? 0,
        ':running' => $exercises['running'] ?? 0,
        ':total' => $total_exercises,
        ':percentage' => $completion_percentage,
        ':xp' => $xp_earned,
        ':coins' => $shadow_coins
    ]);
    
    if ($success) {
        // Update user stats
        $updateQuery = "UPDATE users SET 
                        total_xp = total_xp + :xp,
                        shadow_coins = shadow_coins + :coins,
                        current_streak = current_streak + 1,
                        best_streak = GREATEST(best_streak, current_streak + 1),
                        total_workouts = total_workouts + 1,
                        last_workout_date = :date
                        WHERE id = :id";
        
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->execute([
            ':xp' => $xp_earned,
            ':coins' => $shadow_coins,
            ':date' => $today,
            ':id' => $user_id
        ]);
        
        // Update rank
        $rank_result = updateUserRank($user_id);
        
        // Check for achievements
        checkAchievements($user_id);
        
        return [
            'success' => true,
            'xp_earned' => $xp_earned,
            'coins_earned' => $shadow_coins,
            'completion' => $completion_percentage,
            'rank_up' => $rank_result['rank_up'] ?? false
        ];
    }
    
    return ['success' => false, 'message' => 'Failed to log workout'];
}

/**
 * Check if user can take a rest day
 */
function canTakeRestDay($user_id) {
    $db = new Database();
    $conn = $db->getConnection();
    
    $query = "SELECT rest_days_used_this_week, current_streak FROM users WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch();
    
    // Allow 1 rest day per week, minimum 6 workouts before rest
    return ($user['rest_days_used_this_week'] < 1 && $user['current_streak'] >= 6);
}

/**
 * Take a rest day
 */
function takeRestDay($user_id) {
    $db = new Database();
    $conn = $db->getConnection();
    
    if (!canTakeRestDay($user_id)) {
        return ['success' => false, 'message' => 'Cannot take rest day today'];
    }
    
    $query = "UPDATE users SET 
              rest_days_used_this_week = rest_days_used_this_week + 1,
              last_workout_date = CURDATE()
              WHERE id = :id";
    
    $stmt = $conn->prepare($query);
    $success = $stmt->execute([':id' => $user_id]);
    
    return ['success' => $success, 'message' => 'Rest day logged. No penalty applied.'];
}

// =====================================================
// PENALTY SYSTEM FUNCTIONS
// =====================================================

/**
 * Apply penalty for missed day
 */
function applyMissedDayPenalty($user_id) {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get user data
    $query = "SELECT * FROM users WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch();
    
    // Skip if subscription active
    if ($user['subscription_type'] != 'none') {
        return ['success' => true, 'message' => 'Subscription active - no penalty'];
    }
    
    // Apply 3% penalty
    $new_progress = max($user['rank_progress'] - 3, 0);
    
    $updateQuery = "UPDATE users SET 
                    rank_progress = :progress,
                    missed_days = missed_days + 1
                    WHERE id = :id";
    
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->execute([
        ':progress' => $new_progress,
        ':id' => $user_id
    ]);
    
    return [
        'success' => true,
        'penalty' => 3,
        'new_progress' => $new_progress
    ];
}

/**
 * Recover progress via ads
 */
function recoverWithAds($user_id) {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Log recovery
    $query = "INSERT INTO penalty_recovery 
              (user_id, recovery_date, recovery_type, ads_watched, progress_recovered, revenue_generated)
              VALUES (:user_id, CURDATE(), 'ads', 5, 3, 20.00)";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':user_id' => $user_id]);
    
    // Update user progress
    $updateQuery = "UPDATE users SET rank_progress = rank_progress + 3 WHERE id = :id";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->execute([':id' => $user_id]);
    
    return [
        'success' => true,
        'recovered' => 3,
        'message' => 'Recovered 3% progress by watching ads'
    ];
}

/**
 * Recover progress with Shadow Coins
 */
function recoverWithCoins($user_id) {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Check if user has enough coins
    $userQuery = "SELECT shadow_coins FROM users WHERE id = :id";
    $userStmt = $conn->prepare($userQuery);
    $userStmt->execute([':id' => $user_id]);
    $user = $userStmt->fetch();
    
    if ($user['shadow_coins'] < 100) {
        return ['success' => false, 'message' => 'Not enough Shadow Coins. Need 100.'];
    }
    
    // Log recovery
    $query = "INSERT INTO penalty_recovery 
              (user_id, recovery_date, recovery_type, coins_spent, progress_recovered)
              VALUES (:user_id, CURDATE(), 'coins', 100, 1)";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':user_id' => $user_id]);
    
    // Update user
    $updateQuery = "UPDATE users SET 
                    shadow_coins = shadow_coins - 100,
                    rank_progress = rank_progress + 1
                    WHERE id = :id";
    
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->execute([':id' => $user_id]);
    
    return [
        'success' => true,
        'recovered' => 1,
        'coins_left' => $user['shadow_coins'] - 100,
        'message' => 'Recovered 1% progress using 100 Shadow Coins'
    ];
}

// =====================================================
// AFFILIATE & PARTNER FUNCTIONS
// =====================================================

/**
 * Get eligible partners based on user rank
 */
function getEligiblePartners($user_rank) {
    $db = new Database();
    $conn = $db->getConnection();
    
    $rank_order = ['E' => 0, 'D' => 1, 'C' => 2, 'B' => 3, 'A' => 4, 'S' => 5];
    $user_rank_value = $rank_order[$user_rank];
    
    $query = "SELECT * FROM partners WHERE is_active = TRUE ORDER BY sort_order ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $all_partners = $stmt->fetchAll();
    
    $fitness_partners = [];
    $universal_partners = [];
    
    foreach ($all_partners as $partner) {
        $partner_rank_value = $rank_order[$partner['min_rank_required']];
        
        if ($partner_rank_value <= $user_rank_value) {
            if ($partner['category'] == 'fitness') {
                $fitness_partners[] = $partner;
            } else {
                $universal_partners[] = $partner;
            }
        }
    }
    
    return [
        'fitness' => $fitness_partners,
        'universal' => $universal_partners
    ];
}

/**
 * Track affiliate click
 */
function trackAffiliateClick($user_id, $partner_id, $user_rank) {
    $db = new Database();
    $conn = $db->getConnection();
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    
    $query = "INSERT INTO affiliate_clicks 
              (user_id, partner_id, user_rank_at_click, ip_address)
              VALUES (:user_id, :partner_id, :rank, :ip)";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':user_id' => $user_id,
        ':partner_id' => $partner_id,
        ':rank' => $user_rank,
        ':ip' => $ip
    ]);
}

// =====================================================
// SHADOW ARMY FUNCTIONS
// =====================================================

/**
 * Recruit a shadow soldier
 */
function recruitShadowSoldier($user_id) {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Check if user qualifies (every 10 workouts)
    $userQuery = "SELECT total_workouts FROM users WHERE id = :id";
    $userStmt = $conn->prepare($userQuery);
    $userStmt->execute([':id' => $user_id]);
    $user = $userStmt->fetch();
    
    $soldier_count = $user['total_workouts'] / 10;
    
    // Check current army size
    $armyQuery = "SELECT COUNT(*) as count FROM shadow_army WHERE user_id = :id";
    $armyStmt = $conn->prepare($armyQuery);
    $armyStmt->execute([':id' => $user_id]);
    $army = $armyStmt->fetch();
    
    if ($army['count'] >= $soldier_count) {
        return ['success' => false, 'message' => 'No new soldiers available'];
    }
    
    // Random soldier names from Solo Leveling
    $soldier_names = ['Igris', 'Beru', 'Iron', 'Tank', 'Jima', 'Kaisel', 'Bellion'];
    $name = $soldier_names[array_rand($soldier_names)];
    
    // Random rank based on user's rank
    $ranks = ['Shadow', 'Knight', 'Elite', 'General'];
    $rank_index = min(floor($user['total_workouts'] / 25), 3);
    $soldier_rank = $ranks[$rank_index];
    
    $query = "INSERT INTO shadow_army 
              (user_id, soldier_name, soldier_rank, power_level, recruited_date)
              VALUES (:user_id, :name, :rank, :power, CURDATE())";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':user_id' => $user_id,
        ':name' => $name,
        ':rank' => $soldier_rank,
        ':power' => 10 + ($rank_index * 10)
    ]);
    
    return ['success' => true, 'soldier' => $name];
}

/**
 * Get shadow army for user
 */
function getShadowArmy($user_id) {
    $db = new Database();
    $conn = $db->getConnection();
    
    $query = "SELECT * FROM shadow_army WHERE user_id = :id ORDER BY power_level DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $user_id]);
    
    return $stmt->fetchAll();
}

// =====================================================
// ACHIEVEMENT FUNCTIONS
// =====================================================

/**
 * Check and award achievements
 */
function checkAchievements($user_id) {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get user data
    $userQuery = "SELECT * FROM users WHERE id = :id";
    $userStmt = $conn->prepare($userQuery);
    $userStmt->execute([':id' => $user_id]);
    $user = $userStmt->fetch();
    
    // Get all achievements
    $achQuery = "SELECT * FROM achievements";
    $achStmt = $conn->prepare($achQuery);
    $achStmt->execute();
    $achievements = $achStmt->fetchAll();
    
    // Get user's earned achievements
    $earnedQuery = "SELECT achievement_id FROM user_achievements WHERE user_id = :id";
    $earnedStmt = $conn->prepare($earnedQuery);
    $earnedStmt->execute([':id' => $user_id]);
    $earned = $earnedStmt->fetchAll(PDO::FETCH_COLUMN);
    
    $new_achievements = [];
    
    foreach ($achievements as $ach) {
        if (in_array($ach['id'], $earned)) {
            continue; // Already earned
        }
        
        $earn = false;
        
        switch ($ach['requirement_type']) {
            case 'streak':
                if ($user['current_streak'] >= $ach['requirement_value']) {
                    $earn = true;
                }
                break;
                
            case 'workouts':
                if ($user['total_workouts'] >= $ach['requirement_value']) {
                    $earn = true;
                }
                break;
                
            case 'rank':
                $rank_order = ['E' => 0, 'D' => 1, 'C' => 2, 'B' => 3, 'A' => 4, 'S' => 5];
                if ($rank_order[$user['current_rank']] >= $ach['requirement_value']) {
                    $earn = true;
                }
                break;
        }
        
        if ($earn) {
            // Award achievement
            $awardQuery = "INSERT INTO user_achievements (user_id, achievement_id) VALUES (:uid, :aid)";
            $awardStmt = $conn->prepare($awardQuery);
            $awardStmt->execute([':uid' => $user_id, ':aid' => $ach['id']]);
            
            // Give rewards
            $rewardQuery = "UPDATE users SET 
                           total_xp = total_xp + :xp,
                           shadow_coins = shadow_coins + :coins
                           WHERE id = :id";
            $rewardStmt = $conn->prepare($rewardQuery);
            $rewardStmt->execute([
                ':xp' => $ach['xp_reward'],
                ':coins' => $ach['coin_reward'],
                ':id' => $user_id
            ]);
            
            $new_achievements[] = $ach;
        }
    }
    
    return $new_achievements;
}

// =====================================================
// GUILD FUNCTIONS
// =====================================================

/**
 * Create a guild
 */
function createGuild($user_id, $guild_name, $description) {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Check if user already in a guild
    $checkQuery = "SELECT guild_id FROM guild_members WHERE user_id = :id";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->execute([':id' => $user_id]);
    
    if ($checkStmt->rowCount() > 0) {
        return ['success' => false, 'message' => 'You are already in a guild'];
    }
    
    // Create guild
    $query = "INSERT INTO guilds (guild_name, description, guild_leader_id) 
              VALUES (:name, :desc, :leader)";
    
    $stmt = $conn->prepare($query);
    $success = $stmt->execute([
        ':name' => $guild_name,
        ':desc' => $description,
        ':leader' => $user_id
    ]);
    
    if ($success) {
        $guild_id = $conn->lastInsertId();
        
        // Add leader as member
        $memberQuery = "INSERT INTO guild_members (guild_id, user_id, role) VALUES (:gid, :uid, 'Leader')";
        $memberStmt = $conn->prepare($memberQuery);
        $memberStmt->execute([':gid' => $guild_id, ':uid' => $user_id]);
        
        return ['success' => true, 'guild_id' => $guild_id];
    }
    
    return ['success' => false, 'message' => 'Failed to create guild'];
}

// =====================================================
// RAID FUNCTIONS
// =====================================================

/**
 * Get available raids for user
 */
function getAvailableRaids($user_rank) {
    $db = new Database();
    $conn = $db->getConnection();
    
    $rank_order = ['E' => 0, 'D' => 1, 'C' => 2, 'B' => 3, 'A' => 4, 'S' => 5];
    $user_rank_value = $rank_order[$user_rank];
    
    $query = "SELECT * FROM raids WHERE is_active = TRUE";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $raids = $stmt->fetchAll();
    
    $available = [];
    
    foreach ($raids as $raid) {
        $raid_rank_value = $rank_order[$raid['min_rank_required']];
        if ($raid_rank_value <= $user_rank_value) {
            $available[] = $raid;
        }
    }
    
    return $available;
}

// =====================================================
// UTILITY FUNCTIONS
// =====================================================

/**
 * Sanitize input
 */
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

/**
 * Redirect with message
 */
function redirect($url, $message = null, $type = 'success') {
    if ($message) {
        $_SESSION['flash'] = ['message' => $message, 'type' => $type];
    }
    header('Location: ' . $url);
    exit();
}

/**
 * Display flash message
 */
function displayFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        $class = $flash['type'] == 'success' ? 'alert-success' : 'alert-danger';
        echo '<div class="alert ' . $class . ' alert-dismissible fade show" role="alert">';
        echo $flash['message'];
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
        unset($_SESSION['flash']);
    }
}

/**
 * Get rank badge color
 */
function getRankColor($rank) {
    $colors = [
        'E' => '#808080', // Gray
        'D' => '#00FF00', // Green
        'C' => '#0000FF', // Blue
        'B' => '#800080', // Purple
        'A' => '#FFA500', // Orange
        'S' => '#FF0000'  // Red
    ];
    
    return $colors[$rank] ?? '#FFFFFF';
}

/**
 * Calculate progress to next rank
 */
function calculateProgressToNextRank($current_rank, $streak) {
    $requirements = getRankRequirements();
    
    if ($current_rank == 'S' || !isset($requirements[$current_rank]['next_rank'])) {
        return ['current' => $streak, 'needed' => 60, 'percentage' => 100, 'next_rank' => null];
    }
    
    $next_rank = $requirements[$current_rank]['next_rank'];
    $days_needed = $requirements[$next_rank] ? $requirements[$next_rank]['days'] : 60;
    
    $progress = 100;
    if ($days_needed > 0) {
        $progress = min(($streak / $days_needed) * 100, 100);
    }
    
    return [
        'current' => $streak,
        'needed' => $days_needed,
        'percentage' => $progress,
        'next_rank' => $next_rank
    ];
}
?>