<?php
require_once '../includes/functions.php';
requireLogin();
$user = getCurrentUser();
$progress = calculateProgressToNextRank($user['current_rank'], $user['current_streak']);

$db = new Database(); $conn = $db->getConnection();
$today = date('Y-m-d');

$todayStmt = $conn->prepare("SELECT * FROM workout_logs WHERE user_id=:uid AND workout_date=:d");
$todayStmt->execute([':uid'=>$user['id'],':d'=>$today]);
$todayWorkout = $todayStmt->fetch();

$recentStmt = $conn->prepare("SELECT * FROM workout_logs WHERE user_id=:uid ORDER BY workout_date DESC LIMIT 7");
$recentStmt->execute([':uid'=>$user['id']]);
$recentWorkouts = $recentStmt->fetchAll();

$armyStmt = $conn->prepare("SELECT COUNT(*) as cnt FROM shadow_army WHERE user_id=:uid");
$armyStmt->execute([':uid'=>$user['id']]);
$armyCount = $armyStmt->fetch()['cnt'];

$rankUp = $_SESSION['rank_up'] ?? null;
if ($rankUp) unset($_SESSION['rank_up']);

include '../includes/header.php';
$rank_colors = ['E'=>'#6b7280','D'=>'#10b981','C'=>'#3b82f6','B'=>'#8b5cf6','A'=>'#f59e0b','S'=>'#ef4444'];
$rc = $rank_colors[$user['current_rank']];
$rank_titles = ['E'=>'Weakest Hunter','D'=>'Awakened One','C'=>'Seasoned Hunter','B'=>'Elite Hunter','A'=>'Top-Rank Hunter','S'=>'National Level Hunter'];
?>

<?php if ($rankUp): ?>
<div class="rank-up-overlay" id="rankUpOverlay">
    <div class="rank-up-modal">
        <div style="font-size:2rem;margin-bottom:8px;">🎉</div>
        <div class="rank-up-title">RANK UP!</div>
        <p style="color:var(--text-secondary);font-size:0.85rem;letter-spacing:2px;margin-bottom:24px;">SYSTEM NOTIFICATION</p>
        <div class="rank-change">
            <span class="rank-badge xl" style="color:<?= $rank_colors[$rankUp['old']] ?>;border-color:<?= $rank_colors[$rankUp['old']] ?>;"><?= $rankUp['old'] ?></span>
            <div class="rank-arrow">→</div>
            <span class="rank-badge xl" style="color:<?= $rank_colors[$rankUp['new']] ?>;border-color:<?= $rank_colors[$rankUp['new']] ?>;"><?= $rankUp['new'] ?></span>
        </div>
        <p style="color:var(--text-primary);font-size:1rem;letter-spacing:1px;">You've proven your worth, Hunter.</p>
        <button class="btn btn-primary mt-4" onclick="document.getElementById('rankUpOverlay').remove()">
            <i class="fas fa-bolt"></i> CONTINUE
        </button>
    </div>
</div>
<?php endif; ?>

