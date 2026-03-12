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
$available_soldiers = max(0, floor($user['total_workouts'] / 10) - count($army));

include '../includes/header.php';
?>

<!-- Army Page Specific Styles -->
<style>
/* ── ARMY PAGE BACKGROUND ── */
.army-page-wrapper {
    position: relative;
    min-height: calc(100vh - 70px);
    overflow: hidden;
}

.army-bg {
    position: fixed;
    inset: 0;
    background-image: url('../assets/images/shadow_army_bg.png');
    background-size: cover;
    background-position: center top;
    background-repeat: no-repeat;
    z-index: 0;
}

.army-bg::after {
    content: '';
    position: absolute;
    inset: 0;
    background: 
        linear-gradient(to bottom, 
            rgba(5,3,15,0.65) 0%,
            rgba(5,3,15,0.45) 30%,
            rgba(5,3,15,0.6) 70%,
            rgba(5,3,15,0.92) 100%
        ),
        linear-gradient(to right,
            rgba(5,3,15,0.5) 0%,
            transparent 20%,
            transparent 80%,
            rgba(5,3,15,0.5) 100%
        );
}

.army-content {
    position: relative;
    z-index: 2;
    padding: 24px 0 60px;
}

/* ── NEON FRAME (STATUS screen border) ── */
.status-frame {
    position: relative;
    background: rgba(4, 3, 18, 0.88);
    border: 1.5px solid rgba(168, 85, 247, 0.6);
    border-radius: 6px;
    box-shadow: 
        0 0 0 1px rgba(168,85,247,0.1),
        0 0 20px rgba(168,85,247,0.25),
        0 0 60px rgba(168,85,247,0.1),
        inset 0 0 40px rgba(168,85,247,0.03);
    backdrop-filter: blur(16px);
    margin-bottom: 20px;
    overflow: visible;
}

/* Corner sparks */
.status-frame::before,
.status-frame::after {
    content: '';
    position: absolute;
    width: 18px;
    height: 18px;
    border-color: #c084fc;
    border-style: solid;
    z-index: 10;
}
.status-frame::before {
    top: -2px; left: -2px;
    border-width: 3px 0 0 3px;
    box-shadow: -4px -4px 12px rgba(192,132,252,0.6);
}
.status-frame::after {
    bottom: -2px; right: -2px;
    border-width: 0 3px 3px 0;
    box-shadow: 4px 4px 12px rgba(192,132,252,0.6);
}

/* Extra corners via inner wrapper */
.status-frame-inner::before,
.status-frame-inner::after {
    content: '';
    position: absolute;
    width: 18px;
    height: 18px;
    border-color: #c084fc;
    border-style: solid;
    z-index: 10;
}
.status-frame-inner::before {
    top: -2px; right: -2px;
    border-width: 3px 3px 0 0;
    box-shadow: 4px -4px 12px rgba(192,132,252,0.6);
}
.status-frame-inner::after {
    bottom: -2px; left: -2px;
    border-width: 0 0 3px 3px;
    box-shadow: -4px 4px 12px rgba(192,132,252,0.6);
}

