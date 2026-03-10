<?php
require_once '../includes/functions.php';
requireLogin();
$user = getCurrentUser();
$db = new Database(); $conn = $db->getConnection();

// Handle guild creation/joining
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_guild'])) {
        $gname = sanitize($_POST['guild_name'] ?? '');
        $gdesc = sanitize($_POST['description'] ?? '');
        if (empty($gname)) { $msg = 'error|Guild name is required.'; }
        else {
            $res = createGuild($user['id'], $gname, $gdesc);
            $msg = $res['success'] ? 'success|Guild created!' : 'error|'.$res['message'];
        }
    } elseif (isset($_POST['join_guild'])) {
        $gid = intval($_POST['guild_id']);
        // Check already in guild
        $chk = $conn->prepare("SELECT guild_id FROM guild_members WHERE user_id=:uid");
        $chk->execute([':uid'=>$user['id']]);
        if ($chk->rowCount() > 0) { $msg = 'error|You are already in a guild.'; }
        else {
            $conn->prepare("INSERT INTO guild_members (guild_id,user_id) VALUES (?,?)")->execute([$gid, $user['id']]);
            $conn->prepare("UPDATE guilds SET member_count=member_count+1 WHERE id=?")->execute([$gid]);
            $msg = 'success|You have joined the guild!';
        }
    } elseif (isset($_POST['leave_guild'])) {
        $gid = intval($_POST['guild_id']);
        $conn->prepare("DELETE FROM guild_members WHERE user_id=? AND guild_id=?")->execute([$user['id'],$gid]);
        $conn->prepare("UPDATE guilds SET member_count=GREATEST(member_count-1,0) WHERE id=?")->execute([$gid]);
        $msg = 'success|You left the guild.';
    }
}

// Get my guild
$myGuildStmt = $conn->prepare("SELECT g.*,gm.role FROM guilds g JOIN guild_members gm ON g.id=gm.guild_id WHERE gm.user_id=:uid LIMIT 1");
$myGuildStmt->execute([':uid'=>$user['id']]);
$myGuild = $myGuildStmt->fetch();

// Get all guilds
$allGuildsStmt = $conn->prepare("SELECT g.*,u.username as leader_name FROM guilds g JOIN users u ON g.guild_leader_id=u.id ORDER BY g.total_xp DESC LIMIT 20");
$allGuildsStmt->execute();
$allGuilds = $allGuildsStmt->fetchAll();

// If in guild, get members
$members = [];
if ($myGuild) {
    $memStmt = $conn->prepare("SELECT u.username,u.current_rank,u.total_xp,u.current_streak,gm.role,gm.joined_date FROM guild_members gm JOIN users u ON gm.user_id=u.id WHERE gm.guild_id=:gid ORDER BY u.total_xp DESC");
    $memStmt->execute([':gid'=>$myGuild['id']]);
    $members = $memStmt->fetchAll();
}

include '../includes/header.php';
$rank_colors = ['E'=>'#6b7280','D'=>'#10b981','C'=>'#3b82f6','B'=>'#8b5cf6','A'=>'#f59e0b','S'=>'#ef4444'];
?>

<div class="system-header">
    <div class="text-system">[ HUNTER GUILD SYSTEM ]</div>
    <h1 style="font-size:1.5rem;margin-top:6px;"><i class="fas fa-users" style="color:#c084fc;"></i> GUILDS</h1>
    <p style="color:var(--text-secondary);font-size:0.9rem;margin:4px 0 0;">"Train together. Rise together. Conquer together."</p>
</div>

<?php if ($msg): list($t,$m)=explode('|',$msg,2); ?>
    <div class="alert alert-<?= $t==='success'?'success':'danger' ?> animate-in">
        <i class="fas fa-<?= $t==='success'?'check-circle':'times-circle' ?> me-2"></i><?= htmlspecialchars($m) ?>
    </div>
<?php endif; ?>

