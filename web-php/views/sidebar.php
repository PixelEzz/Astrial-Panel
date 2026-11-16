<?php
$sideLogoUrl = $theme['logo_url'] ?? '';
$sideCompanyName = $theme['company_name'] ?? 'Astrial Panel';
?>
<aside class="w-64 glass-card border-r border-white/5 flex flex-col justify-between h-screen sticky top-0 z-40 shrink-0">
  <div class="p-5 space-y-6">
    <!-- Brand Logo -->
    <div class="flex items-center space-x-3">
      <div id="panel-logo" class="w-10 h-10 rounded-xl flex items-center justify-center font-extrabold text-white text-base shadow-xl glow-primary overflow-hidden shrink-0" style="background: var(--primary);">
        <?php if (!empty($sideLogoUrl)): ?>
          <img id="panel-logo-img" src="<?= e($sideLogoUrl) ?>" alt="Logo" class="w-full h-full object-cover">
        <?php else: ?>
          <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        <?php endif; ?>
      </div>
      <div class="min-w-0">
        <h1 id="panel-title" class="font-extrabold text-base tracking-tight bg-gradient-to-r from-white via-slate-200 to-indigo-300 bg-clip-text text-transparent truncate">
          <?= e($sideCompanyName) ?>
        </h1>
        <div class="flex items-center gap-1.5 mt-0.5">
          <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
          <span id="sidebar-role-indicator" class="text-[11px] font-mono text-slate-400">ADMIN MODE</span>
        </div>
      </div>
    </div>

    <!-- Navigation Items -->
    <nav class="space-y-1 text-sm font-medium">
      <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 px-3 py-1">Management</div>
      
      <button onclick="switchTab('servers')" id="tab-btn-servers" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-white transition font-semibold" style="background: var(--primary);">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/></svg>
        <span id="tab-label-servers">Servers & Clusters</span>
      </button>

      <!-- Admin Only Sections (Hidden for Client accounts) -->
      <div id="nav-admin-section" class="space-y-1">
        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 px-3 py-2 mt-3">Infrastructure (Admin)</div>

        <button onclick="switchTab('eggs')" id="tab-btn-eggs" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-white/5 transition">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
          <span>Egg Library (44 Games)</span>
        </button>

        <button onclick="switchTab('nodes')" id="tab-btn-nodes" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-white/5 transition">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
          <span>Nodes & Hardware</span>
        </button>

        <button onclick="switchTab('users')" id="tab-btn-users" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-white/5 transition">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
          <span>User Accounts</span>
        </button>

        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 px-3 py-2 mt-3">Enterprise & Automation</div>

        <button onclick="switchTab('theme')" id="tab-btn-theme" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-white/5 transition">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4 5 5 0 012-4.143V7a5 5 0 0110 0v5.857A5 5 0 0117 17a4 4 0 01-4 4H7z"/></svg>
          <span>Theme Studio (Zero-Rebuild)</span>
        </button>

        <button onclick="switchTab('billing')" id="tab-btn-billing" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-white/5 transition">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
          <span>WHMCS & Billing Suite</span>
        </button>

        <button onclick="switchTab('mail')" id="tab-btn-mail" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-white/5 transition">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
          <span>Mailing & Audit Logs</span>
        </button>
      </div>

      <!-- Client Only Sections (Shown for Client accounts) -->
      <div id="nav-client-section" class="hidden space-y-1">
        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 px-3 py-2 mt-3">Account Services</div>

        <button onclick="switchTab('account')" id="tab-btn-account" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-white/5 transition">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
          <span>Account Security & API Keys</span>
        </button>
      </div>
    </nav>
  </div>

  <!-- User / Session Footer -->
  <div class="p-4 border-t border-white/5 bg-black/20 space-y-3">
    <div class="flex items-center justify-between">
      <div class="flex items-center space-x-3 min-w-0">
        <div id="user-avatar" class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center font-bold text-white text-xs shadow-md shrink-0">
          AD
        </div>
        <div class="min-w-0">
          <div id="user-display-name" class="text-xs font-bold text-white truncate">Administrator</div>
          <div id="user-display-email" class="text-[11px] text-slate-400 font-mono truncate">admin@example.com</div>
        </div>
      </div>
      <span id="user-role-badge" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 uppercase shrink-0">
        ADMIN
      </span>
    </div>

    <!-- Sign Out Button -->
    <div class="pt-1 border-t border-white/5">
      <button onclick="logout()" class="w-full py-2 px-3 rounded-xl bg-white/5 hover:bg-rose-500/20 border border-white/5 hover:border-rose-500/30 text-slate-300 hover:text-rose-400 font-semibold text-xs flex items-center justify-center gap-2 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        <span>Sign Out</span>
      </button>
    </div>
  </div>
</aside>
