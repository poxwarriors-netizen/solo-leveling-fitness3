<?php
require_once '../includes/functions.php';
requireLogin();
$user = getCurrentUser();
$db = new Database(); $conn = $db->getConnection();

$progress = calculateProgressToNextRank($user['current_rank'], $user['current_streak']);

// Get last 30 workout logs for calendar
$logsStmt = $conn->prepare("SELECT workout_date, completion_percentage, xp_earned FROM workout_logs WHERE user_id=:uid ORDER BY workout_date DESC LIMIT 30");
$logsStmt->execute([':uid' => $user['id']]);
$logs = $logsStmt->fetchAll();
$log_dates = array_column($logs, null, 'workout_date');

// Army count
$armyStmt = $conn->prepare("SELECT COUNT(*) as cnt FROM shadow_army WHERE user_id=:uid");
$armyStmt->execute([':uid' => $user['id']]);
$army_count = $armyStmt->fetch()['cnt'];

// Achievements earned
$achStmt = $conn->prepare("SELECT COUNT(*) as cnt FROM user_achievements WHERE user_id=:uid");
$achStmt->execute([':uid' => $user['id']]);
$ach_count = $achStmt->fetch()['cnt'];

// Profile update
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bio = sanitize($_POST['bio'] ?? '');
    $conn->prepare("UPDATE users SET bio=:bio WHERE id=:id")->execute([':bio'=>$bio, ':id'=>$user['id']]);
    $msg = 'success|Profile updated.';
    $user['bio'] = $bio;
}

include '../includes/header.php';
$rank_colors = ['E'=>'#6b7280','D'=>'#10b981','C'=>'#3b82f6','B'=>'#8b5cf6','A'=>'#f59e0b','S'=>'#ef4444'];
$rc = $rank_colors[$user['current_rank']];
$rank_titles = ['E'=>'Weakest Hunter','D'=>'Awakened One','C'=>'Seasoned Hunter','B'=>'Elite Hunter','A'=>'Top-Rank Hunter','S'=>'National Level Hunter'];
?>

<div class="system-header">
    <div class="text-system">[ HUNTER PROFILE ]</div>
    <h1 style="font-size:1.5rem;margin-top:6px;"><i class="fas fa-user-circle" style="color:var(--accent-blue);"></i> HUNTER STATUS</h1>
</div>

<?php if ($msg): list($t,$m)=explode('|',$msg,2); ?>
    <div class="alert alert-<?= $t==='success'?'success':'danger' ?> animate-in"><i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($m) ?></div>
<?php endif; ?>

<!-- Profile Hero -->
<div class="card mb-4" style="border-color:<?= $rc ?>40;">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-2 text-center mb-3 mb-md-0">
                <div class="profile-avatar mx-auto" style="background:linear-gradient(135deg,<?= $rc ?>40,rgba(0,212,255,0.2));box-shadow:0 0 30px <?= $rc ?>60;">
                    <?= strtoupper(substr($user['username'],0,1)) ?>
                </div>
            </div>
            <div class="col-md-7">
                <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:6px;">
                    <h2 style="font-size:1.4rem;margin:0;color:#fff;"><?= htmlspecialchars($user['full_name']) ?></h2>
                    <span class="rank-badge large" style="color:<?= $rc ?>;border-color:<?= $rc ?>;"><?= $user['current_rank'] ?></span>
                </div>
                <div style="font-size:0.8rem;color:<?= $rc ?>;letter-spacing:2px;font-weight:700;margin-bottom:8px;">
                    @<?= htmlspecialchars($user['username']) ?> &nbsp;·&nbsp; <?= $rank_titles[$user['current_rank']] ?? 'Hunter' ?>
                </div>
                <div style="font-size:0.85rem;color:var(--text-secondary);">
                    <?= htmlspecialchars($user['bio'] ?: '"I alone level up."') ?>
                </div>
                <div style="margin-top:12px;font-size:0.78rem;color:var(--text-secondary);">
                    <i class="fas fa-calendar me-1"></i> Joined <?= date('M Y', strtotime($user['join_date'])) ?>
                    &nbsp;|&nbsp; <i class="fas fa-envelope me-1"></i> <?= htmlspecialchars($user['email']) ?>
                </div>
            </div>
            <div class="col-md-3 text-md-end mt-3 mt-md-0">
                <button class="btn btn-primary btn-sm" onclick="document.getElementById('editForm').classList.toggle('d-none')">
                    <i class="fas fa-edit"></i> Edit Profile
                </button>
            </div>
        </div>

        <!-- XP Progress -->
        <div style="margin-top:20px;">
            <div class="xp-label">
                <span style="font-size:0.78rem;color:var(--text-secondary);">Progress to <?= $progress['next_rank'] ? $progress['next_rank'].'-Rank' : 'MAX RANK' ?></span>
                <span style="color:var(--accent-blue);font-weight:700;"><?= $user['current_streak'] ?>/<?= $progress['needed'] ?> days</span>
            </div>
            <div class="progress lg">
                <div class="progress-bar" style="width:<?= min($progress['percentage'],100) ?>%;background:linear-gradient(90deg,<?= $rc ?>,var(--accent-blue));"></div>
            </div>
        </div>

        <!-- Edit bio -->
        <div id="editForm" class="d-none mt-3">
            <div class="divider"></div>
            <form method="POST">
                <div class="mb-2">
                    <label class="form-label">Bio / Tagline</label>
                    <input type="text" class="form-control" name="bio" maxlength="150" value="<?= htmlspecialchars($user['bio'] ?? '') ?>" placeholder="Your hunter motto...">
                </div>
                <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-save"></i> Save</button>
            </form>
        </div>
    </div>
