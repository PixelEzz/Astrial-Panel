<?php
require_once __DIR__ . '/helpers.php';

if (isInstalled()) {
    header('Location: /login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Installation & Setup Wizard - Astrial Panel</title>
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%236366f1'><path d='M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5'/></svg>">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    :root {
      --primary: #6366f1;
      --secondary: #3b82f6;
      --accent: #10b981;
      --bg: #090d16;
    }
    body {
      background-color: var(--bg);
      color: #f8fafc;
      font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      min-height: 100vh;
    }
    .glass-card {
      background: rgba(15, 23, 42, 0.85);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border: 1px solid rgba(255, 255, 255, 0.08);
    }
    .primary-btn {
      background: var(--primary);
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .primary-btn:hover {
      filter: brightness(1.15);
      transform: translateY(-1px);
    }
    .glow-primary {
      box-shadow: 0 0 25px -5px var(--primary);
    }
  </style>
</head>
<body class="flex items-center justify-center p-4 sm:p-6 min-h-screen relative overflow-x-hidden selection:bg-indigo-500 selection:text-white">
  <!-- Ambient background glow -->
  <div class="fixed w-[600px] h-[600px] rounded-full blur-3xl opacity-20 pointer-events-none" style="background: var(--primary); top: -10%; left: -10%;"></div>
  <div class="fixed w-[500px] h-[500px] rounded-full blur-3xl opacity-15 pointer-events-none" style="background: var(--secondary); bottom: -10%; right: -10%;"></div>

  <div class="w-full max-w-2xl glass-card rounded-3xl border border-white/10 p-6 sm:p-8 space-y-6 shadow-2xl relative z-10 my-8">
    
    <!-- Header -->
    <div class="flex items-center space-x-4 border-b border-white/5 pb-5">
      <div class="w-12 h-12 rounded-2xl flex items-center justify-center font-black text-white text-xl shadow-xl glow-primary shrink-0" style="background: var(--primary);">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
      </div>
      <div>
        <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">Astrial Panel Setup Wizard</h1>
        <p class="text-xs text-slate-400">Production Installation, Database Initializer & Root Administrator Setup</p>
      </div>
    </div>

    <!-- Alert Banner -->
    <div id="install-alert" class="hidden p-4 rounded-xl text-xs font-semibold flex items-center gap-2"></div>

    <form id="install-form" onsubmit="executeInstallation(event)" class="space-y-6">
      
      <!-- Section 1: PostgreSQL Database Configuration -->
      <div class="space-y-3">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <span class="w-5 h-5 rounded-full bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 flex items-center justify-center text-[10px] font-bold">1</span>
            <h3 class="text-xs font-bold uppercase tracking-wider text-white">PostgreSQL 16 Database Configuration</h3>
          </div>
          <button type="button" onclick="testDatabaseConnection()" id="btn-test-db" class="px-3 py-1 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 text-xs text-indigo-300 font-bold transition flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>Test Connection</span>
          </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div class="sm:col-span-2">
            <label class="text-[11px] text-slate-400 block mb-1">Database Host</label>
            <input type="text" id="db-host" value="127.0.0.1" required 
                   class="w-full bg-slate-900 border border-slate-700/80 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500 font-mono">
          </div>
          <div>
            <label class="text-[11px] text-slate-400 block mb-1">Port</label>
            <input type="number" id="db-port" value="5432" required 
                   class="w-full bg-slate-900 border border-slate-700/80 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500 font-mono">
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div>
            <label class="text-[11px] text-slate-400 block mb-1">Database Name</label>
            <input type="text" id="db-name" value="gamepanel" required 
                   class="w-full bg-slate-900 border border-slate-700/80 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500 font-mono">
          </div>
          <div>
            <label class="text-[11px] text-slate-400 block mb-1">Username</label>
            <input type="text" id="db-user" value="postgres" required 
                   class="w-full bg-slate-900 border border-slate-700/80 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500 font-mono">
          </div>
          <div>
            <label class="text-[11px] text-slate-400 block mb-1">Password</label>
            <input type="password" id="db-pass" value="postgres" placeholder="••••••••" 
                   class="w-full bg-slate-900 border border-slate-700/80 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500 font-mono">
          </div>
        </div>

        <div id="db-test-result" class="hidden text-xs font-mono p-2.5 rounded-xl"></div>
      </div>

      <!-- Section 2: Panel Public Environment & Node -->
      <div class="space-y-3 pt-3 border-t border-white/5">
        <div class="flex items-center gap-2">
          <span class="w-5 h-5 rounded-full bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 flex items-center justify-center text-[10px] font-bold">2</span>
          <h3 class="text-xs font-bold uppercase tracking-wider text-white">Public Environment & Node Setup</h3>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="text-[11px] text-slate-400 block mb-1">Company / Hosting Brand Name</label>
            <input type="text" id="company-name" value="Astrial Panel" required 
                   class="w-full bg-slate-900 border border-slate-700/80 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500 font-bold">
          </div>
          <div>
            <label class="text-[11px] text-slate-400 block mb-1">Public Panel URL</label>
            <input type="text" id="panel-url" value="" placeholder="https://panel.yourdomain.com" required 
                   class="w-full bg-slate-900 border border-slate-700/80 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500 font-mono">
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="text-[11px] text-slate-400 block mb-1">Primary Node FQDN / IP</label>
            <input type="text" id="node-fqdn" value="localhost" required 
                   class="w-full bg-slate-900 border border-slate-700/80 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500 font-mono">
          </div>
          <div>
            <label class="text-[11px] text-slate-400 block mb-1">Node Daemon URL (Wings)</label>
            <input type="text" id="node-daemon-url" value="http://localhost:8081" required 
                   class="w-full bg-slate-900 border border-slate-700/80 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500 font-mono">
          </div>
        </div>
      </div>

      <!-- Section 3: Root Administrator Account -->
      <div class="space-y-3 pt-3 border-t border-white/5">
        <div class="flex items-center gap-2">
          <span class="w-5 h-5 rounded-full bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 flex items-center justify-center text-[10px] font-bold">3</span>
          <h3 class="text-xs font-bold uppercase tracking-wider text-white">Root Administrator Account</h3>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="text-[11px] text-slate-400 block mb-1">Administrator Full Name</label>
            <input type="text" id="admin-name" value="System Administrator" required 
                   class="w-full bg-slate-900 border border-slate-700/80 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500">
          </div>
          <div>
            <label class="text-[11px] text-slate-400 block mb-1">Administrator Email</label>
            <input type="email" id="admin-email" value="admin@example.com" placeholder="admin@yourhost.com" required 
                   class="w-full bg-slate-900 border border-slate-700/80 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500 font-mono">
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="text-[11px] text-slate-400 block mb-1">Administrator Password (min 6 chars)</label>
            <input type="password" id="admin-pass" value="admin123" minlength="6" required 
                   class="w-full bg-slate-900 border border-slate-700/80 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500 font-mono">
          </div>
          <div>
            <label class="text-[11px] text-slate-400 block mb-1">Confirm Administrator Password</label>
            <input type="password" id="admin-confirm" value="admin123" minlength="6" required 
                   class="w-full bg-slate-900 border border-slate-700/80 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500 font-mono">
          </div>
        </div>
      </div>

      <!-- Submit Button -->
      <div class="pt-2 border-t border-white/5">
        <button type="submit" id="btn-submit-install" class="primary-btn w-full py-3.5 rounded-xl text-xs font-black text-white shadow-xl flex items-center justify-center gap-2">
          <span>Initialize & Install Astrial Panel</span>
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
        </button>
      </div>
    </form>

    <div class="text-center text-[11px] text-slate-500 font-mono">
      Astrial Panel &bull; Commercial Game Server Hosting Infrastructure &bull; v2.0 Production
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const panelUrlInput = document.getElementById('panel-url');
      if (panelUrlInput && !panelUrlInput.value) {
        panelUrlInput.value = window.location.origin;
      }
      const nodeFqdnInput = document.getElementById('node-fqdn');
      if (nodeFqdnInput && nodeFqdnInput.value === 'localhost') {
        nodeFqdnInput.value = window.location.hostname || 'localhost';
      }
    });

    async function testDatabaseConnection() {
      const btn = document.getElementById('btn-test-db');
      const resEl = document.getElementById('db-test-result');
      btn.disabled = true;
      btn.innerText = 'Connecting...';
      resEl.classList.remove('hidden', 'bg-emerald-500/10', 'text-emerald-400', 'border-emerald-500/20', 'bg-rose-500/10', 'text-rose-400', 'border-rose-500/20');
      resEl.className = 'text-xs font-mono p-2.5 rounded-xl border bg-black/40 text-slate-300';
      resEl.innerText = 'Attempting connection to PostgreSQL...';

      const payload = {
        db_host: document.getElementById('db-host').value,
        db_port: document.getElementById('db-port').value,
        db_name: document.getElementById('db-name').value,
        db_user: document.getElementById('db-user').value,
        db_password: document.getElementById('db-pass').value
      };

      try {
        const res = await fetch('/api/install/test-db', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
          resEl.className = 'text-xs font-mono p-2.5 rounded-xl border bg-emerald-500/10 text-emerald-400 border-emerald-500/30';
          resEl.innerText = '✓ ' + (data.message || 'Database connection verified successfully!');
        } else {
          resEl.className = 'text-xs font-mono p-2.5 rounded-xl border bg-rose-500/10 text-rose-400 border-rose-500/30';
          resEl.innerText = '✗ ' + (data.error || 'Connection failed.');
        }
      } catch (err) {
        resEl.className = 'text-xs font-mono p-2.5 rounded-xl border bg-rose-500/10 text-rose-400 border-rose-500/30';
        resEl.innerText = '✗ Network error: ' + err.message;
      } finally {
        btn.disabled = false;
        btn.innerText = 'Test Connection';
      }
    }

    async function executeInstallation(e) {
      e.preventDefault();
      const alertBox = document.getElementById('install-alert');
      alertBox.classList.add('hidden');

      const pass = document.getElementById('admin-pass').value;
      const confirm = document.getElementById('admin-confirm').value;

      if (pass !== confirm) {
        alertBox.className = 'p-4 rounded-xl text-xs font-semibold bg-rose-500/15 border border-rose-500/30 text-rose-300 flex items-center gap-2';
        alertBox.innerText = 'Error: Administrator passwords do not match.';
        alertBox.classList.remove('hidden');
        return;
      }

      const submitBtn = document.getElementById('btn-submit-install');
      submitBtn.disabled = true;
      submitBtn.innerHTML = `<span>Applying Migrations & Initializing Astrial...</span>`;

      const payload = {
        db_host: document.getElementById('db-host').value,
        db_port: document.getElementById('db-port').value,
        db_name: document.getElementById('db-name').value,
        db_user: document.getElementById('db-user').value,
        db_password: document.getElementById('db-pass').value,
        panel_url: document.getElementById('panel-url').value,
        company_name: document.getElementById('company-name').value,
        admin_name: document.getElementById('admin-name').value,
        admin_email: document.getElementById('admin-email').value,
        admin_password: pass,
        node_name: 'Primary Node',
        node_fqdn: document.getElementById('node-fqdn').value,
        node_daemon_url: document.getElementById('node-daemon-url').value
      };

      try {
        const res = await fetch('/api/install/setup', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
          alertBox.className = 'p-4 rounded-xl text-xs font-semibold bg-emerald-500/15 border border-emerald-500/30 text-emerald-300 flex items-center gap-2';
          alertBox.innerText = '✓ ' + (data.message || 'Installation completed successfully! Redirecting to login...');
          alertBox.classList.remove('hidden');
          setTimeout(() => {
            window.location.href = '/login.php';
          }, 1500);
        } else {
          alertBox.className = 'p-4 rounded-xl text-xs font-semibold bg-rose-500/15 border border-rose-500/30 text-rose-300 flex items-center gap-2';
          alertBox.innerText = 'Installation error: ' + (data.error || 'Unknown error occurred.');
          alertBox.classList.remove('hidden');
          submitBtn.disabled = false;
          submitBtn.innerHTML = `<span>Initialize & Install Astrial Panel</span>`;
        }
      } catch (err) {
        alertBox.className = 'p-4 rounded-xl text-xs font-semibold bg-rose-500/15 border border-rose-500/30 text-rose-300 flex items-center gap-2';
        alertBox.innerText = 'Network error: ' + err.message;
        alertBox.classList.remove('hidden');
        submitBtn.disabled = false;
        submitBtn.innerHTML = `<span>Initialize & Install Astrial Panel</span>`;
      }
    }
  </script>
</body>
</html>
