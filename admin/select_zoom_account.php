<?php
require_once 'includes/multi_account_config.php';

// Handle account selection
if ($_POST && isset($_POST['select_account'])) {
    $credentials_id = intval($_POST['credentials_id']);
    
    if (setCurrentZoomAccount($credentials_id)) {
        header("Location: admin_dashboard.php");
        exit();
    } else {
        $error = "Invalid account selection. Please try again.";
    }
}

$zoom_accounts = getAllZoomCredentials();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Zoom Account - TTT NOMS</title>
    <link href="../common/css/bootstrap.min.css" rel="stylesheet">
    <link href="../common/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">🔗 Select Zoom Account</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        
                        <?php if (empty($zoom_accounts)): ?>
                            <div class="alert alert-warning">
                                <h5>No Zoom Accounts Available</h5>
                                <p>No active Zoom API credentials found. Please contact your administrator to add Zoom accounts.</p>
                            </div>
                        <?php else: ?>
                            <p class="mb-4">Please select which Zoom account you want to use for managing meetings and attendance:</p>
                            
                            <form method="POST" action="">
                                <div class="row">
                                    <?php foreach ($zoom_accounts as $account): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card border-2 zoom-account-card" style="cursor: pointer;" onclick="selectAccount(<?= $account['id'] ?>)">
                                                <div class="card-body text-center">
                                                    <div class="zoom-icon mb-3">
                                                        <i class="fas fa-video fa-3x text-primary"></i>
                                                    </div>
                                                    <h5 class="card-title"><?= htmlspecialchars($account['name']) ?></h5>
                                                    <p class="card-text text-muted">
                                                        Account ID: <?= substr($account['account_id'], 0, 8) ?>...
                                                    </p>
                                                    <button type="button" class="btn btn-primary" onclick="selectAccount(<?= $account['id'] ?>)">
                                                        Select This Account
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <input type="hidden" name="credentials_id" id="selected_credentials_id" value="">
                                <input type="hidden" name="select_account" value="1">
                            </form>
                        <?php endif; ?>
                        
                        <div class="mt-4 text-center">
                            <small class="text-muted">
                                ℹ️ You can change your account selection later from the admin dashboard.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../common/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectAccount(credentialsId) {
            document.getElementById('selected_credentials_id').value = credentialsId;
            document.querySelector('form').submit();
        }
        
        // Add hover effects to cards
        document.querySelectorAll('.zoom-account-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.borderColor = '#0d6efd';
                this.style.transform = 'scale(1.02)';
                this.style.transition = 'all 0.2s ease';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.borderColor = '#dee2e6';
                this.style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>
