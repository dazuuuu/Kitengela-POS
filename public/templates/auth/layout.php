<?php
// public/templates/auth/layout.php
// Centered card used by register / login / otp-verify / activate.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? 'Rongai POS'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/Rongai/public/assets/css/pos-portal.css">
</head>
<body class="pos-portal">
    <div class="pos-portal-bg" aria-hidden="true"></div>

    <div class="pos-portal-shell">
        <aside class="pos-brand-panel">
            <?php
            require_once ROOT_PATH . '/app/helpers/Branding.php';
            $authLogo = Branding::authLogo(class_exists('Database') ? Database::pdo() : null);
            ?>
            <div class="pos-brand-logo">
                <img src="<?php echo htmlspecialchars($authLogo); ?>" alt="Rongai POS" width="64" height="64"
                     onerror="this.style.display='none';this.parentNode.innerHTML='<i class=\'fas fa-cash-register\' style=\'font-size:1.6rem;color:#5eead4;line-height:60px;text-align:center;width:100%\'></i>'">
            </div>
            <h1 class="pos-brand-title">Rongai POS</h1>
            <p class="pos-brand-tag">Secure sign-in for your business</p>
            <ul class="pos-feature-list">
                <li><i class="fas fa-lock"></i> Encrypted login &amp; OTP verification</li>
                <li><i class="fas fa-shield-halved"></i> Role-based access control</li>
                <li><i class="fas fa-receipt"></i> POS-ready for daily operations</li>
            </ul>
        </aside>

        <div class="pos-stage" id="stage">
            <div class="pos-card-wrap" id="cardWrap">
                <div class="pos-card-shadow"></div>
                <div class="pos-card pos-portal">
                    <div class="pos-card-top"></div>
                    <div class="pos-card-body">
                        <div class="pos-auth-head">
                            <div class="pos-brand-logo">
                                <img src="<?php echo htmlspecialchars($authLogo); ?>" alt="Rongai POS" width="64" height="64"
                                     onerror="this.style.display='none'">
                            </div>
                            <h2>Rongai POS</h2>
                        </div>
                        <div class="text-center my-3">
                            <span class="pos-badge-secure"><span class="pos-badge-dot"></span>Secure sign-in</span>
                        </div>
                        <?php echo $content ?? ''; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    (function(){
        var wrap = document.getElementById('cardWrap');
        if (!wrap) return;
        document.addEventListener('mousemove', function(e) {
            var r = wrap.getBoundingClientRect();
            var dx = (e.clientX - (r.left + r.width / 2)) / r.width;
            var dy = (e.clientY - (r.top + r.height / 2)) / r.height;
            wrap.style.transform = 'rotateX(' + (dy * -8) + 'deg) rotateY(' + (dx * 8) + 'deg)';
        });
        document.addEventListener('mouseleave', function() {
            wrap.style.transform = '';
        });
    })();
    </script>
</body>
</html>
