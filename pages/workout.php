<?php
require_once '../includes/functions.php';
requireLogin();
$user = getCurrentUser();
$error = '';

$db = new Database(); $conn = $db->getConnection();
$today = date('Y-m-d');
$chkStmt = $conn->prepare("SELECT id FROM workout_logs WHERE user_id=:uid AND workout_date=:d");
$chkStmt->execute([':uid'=>$user['id'],':d'=>$today]);

if ($chkStmt->rowCount() > 0) {
    header('Location: dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $exercises = [
        'pushups'  => intval($_POST['pushups'] ?? 0),
        'situps'   => intval($_POST['situps'] ?? 0),
        'squats'   => intval($_POST['squats'] ?? 0),
        'running'  => floatval($_POST['running'] ?? 0),
    ];
    $result = logWorkout($user['id'], $exercises);
    if ($result['success']) {
        $_SESSION['flash'] = ['message' => "Quest cleared! +{$result['xp_earned']} XP • +{$result['coins_earned']} Shadow Coins", 'type' => 'success'];
        header('Location: dashboard.php');
        exit();
    } else {
        $error = $result['message'];
    }
}
include '../includes/header.php';
?>

<div class="system-header">
    <div class="text-system">[ DAILY QUEST SYSTEM ]</div>
    <h1 style="font-size:1.5rem;margin-top:6px;"><i class="fas fa-scroll" style="color:var(--accent-blue);"></i> DAILY QUEST</h1>
    <p style="color:var(--text-secondary);font-size:0.9rem;margin:4px 0 0;">"Every rep brings you closer to your next rank. The System is watching."</p>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger animate-in"><i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Quest: Preparing to become strong (from SL) -->
<div class="row justify-content-center">
    <div class="col-lg-8">

        <div class="card" style="border-color:rgba(0,212,255,0.4);">
            <div class="card-header" style="justify-content:space-between;">
                <span><i class="fas fa-fire me-2"></i>「 PREPARING TO BECOME STRONG 」</span>
                <span id="totalPctDisplay" style="color:var(--success-green);font-family:'Orbitron',monospace;font-size:0.9rem;">0%</span>
            </div>
            <div class="card-body">
                <form method="POST" id="workoutForm">

                    <div class="row g-3">
                        <!-- Push-ups -->
                        <div class="col-sm-6">
                            <div class="exercise-input-card">
                                <div class="exercise-header">
                                    <div>
                                        <div style="font-size:1.3rem;">💪</div>
                                        <label class="form-label mt-1">Push-ups</label>
                                        <div class="exercise-target">Target: 60 reps</div>
                                    </div>
                                    <div class="exercise-count" id="puVal">0</div>
                                </div>
                                <input type="number" class="form-control" name="pushups" id="pushups" min="0" max="300" value="0">
                                <div class="progress mt-2" style="height:6px;"><div class="progress-bar" id="puBar" style="width:0%;"></div></div>
                            </div>
                        </div>

                        <!-- Sit-ups -->
                        <div class="col-sm-6">
                            <div class="exercise-input-card">
                                <div class="exercise-header">
                                    <div>
                                        <div style="font-size:1.3rem;">🏋️</div>
                                        <label class="form-label mt-1">Sit-ups</label>
                                        <div class="exercise-target">Target: 60 reps</div>
                                    </div>
                                    <div class="exercise-count" id="suVal">0</div>
                                </div>
                                <input type="number" class="form-control" name="situps" id="situps" min="0" max="300" value="0">
                                <div class="progress mt-2" style="height:6px;"><div class="progress-bar" id="suBar" style="width:0%;"></div></div>
                            </div>
                        </div>

                        <!-- Squats -->
                        <div class="col-sm-6">
                            <div class="exercise-input-card">
                                <div class="exercise-header">
                                    <div>
                                        <div style="font-size:1.3rem;">🦵</div>
                                        <label class="form-label mt-1">Squats</label>
                                        <div class="exercise-target">Target: 60 reps</div>
                                    </div>
                                    <div class="exercise-count" id="sqVal">0</div>
                                </div>
                                <input type="number" class="form-control" name="squats" id="squats" min="0" max="300" value="0">
                                <div class="progress mt-2" style="height:6px;"><div class="progress-bar" id="sqBar" style="width:0%;"></div></div>
                            </div>
                        </div>

                        <!-- Running -->
                        <div class="col-sm-6">
                            <div class="exercise-input-card">
                                <div class="exercise-header">
                                    <div>
                                        <div style="font-size:1.3rem;">🏃</div>
                                        <label class="form-label mt-1">Running (km)</label>
                                        <div class="exercise-target">Target: 10 km</div>
                                    </div>
                                    <div class="exercise-count" id="rnVal">0</div>
                                </div>
                                <input type="number" step="0.1" class="form-control" name="running" id="running" min="0" max="100" value="0">
                                <div class="progress mt-2" style="height:6px;"><div class="progress-bar" id="rnBar" style="width:0%;"></div></div>
                            </div>
                        </div>
                    </div>

                    <!-- Overall progress -->
                    <div class="mt-4">
                        <div class="xp-label">
                            <span><i class="fas fa-chart-bar me-1"></i> Overall Quest Completion</span>
                            <span id="totalPctText" style="color:var(--accent-blue);">0%</span>
                        </div>
                        <div class="progress xl">
                            <div class="progress-bar xl" id="totalBar" style="width:0%;"></div>
                        </div>
                    </div>

                    <!-- Rewards preview -->
                    <div style="background:rgba(0,0,0,0.3);border:1px solid var(--border-blue);border-radius:6px;padding:14px;margin-top:16px;">
                        <div style="font-size:0.75rem;color:var(--text-secondary);letter-spacing:2px;text-transform:uppercase;margin-bottom:8px;">Potential Rewards</div>
                        <div style="display:flex;gap:16px;flex-wrap:wrap;">
                            <span style="color:var(--gold);font-weight:700;"><i class="fas fa-star me-1"></i>Base: 100 XP</span>
                            <span style="color:var(--success-green);font-weight:700;"><i class="fas fa-crown me-1"></i>Perfect: +50 XP</span>
                            <span style="color:var(--accent-blue);font-weight:700;"><i class="fas fa-coins me-1"></i>Coins: 10–15</span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 btn-lg mt-4" id="submitBtn" style="letter-spacing:3px;">
                        <i class="fas fa-check-circle"></i> COMPLETE QUEST
                    </button>
                </form>
            </div>
        </div>

        <!-- Rank tips -->
        <div class="card" style="border-color:rgba(124,58,237,0.3);">
            <div class="card-header" style="color:#c084fc;border-color:rgba(124,58,237,0.3);">
                <i class="fas fa-info-circle"></i> RANK ADVANCEMENT TIPS
            </div>
            <div class="card-body">
                <ul style="color:var(--text-secondary);font-size:0.87rem;line-height:2;margin:0;padding-left:20px;">
                    <li>Complete quests daily to maintain your streak</li>
                    <li>Full completion (100%) earns bonus XP and coins</li>
                    <li>Missing days applies a 3% progress penalty</li>
                    <li>You can use 1 rest day per week after 6 consecutive days</li>
                    <li>Shadow soldiers are recruited every 10 completed quests</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
