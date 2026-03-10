<?php
require_once '../includes/functions.php';
requireLogin();
$user = getCurrentUser();
$db = new Database(); $conn = $db->getConnection();

// Get all achievements
$achStmt = $conn->prepare("SELECT a.*, (SELECT earned_date FROM user_achievements ua WHERE ua.achievement_id=a.id AND ua.user_id=:uid LIMIT 1) as earned_date FROM achievements a ORDER BY a.requirement_value ASC");
$achStmt->execute([':uid' => $user['id']]);
$achievements = $achStmt->fetchAll();

$earned_count = 0;
foreach ($achievements as $a) { if ($a['earned_date']) $earned_count++; }

include '../includes/header.php';

$rarity_icons = ['Common'=>'⚪','Rare'=>'🔵','Epic'=>'🟣','Legendary'=>'🟡','Mythic'=>'🔴'];
$rarity_emojis = ['Common'=>'','Rare'=>'','Epic'=>'💜','Legendary'=>'✨','Mythic'=>'🔥'];
?>

<div class="system-header">
    <div class="text-system">[ ACHIEVEMENT SYSTEM ]</div>
    <h1 style="font-size:1.5rem;margin-top:6px;"><i class="fas fa-trophy" style="color:var(--gold);"></i> HUNTER TITLES</h1>
    <p style="color:var(--text-secondary);font-size:0.9rem;margin:4px 0 0;">Prove your worth. Earn legendary titles.</p>
</div>

<!-- Summary -->
<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(150px,1fr));margin-bottom:24px;">
    <div class="stat-card">
        <div class="stat-icon" style="color:var(--gold);">🏆</div>
        <div class="stat-value"><?= $earned_count ?>/<?= count($achievements) ?></div>
        <div class="stat-label">Titles Earned</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">⚡</div>
        <div class="stat-value"><?= $user['current_streak'] ?></div>
        <div class="stat-label">Current Streak</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">💪</div>
        <div class="stat-value"><?= $user['total_workouts'] ?></div>
        <div class="stat-label">Total Quests</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="color:var(--gold);">🌟</div>
        <div class="stat-value"><?= number_format($user['total_xp']) ?></div>
        <div class="stat-label">Total XP</div>
    </div>
</div>

<!-- Progress bar -->
<?php $pct = count($achievements) > 0 ? round(($earned_count / count($achievements)) * 100) : 0; ?>
<div class="card mb-4">
    <div class="card-body">
        <div class="xp-label">
            <span><i class="fas fa-trophy me-1" style="color:var(--gold);"></i> Achievement Progress</span>
            <span style="color:var(--gold);"><?= $pct ?>%</span>
        </div>
        <div class="progress lg"><div class="progress-bar" style="width:<?= $pct ?>%;background:linear-gradient(90deg,var(--accent-purple),var(--gold));"></div></div>
        <small style="color:var(--text-secondary);"><?= $earned_count ?> of <?= count($achievements) ?> titles unlocked</small>
    </div>
</div>

<!-- Achievement List -->
<div class="card">
    <div class="card-header" style="color:var(--gold);"><i class="fas fa-medal"></i> ALL TITLES</div>
    <div class="card-body">
        <?php if (empty($achievements)): ?>
            <p class="text-center" style="color:var(--text-secondary);">No achievements defined yet.</p>
        <?php else: ?>
            <?php foreach ($achievements as $ach):
                $earned = !empty($ach['earned_date']);
                $icon = $rarity_icons[$ach['rarity']] ?? '⚪';
            ?>
            <div class="achievement-card <?= $earned ? 'earned' : 'locked' ?>">
                <div class="achievement-icon"><?= $icon ?></div>
                <div style="flex:1;">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;">
                        <div>
                            <div style="font-family:'Orbitron',monospace;font-size:0.85rem;font-weight:700;color:<?= $earned ? 'var(--success-green)' : 'var(--text-primary)' ?>;">
                                <?= htmlspecialchars($ach['achievement_name']) ?>
                                <?php if ($earned): ?><i class="fas fa-check-circle ms-2" style="color:var(--success-green);font-size:0.7rem;"></i><?php endif; ?>
                            </div>
                            <div style="color:var(--text-secondary);font-size:0.82rem;margin-top:2px;"><?= htmlspecialchars($ach['description']) ?></div>
                            <div style="margin-top:6px;font-size:0.75rem;color:var(--text-secondary);">
                                <span style="color:var(--warning-yellow);margin-right:12px;"><i class="fas fa-star me-1"></i>+<?= $ach['xp_reward'] ?> XP</span>
                                <span style="color:var(--accent-blue);"><i class="fas fa-coins me-1"></i>+<?= $ach['coin_reward'] ?> Coins</span>
                            </div>
                            <?php if ($earned): ?>
                                <div style="font-size:0.7rem;color:var(--text-secondary);margin-top:4px;">
                                    Earned: <?= date('M d, Y', strtotime($ach['earned_date'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div style="text-align:right;">
                            <span class="achievement-rarity rarity-<?= $ach['rarity'] ?>"><?= $ach['rarity'] ?></span>
                            <?php if (!$earned): ?>
                                <div style="font-size:0.7rem;color:var(--text-secondary);margin-top:6px;">
                                    <?= ucfirst($ach['requirement_type']) ?>: <?= $ach['requirement_value'] ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
