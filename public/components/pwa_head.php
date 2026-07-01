<?php /* PWA head tags + service-worker registration. Include inside <head>. */ ?>
<link rel="manifest" href="/Rongai/public/manifest.webmanifest">
<meta name="theme-color" content="#0f172a">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Rongai POS">
<link rel="icon" href="/Rongai/public/assets/icons/favicon-32.png" type="image/png" sizes="32x32">
<link rel="icon" href="/Rongai/public/assets/icons/icon-192.png" type="image/png" sizes="192x192">
<link rel="apple-touch-icon" href="/Rongai/public/assets/icons/apple-touch-icon.png">
<script>
if ('serviceWorker' in navigator) {
  window.addEventListener('load', function () {
    navigator.serviceWorker.register('/Rongai/public/sw.js', { scope: '/Rongai/public/' })
      .catch(function (e) { console.warn('SW registration failed', e); });
  });
}
</script>
