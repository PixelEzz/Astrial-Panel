<?php
require_once __DIR__ . '/../helpers.php';
if (!isInstalled()) {
    header('Location: /install.php');
    exit;
}
$api = new ApiClient();
$theme = $api->get('/api/theme');
$companyName = $theme['company_name'] ?? 'Astrial Panel';
$supportUrl = $theme['support_url'] ?? '';
$footerText = $theme['footer_text'] ?? '© 2026 Astrial Panel Technologies. All rights reserved.';
$headerScripts = $theme['header_scripts'] ?? '';
$primary = $theme['primary_color'] ?? '#6366f1';
$secondary = $theme['secondary_color'] ?? '#3b82f6';
$accent = $theme['accent_color'] ?? '#10b981';
$mode = $theme['mode'] ?? 'dark';
$blur = $theme['blur'] ?? 16;
$customCss = $theme['custom_css'] ?? '';
$wallpaperUrl = $theme['wallpaper_url'] ?? '';
$logoUrl = $theme['logo_url'] ?? '';
$faviconUrl = !empty($theme['favicon_url']) ? $theme['favicon_url'] : "data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%236366f1'><path d='M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5'/></svg>";
$bg = ($mode === 'oled') ? '#000000' : (($mode === 'light') ? '#f8fafc' : '#090d16');
$cardBg = ($mode === 'oled') ? 'rgba(10, 10, 10, 0.85)' : (($mode === 'light') ? 'rgba(255, 255, 255, 0.9)' : 'rgba(15, 23, 42, 0.75)');
$textColor = ($mode === 'light') ? '#0f172a' : '#f8fafc';
?>
<!DOCTYPE html>
<html lang="en" class="<?= e($mode) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($companyName) ?> - Commercial Next-Gen Cloud</title>
  <link rel="icon" id="panel-favicon" href="<?= e($faviconUrl) ?>">
  
  <script>
    if (!Element.prototype.checkVisibility) {
      Element.prototype.checkVisibility = function() {
        return !!(this.offsetWidth || this.offsetHeight || this.getClientRects().length);
      };
    }
    // Auth Guard: If no valid session token exists in localStorage, redirect to login page immediately!
    (function() {
      try {
        const stored = localStorage.getItem('auth_user');
        if (!stored) {
          window.location.replace('/login.php');
          return;
        }
        const u = JSON.parse(stored);
        if (!u || !u.token) {
          window.location.replace('/login.php');
          return;
        }
      } catch (e) {
        window.location.replace('/login.php');
      }
    })();
  </script>
  
  <script src="https://cdn.tailwindcss.com" async></script>
  
  <style id="custom-theme-vars">
    :root {
      --primary: <?= e($primary) ?>;
      --secondary: <?= e($secondary) ?>;
      --accent: <?= e($accent) ?>;
      --bg: <?= e($bg) ?>;
      --card-bg: <?= e($cardBg) ?>;
      --blur: <?= e((string)$blur) ?>px;
      --login-wallpaper: url('<?= e($wallpaperUrl) ?>');
    }
    body {
      background-color: var(--bg);
      color: <?= e($textColor) ?>;
      font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      min-height: 100vh;
    }
    .glass-card {
      background: var(--card-bg);
      backdrop-filter: blur(var(--blur));
      -webkit-backdrop-filter: blur(var(--blur));
      border: 1px solid rgba(255, 255, 255, 0.08);
    }
    .glass-card:hover {
      border-color: rgba(255, 255, 255, 0.16);
    }
    .glow-primary {
      box-shadow: 0 0 25px -5px var(--primary);
    }
    .primary-btn {
      background: var(--primary);
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .primary-btn:hover {
      filter: brightness(1.15);
      transform: translateY(-1px);
    }
    .terminal-container {
      background: #020408;
      color: #38bdf8;
      font-family: 'JetBrains Mono', 'Fira Code', 'Courier New', monospace;
    }
    ::-webkit-scrollbar {
      width: 6px;
      height: 6px;
    }
    ::-webkit-scrollbar-track {
      background: rgba(0, 0, 0, 0.2);
    }
    ::-webkit-scrollbar-thumb {
      background: rgba(255, 255, 255, 0.15);
      border-radius: 9999px;
    }
    ::-webkit-scrollbar-thumb:hover {
      background: rgba(255, 255, 255, 0.3);
    }
    .login-bg-overlay {
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
    }
  </style>
  <style id="custom-css-injection"><?= $customCss ?></style>
  <?php if (!empty($headerScripts)): ?>
    <?= $headerScripts ?>
  <?php endif; ?>
</head>
<body class="flex antialiased selection:bg-indigo-500 selection:text-white relative">
