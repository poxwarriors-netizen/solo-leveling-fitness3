<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$current_page = basename($_SERVER['PHP_SELF']);
$user_rank = $_SESSION['user_rank'] ?? 'E';
$logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];

$rank_colors = [
    'E' => '#6b7280', 'D' => '#10b981', 'C' => '#3b82f6',
    'B' => '#8b5cf6', 'A' => '#f59e0b', 'S' => '#ef4444'
];
$rank_color = $rank_colors[$user_rank] ?? '#6b7280';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solo Leveling Fitness ⌈ The System ⌋</title>
    <meta name="description" content="Train like Sung Jin-Woo. Level up in real life. Solo Leveling Fitness tracks your workouts, ranks, and shadow army.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/solo-leveling-fitness/assets/css/style.css">
</head>
<body>

<!-- Floating particles -->
<div class="particles" id="particles"></div>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="/solo-leveling-fitness/index.php">
            <div class="brand-icon">⚔</div>
            <span>SOLO <span class="brand-s">LVL</span></span>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                style="color:var(--accent-blue);font-size:1.2rem;">
            <i class="fas fa-bars"></i>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
                <?php if ($logged_in): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page=='dashboard.php'?'active':'' ?>" href="/solo-leveling-fitness/pages/dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page=='workout.php'?'active':'' ?>" href="/solo-leveling-fitness/pages/workout.php">
                            <i class="fas fa-scroll"></i> Daily Quest
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page=='shadow_army.php'?'active':'' ?>" href="/solo-leveling-fitness/pages/shadow_army.php">
                            <i class="fas fa-ghost"></i> Army
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page=='raids.php'?'active':'' ?>" href="/solo-leveling-fitness/pages/raids.php">
                            <i class="fas fa-dragon"></i> Raids
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page=='achievements.php'?'active':'' ?>" href="/solo-leveling-fitness/pages/achievements.php">
                            <i class="fas fa-trophy"></i> Titles
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page=='guild.php'?'active':'' ?>" href="/solo-leveling-fitness/pages/guild.php">
                            <i class="fas fa-users"></i> Guild
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page=='leaderboard.php'?'active':'' ?>" href="/solo-leveling-fitness/pages/leaderboard.php">
                            <i class="fas fa-crown"></i> Ranking
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page=='rewards.php'?'active':'' ?>" href="/solo-leveling-fitness/pages/rewards.php">
                            <i class="fas fa-gem"></i> Store
                        </a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="nav-link d-flex align-items-center gap-2" href="/solo-leveling-fitness/pages/profile.php">
                            <span class="rank-badge-nav" style="color:<?= $rank_color ?>;border-color:<?= $rank_color ?>;"><?= $user_rank ?></span>
                            <span class="d-none d-lg-inline text-xs" style="color:var(--text-secondary);font-size:0.78rem;"><?= htmlspecialchars($_SESSION['username'] ?? 'Hunter') ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/solo-leveling-fitness/pages/logout.php" style="color:var(--danger-red)!important;">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page=='login.php'?'active':'' ?>" href="/solo-leveling-fitness/pages/login.php">
                            <i class="fas fa-sign-in-alt"></i> Enter Gate
                        </a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-primary btn-sm" href="/solo-leveling-fitness/pages/register.php">
                            <i class="fas fa-bolt"></i> Awaken
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- MAIN WRAPPER -->
<main class="main-content">
<div class="container">

<?php
// Flash messages
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    $cls = $flash['type'] === 'success' ? 'alert-success' : ($flash['type'] === 'warning' ? 'alert-warning' : 'alert-danger');
    echo '<div class="alert '.$cls.' alert-dismissible animate-in" role="alert" style="margin-top:16px;">';
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" style="filter:invert(1);opacity:0.6;"></button>';
    echo htmlspecialchars($flash['message']);
    echo '</div>';
    unset($_SESSION['flash']);
}
?>