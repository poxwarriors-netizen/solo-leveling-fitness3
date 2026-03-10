<?php
require_once '../includes/functions.php';
requireLogin();

$user = getCurrentUser();
$partners = getEligiblePartners($user['current_rank']);

include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-gem"></i> HUNTER'S PRIVILEGE STORE</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="alert alert-info">
                            <h5>Your Rank: <span class="rank-badge <?php echo $user['current_rank']; ?>"><?php echo $user['current_rank']; ?></span></h5>
                            <p class="mb-0">Higher ranks unlock better discounts and universal partners!</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-success">
                            <h5>Shadow Coins: <span class="glow-text"><?php echo $user['shadow_coins']; ?></span></h5>
                            <p class="mb-0">Use coins to recover from penalties or buy exclusive items.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Fitness Partners (All Ranks) -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-dumbbell"></i> FITNESS PARTNERS (E-RANK AND ABOVE)</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($partners['fitness'] as $partner): ?>
                        <div class="col-md-4 col-lg-3">
                            <div class="partner-card">
                                <div class="partner-name"><?php echo $partner['partner_name']; ?></div>
                                <div class="partner-discount"><?php echo $partner['discount_percent']; ?>% OFF</div>
                                <p class="small"><?php echo $partner['description']; ?></p>
                                <span class="partner-category category-fitness">FITNESS</span>
                                <small class="d-block mt-2">Min Rank: <?php echo $partner['min_rank_required']; ?></small>
                                <a href="<?php echo $partner['affiliate_link']; ?>" 
                                   class="btn btn-primary btn-sm mt-3 w-100" 
                                   target="_blank"
                                   onclick="trackAffiliate(<?php echo $partner['id']; ?>)">
                                    SHOP NOW
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Universal Partners (A/S Only) -->
<?php if (!empty($partners['universal'])): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card" style="border-color: var(--accent-purple);">
            <div class="card-header" style="background: linear-gradient(90deg, var(--accent-purple), var(--accent-blue));">
                <h4><i class="fas fa-globe"></i> UNIVERSAL PARTNERS (A/S-RANK EXCLUSIVE)</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($partners['universal'] as $partner): ?>
                        <div class="col-md-4 col-lg-3">
                            <div class="partner-card">
                                <div class="partner-name"><?php echo $partner['partner_name']; ?></div>
                                <div class="partner-discount"><?php echo $partner['discount_percent']; ?>% OFF</div>
                                <p class="small"><?php echo $partner['description']; ?></p>
                                <span class="partner-category category-universal">UNIVERSAL</span>
                                <small class="d-block mt-2">Min Rank: <?php echo $partner['min_rank_required']; ?></small>
                                <a href="<?php echo $partner['affiliate_link']; ?>" 
                                   class="btn btn-primary btn-sm mt-3 w-100" 
                                   target="_blank"
                                   onclick="trackAffiliate(<?php echo $partner['id']; ?>)">
                                    SHOP NOW
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Recovery Options -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-heartbeat"></i> PENALTY RECOVERY</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card bg-dark">
                            <div class="card-body text-center">
                                <i class="fas fa-video fa-3x mb-3" style="color: var(--accent-blue);"></i>
                                <h5>Watch Ads</h5>
                                <p>Watch 5 ads to recover 3% progress</p>
                                <form method="POST" action="recover_ads.php">
                                    <button type="submit" class="btn btn-primary w-100">RECOVER (5 ADS)</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card bg-dark">
                            <div class="card-body text-center">
                                <i class="fas fa-coins fa-3x mb-3" style="color: var(--warning-yellow);"></i>
                                <h5>Shadow Coins</h5>
                                <p>Spend 100 coins to recover 1% progress</p>
                                <p class="small">Your coins: <?php echo $user['shadow_coins']; ?></p>
                                <form method="POST" action="recover_coins.php">
                                    <button type="submit" class="btn btn-warning w-100" 
                                            <?php echo $user['shadow_coins'] < 100 ? 'disabled' : ''; ?>>
                                        RECOVER (100 COINS)
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card bg-dark">
                            <div class="card-body text-center">
                                <i class="fas fa-crown fa-3x mb-3" style="color: var(--accent-purple);"></i>
                                <h5>Subscribe</h5>
                                <p>No penalties + double progress</p>
                                <p class="small">₹199/month or ₹1999/year</p>
                                <a href="subscribe.php" class="btn btn-primary w-100">SUBSCRIBE</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function trackAffiliate(partnerId) {
    fetch('track_affiliate.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'partner_id=' + partnerId
    });
}
</script>

<?php include '../includes/footer.php'; ?>