</div>

<!-- Stats -->
<div class="stats-grid mb-4">
    <div class="stat-card">
        <div class="stat-icon" style="color:var(--accent-blue);">⚡</div>
        <div class="stat-value" data-target="<?= $user['current_streak'] ?>"><?= $user['current_streak'] ?></div>
        <div class="stat-label">Current Streak</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="color:var(--danger-red);">🔥</div>
        <div class="stat-value"><?= $user['best_streak'] ?></div>
        <div class="stat-label">Best Streak</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">⚔</div>
        <div class="stat-value"><?= $user['total_workouts'] ?></div>
        <div class="stat-label">Quests Done</div>
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
        <div class="stat-value"><?= $army_count ?></div>
        <div class="stat-label">Army Size</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="color:var(--gold);">🏆</div>
        <div class="stat-value"><?= $ach_count ?></div>
        <div class="stat-label">Titles</div>
    </div>
</div>

<!-- Last 30 days calendar -->
<div class="card">
    <div class="card-header"><i class="fas fa-calendar-alt"></i> QUEST CALENDAR (LAST 30 DAYS)</div>
    <div class="card-body">
        <div class="streak-calendar">
            <?php
            for ($i = 29; $i >= 0; $i--) {
                $day = date('Y-m-d', strtotime("-{$i} days"));
                $label = date('d', strtotime($day));
                $today = ($i === 0);
                if ($today) {
                    echo "<div class='streak-day today' title='Today ({$day})'>$label</div>";
                } elseif (isset($log_dates[$day])) {
                    $pct = $log_dates[$day]['completion_percentage'];
                    echo "<div class='streak-day completed' title='Completed $pct% on $day'>$label</div>";
                } else {
                    echo "<div class='streak-day empty' title='$day'>$label</div>";
                }
            }
            ?>
        </div>
        <div style="display:flex;gap:16px;margin-top:14px;font-size:0.75rem;flex-wrap:wrap;">
            <span><span class="streak-day completed" style="display:inline-flex;width:14px;height:14px;vertical-align:middle;border-radius:3px;margin-right:4px;"></span>Completed</span>
            <span><span class="streak-day today" style="display:inline-flex;width:14px;height:14px;vertical-align:middle;border-radius:3px;margin-right:4px;"></span>Today</span>
            <span><span class="streak-day empty" style="display:inline-flex;width:14px;height:14px;vertical-align:middle;border-radius:3px;margin-right:4px;"></span>Missed</span>
        </div>
    </div>
</div>

<!-- Recent workouts -->
<div class="card">
    <div class="card-header"><i class="fas fa-history"></i> RECENT QUEST LOG</div>
    <div class="card-body" style="padding:0;">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr>
                    <th>Date</th><th>Push-ups</th><th>Sit-ups</th><th>Squats</th><th>Running</th><th>Completion</th><th>XP</th>
                </tr></thead>
                <tbody>
                <?php foreach (array_slice($logs,0,10) as $l): $pct=round($l['completion_percentage']); ?>
                    <tr>
                        <td><?= date('M d Y', strtotime($l['workout_date'])) ?></td>
                        <td><?= $l['pushups'] ?? '-' ?></td>
                        <td><?= $l['situps'] ?? '-' ?></td>
                        <td><?= $l['squats'] ?? '-' ?></td>
                        <td><?= $l['running_km'] ?? '-' ?>km</td>
                        <td>
                            <span style="color:<?= $pct>=100?'var(--success-green)':($pct>=50?'var(--warning-yellow)':'var(--danger-red)') ?>;font-weight:700;"><?= $pct ?>%</span>
                        </td>
                        <td style="color:var(--gold);font-weight:700;">+<?= $l['xp_earned'] ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="7" class="text-center" style="color:var(--text-secondary);padding:20px;">No workouts logged yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
