<?php
require_once '../includes/functions.php';
requireLogin();
$user = getCurrentUser();
$db = new Database(); $conn = $db->getConnection();

// Get available raids
$raids = getAvailableRaids($user['current_rank']);

// Get user's completed raids
$completedStmt = $conn->prepare("SELECT raid_id FROM raid_completions WHERE user_id = :uid");
$completedStmt->execute([':uid' => $user['id']]);
$completed = $completedStmt->fetchAll(PDO::FETCH_COLUMN);

// Handle raid completion
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['raid_id'])) {
    $raid_id = intval($_POST['raid_id']);
    if (!in_array($raid_id, $completed)) {
        // Get raid info
        $raidStmt = $conn->prepare("SELECT * FROM raids WHERE id = :id AND is_active = 1");
        $raidStmt->execute([':id' => $raid_id]);
        $raid = $raidStmt->fetch();
        if ($raid) {
            $conn->prepare("INSERT INTO raid_completions (user_id, raid_id, performance_score) VALUES (?,?,?)")
                 ->execute([$user['id'], $raid_id, rand(70,100)]);
            $conn->prepare("UPDATE users SET total_xp=total_xp+:xp, shadow_coins=shadow_coins+:coins WHERE id=:id")
                 ->execute([':xp'=>$raid['xp_reward'],':coins'=>$raid['coin_reward'],':id'=>$user['id']]);
            $msg = "success|Raid cleared! +{$raid['xp_reward']} XP, +{$raid['coin_reward']} Shadow Coins!";
            $completed[] = $raid_id;
        }
    } else {
        $msg = "error|You've already cleared this raid.";
    }
}

include '../includes/header.php';

$rank_colors = ['E'=>'#6b7280','D'=>'#10b981','C'=>'#3b82f6','B'=>'#8b5cf6','A'=>'#f59e0b','S'=>'#ef4444'];
$boss_icons = ['Low Orb Guardian'=>'🔮','High Orb Keeper'=>'👁','Demon King'=>'👿','Ant King'=>'🐜'];
?>

<div class="system-header">
    <div class="text-system">[ GATE RAID SYSTEM ]</div>
    <h1 style="font-size:1.5rem;margin-top:6px;"><i class="fas fa-dragon" style="color:var(--danger-red);"></i> GATE RAIDS</h1>
    <p style="color:var(--text-secondary);font-size:0.9rem;margin:4px 0 0;">
        Clear gates. Claim rewards. Your rank: 
        <span style="color:<?= $rank_colors[$user['current_rank']] ?>;font-weight:700;"><?= $user['current_rank'] ?>-Rank</span>
    </p>
</div>

<?php if ($msg): list($type,$text)=explode('|',$msg,2); ?>
    <div class="alert alert-<?= $type==='success'?'success':'danger' ?> animate-in">
        <i class="fas fa-<?= $type==='success'?'check-circle':'times-circle' ?> me-2"></i><?= htmlspecialchars($text) ?>
    </div>
<?php endif; ?>

<!-- Stats bar -->
<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(140px,1fr));margin-bottom:24px;">
    <div class="stat-card">
        <div class="stat-icon" style="color:var(--danger-red);">🐉</div>
        <div class="stat-value"><?= count($completed) ?></div>
        <div class="stat-label">Raids Cleared</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">⚔</div>
        <div class="stat-value"><?= count($raids) ?></div>
        <div class="stat-label">Available</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="color:var(--gold);">🌟</div>
        <div class="stat-value"><?= number_format($user['total_xp']) ?></div>
        <div class="stat-label">Total XP</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="color:var(--accent-blue);">💰</div>
        <div class="stat-value"><?= $user['shadow_coins'] ?></div>
        <div class="stat-label">Shadow Coins</div>
    </div>
</div>

