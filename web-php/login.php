<?php
require_once __DIR__ . '/helpers.php';
if (!isInstalled()) {
    header('Location: /install.php');
    exit;
}
$api = new ApiClient();
$theme = $api->get('/api/theme');
$companyName = $theme['company_name'] ?? 'Astrial Panel';
$primary = $theme['primary_color'] ?? '#6366f1';
$secondary = $theme['secondary_color'] ?? '#3b82f6';
$accent = $theme['accent_color'] ?? '#10b981';
$mode = $theme['mode'] ?? 'dark';
$blur = $theme['blur'] ?? 16;
$customCss = $theme['custom_css'] ?? '';
$wallpaperUrl = !empty($theme['wallpaper_url']) ? $theme['wallpaper_url'] : 'https://images.unsplash.com/photo-1542751371-adc38448a05e?auto=format&fit=crop&w=1920&q=80';
$logoUrl = $theme['logo_url'] ?? '';
$faviconUrl = !empty($theme['favicon_url']) ? $theme['favicon_url'] : "data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%236366f1'><path d='M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5'/></svg>";
$footerText = $theme['footer_text'] ?? '© 2026 Astrial Panel Technologies. All rights reserved.';
$bg = ($mode === 'oled') ? '#000000' : (($mode === 'light') ? '#f8fafc' : '#090d16');
$cardBg = ($mode === 'oled') ? 'rgba(10, 10, 10, 0.85)' : (($mode === 'light') ? 'rgba(255, 255, 255, 0.9)' : 'rgba(15, 23, 42, 0.75)');
$textColor = ($mode === 'light') ? '#0f172a' : '#f8fafc';
?>
<!DOCTYPE html>
<html lang="en" class="<?= e($mode) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - <?= e($companyName) ?></title>
  <link rel="icon" href="<?= e($faviconUrl) ?>">

  <script>
    if (!Element.prototype.checkVisibility) {
      Element.prototype.checkVisibility = function() {
        return !!(this.offsetWidth || this.offsetHeight || this.getClientRects().length);
      };
    }
  </script>

  <script src="https://cdn.tailwindcss.com"></script>

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
    }
    .primary-btn {
      background-color: var(--primary);
      transition: all 0.2s ease-in-out;
    }
    .primary-btn:hover {
      filter: brightness(1.15);
      transform: translateY(-1px);
    }
    .login-bg-overlay {
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
    }
  </style>
  <?php if (!empty($customCss)): ?>
  <style id="theme-custom-injected-css">
    <?= $customCss ?>
  </style>
  <?php endif; ?>