<?php if ($myGuild): ?>
<!-- MY GUILD -->
<div class="card mb-4" style="border-color:rgba(124,58,237,0.5);">
    <div class="card-header" style="background:rgba(124,58,237,0.15);border-color:rgba(124,58,237,0.4);color:#c084fc;">
        <i class="fas fa-shield-alt"></i> YOUR GUILD
    </div>
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h3 style="font-size:1.2rem;color:#c084fc;margin-bottom:4px;"><?= htmlspecialchars($myGuild['guild_name']) ?></h3>
                <p style="color:var(--text-secondary);font-size:0.9rem;margin-bottom:12px;"><?= htmlspecialchars($myGuild['description'] ?: 'No description set.') ?></p>
                <div style="display:flex;gap:20px;font-size:0.85rem;flex-wrap:wrap;">
                    <span><i class="fas fa-users me-1" style="color:var(--accent-blue);"></i><?= $myGuild['member_count'] ?> members</span>
                    <span><i class="fas fa-star me-1" style="color:var(--gold);"></i><?= number_format($myGuild['total_xp']) ?> XP</span>
                    <span style="color:#c084fc;font-weight:700;">Your Role: <?= $myGuild['role'] ?></span>
                </div>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <form method="POST" onsubmit="return confirm('Leave this guild?');">
                    <input type="hidden" name="guild_id" value="<?= $myGuild['id'] ?>">
                    <button type="submit" name="leave_guild" class="btn btn-danger btn-sm">
                        <i class="fas fa-door-open"></i> Leave Guild
                    </button>
                </form>
            </div>
        </div>

        <!-- Members -->
        <div class="divider"></div>
        <h5 style="font-size:0.8rem;letter-spacing:2px;color:var(--accent-blue);margin-bottom:12px;">MEMBERS (<?= count($members) ?>)</h5>
        <div class="table-responsive">
            <table class="table table-borderless mb-0">
                <thead><tr>
                    <th>Hunter</th><th>Rank</th><th>XP</th><th>Streak</th><th>Role</th>
                </tr></thead>
                <tbody>
                <?php foreach ($members as $m): $rc = $rank_colors[$m['current_rank']]; ?>
                    <tr>
                        <td style="font-weight:700;"><?= htmlspecialchars($m['username']) ?></td>
                        <td><span style="color:<?= $rc ?>;font-weight:700;font-family:'Orbitron',monospace;"><?= $m['current_rank'] ?></span></td>
                        <td><?= number_format($m['total_xp']) ?></td>
                        <td>🔥 <?= $m['current_streak'] ?></td>
                        <td><span style="font-size:0.75rem;color:var(--text-secondary);"><?= $m['role'] ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php else: ?>
<!-- CREATE GUILD -->
<div class="row mb-4">
    <div class="col-md-5 mb-3">
        <div class="card" style="border-color:rgba(124,58,237,0.4);">
            <div class="card-header" style="color:#c084fc;border-color:rgba(124,58,237,0.4);">
                <i class="fas fa-plus-circle"></i> FOUND A GUILD
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Guild Name</label>
                        <input type="text" class="form-control" name="guild_name" placeholder="e.g. Ahjin Guild" required maxlength="50">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3" placeholder="Guild motto or description" maxlength="200"></textarea>
                    </div>
                    <button type="submit" name="create_guild" class="btn btn-purple w-100">
                        <i class="fas fa-shield-alt"></i> FOUND GUILD
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-7 mb-3">
        <div class="card">
            <div class="card-header"><i class="fas fa-info-circle"></i> GUILD SYSTEM</div>
            <div class="card-body">
                <ul style="color:var(--text-secondary);font-size:0.87rem;line-height:2;margin:0;padding-left:20px;">
                    <li>Create or join a Hunter Guild</li>
                    <li>Share XP contributions with guild members</li>
                    <li>Compete in guild vs guild rankings</li>
                    <li>Guild rank depends on collective member XP</li>
                    <li>Leaders can promote/kick members</li>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ALL GUILDS -->
<div class="card">
    <div class="card-header" style="color:var(--accent-blue);"><i class="fas fa-list"></i> ALL GUILDS (TOP 20)</div>
    <div class="card-body" style="padding:12px;">
        <?php if (empty($allGuilds)): ?>
            <p class="text-center" style="color:var(--text-secondary);padding:20px;">No guilds exist yet. Be the first to found one!</p>
        <?php else: ?>
            <?php foreach ($allGuilds as $i => $g): $isMe = $myGuild && $myGuild['id'] == $g['id']; ?>
            <div class="leaderboard-row <?= $i<3?'top-'.($i+1):'' ?>" style="<?= $isMe ? 'border-color:rgba(124,58,237,0.5);background:rgba(124,58,237,0.05);' : '' ?>">
                <div class="leaderboard-rank <?= $i===0?'rank-1':($i===1?'rank-2':($i===2?'rank-3':'')) ?>">
                    <?= $i<3 ? ['🥇','🥈','🥉'][$i] : '#'.($i+1) ?>
                </div>
                <div style="flex:1;">
                    <div style="font-weight:700;color:<?= $isMe?'#c084fc':'var(--text-primary)' ?>;">
                        <?= htmlspecialchars($g['guild_name']) ?>
                        <?php if ($isMe): ?><span style="font-size:0.65rem;color:#c084fc;margin-left:6px;">[YOUR GUILD]</span><?php endif; ?>
                    </div>
                    <div style="font-size:0.75rem;color:var(--text-secondary);">
                        Leader: <?= htmlspecialchars($g['leader_name']) ?> &nbsp;|&nbsp; <?= $g['member_count'] ?> members
                    </div>
                </div>
                <div style="text-align:right;margin-right:12px;">
                    <div style="font-family:'Orbitron',monospace;font-weight:900;font-size:0.9rem;color:#fff;"><?= number_format($g['total_xp']) ?></div>
                    <div style="font-size:0.65rem;color:var(--text-secondary);">GUILD XP</div>
                </div>
                <?php if (!$myGuild): ?>
                <div>
                    <form method="POST">
                        <input type="hidden" name="guild_id" value="<?= $g['id'] ?>">
                        <button type="submit" name="join_guild" class="btn btn-purple btn-sm">JOIN</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