<!-- Welcome Header -->
<div class="system-header">
    <div class="text-system">[ SYSTEM MESSAGE ]</div>
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;margin-top:8px;">
        <div>
            <h1 style="font-size:1.3rem;margin-bottom:4px;">
                Welcome back, <span class="glow-text"><?= htmlspecialchars($user['full_name']) ?></span>
            </h1>
            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                <span class="rank-badge" style="color:<?= $rc ?>;border-color:<?= $rc ?>;"><?= $user['current_rank'] ?></span>
                <span style="font-size:0.82rem;color:<?= $rc ?>;font-weight:700;letter-spacing:1px;"><?= $rank_titles[$user['current_rank']] ?></span>
            </div>
        </div>
        <div style="text-align:right;margin-top:6px;">
            <div style="font-size:0.75rem;color:var(--text-secondary);letter-spacing:1px;">TODAY</div>
            <div style="font-family:'Orbitron',monospace;font-size:0.9rem;color:var(--accent-blue);"><?= date('D, M d') ?></div>
        </div>
    </div>

    <!-- XP Progress -->
    <div style="margin-top:16px;">
        <div class="xp-label">
            <span>Progress to <?= $progress['next_rank'] ? $progress['next_rank'].'-Rank' : 'MAX (S-Rank)' ?></span>
            <span style="color:var(--accent-blue);"><?= $user['current_streak'] ?>/<?= $progress['needed'] ?> days</span>
        </div>
        <div class="progress lg">
            <div class="progress-bar" style="width:<?= min($progress['percentage'],100) ?>%;background:linear-gradient(90deg,<?= $rc ?>,var(--accent-blue));"></div>
        </div>
    </div>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="color:var(--accent-blue);">⚡</div>
        <div class="stat-value" data-target="<?= $user['current_streak'] ?>"><?= $user['current_streak'] ?></div>
        <div class="stat-label">Streak Days</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="color:var(--danger-red);">🔥</div>
        <div class="stat-value"><?= $user['best_streak'] ?></div>
        <div class="stat-label">Best Streak</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">⚔</div>
        <div class="stat-value"><?= $user['total_workouts'] ?></div>
        <div class="stat-label">Total Quests</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="color:var(--gold);">🌟</div>
        <div class="stat-value"><?= number_format($user['total_xp']) ?></div>
        <div class="stat-label">Total XP</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="color:var(--accent-blue);">🪙</div>
        <div class="stat-value"><?= $user['shadow_coins'] ?></div>
        <div class="stat-label">Shadow Coins</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="color:#c084fc;">👥</div>
        <div class="stat-value"><?= $armyCount ?></div>
        <div class="stat-label">Shadow Army</div>
    </div>
</div>