const targets = { pushups: 60, situps: 60, squats: 60, running: 10 };
const fields = ['pushups', 'situps', 'squats', 'running'];
const vals = { pushups: 'puVal', situps: 'suVal', squats: 'sqVal', running: 'rnVal' };
const bars = { pushups: 'puBar', situps: 'suBar', squats: 'sqBar', running: 'rnBar' };

function updateAll() {
    let total = 0;
    fields.forEach(f => {
        const v = parseFloat(document.getElementById(f).value) || 0;
        const pct = Math.min((v / targets[f]) * 100, 100);
        document.getElementById(vals[f]).textContent = v;
        const bar = document.getElementById(bars[f]);
        bar.style.width = pct + '%';
        bar.style.background = pct >= 100 ? 'var(--success-green)' : 'linear-gradient(90deg,var(--accent-purple),var(--accent-blue))';
        total += pct;
    });
    const avg = total / 4;
    const totalBar = document.getElementById('totalBar');
    totalBar.style.width = avg + '%';
    totalBar.style.background = avg >= 100 ? 'linear-gradient(90deg,var(--success-green),var(--accent-blue))' : avg >= 75 ? 'linear-gradient(90deg,var(--accent-blue),var(--accent-purple))' : 'linear-gradient(90deg,var(--accent-purple),var(--accent-blue))';
    const pctText = Math.round(avg) + '%';
    document.getElementById('totalPctText').textContent = pctText;
    document.getElementById('totalPctDisplay').textContent = pctText;
}

fields.forEach(f => document.getElementById(f).addEventListener('input', updateAll));

document.getElementById('workoutForm').addEventListener('submit', function(e) {
    const total = fields.reduce((s, f) => s + (parseFloat(document.getElementById(f).value) || 0), 0);
    if (total === 0) {
        e.preventDefault();
        alert('Complete at least one exercise to submit your quest!');
    }
});
</script>

<?php include '../includes/footer.php'; ?>