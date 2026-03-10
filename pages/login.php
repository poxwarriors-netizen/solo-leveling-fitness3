<?php
require_once '../includes/functions.php';
if (isLoggedIn()) { header('Location: dashboard.php'); exit(); }

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (empty($username) || empty($password)) {
        $error = 'Enter your Hunter name and Gate Key.';
    } else {
        $result = loginUser($username, $password);
        if ($result['success']) { header('Location: dashboard.php'); exit(); }
        else { $error = $result['message']; }
    }
}
include '../includes/header.php';
?>

<div class="auth-wrapper">
    <div style="width:100%;max-width:420px;">

        <!-- System notification bar -->
        <div class="text-center mb-4" style="animation:slideDown 0.4s ease;">
            <div class="text-system" style="color:var(--accent-blue);opacity:0.7;">[ SYSTEM NOTIFICATION ]</div>
            <div style="font-size:0.8rem;color:var(--text-secondary);margin-top:4px;">A new gate has appeared...</div>
        </div>

        <div class="auth-card animate-in">

            <!-- Glitch Loader -->
            <div class="loader">
                <div data-glitch="Loading..." class="glitch">Loading...</div>
            </div>

            <div class="auth-logo">
                <div class="logo-circle">⚔</div>
                <h2 style="font-size:1.1rem;letter-spacing:4px;color:var(--accent-blue);">ENTER THE GATE</h2>
                <p style="font-size:0.8rem;color:var(--text-secondary);margin-top:4px;">Prove your worth, Hunter</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger animate-in">
                    <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-4">
                    <label class="form-label"><i class="fas fa-user me-1"></i> Hunter Name</label>
                    <input type="text" class="form-control" name="username"
                           placeholder="Enter your hunter designation" required
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>

                <div class="mb-4">
                    <label class="form-label"><i class="fas fa-lock me-1"></i> Gate Key</label>
                    <div style="position:relative;">
                        <input type="password" class="form-control" name="password" id="passField"
                               placeholder="Enter your secret key" required>
                        <button type="button" onclick="togglePass()" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-secondary);cursor:pointer;font-size:0.9rem;">
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-glitch mt-2">
                    ⚡ AWAKEN
                </button>
            </form>

            <div class="divider"></div>

            <div class="text-center" style="font-size:0.85rem;color:var(--text-secondary);">
                Not yet awakened? 
                <a href="register.php" style="color:var(--accent-blue);text-decoration:none;font-weight:700;">
                    Begin your journey →
                </a>
            </div>
        </div>

        <!-- System quote -->
        <div class="text-center mt-4" style="animation:slideDown 0.6s ease;">
            <p style="color:var(--text-secondary);font-size:0.85rem;font-style:italic;">
                "The System awakens those with the will to become stronger."
            </p>
            <small style="color:var(--accent-blue);opacity:0.5;font-size:0.7rem;letter-spacing:2px;">— THE SYSTEM</small>
        </div>
    </div>
</div>

<script>
function togglePass() {
    const f = document.getElementById('passField');
    const i = document.getElementById('eyeIcon');
    if (f.type === 'password') { f.type = 'text'; i.className = 'fas fa-eye-slash'; }
    else { f.type = 'password'; i.className = 'fas fa-eye'; }
}
</script>

<?php include '../includes/footer.php'; ?>