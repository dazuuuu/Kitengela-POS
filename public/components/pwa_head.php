<?php /* PWA head tags + service-worker registration. Include inside <head>. */ ?>
<link rel="manifest" href="/Kitale/public/manifest.webmanifest">
<meta name="theme-color" content="#0f172a">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Kitale POS">
<link rel="apple-touch-icon" href="/Kitale/public/assets/icons/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="/Kitale/public/assets/icons/favicon-32.png">
<script>
if ('serviceWorker' in navigator) {
  window.addEventListener('load', function () {
    navigator.serviceWorker.register('/Kitale/public/sw.js', { scope: '/Kitale/public/' })
      .catch(function (e) { console.warn('SW registration failed', e); });
  });
}
</script>