<!-- Daily Quest + Recent Log -->
<div class="row mt-2">
    <div class="col-md-5 mb-3">
        <div class="card" style="border-color:<?= $todayWorkout ? 'rgba(0,255,170,0.4)' : 'rgba(255,51,102,0.4)' ?>;">
            <div class="card-header" style="color:<?= $todayWorkout ? 'var(--success-green)' : 'var(--danger-red)' ?>;">
                <i class="fas fa-<?= $todayWorkout ? 'check-circle' : 'exclamation-circle' ?>"></i>
                [DAILY QUEST] PREPARING TO BECOME STRONG
            </div>
            <div class="card-body">
                <?php if ($todayWorkout): ?>
                    <div class="system-message" style="border-left-color:var(--success-green);color:var(--success-green);margin-bottom:14px;">
                        <i class="fas fa-check-circle me-2"></i> Quest completed today!
                    </div>
                    <div style="display:flex;gap:16px;margin-bottom:16px;">
                        <div style="flex:1;text-align:center;background:rgba(0,255,170,0.05);border:1px solid rgba(0,255,170,0.2);border-radius:6px;padding:12px;">
                            <div style="font-family:'Orbitron',monospace;font-size:1.4rem;font-weight:900;color:var(--gold);">+<?= $todayWorkout['xp_earned'] ?></div>
                            <div style="font-size:0.7rem;color:var(--text-secondary);letter-spacing:1px;">XP EARNED</div>
                        </div>
                        <div style="flex:1;text-align:center;background:rgba(0,212,255,0.05);border:1px solid rgba(0,212,255,0.2);border-radius:6px;padding:12px;">
                            <div style="font-family:'Orbitron',monospace;font-size:1.4rem;font-weight:900;color:var(--accent-blue);">+<?= $todayWorkout['shadow_coins_earned'] ?></div>
                            <div style="font-size:0.7rem;color:var(--text-secondary);letter-spacing:1px;">COINS</div>
                        </div>
                    </div>
                    <a href="workout.php" class="btn btn-success w-100">
                        <i class="fas fa-eye"></i> VIEW QUEST DETAILS
                    </a>
                <?php else: ?>
                    <div class="system-message penalty" style="margin-bottom:14px;">
                        <i class="fas fa-exclamation-triangle me-2"></i> 
                        Warning: Daily Quest incomplete. Penalty will be enforced upon failure.
                    </div>
                    <a href="workout.php" class="btn btn-primary w-100 mb-2" style="letter-spacing:2px;">
                        <i class="fas fa-scroll"></i> ACCEPT QUEST
                    </a>
                    <?php if (canTakeRestDay($user['id'])): ?>
                        <form method="POST" action="rest_day.php" class="mt-2">
                            <button type="submit" class="btn w-100" style="border-color:var(--text-secondary);color:var(--text-secondary);">
                                <i class="fas fa-bed"></i> USE REST DAY
                            </button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-7 mb-3">
        <div class="card">
            <div class="card-header"><i class="fas fa-history"></i> [SYSTEM LOG] RECENT QUESTS</div>
            <div class="card-body" style="padding:0;">
                <?php if (empty($recentWorkouts)): ?>
                    <div class="text-center py-4" style="color:var(--text-secondary);">
                        <div style="font-size:2rem;opacity:0.3;">📜</div>
                        <p style="margin-top:8px;font-size:0.85rem;">No quest logs found. You must train to survive.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead><tr><th>Date</th><th>Completion</th><th>XP</th><th>Coins</th></tr></thead>
                            <tbody>
                            <?php foreach ($recentWorkouts as $w): $pct=round($w['completion_percentage']); ?>
                                <tr>
                                    <td><?= date('M d', strtotime($w['workout_date'])) ?></td>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:8px;">
                                            <div class="progress" style="flex:1;height:6px;min-width:60px;">
                                                <div class="progress-bar" style="width:<?= $pct ?>%;background:<?= $pct>=100?'var(--success-green)':($pct>=75?'var(--accent-blue)':'var(--warning-yellow)') ?>;"></div>
                                            </div>
                                            <span style="font-size:0.75rem;font-weight:700;color:<?= $pct>=100?'var(--success-green)':'var(--warning-yellow)' ?>;"><?= $pct ?>%</span>
                                        </div>
                                    </td>
                                    <td style="color:var(--gold);font-weight:700;">+<?= $w['xp_earned'] ?></td>
                                    <td style="color:var(--accent-blue);">+<?= $w['shadow_coins_earned'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Nav -->
<div class="card">
    <div class="card-header"><i class="fas fa-bolt"></i> QUICK ACCESS</div>
    <div class="card-body">
        <div class="row g-3">
            <?php
            $quick_links = [
                ['workout.php','fas fa-scroll','DAILY QUEST','var(--accent-blue)'],
                ['shadow_army.php','fas fa-ghost','SHADOW ARMY','#c084fc'],
                ['raids.php','fas fa-dragon','RAIDS','var(--danger-red)'],
                ['achievements.php','fas fa-trophy','TITLES','var(--gold)'],
                ['guild.php','fas fa-users','GUILD','#10b981'],
                ['leaderboard.php','fas fa-crown','RANKING','var(--gold)'],
                ['rewards.php','fas fa-gem','STORE','var(--accent-blue)'],
                ['profile.php','fas fa-user','PROFILE','var(--text-secondary)'],
            ];
            foreach ($quick_links as [$url,$icon,$label,$col]):
            ?>
            <div class="col-6 col-md-3">
                <a href="<?= $url ?>" class="btn btn-primary w-100 d-flex flex-column align-items-center py-3 text-decoration-none" style="border-color:<?= $col ?>40;background:rgba(0,0,0,0.3) !important;color:<?= $col ?> !important;gap:6px;min-height:80px;">
                    <i class="<?= $icon ?>" style="font-size:1.4rem;"></i>
                    <span style="font-size:0.72rem;letter-spacing:1.5px;"><?= $label ?></span>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>