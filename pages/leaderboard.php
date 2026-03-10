<?php
require_once '../includes/functions.php';
requireLogin();
$user = getCurrentUser();
$db = new Database(); $conn = $db->getConnection();

// Get top hunters sorted by XP
$lbStmt = $conn->prepare("SELECT id, username, full_name, current_rank, total_xp, current_streak, best_streak, total_workouts, shadow_coins FROM users WHERE is_active=1 ORDER BY total_xp DESC LIMIT 50");
$lbStmt->execute();
$hunters = $lbStmt->fetchAll();

// Find current user's position
$posStmt = $conn->prepare("SELECT COUNT(*)+1 as pos FROM users WHERE total_xp > (SELECT total_xp FROM users WHERE id=:id) AND is_active=1");
$posStmt->execute([':id' => $user['id']]);
$myPos = $posStmt->fetch()['pos'];

include '../includes/header.php';
$rank_colors = ['E'=>'#6b7280','D'=>'#10b981','C'=>'#3b82f6','B'=>'#8b5cf6','A'=>'#f59e0b','S'=>'#ef4444'];
?>

<div class="system-header">
    <div class="text-system">[ HUNTER ASSOCIATION RANKING ]</div>
    <h1 style="font-size:1.5rem;margin-top:6px;"><i class="fas fa-crown" style="color:var(--gold);"></i> GLOBAL RANKING</h1>
    <p style="color:var(--text-secondary);font-size:0.9rem;margin:4px 0 0;">
        Your rank: <strong style="color:var(--accent-blue);">#<?= $myPos ?></strong>
    </p>
</div>

<!-- Top 3 podium -->
<?php if (count($hunters) >= 3): ?>
<div class="row mb-4 justify-content-center">
    <?php
    $podium_order = [1, 0, 2]; // 2nd, 1st, 3rd visual order
    $podium_heights = [1 => 'var(--gold)', 0 => '#94a3b8', 2 => '#b46d36'];
    $trophies = ['🥇','🥈','🥉'];
    foreach ($podium_order as $idx):
        if (!isset($hunters[$idx])) continue;
        $h = $hunters[$idx]; $pos = $idx + 1;
        $rc = $rank_colors[$h['current_rank']];
    ?>
    <div class="col-4 col-md-3 text-center">
        <div style="padding:<?= $pos===1?'20px':'10px' ?> 12px;background:rgba(0,0,0,0.4);border:1px solid <?= $podium_heights[$idx] ?>;border-radius:8px;transition:all 0.3s;">
            <div style="font-size:1.8rem;"><?= $trophies[$idx] ?></div>
            <div style="font-family:'Orbitron',monospace;font-size:0.7rem;font-weight:700;color:<?= $podium_heights[$idx] ?>;margin:6px 0 2px;"><?= htmlspecialchars($h['username']) ?></div>
            <span class="rank-badge" style="color:<?= $rc ?>;border-color:<?= $rc ?>;background:rgba(0,0,0,0.3);width:30px;height:30px;font-size:0.7rem;"><?= $h['current_rank'] ?></span>
            <div style="font-family:'Orbitron',monospace;font-size:0.9rem;font-weight:900;color:#fff;margin-top:6px;"><?= number_format($h['total_xp']) ?></div>
            <div style="font-size:0.65rem;color:var(--text-secondary);letter-spacing:1px;">XP</div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Full leaderboard -->
<div class="card">
    <div class="card-header" style="color:var(--gold);"><i class="fas fa-list-ol"></i> TOP HUNTERS</div>
    <div class="card-body" style="padding:12px;">
        <?php foreach ($hunters as $i => $h):
            $pos = $i + 1;
            $isMe = $h['id'] == $user['id'];
            $rc = $rank_colors[$h['current_rank']];
            $pos_class = $pos===1?'rank-1':($pos===2?'rank-2':($pos===3?'rank-3':''));
            $pos_icons = [1=>'🥇',2=>'🥈',3=>'🥉'];
        ?>
        <div class="leaderboard-row top-<?= $pos <= 3 ? $pos : 'other' ?>" style="<?= $isMe ? 'border-color:rgba(0,212,255,0.5);background:rgba(0,212,255,0.05);' : '' ?>">
            <div class="leaderboard-rank <?= $pos_class ?>">
                <?= isset($pos_icons[$pos]) ? $pos_icons[$pos] : '#'.$pos ?>
            </div>
            <div style="flex:1;">
                <div style="font-weight:700;font-size:0.95rem;color:<?= $isMe ? 'var(--accent-blue)' : 'var(--text-primary)' ?>;">
                    <?= htmlspecialchars($h['username']) ?>
                    <?php if ($isMe): ?><span style="font-size:0.65rem;color:var(--accent-blue);margin-left:6px;letter-spacing:2px;">[YOU]</span><?php endif; ?>
                </div>
                <div style="font-size:0.75rem;color:var(--text-secondary);">
                    🔥 <?= $h['current_streak'] ?> streak &nbsp;|&nbsp; ⚔ <?= $h['total_workouts'] ?> quests
                </div>
            </div>
            <div style="text-align:right;">
                <span class="rank-badge" style="color:<?= $rc ?>;border-color:<?= $rc ?>;width:32px;height:32px;font-size:0.75rem;"><?= $h['current_rank'] ?></span>
            </div>
            <div style="text-align:right;min-width:80px;">
                <div style="font-family:'Orbitron',monospace;font-weight:900;font-size:0.9rem;color:#fff;"><?= number_format($h['total_xp']) ?></div>
                <div style="font-size:0.65rem;color:var(--text-secondary);letter-spacing:1px;">XP</div>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if ($myPos > 50): ?>
        <div class="divider"></div>
        <div class="leaderboard-row" style="border-color:rgba(0,212,255,0.4);background:rgba(0,212,255,0.04);">
            <div class="leaderboard-rank">#<?= $myPos ?></div>
            <div style="flex:1;">
                <div style="font-weight:700;color:var(--accent-blue);"><?= htmlspecialchars($user['username']) ?> [YOU]</div>
                <div style="font-size:0.75rem;color:var(--text-secondary);">🔥 <?= $user['current_streak'] ?> streak</div>
            </div>
            <div style="text-align:right;">
                <div style="font-family:'Orbitron',monospace;font-weight:900;font-size:0.9rem;"><?= number_format($user['total_xp']) ?></div>
                <div style="font-size:0.65rem;color:var(--text-secondary);">XP</div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