<!-- Raids List -->
<?php if (empty($raids)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <div style="font-size:3rem;opacity:0.3;">🚪</div>
            <p style="color:var(--text-secondary);margin-top:12px;">No raids available for your rank yet.</p>
            <p style="font-size:0.85rem;color:var(--text-secondary);">Train harder to unlock higher-rank gates.</p>
        </div>
    </div>
<?php else: ?>
    <?php
    $type_labels = ['daily'=>'DAILY','weekly'=>'WEEKLY','monthly'=>'MONTHLY','special'=>'SPECIAL EVENT'];
    $type_colors = ['daily'=>'var(--accent-blue)','weekly'=>'#c084fc','monthly'=>'var(--gold)','special'=>'var(--danger-red)'];
    ?>
    <div class="row">
    <?php foreach ($raids as $raid):
        $is_done = in_array($raid['id'], $completed);
        $rank_col = $rank_colors[$raid['min_rank_required']] ?? '#fff';
        $boss_icon = $boss_icons[$raid['boss_name']] ?? '👾';
        $t_color = $type_colors[$raid['raid_type']] ?? 'var(--accent-blue)';
    ?>
    <div class="col-md-6 mb-3">
        <div class="raid-card <?= $is_done ? '' : '' ?>" style="<?= $is_done ? 'opacity:0.65;' : '' ?>">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;">
                <div>
                    <span style="font-size:0.65rem;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:<?= $t_color ?>;border:1px solid <?= $t_color ?>;padding:2px 8px;border-radius:10px;">
                        <?= $type_labels[$raid['raid_type']] ?>
                    </span>
                    <span style="font-size:0.65rem;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:<?= $rank_col ?>;border:1px solid <?= $rank_col ?>;padding:2px 8px;border-radius:10px;margin-left:6px;">
                        <?= $raid['min_rank_required'] ?>+ RANK
                    </span>
                </div>
                <span style="font-size:1.5rem;"><?= $boss_icon ?></span>
            </div>

            <h4 style="font-size:1rem;letter-spacing:2px;color:<?= $is_done ? 'var(--success-green)' : 'var(--text-primary)' ?>;margin-bottom:4px;">
                <?= $is_done ? '✓ ' : '' ?><?= htmlspecialchars($raid['raid_name']) ?>
            </h4>
            <p style="font-size:0.8rem;color:var(--danger-red);font-weight:700;margin-bottom:8px;">
                Boss: <?= htmlspecialchars($raid['boss_name']) ?>
            </p>
            <p style="font-size:0.82rem;color:var(--text-secondary);margin-bottom:14px;">
                <?= htmlspecialchars($raid['description'] ?? 'Clear the gate and defeat the boss.') ?>
            </p>

            <div style="display:flex;gap:16px;margin-bottom:14px;">
                <div style="font-size:0.8rem;">
                    <i class="fas fa-star me-1" style="color:var(--warning-yellow);"></i>
                    <span style="color:var(--warning-yellow);font-weight:700;"><?= number_format($raid['xp_reward']) ?> XP</span>
                </div>
                <div style="font-size:0.8rem;">
                    <i class="fas fa-coins me-1" style="color:var(--accent-blue);"></i>
                    <span style="color:var(--accent-blue);font-weight:700;"><?= $raid['coin_reward'] ?> Coins</span>
                </div>
            </div>

            <?php if ($is_done): ?>
                <div class="btn btn-success w-100" style="cursor:default;">
                    <i class="fas fa-check-circle"></i> GATE CLEARED
                </div>
            <?php else: ?>
                <form method="POST">
                    <input type="hidden" name="raid_id" value="<?= $raid['id'] ?>">
                    <button type="submit" class="btn btn-danger w-100">
                        <i class="fas fa-dragon"></i> ENTER GATE
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Locked Raids Info -->
<div class="card" style="border-color:rgba(239,68,68,0.2);">
    <div class="card-header" style="color:var(--danger-red);border-color:rgba(239,68,68,0.2);">
        <i class="fas fa-lock"></i> LOCKED RAIDS
    </div>
    <div class="card-body">
        <p style="color:var(--text-secondary);font-size:0.85rem;margin-bottom:12px;">
            Higher rank gates contain stronger bosses and greater rewards. Raise your rank to unlock them.
        </p>
        <div class="row g-2">
            <?php
            $all_raids_stmt = $conn->prepare("SELECT * FROM raids WHERE is_active=1 ORDER BY min_rank_required");
            $all_raids_stmt->execute();
            $all_raids = $all_raids_stmt->fetchAll();
            $rank_order = ['E'=>0,'D'=>1,'C'=>2,'B'=>3,'A'=>4,'S'=>5];
            $user_rank_val = $rank_order[$user['current_rank']];
            foreach ($all_raids as $r):
                if ($rank_order[$r['min_rank_required']] <= $user_rank_val) continue;
                $rc = $rank_colors[$r['min_rank_required']] ?? '#fff';
            ?>
            <div class="col-md-6">
                <div style="background:rgba(0,0,0,0.4);border:1px solid rgba(239,68,68,0.15);border-radius:6px;padding:12px;display:flex;align-items:center;gap:12px;">
                    <i class="fas fa-lock" style="color:rgba(239,68,68,0.4);font-size:1.2rem;"></i>
                    <div>
                        <div style="font-size:0.85rem;font-weight:700;color:var(--text-secondary);"><?= htmlspecialchars($r['raid_name']) ?></div>
                        <div style="font-size:0.7rem;color:<?= $rc ?>;">Requires <?= $r['min_rank_required'] ?>-Rank</div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