/* Animated top edge glow line */
.status-frame .frame-top-line {
    position: absolute;
    top: -1px; left: 10%; right: 10%;
    height: 2px;
    background: linear-gradient(90deg, transparent, #c084fc, #a855f7, #c084fc, transparent);
    box-shadow: 0 0 12px #c084fc, 0 0 24px rgba(192,132,252,0.4);
    animation: frameSweep 4s ease-in-out infinite alternate;
    border-radius: 2px;
    z-index: 5;
}

.status-frame .frame-bottom-line {
    position: absolute;
    bottom: -1px; left: 10%; right: 10%;
    height: 2px;
    background: linear-gradient(90deg, transparent, #7c3aed, #c084fc, #7c3aed, transparent);
    box-shadow: 0 0 10px rgba(124,58,237,0.7);
    animation: frameSweep 4s ease-in-out infinite alternate-reverse;
    border-radius: 2px;
    z-index: 5;
}

@keyframes frameSweep {
    0%   { opacity: 0.6; left: 5%; right: 30%; }
    100% { opacity: 1;   left: 30%; right: 5%; }
}

/* ── STATUS PANEL HEADER ── */
.status-panel-title {
    font-family: 'Orbitron', monospace;
    font-size: 1.5rem;
    font-weight: 900;
    letter-spacing: 8px;
    text-transform: uppercase;
    color: #e2cfff;
    text-shadow: 
        0 0 20px rgba(192,132,252,0.8),
        0 0 40px rgba(124,58,237,0.5);
    text-align: center;
    padding: 20px 24px 14px;
    border-bottom: 1px solid rgba(124,58,237,0.3);
    position: relative;
}

.status-panel-title .title-bracket {
    color: rgba(192,132,252,0.5);
    font-size: 1rem;
    vertical-align: middle;
}

/* ── MAIN STATUS HUD (top stats + level section) ── */
.status-hud {
    display: flex;
    align-items: center;
    gap: 24px;
    padding: 20px 28px;
    border-bottom: 1px solid rgba(124,58,237,0.25);
    flex-wrap: wrap;
}

.hud-level-block {
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 80px;
}

.hud-level-num {
    font-family: 'Orbitron', monospace;
    font-size: 3rem;
    font-weight: 900;
    color: #fff;
    text-shadow: 0 0 20px rgba(192,132,252,0.8), 0 0 40px rgba(192,132,252,0.4);
    line-height: 1;
}

.hud-level-label {
    font-family: 'Orbitron', monospace;
    font-size: 0.65rem;
    letter-spacing: 3px;
    color: #a78bfa;
    margin-top: 4px;
}

.hud-meta {
    flex: 1;
}

.hud-meta-row {
    font-size: 0.9rem;
    color: #c8d8f8;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.hud-meta-row .meta-key {
    color: #6b7280;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    min-width: 50px;
}

.hud-meta-row .meta-val {
    color: #e2cfff;
    font-weight: 700;
}

/* ── HP/MP/PATROL BARS ── */
.bars-section {
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

.bar-group {
    display: flex;
    align-items: center;
    gap: 10px;
}

.bar-icon {
    font-size: 1.1rem;
    color: #c084fc;
}

.bar-icon.mp { color: #60a5fa; }
.bar-icon.patrol { color: #fbbf24; }

.bar-wrapper {
    display: flex;
    flex-direction: column;
    gap: 3px;
    min-width: 100px;
}

.stat-bar {
    height: 6px;
    background: rgba(255,255,255,0.08);
    border-radius: 3px;
    border: 1px solid rgba(255,255,255,0.07);
    overflow: hidden;
    position: relative;
}

.stat-bar-fill {
    height: 100%;
    border-radius: 3px;
    position: relative;
    animation: barShine 2s linear infinite;
}

.stat-bar-fill.hp {
    background: linear-gradient(90deg, #c084fc, #a855f7);
    box-shadow: 0 0 6px rgba(192,132,252,0.5);
}
.stat-bar-fill.mp {
    background: linear-gradient(90deg, #3b82f6, #60a5fa);
    box-shadow: 0 0 6px rgba(96,165,250,0.5);
}

@keyframes barShine {
    0% { filter: brightness(0.9); }
    50% { filter: brightness(1.15); }
    100% { filter: brightness(0.9); }
}

.bar-text {
    font-family: 'Orbitron', monospace;
    font-size: 0.6rem;
    color: #6b7280;
    letter-spacing: 1px;
}

/* ── STATS ATTRIBUTES PANEL ── */
.stats-attributes {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0;
    padding: 0;
    border-top: 1px solid rgba(124,58,237,0.2);
}

.attr-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 20px;
    border-right: 1px solid rgba(124,58,237,0.15);
    border-bottom: 1px solid rgba(124,58,237,0.15);
    transition: background 0.2s;
}

.attr-row:nth-child(even) {
    border-right: none;
}

.attr-row:hover {
    background: rgba(124,58,237,0.06);
}

.attr-icon {
    font-size: 1.1rem;
    opacity: 0.8;
}

.attr-name {
    font-family: 'Orbitron', monospace;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 2px;
    color: #c8d8f8;
    min-width: 32px;
}

.attr-value {
    font-family: 'Orbitron', monospace;
    font-size: 1rem;
    font-weight: 900;
    color: #fff;
}

.attr-bonus {
    font-family: 'Orbitron', monospace;
    font-size: 0.7rem;
    color: #4ade80;
    margin-left: 4px;
}

/* ── RECRUIT PANEL ── */
.recruit-panel {
    background: rgba(30, 10, 60, 0.6);
    border: 1px solid rgba(124,58,237,0.5);
    border-radius: 6px;
    overflow: hidden;
    margin-bottom: 20px;
}

.recruit-header {
    background: rgba(124,58,237,0.15);
    border-bottom: 1px solid rgba(124,58,237,0.4);
    padding: 14px 20px;
    font-family: 'Orbitron', monospace;
    font-size: 0.75rem;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: #c084fc;
    display: flex;
    align-items: center;
    gap: 10px;
}

.recruit-body {
    padding: 20px;
}

.btn-arise {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    padding: 14px 20px;
    background: linear-gradient(135deg, rgba(124,58,237,0.3), rgba(168,85,247,0.2));
    border: 1.5px solid #7c3aed;
    border-radius: 4px;
    color: #e2cfff;
    font-family: 'Orbitron', monospace;
    font-size: 0.85rem;
    font-weight: 700;
    letter-spacing: 3px;
    cursor: pointer;
    text-transform: uppercase;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    box-shadow: 0 0 20px rgba(124,58,237,0.2), inset 0 0 20px rgba(124,58,237,0.05);
    margin-top: 14px;
}

.btn-arise::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, transparent, rgba(192,132,252,0.1), transparent);
    animation: ariseSweep 3s linear infinite;
}

@keyframes ariseSweep {
    0%   { transform: translateX(-100%) skewX(-15deg); }
    100% { transform: translateX(200%) skewX(-15deg); }
}

.btn-arise:hover {
    background: linear-gradient(135deg, rgba(124,58,237,0.5), rgba(168,85,247,0.35));
    box-shadow: 0 0 40px rgba(124,58,237,0.5), inset 0 0 20px rgba(192,132,252,0.1);
    border-color: #c084fc;
    color: #fff;
    transform: translateY(-2px);
    text-shadow: 0 0 10px rgba(192,132,252,0.8);
}

/* ── SOLDIER ARMY GRID (enhanced) ── */
.army-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 14px;
}

.shadow-soldier-card {
    position: relative;
    background: rgba(8, 4, 24, 0.9);
    border: 1px solid rgba(124,58,237,0.4);
    border-radius: 6px;
    padding: 18px 12px;
    text-align: center;
    cursor: default;
    transition: all 0.35s ease;
    overflow: hidden;
}

.shadow-soldier-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 2px;
    background: linear-gradient(90deg, transparent, #7c3aed, #c084fc, #7c3aed, transparent);
    opacity: 0;
    transition: opacity 0.3s;
}
.shadow-soldier-card:hover::before { opacity: 1; }

.shadow-soldier-card::after {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse at 50% 120%, rgba(124,58,237,0.15) 0%, transparent 70%);
    opacity: 0;
    transition: opacity 0.35s;
}
.shadow-soldier-card:hover::after { opacity: 1; }

.shadow-soldier-card:hover {
    border-color: rgba(192,132,252,0.7);
    transform: translateY(-5px);
    box-shadow: 
        0 10px 30px rgba(0,0,0,0.5),
        0 0 20px rgba(124,58,237,0.3),
        0 0 40px rgba(124,58,237,0.1);
}

.ss-avatar {
    font-size: 2.8rem;
    margin-bottom: 10px;
    display: block;
    filter: drop-shadow(0 0 10px rgba(124,58,237,0.7));
    transition: filter 0.3s;
}
.shadow-soldier-card:hover .ss-avatar {
    filter: drop-shadow(0 0 16px rgba(192,132,252,0.9));
}

.ss-name {
    font-family: 'Orbitron', monospace;
    font-size: 0.7rem;
    font-weight: 700;
    color: #c084fc;
    letter-spacing: 2px;
    margin-bottom: 6px;
    text-transform: uppercase;
}

.ss-rank-badge {
    display: inline-block;
    padding: 2px 10px;
    border-radius: 20px;
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    border: 1px solid;
    margin-bottom: 8px;
}
.ss-rank-badge.Shadow  { color: #94a3b8; border-color: #94a3b8; background: rgba(148,163,184,0.1); }
.ss-rank-badge.Knight  { color: #00d4ff; border-color: #00d4ff; background: rgba(0,212,255,0.1); }
.ss-rank-badge.Elite   { color: #c084fc; border-color: #c084fc; background: rgba(192,132,252,0.1); }
.ss-rank-badge.General { color: #fbbf24; border-color: #fbbf24; background: rgba(251,191,36,0.1); }

.ss-power {
    font-family: 'Orbitron', monospace;
    font-size: 1rem;
    font-weight: 900;
    color: #fff;
    text-shadow: 0 0 8px rgba(255,255,255,0.3);
}

.ss-date {
    font-size: 0.65rem;
    color: #4b5563;
    margin-top: 5px;
    letter-spacing: 1px;
}

/* ── RANK INFO CARDS ── */
.rank-info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}

.rank-info-item {
    background: rgba(0,0,0,0.4);
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 6px;
    padding: 12px 14px;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: all 0.25s;
}

.rank-info-item:hover {
    border-color: rgba(124,58,237,0.4);
    background: rgba(124,58,237,0.06);
}

.rank-info-icon { font-size: 1.3rem; }

.rank-info-label {
    font-weight: 700;
    font-size: 0.8rem;
    color: #c084fc;
    letter-spacing: 1px;
}

.rank-info-req {
    font-size: 0.7rem;
    color: #4b5563;
    margin-top: 2px;
}

/* ── AVAILABLE BADGE ── */
.available-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(192,132,252,0.12);
    border: 1px solid rgba(192,132,252,0.4);
    border-radius: 20px;
    padding: 4px 14px;
    font-family: 'Orbitron', monospace;
    font-size: 0.7rem;
    color: #c084fc;
    letter-spacing: 2px;
    animation: badgePulse 2s ease-in-out infinite;
}

@keyframes badgePulse {
    0%, 100% { box-shadow: 0 0 0 rgba(192,132,252,0); }
    50% { box-shadow: 0 0 12px rgba(192,132,252,0.3); }
}

/* ── ARMY STATS QUICK BAR ── */
.army-stats-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    padding: 14px 20px;
    background: rgba(3,2,15,0.8);
    border-bottom: 1px solid rgba(124,58,237,0.2);
}

.army-stat-pill {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(10,5,30,0.9);
    border: 1px solid rgba(124,58,237,0.3);
    border-radius: 4px;
    padding: 8px 14px;
    transition: all 0.2s;
}

.army-stat-pill:hover {
    border-color: rgba(192,132,252,0.5);
    background: rgba(20,10,50,0.9);
}

.army-stat-pill .pill-icon { font-size: 1rem; }
.army-stat-pill .pill-val {
    font-family: 'Orbitron', monospace;
    font-size: 1rem;
    font-weight: 900;
    color: #fff;
}
.army-stat-pill .pill-label {
    font-size: 0.65rem;
    color: #6b7280;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    line-height: 1.2;
}

/* ── EMPTY STATE ── */
.empty-army {
    text-align: center;
    padding: 60px 20px;
}

.empty-army-icon {
    font-size: 4rem;
    opacity: 0.2;
    display: block;
    margin-bottom: 16px;
    animation: ghostFloat 3s ease-in-out infinite;
}

@keyframes ghostFloat {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

/* ── SECTION LABEL ── */
.section-label {
    font-family: 'Orbitron', monospace;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 4px;
    text-transform: uppercase;
    color: #c084fc;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-label::after {
    content: '';
    flex: 1;
    height: 1px;
    background: linear-gradient(90deg, rgba(192,132,252,0.4), transparent);
}

/* ── PATROL BADGE ── */
.patrol-badge {
    display: flex;
    flex-direction: column;
    align-items: center;
    background: rgba(252,191,36,0.1);
    border: 1px solid rgba(251,191,36,0.3);
    border-radius: 6px;
    padding: 8px 14px;
    min-width: 80px;
    text-align: center;
}
.patrol-badge .patrol-icon { font-size: 1.5rem; margin-bottom: 2px; }
.patrol-badge .patrol-label {
    font-size: 0.55rem;
    letter-spacing: 2px;
    color: #fbbf24;
    font-weight: 700;
    text-transform: uppercase;
}
.patrol-badge .patrol-count {
    font-family: 'Orbitron', monospace;
    font-size: 0.75rem;
    color: #fbbf24;
}
</style>

<div class="army-page-wrapper">
    <!-- Background Layer -->
    <div class="army-bg"></div>

    <!-- Content -->
    <div class="army-content">
        <div class="container">

            <?php if ($recruit_msg): list($type, $msg) = explode('|', $recruit_msg, 2); ?>
            <div class="alert alert-<?= $type === 'success' ? 'success' : 'danger' ?> animate-in mb-3" style="position:relative;z-index:3;">
                <i class="fas fa-<?= $type === 'success' ? 'check-circle' : 'times-circle' ?> me-2"></i><?= htmlspecialchars($msg) ?>
            </div>
            <?php endif; ?>

            <!-- ════════════════════════════════════════════
                 MAIN STATUS FRAME  (mimics in-game STATUS panel)
                 ════════════════════════════════════════════ -->
            <div class="status-frame">
                <div class="status-frame-inner" style="position:relative;">
                    <span class="frame-top-line"></span>
                    <span class="frame-bottom-line"></span>

                    <!-- Panel Title -->
                    <div class="status-panel-title">
                        <span class="title-bracket">『</span>
                        SHADOW ARMY STATUS
                        <span class="title-bracket">』</span>
                    </div>

                    <!-- Quick Army Stats Bar -->
                    <div class="army-stats-bar">
                        <div class="army-stat-pill">
                            <span class="pill-icon">👥</span>
                            <div>
                                <div class="pill-val"><?= count($army) ?></div>
                                <div class="pill-label">Total Soldiers</div>
                            </div>
                        </div>
                        <div class="army-stat-pill">
                            <span class="pill-icon">⚡</span>
                            <div>
                                <div class="pill-val"><?= number_format($total_power) ?></div>
                                <div class="pill-label">Army Power</div>
                            </div>
                        </div>
                        <?php foreach ($soldier_types as $rank => $count): 
                            $icons = ['Shadow'=>'👤','Knight'=>'⚔','Elite'=>'🛡','General'=>'👑'];
                        ?>
                        <div class="army-stat-pill">
                            <span class="pill-icon"><?= $icons[$rank] ?? '🗡' ?></span>
                            <div>
                                <div class="pill-val"><?= $count ?></div>
                                <div class="pill-label"><?= $rank ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <?php if ($available_soldiers > 0): ?>
                        <div class="ms-auto d-flex align-items-center">
                            <span class="available-badge">
                                <i class="fas fa-plus"></i>
                                <?= $available_soldiers ?> AVAILABLE
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- HUD Level + Meta + Bars -->
                    <div class="status-hud">
                        <!-- Level -->
                        <div class="hud-level-block">
                            <div class="hud-level-num"><?= min(99, floor($user['total_workouts'] / 5) + 1) ?></div>
                            <div class="hud-level-label">LEVEL</div>
                        </div>

                        <!-- Separator -->
                        <div style="width:1px;height:60px;background:rgba(124,58,237,0.3);flex-shrink:0;"></div>

                        <!-- Job + Title -->
                        <div class="hud-meta">
                            <div class="hud-meta-row">
                                <span class="meta-key">JOB:</span>
                                <span class="meta-val">Shadow Monarch</span>
                            </div>
                            <div class="hud-meta-row">
                                <span class="meta-key">GUILD:</span>
                                <span class="meta-val"><?= htmlspecialchars($user['username'] ?? 'Solo Hunter') ?></span>
                            </div>
                            <div class="hud-meta-row">
                                <span class="meta-key">RANK:</span>
                                <span class="meta-val" style="color:var(--rank-s);text-shadow:0 0 8px var(--rank-s);">
                                    <?= htmlspecialchars($user['rank'] ?? 'E') ?>
                                </span>
                            </div>
                        </div>

                        <!-- Separator -->
                        <div style="width:1px;height:60px;background:rgba(124,58,237,0.3);flex-shrink:0;display:none;" class="d-md-block"></div>

                        <!-- HP/MP Bars -->
                        <div class="bars-section">
                            <div class="bar-group">
                                <span class="bar-icon">✙</span>
                                <div class="bar-wrapper">
                                    <div class="stat-bar" style="width:120px;">
                                        <div class="stat-bar-fill hp" style="width:<?= min(100, ($user['total_workouts'] ?? 0) % 100) ?>%;"></div>
                                    </div>
                                    <div class="bar-text">HP <?= ($user['total_workouts'] ?? 0) * 100 ?>/<?= ($user['total_workouts'] ?? 0) * 100 ?></div>
                                </div>
                            </div>
                            <div class="bar-group">
                                <span class="bar-icon mp">💧</span>
                                <div class="bar-wrapper">
                                    <div class="stat-bar" style="width:120px;">
                                        <div class="stat-bar-fill mp" style="width:<?= min(100, ($user['xp'] ?? 0) % 100) ?>%;"></div>
                                    </div>
                                    <div class="bar-text">MP <?= ($user['xp'] ?? 0) ?>/<?= max(100, ($user['xp'] ?? 0)) ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Patrol badge -->
                        <div class="patrol-badge ms-auto">
                            <div class="patrol-icon">🗺</div>
                            <div class="patrol-label">Patrol</div>
                            <div class="patrol-count"><?= count($army) ?> Active</div>
                        </div>
                    </div>

                    <!-- Attribute rows (STR/AGI/INT/VIT/PER) -->
                    <div class="stats-attributes">
                        <?php
                        $tw = $user['total_workouts'] ?? 0;
                        $xp = $user['xp'] ?? 0;
                        $base_str = 100 + ($tw * 3);
                        $base_agi = 100 + ($tw * 2.5);
                        $base_int = 100 + ($xp / 50);
                        $base_vit = 100 + ($tw * 2.8);
                        $base_per = 100 + ($tw * 2.2);
                        $bonus = count($army) * 5;
                        $attrs = [
                            ['icon'=>'↔','key'=>'STR','val'=>round($base_str),'bonus'=>$bonus],
                            ['icon'=>'❤','key'=>'VIT','val'=>round($base_vit),'bonus'=>$bonus],
                            ['icon'=>'🏃','key'=>'AGI','val'=>round($base_agi),'bonus'=>round($bonus*1.2)],
                            ['icon'=>'🧠','key'=>'INT','val'=>round($base_int),'bonus'=>$bonus],
                            ['icon'=>'👁','key'=>'PER','val'=>round($base_per),'bonus'=>$bonus],
                        ];
                        foreach ($attrs as $i => $a):
                        ?>
                        <div class="attr-row <?= ($i % 2 == 0 && $i == count($attrs)-1) ? 'colspan-2' : '' ?>">
                            <span class="attr-icon"><?= $a['icon'] ?></span>
                            <span class="attr-name"><?= $a['key'] ?>:</span>
                            <span class="attr-value"><?= $a['val'] ?></span>
                            <span class="attr-bonus">(+<?= $a['bonus'] ?>)</span>
                        </div>
                        <?php endforeach; ?>

                        <!-- Available Points slot -->
                        <div class="attr-row" style="flex-direction:column;align-items:flex-start;">
                            <div style="font-size:0.6rem;color:#4b5563;letter-spacing:2px;text-transform:uppercase;line-height:1.3;">
                                Available<br>Mana<br>Release
                            </div>
                            <div style="font-family:'Orbitron',monospace;font-size:1.5rem;font-weight:900;color:#fff;margin-top:4px;">
                                <?= $available_soldiers ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- end status-frame -->

            <!-- ════════════════════════════════════════════
                 LOWER SECTION: Recruit + Ranks | Army Grid
                 ════════════════════════════════════════════ -->
            <div class="row g-3">

                <!-- Recruit + Ranks column -->
                <div class="col-md-4">
                    <!-- Extraction Panel -->
                    <div class="recruit-panel">
                        <div class="recruit-header">
                            <i class="fas fa-magic"></i> SHADOW EXTRACTION
                        </div>
                        <div class="recruit-body">
                            <p style="color:var(--text-secondary);font-size:0.88rem;line-height:1.6;">
                                Every <strong style="color:#c084fc;">10 completed quests</strong> unlocks a new shadow soldier.
                                You've completed <strong style="color:var(--accent-blue);"><?= $tw ?></strong> quests.
                            </p>
                            <div style="margin-top:12px;font-size:0.8rem;color:#6b7280;">
                                Soldiers available: 
                                <strong style="color:#c084fc;font-size:1rem;"><?= $available_soldiers ?></strong>
                            </div>
                            <form method="POST">
                                <button type="submit" name="recruit" class="btn-arise">
                                    <i class="fas fa-bolt"></i> ARISE! — EXTRACT SHADOW
                                </button>
                            </form>
                            <div style="margin-top:14px;font-size:0.75rem;color:#4b5563;display:flex;align-items:center;gap:6px;">
                                <i class="fas fa-info-circle" style="color:var(--accent-blue);"></i>
                                Soldier ranks increase with your total workouts.
                            </div>
                        </div>
                    </div>

                    <!-- Rank Info -->
                    <div class="recruit-panel">
                        <div class="recruit-header">
                            <i class="fas fa-star"></i> SOLDIER RANKS
                        </div>
                        <div class="recruit-body">
                            <div class="rank-info-grid">
                                <?php
                                $ranks_info = [
                                    ['Shadow','👤','#94a3b8','0–24 quests'],
                                    ['Knight','⚔','#00d4ff','25–49 quests'],
                                    ['Elite','🛡','#c084fc','50–74 quests'],
                                    ['General','👑','#fbbf24','75+ quests'],
                                ];
                                foreach ($ranks_info as [$r,$ic,$col,$req]):
                                ?>
                                <div class="rank-info-item">
                                    <span class="rank-info-icon"><?= $ic ?></span>
                                    <div>
                                        <div class="rank-info-label" style="color:<?= $col ?>;"><?= $r ?></div>
                                        <div class="rank-info-req"><?= $req ?></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Army Grid column -->
                <div class="col-md-8">
                    <div class="status-frame" style="margin-bottom:0;">
                        <div class="status-frame-inner" style="position:relative;">
                            <span class="frame-top-line"></span>
                            <span class="frame-bottom-line"></span>
                            <div class="status-panel-title" style="font-size:0.9rem;padding:14px 20px 12px;">
                                <span class="title-bracket">『</span>
                                YOUR SHADOW ARMY &nbsp;
                                <span style="color:#4b5563;font-size:0.7rem;">(<?= count($army) ?> soldiers)</span>
                                <span class="title-bracket">』</span>
                            </div>
                            <div style="padding:20px;">
                                <?php if (empty($army)): ?>
                                <div class="empty-army">
                                    <span class="empty-army-icon">👤</span>
                                    <p style="color:var(--text-secondary);font-size:0.95rem;">No shadows yet.</p>
                                    <p style="font-size:0.82rem;color:#374151;margin-top:6px;">Complete 10 daily quests to unlock your first shadow soldier.</p>
                                </div>
                                <?php else: ?>
                                <div class="army-grid">
                                    <?php
                                    $avatars = ['Igris'=>'⚔','Beru'=>'🐝','Iron'=>'🛡','Tank'=>'💪','Jima'=>'🗡','Kaisel'=>'🐉','Bellion'=>'👾'];
                                    foreach ($army as $s):
                                        $av = $avatars[$s['soldier_name']] ?? '👤';
                                    ?>
                                    <div class="shadow-soldier-card">
                                        <span class="ss-avatar"><?= $av ?></span>
                                        <div class="ss-name"><?= htmlspecialchars($s['soldier_name']) ?></div>
                                        <div>
                                            <span class="ss-rank-badge <?= htmlspecialchars($s['soldier_rank']) ?>">
                                                <?= htmlspecialchars($s['soldier_rank']) ?>
                                            </span>
                                        </div>
                                        <div class="ss-power">⚡ <?= $s['power_level'] ?></div>
                                        <div class="ss-date">Recruited <?= date('M d', strtotime($s['recruited_date'])) ?></div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

            </div><!-- end row -->

        </div><!-- end container -->
    </div><!-- end army-content -->
</div><!-- end army-page-wrapper -->

<?php include '../includes/footer.php'; ?>
