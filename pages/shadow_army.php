<?php
require_once '../includes/functions.php';
requireLogin();

$user = getCurrentUser();
$db = new Database(); $conn = $db->getConnection();

// Get shadow army
$armyStmt = $conn->prepare("SELECT * FROM shadow_army WHERE user_id = :uid ORDER BY power_level DESC");
$armyStmt->execute([':uid' => $user['id']]);
$army = $armyStmt->fetchAll();

// Recruit attempt
$recruit_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recruit'])) {
    $res = recruitShadowSoldier($user['id']);
    $recruit_msg = $res['success'] ? "success|Soldier {$res['soldier']} has answered your call!" : "error|{$res['message']}";
}

// Stats
$soldier_types = ['Shadow'=>0,'Knight'=>0,'Elite'=>0,'General'=>0];
foreach ($army as $s) { if (isset($soldier_types[$s['soldier_rank']])) $soldier_types[$s['soldier_rank']]++; }
$total_power = array_sum(array_column($army, 'power_level'));

include '../includes/header.php';
?>

<!-- Page Header -->
<div class="system-header">
    <div class="text-system">[ SHADOW EXTRACTION SYSTEM ]</div>
    <h1 style="font-size:1.5rem;margin-top:6px;color:var(--text-primary);">
        <i class="fas fa-ghost" style="color:#c084fc;"></i> SHADOW ARMY
    </h1>
    <p style="color:var(--text-secondary);font-size:0.9rem;margin:4px 0 0;">
        "From the ashes of the fallen, I raise my shadows."
    </p>
</div>

<?php if ($recruit_msg): list($type, $msg) = explode('|', $recruit_msg, 2); ?>
    <div class="alert alert-<?= $type === 'success' ? 'success' : 'danger' ?> animate-in">
        <i class="fas fa-<?= $type === 'success' ? 'check-circle' : 'times-circle' ?> me-2"></i><?= htmlspecialchars($msg) ?>
    </div>
<?php endif; ?>

<!-- Army Stats -->
<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(140px,1fr));">
    <div class="stat-card">
        <div class="stat-icon" style="color:#c084fc;">👥</div>
        <div class="stat-value" data-target="<?= count($army) ?>"><?= count($army) ?></div>
        <div class="stat-label">Total Soldiers</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="color:var(--accent-blue);">⚡</div>
        <div class="stat-value" data-target="<?= $total_power ?>"><?= $total_power ?></div>
        <div class="stat-label">Army Power</div>
    </div>
    <?php foreach ($soldier_types as $rank => $count): ?>
    <div class="stat-card">
        <div class="stat-icon">🗡</div>
        <div class="stat-value"><?= $count ?></div>
        <div class="stat-label"><?= $rank ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Recruit + Info -->
<div class="row mb-4">
    <div class="col-md-5 mb-3">
        <div class="card" style="border-color:rgba(124,58,237,0.5);">
            <div class="card-header" style="background:rgba(124,58,237,0.15);border-color:rgba(124,58,237,0.4);color:#c084fc;">
                <i class="fas fa-plus-circle"></i> SHADOW EXTRACTION
            </div>
            <div class="card-body">
                <p style="color:var(--text-secondary);font-size:0.9rem;margin-bottom:16px;">
                    Every 10 completed quests unlocks a new shadow soldier. 
                    You've completed <strong style="color:var(--accent-blue);"><?= $user['total_workouts'] ?></strong> quests.
                </p>
                <p style="font-size:0.85rem;color:var(--text-secondary);">
                    Soldiers available: <strong style="color:#c084fc;"><?= max(0, floor($user['total_workouts'] / 10) - count($army)) ?></strong>
                </p>
                <form method="POST" class="mt-3">
                    <button type="submit" name="recruit" class="btn btn-purple w-100">
                        <i class="fas fa-magic"></i> ARISE! EXTRACT SHADOW
                    </button>
                </form>
                <div class="divider"></div>
                <div style="font-size:0.78rem;color:var(--text-secondary);">
                    <i class="fas fa-info-circle me-1" style="color:var(--accent-blue);"></i>
                    Soldier ranks increase with your total workouts.
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-7 mb-3">
        <div class="card">
            <div class="card-header"><i class="fas fa-star"></i> SOLDIER RANKS</div>
            <div class="card-body">
                <div class="row g-2">
                    <?php
                    $ranks_info = [
                        ['Shadow','👤','#94a3b8','0-24 workouts','Basic shadow soldier'],
                        ['Knight','⚔','var(--accent-blue)','25-49 workouts','Enhanced combat ability'],
                        ['Elite','🛡','#c084fc','50-74 workouts','Elite commander unit'],
                        ['General','👑','var(--gold)','75+ workouts','Supreme shadow general'],
                    ];
                    foreach ($ranks_info as [$r,$ic,$col,$req,$desc]): ?>
                    <div class="col-6">
                        <div style="background:rgba(0,0,0,0.3);border:1px solid rgba(255,255,255,0.06);border-radius:6px;padding:12px;">
                            <div style="font-size:1.3rem;"><?= $ic ?></div>
                            <div style="font-weight:700;color:<?= $col ?>;font-size:0.85rem;"><?= $r ?></div>
                            <div style="font-size:0.7rem;color:var(--text-secondary);"><?= $req ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Army Grid -->
<div class="card">
    <div class="card-header" style="color:#c084fc;">
        <i class="fas fa-ghost"></i> YOUR SHADOW ARMY (<?= count($army) ?> soldiers)
    </div>
    <div class="card-body">
        <?php if (empty($army)): ?>
            <div class="text-center py-5">
                <div style="font-size:3rem;opacity:0.3;">👤</div>
                <p style="color:var(--text-secondary);margin-top:12px;">No shadows yet. Complete quests to extract your first soldier.</p>
                <p style="font-size:0.85rem;color:var(--text-secondary);">Complete 10 daily quests to unlock your first shadow.</p>
            </div>
        <?php else: ?>
            <div class="soldier-grid">
                <?php
                $avatars = ['Igris'=>'⚔','Beru'=>'🐝','Iron'=>'🛡','Tank'=>'💪','Jima'=>'🗡','Kaisel'=>'🐉','Bellion'=>'👾'];
                foreach ($army as $s):
                    $av = $avatars[$s['soldier_name']] ?? '👤';
                ?>
                <div class="soldier-card">
                    <div class="soldier-avatar"><?= $av ?></div>
                    <div class="soldier-name"><?= htmlspecialchars($s['soldier_name']) ?></div>
                    <div class="mb-2">
                        <span class="soldier-rank-tag <?= htmlspecialchars($s['soldier_rank']) ?>"><?= htmlspecialchars($s['soldier_rank']) ?></span>
                    </div>
                    <div class="soldier-power">⚡ <?= $s['power_level'] ?></div>
                    <div style="font-size:0.7rem;color:var(--text-secondary);margin-top:6px;">
                        Recruited <?= date('M d', strtotime($s['recruited_date'])) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
