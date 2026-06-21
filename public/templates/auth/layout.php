<?php
// public/templates/auth/layout.php
// Centered card used by register / login / otp-verify / activate.
// Always shows the DEFAULT Modern logo (never a tenant logo) per the branding rule.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? 'Modern POS'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;
             background:linear-gradient(135deg,#0f172a,#1e293b);font-family:-apple-system,'Segoe UI',Roboto,Arial,sans-serif;padding:20px;}
        .auth-card{width:100%;max-width:420px;background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.3);overflow:hidden;}
        .auth-head{text-align:center;padding:28px 28px 8px;}
        .auth-head img{height:40px;}
        .auth-body{padding:8px 28px 28px;}
        .auth-title{font-size:1.3rem;font-weight:700;color:#0f172a;text-align:center;margin:14px 0 4px;}
        .auth-sub{color:#64748b;font-size:.9rem;text-align:center;margin-bottom:20px;}
        .form-label{font-weight:600;font-size:.85rem;color:#334155;}
        .form-control{padding:11px 13px;}
        .btn-auth{width:100%;padding:12px;font-weight:600;background:#2563eb;border:none;border-radius:8px;color:#fff;}
        .btn-auth:hover{background:#1d4ed8;color:#fff;}
        .auth-alert{border-radius:8px;padding:10px 14px;font-size:.88rem;margin-bottom:16px;}
        .auth-alert.err{background:#fee2e2;color:#991b1b;} .auth-alert.ok{background:#dcfce7;color:#166534;}
        .auth-foot{text-align:center;font-size:.88rem;color:#64748b;margin-top:18px;}
        .auth-foot a{color:#2563eb;text-decoration:none;font-weight:600;}
        .otp-input{letter-spacing:.5em;text-align:center;font-size:1.4rem;font-weight:700;}
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="auth-head">
            <img src="<?php echo htmlspecialchars(Branding::loginLogo()); ?>" alt="Modern POS"
                 onerror="this.style.display='none'">
        </div>
        <div class="auth-body">
            <?php echo $content ?? ''; ?>
        </div>
    </div>
</body>
</html>