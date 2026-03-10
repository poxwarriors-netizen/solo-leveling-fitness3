<?php
require_once '../includes/functions.php';
if (isLoggedIn()) { header('Location: dashboard.php'); exit(); }

$error = ''; $success = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $full_name = sanitize($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    if (empty($username)||empty($email)||empty($full_name)||empty($password)) {
        $error = 'All fields are required to awaken.';
    } elseif ($password !== $confirm_password) {
        $error = 'Gate Keys do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Gate Key must be at least 6 characters.';
    } else {
        $result = registerUser($username, $email, $password, $full_name);
        if ($result['success']) { header('Location: dashboard.php'); exit(); }
        else { $error = $result['message']; }
    }
}
include '../includes/header.php';
?>

<div class="auth-wrapper" style="align-items:flex-start;padding-top:40px;">
    <div style="width:100%;max-width:480px;margin:0 auto;">

        <div class="text-center mb-4">
            <div class="text-system" style="color:var(--accent-blue);opacity:0.7;">[ SYSTEM NOTIFICATION ]</div>
            <div style="font-size:0.8rem;color:var(--text-secondary);margin-top:4px;">Awakening protocol initiated...</div>
        </div>

        <div class="auth-card animate-in">
            <div class="auth-logo">
                <div class="logo-circle">⚡</div>
                <h2 style="font-size:1rem;letter-spacing:4px;color:var(--accent-blue);">AWAKEN YOUR POWER</h2>
                <p style="font-size:0.8rem;color:var(--text-secondary);margin-top:4px;">You have been chosen by the System</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><i class="fas fa-id-card me-1"></i> Full Name</label>
                        <input type="text" class="form-control" name="full_name" placeholder="Your real name" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><i class="fas fa-user me-1"></i> Hunter Name</label>
                        <input type="text" class="form-control" name="username" placeholder="Hunter designation" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label"><i class="fas fa-envelope me-1"></i> Email</label>
                    <input type="email" class="form-control" name="email" placeholder="hunter@association.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><i class="fas fa-lock me-1"></i> Gate Key</label>
                        <input type="password" class="form-control" name="password" placeholder="Min 6 characters" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><i class="fas fa-lock me-1"></i> Confirm Key</label>
                        <input type="password" class="form-control" name="confirm_password" placeholder="Repeat key" required>
                    </div>
                </div>

                <!-- Rank info -->
                <div style="background:rgba(0,0,0,0.3);border:1px solid var(--border-blue);border-radius:6px;padding:14px;margin-bottom:16px;">
                    <p style="font-size:0.78rem;color:var(--text-secondary);margin:0;letter-spacing:0.5px;">
                        <i class="fas fa-info-circle me-1" style="color:var(--accent-blue);"></i>
                        All hunters begin at <span style="color:var(--rank-e);font-weight:700;">E-Rank</span>. 
                        Train daily to rise to <span style="color:var(--rank-s);font-weight:700;">S-Rank Shadow Monarch</span>.
                    </p>
                </div>

                <button type="submit" class="btn btn-primary w-100 btn-lg" style="letter-spacing:3px;">
                    <i class="fas fa-bolt"></i> BEGIN AWAKENING
                </button>
            </form>

            <div class="divider"></div>
            <div class="text-center" style="font-size:0.85rem;color:var(--text-secondary);">
                Already a hunter? <a href="login.php" style="color:var(--accent-blue);text-decoration:none;font-weight:700;">Enter the Gate →</a>
            </div>
        </div>

        <div class="text-center mt-4">
            <p style="color:var(--text-secondary);font-size:0.85rem;font-style:italic;">
                "Prove your worth and rise from E-rank to Shadow Monarch."
            </p>
            <small style="color:var(--accent-blue);opacity:0.5;font-size:0.7rem;letter-spacing:2px;">— THE SYSTEM</small>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>