</head>
<body class="flex items-center justify-center min-h-screen p-4 relative overflow-hidden login-bg-overlay" style="background-image: var(--login-wallpaper);">

  <!-- Ambient Lighting Effects -->
  <div class="absolute inset-0 bg-black/75 backdrop-blur-xl"></div>
  <div class="absolute w-[500px] h-[500px] rounded-full blur-3xl opacity-20 pointer-events-none" style="background: var(--primary); top: 10%; left: 15%;"></div>
  <div class="absolute w-[450px] h-[450px] rounded-full blur-3xl opacity-20 pointer-events-none" style="background: var(--secondary); bottom: 10%; right: 15%;"></div>

  <!-- Main Commercial Container -->
  <div class="relative w-full max-w-md glass-card rounded-3xl border border-white/10 p-8 space-y-6 shadow-2xl z-10">
    
    <!-- Branding Header -->
    <div class="text-center space-y-2">
      <div id="login-brand-logo" class="w-14 h-14 mx-auto rounded-2xl flex items-center justify-center font-black text-white text-2xl shadow-xl overflow-hidden shrink-0" style="background: var(--primary);">
        <?php if (!empty($logoUrl)): ?>
          <img src="<?= e($logoUrl) ?>" alt="Logo" class="w-full h-full object-cover">
        <?php else: ?>
          <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        <?php endif; ?>
      </div>
      <h1 class="text-2xl font-black text-white tracking-tight pt-1">
        <?= e($companyName) ?>
      </h1>
      <p class="text-xs text-slate-400">
        Enterprise game server orchestration & node cluster
      </p>
      <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-[10px] font-mono text-emerald-400 font-bold mt-1">
        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> ALL SYSTEMS OPERATIONAL
      </div>
    </div>

    <!-- Segmented Tab Switcher (Sign In vs Register) -->
    <div class="grid grid-cols-2 p-1 rounded-xl bg-slate-900/80 border border-white/5 text-xs font-bold">
      <button type="button" id="tab-btn-signin" onclick="switchAuthTab('signin')" class="py-2 rounded-lg text-white transition shadow-sm" style="background: var(--primary);">
        Sign In
      </button>
      <button type="button" id="tab-btn-register" onclick="switchAuthTab('register')" class="py-2 rounded-lg text-slate-400 hover:text-white transition">
        Create Account
      </button>
    </div>

    <!-- Error Alert Box -->
    <div id="login-error-banner" class="hidden p-3.5 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-300 text-xs font-medium flex items-center gap-2">
      <svg class="w-4 h-4 shrink-0 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      <span id="login-error-text">Invalid credentials.</span>
    </div>

    <!-- ========================================== -->
    <!-- TAB 1: SIGN IN FORM -->
    <!-- ========================================== -->
    <form id="form-signin" onsubmit="handleSignIn(event)" class="space-y-4">
      <div>
        <label class="text-xs font-bold text-slate-300 block mb-1.5">Email Address</label>
        <div class="relative">
          <svg class="w-4 h-4 text-slate-500 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/></svg>
          <input type="email" id="login-email" placeholder="name@domain.com" required 
                 class="w-full bg-slate-900/90 border border-slate-700/80 rounded-xl pl-10 pr-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition shadow-inner">
        </div>
      </div>

      <div>
        <div class="flex justify-between items-center mb-1.5">
          <label class="text-xs font-bold text-slate-300">Password</label>
          <a href="#" onclick="alert('Please contact your server hosting administrator to reset your password.')" class="text-[11px] text-indigo-400 hover:text-indigo-300 font-medium">Forgot?</a>
        </div>
        <div class="relative">
          <svg class="w-4 h-4 text-slate-500 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
          <input type="password" id="login-password" placeholder="••••••••" required 
                 class="w-full bg-slate-900/90 border border-slate-700/80 rounded-xl pl-10 pr-10 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition shadow-inner font-mono">
          <button type="button" onclick="togglePasswordVisibility('login-password')" class="absolute right-3.5 top-2.5 text-slate-500 hover:text-slate-300 text-xs">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
          </button>
        </div>
      </div>

      <div class="flex items-center justify-between text-xs text-slate-400 pt-1">
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" id="login-remember" checked class="rounded bg-slate-900 border-slate-700 text-indigo-500 focus:ring-0">
          <span>Remember this session</span>
        </label>
      </div>

      <button type="submit" id="btn-login-submit" class="primary-btn w-full py-3 rounded-xl text-xs font-extrabold text-white shadow-xl flex items-center justify-center gap-2">
        <span>Sign In to Panel</span>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
      </button>
    </form>

    <!-- ========================================== -->
    <!-- TAB 2: REGISTER CLIENT FORM -->
    <!-- ========================================== -->
    <form id="form-register" onsubmit="handleRegister(event)" class="space-y-4 hidden">
      <div>
        <label class="text-xs font-bold text-slate-300 block mb-1.5">Your Email Address</label>
        <div class="relative">
          <svg class="w-4 h-4 text-slate-500 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/></svg>
          <input type="email" id="reg-email" placeholder="customer@domain.com" required 
                 class="w-full bg-slate-900/90 border border-slate-700/80 rounded-xl pl-10 pr-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition shadow-inner">
        </div>
      </div>

      <div>
        <label class="text-xs font-bold text-slate-300 block mb-1.5">Choose Password (min 6 chars)</label>
        <div class="relative">
          <svg class="w-4 h-4 text-slate-500 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
          <input type="password" id="reg-password" minlength="6" placeholder="••••••••" required 
                 class="w-full bg-slate-900/90 border border-slate-700/80 rounded-xl pl-10 pr-10 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition shadow-inner font-mono">
          <button type="button" onclick="togglePasswordVisibility('reg-password')" class="absolute right-3.5 top-2.5 text-slate-500 hover:text-slate-300 text-xs">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
          </button>
        </div>
      </div>

      <div>
        <label class="text-xs font-bold text-slate-300 block mb-1.5">Confirm Password</label>
        <div class="relative">
          <svg class="w-4 h-4 text-slate-500 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
          <input type="password" id="reg-confirm" minlength="6" placeholder="••••••••" required 
                 class="w-full bg-slate-900/90 border border-slate-700/80 rounded-xl pl-10 pr-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition shadow-inner font-mono">
        </div>
      </div>

      <button type="submit" id="btn-register-submit" class="primary-btn w-full py-3 rounded-xl text-xs font-extrabold text-white shadow-xl flex items-center justify-center gap-2">
        <span>Create Client Account</span>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
      </button>
    </form>

    <!-- Security & Commercial Footer -->
    <div class="pt-2 border-t border-white/5 flex items-center justify-center gap-2 text-[11px] text-slate-500 font-mono">
      <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
      <span>Encrypted Session &bull; TLS 1.3 End-to-End</span>
    </div>

    <div class="text-center text-[10px] text-slate-500 font-mono">
      <?= e($footerText) ?>
    </div>
  </div>

  <script>
    function switchAuthTab(tab) {
      const btnSignin = document.getElementById('tab-btn-signin');
      const btnReg = document.getElementById('tab-btn-register');
      const formSignin = document.getElementById('form-signin');
      const formReg = document.getElementById('form-register');
      const errorBanner = document.getElementById('login-error-banner');
      errorBanner.classList.add('hidden');

      if (tab === 'signin') {
        btnSignin.style.background = 'var(--primary)';
        btnSignin.classList.add('text-white');
        btnSignin.classList.remove('text-slate-400');
        btnReg.style.background = 'transparent';
        btnReg.classList.add('text-slate-400');
        btnReg.classList.remove('text-white');
        formSignin.classList.remove('hidden');
        formReg.classList.add('hidden');
      } else {
        btnReg.style.background = 'var(--primary)';
        btnReg.classList.add('text-white');
        btnReg.classList.remove('text-slate-400');
        btnSignin.style.background = 'transparent';
        btnSignin.classList.add('text-slate-400');
        btnSignin.classList.remove('text-white');
        formReg.classList.remove('hidden');
        formSignin.classList.add('hidden');
      }
    }

    function togglePasswordVisibility(id) {
      const el = document.getElementById(id);
      if (el) {
        el.type = el.type === 'password' ? 'text' : 'password';
      }
    }

    function showError(msg) {
      const banner = document.getElementById('login-error-banner');
      const text = document.getElementById('login-error-text');
      text.innerText = msg;
      banner.classList.remove('hidden');
    }

    async function handleSignIn(e) {
      if (e) e.preventDefault();
      const email = document.getElementById('login-email').value.trim();
      const password = document.getElementById('login-password').value;
      const errorBanner = document.getElementById('login-error-banner');
      errorBanner.classList.add('hidden');

      try {
        const res = await fetch('/api/auth/login', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ email, password })
        });

        if (!res.ok) {
          const errData = await res.json().catch(() => ({}));
          showError(errData.error || 'Invalid email or password.');
          return;
        }

        const data = await res.json();
        const authUser = {
          id: data.user.id,
          email: data.user.email,
          role: data.user.role,
          token: data.token
        };
        localStorage.setItem('auth_user', JSON.stringify(authUser));
        // Redirect to main panel
        window.location.href = '/index.php';
      } catch (err) {
        showError('Network error: ' + err.message);
      }
    }

    async function handleRegister(e) {
      e.preventDefault();
      const email = document.getElementById('reg-email').value.trim();
      const password = document.getElementById('reg-password').value;
      const confirm = document.getElementById('reg-confirm').value;

      if (password !== confirm) {
        showError('Passwords do not match.');
        return;
      }
      if (password.length < 6) {
        showError('Password must be at least 6 characters.');
        return;
      }

      try {
        const res = await fetch('/api/auth/register', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ email, password })
        });

        if (!res.ok) {
          const errData = await res.json().catch(() => ({}));
          showError(errData.error || 'Failed to register account.');
          return;
        }

        const data = await res.json();
        const authUser = {
          id: data.user.id,
          email: data.user.email,
          role: data.user.role,
          token: data.token
        };
        localStorage.setItem('auth_user', JSON.stringify(authUser));
        window.location.href = '/index.php';
      } catch (err) {
        showError('Network error: ' + err.message);
      }
    }
  </script>
</body>
</html>
