<?php
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/views/header.php';
require_once __DIR__ . '/views/sidebar.php';
?>

<!-- Main Content Area -->
<main id="main-content-panel" class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
  <!-- Top Bar -->
  <header class="glass-card sticky top-0 z-30 px-8 py-3.5 flex items-center justify-between border-b border-white/5 bg-slate-950/60 backdrop-blur-xl">
    <div class="flex items-center gap-4">
      <div class="relative w-80">
        <svg class="w-4 h-4 text-slate-500 absolute left-3.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input type="text" id="global-search" placeholder="Search servers, eggs, nodes... (⌘K)" 
               class="w-full bg-slate-900/90 border border-white/10 rounded-xl pl-10 pr-4 py-1.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition shadow-inner">
      </div>
      <div class="hidden sm:flex items-center gap-2">
        <span class="px-2.5 py-1 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[11px] font-mono font-bold flex items-center gap-1.5 shadow-sm">
          <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span> CLUSTER 100% HEALTHY
        </span>
        <span id="header-account-type-pill" class="px-2.5 py-1 rounded-lg bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-[11px] font-mono font-bold uppercase">
          ADMINISTRATOR CONSOLE
        </span>
      </div>
    </div>

    <div class="flex items-center gap-3">
      <button id="btn-admin-area" onclick="toggleAdminArea()" class="hidden px-3.5 py-2 rounded-xl bg-indigo-500/10 hover:bg-indigo-500/20 border border-indigo-500/30 text-xs font-bold text-indigo-300 hover:text-white transition flex items-center gap-1.5 shadow-sm">
        <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        <span id="admin-area-btn-text">Admin Area</span>
      </button>
      <button onclick="switchTab('billing')" id="btn-deploy-server-top" class="primary-btn px-4 py-2 rounded-xl text-xs font-bold text-white shadow-xl flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        <span>+ Deploy Server</span>
      </button>
      <button onclick="logout()" class="px-3.5 py-2 rounded-xl bg-white/5 hover:bg-rose-500/20 border border-white/10 hover:border-rose-500/30 text-xs font-bold text-slate-300 hover:text-rose-400 transition flex items-center gap-1.5">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        <span>Log Out</span>
      </button>
    </div>
  </header>

  <div class="p-8 max-w-7xl w-full mx-auto space-y-8 flex-1">
    
    <!-- ========================================== -->
    <!-- VIEW 1: SERVERS & CLUSTERS (PTERODACTYL STYLE) -->
    <!-- ========================================== -->
    <section id="view-servers" class="space-y-6">
      <!-- 2026 Cluster Telemetry HUD -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="glass-card p-5 rounded-2xl border border-white/5 space-y-2 relative overflow-hidden group">
          <div class="absolute right-0 top-0 w-24 h-24 bg-indigo-500/5 rounded-full blur-2xl group-hover:bg-indigo-500/10 transition"></div>
          <div class="flex justify-between items-center text-xs text-slate-400 font-medium">
            <span>Deployed Instances</span>
            <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
          </div>
          <div id="metric-total-servers" class="text-3xl font-black text-white tracking-tight">0</div>
          <div class="text-[11px] text-slate-500 font-mono flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> 100% Online &bull; Node 1
          </div>
        </div>

        <div class="glass-card p-5 rounded-2xl border border-white/5 space-y-2 relative overflow-hidden group">
          <div class="absolute right-0 top-0 w-24 h-24 bg-emerald-500/5 rounded-full blur-2xl group-hover:bg-emerald-500/10 transition"></div>
          <div class="flex justify-between items-center text-xs text-slate-400 font-medium">
            <span>Memory Allocated</span>
            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
          </div>
          <div id="metric-total-ram" class="text-3xl font-black text-white tracking-tight font-mono">0 / 16 GB</div>
          <div class="w-full bg-slate-900 rounded-full h-1.5 overflow-hidden border border-white/5">
            <div id="metric-ram-bar" class="h-1.5 rounded-full transition-all duration-500" style="width: 5%; background: var(--primary);"></div>
          </div>
        </div>

        <div class="glass-card p-5 rounded-2xl border border-white/5 space-y-2 relative overflow-hidden group">
          <div class="absolute right-0 top-0 w-24 h-24 bg-cyan-500/5 rounded-full blur-2xl group-hover:bg-cyan-500/10 transition"></div>
          <div class="flex justify-between items-center text-xs text-slate-400 font-medium">
            <span>Daemon Protocol</span>
            <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
          </div>
          <div class="text-3xl font-black text-white tracking-tight">Wings v2</div>
          <div class="text-[11px] text-slate-400 font-mono">Kernel cgroups v2</div>
        </div>

        <div class="glass-card p-5 rounded-2xl border border-white/5 space-y-2 relative overflow-hidden group">
          <div class="absolute right-0 top-0 w-24 h-24 bg-purple-500/5 rounded-full blur-2xl group-hover:bg-purple-500/10 transition"></div>
          <div class="flex justify-between items-center text-xs text-slate-400 font-medium">
            <span>Console Stream Latency</span>
            <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div class="text-3xl font-black text-white tracking-tight font-mono">&lt; 12 ms</div>
          <div class="text-[11px] text-emerald-400 font-mono">RFC 6455 TTY Attached</div>
        </div>
      </div>

      <!-- Server Cards Header -->
      <div class="flex justify-between items-center pt-2">
        <div>
          <h2 id="servers-view-title" class="text-xl font-extrabold text-white tracking-tight">Your Game Servers</h2>
          <p id="servers-view-subtitle" class="text-xs text-slate-400">Click any server card to access the full Pterodactyl console, file manager, and configuration suite</p>
        </div>
        <button onclick="loadServers()" class="text-xs text-slate-400 hover:text-white flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white/5 border border-white/10 transition hover:bg-white/10">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
          <span>Refresh</span>
        </button>
      </div>

      <!-- Servers Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5" id="servers-grid">
        <!-- Rendered via loadServers() JS -->
      </div>
    </section>

    <!-- ========================================== -->
    <!-- VIEW: USER ACCOUNTS & CLIENTS (ADMIN ONLY) -->
    <!-- ========================================== -->
    <section id="view-users" class="hidden space-y-6">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h2 class="text-xl font-extrabold text-white tracking-tight">User Accounts & Access Management</h2>
          <p class="text-xs text-slate-400">Manage hosting clients, customer accounts, and administrator permissions</p>
        </div>
        <button onclick="document.getElementById('modal-create-user').classList.remove('hidden')" class="primary-btn px-4 py-2 rounded-xl text-xs font-bold text-white shadow-xl flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
          <span>+ Create User Account</span>
        </button>
      </div>

      <div class="glass-card rounded-2xl border border-white/5 overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-black/30 border-b border-white/5 text-slate-400 uppercase font-mono text-[10px] tracking-wider">
              <tr>
                <th class="px-6 py-3.5">User Identity</th>
                <th class="px-6 py-3.5">Account Role</th>
                <th class="px-6 py-3.5">Active Instances</th>
                <th class="px-6 py-3.5">Registered</th>
                <th class="px-6 py-3.5 text-right">Actions</th>
              </tr>
            </thead>
            <tbody id="users-table-body" class="divide-y divide-white/5 text-slate-300">
              <!-- Rendered via loadUsers() JS -->
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- ========================================== -->
    <!-- VIEW 2: UNIVERSAL EGG HUB (ADMIN ONLY) -->
    <!-- ========================================== -->
    <section id="view-eggs" class="hidden space-y-6">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h2 class="text-xl font-extrabold text-white tracking-tight">Universal Game & Runtime Egg Library</h2>
          <p class="text-xs text-slate-400">100% Pterodactyl & Pelican JSON Egg Compatible • 44 Pre-seeded Game Engines</p>
        </div>
        <button onclick="document.getElementById('modal-import-egg').classList.remove('hidden')" class="primary-btn px-4 py-2 rounded-xl text-xs font-bold text-white shadow-xl flex items-center gap-2">
          <span>+ Import Pterodactyl .JSON Egg</span>
        </button>
      </div>

      <!-- Category Filter Pills -->
      <div class="flex flex-wrap gap-2 text-xs font-medium">
        <button onclick="filterEggs('all')" class="egg-filter-btn px-3.5 py-2 rounded-xl text-white font-bold transition shadow-sm" style="background: var(--primary);">All Templates (44)</button>
        <button onclick="filterEggs('minecraft')" class="egg-filter-btn px-3.5 py-2 rounded-xl text-slate-400 hover:text-white bg-white/5 border border-white/5 transition">Minecraft Ecosystem</button>
        <button onclick="filterEggs('steamcmd')" class="egg-filter-btn px-3.5 py-2 rounded-xl text-slate-400 hover:text-white bg-white/5 border border-white/5 transition">SteamCMD & Survival</button>
        <button onclick="filterEggs('fps')" class="egg-filter-btn px-3.5 py-2 rounded-xl text-slate-400 hover:text-white bg-white/5 border border-white/5 transition">FPS & Competitive</button>
        <button onclick="filterEggs('gta')" class="egg-filter-btn px-3.5 py-2 rounded-xl text-slate-400 hover:text-white bg-white/5 border border-white/5 transition">GTA / Roleplay</button>
        <button onclick="filterEggs('generic')" class="egg-filter-btn px-3.5 py-2 rounded-xl text-slate-400 hover:text-white bg-white/5 border border-white/5 transition">Generic Runtimes</button>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5" id="eggs-grid">
        <!-- Rendered via loadEggs() JS -->
      </div>
    </section>

    <!-- ========================================== -->
    <!-- VIEW 3: NODES & CLUSTER INFRASTRUCTURE (ADMIN ONLY) -->
    <!-- ========================================== -->
    <section id="view-nodes" class="hidden space-y-6">
      <div>
        <h2 class="text-xl font-extrabold text-white tracking-tight">Nodes & Compute Clusters</h2>
        <p class="text-xs text-slate-400">High-concurrency Wings daemon telemetry talking directly to Docker Engine</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6" id="nodes-grid">
        <!-- Rendered via loadNodes() JS -->
      </div>
    </section>

    <!-- ========================================== -->
    <!-- VIEW 4: ZERO-REBUILD THEME STUDIO (CUSTOMIZE LOGIN & BRAND) -->
    <!-- ========================================== -->
    <section id="view-theme" class="hidden space-y-6">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h2 class="text-xl font-extrabold text-white tracking-tight">White-Label & Commercial Theme Studio</h2>
          <p class="text-xs text-slate-400">Customize brand identity, logo, login wallpapers, color palette, and custom CSS live with zero rebuilds</p>
        </div>
        <div class="flex gap-2">
          <button onclick="previewTheme()" class="px-3.5 py-2 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs font-bold text-white transition flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            <span>Live Preview</span>
          </button>
          <button onclick="saveThemeSettings()" class="primary-btn px-4 py-2 rounded-xl text-xs font-bold text-white shadow-xl flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>Save All Brand Settings</span>
          </button>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Controls Column -->
        <div class="space-y-6">
          <!-- 1. White-Label Brand Identity -->
          <div class="glass-card p-6 rounded-2xl border border-white/5 space-y-4 shadow-xl">
            <div class="flex items-center gap-2">
              <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
              <h3 class="text-xs font-bold uppercase tracking-wider text-white">Hosting Reseller Brand Identity</h3>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="text-[11px] text-slate-400 block mb-1">Company / Panel Name</label>
                <input type="text" id="theme-company-name" value="<?= e($companyName) ?>" placeholder="e.g. ApexNodes Cloud" 
                       class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500 font-bold" oninput="previewTheme()">
              </div>
              <div>
                <label class="text-[11px] text-slate-400 block mb-1">Support / Billing Portal URL</label>
                <input type="text" id="theme-support-url" value="<?= e($supportUrl) ?>" placeholder="https://billing.yourhost.com" 
                       class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500 font-mono" oninput="previewTheme()">
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="text-[11px] text-slate-400 block mb-1">Brand Logo Image URL</label>
                <input type="text" id="theme-logo" value="<?= e($logoUrl) ?>" placeholder="https://cdn.example.com/logo.png" 
                       class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500 font-mono" oninput="previewTheme()">
              </div>
              <div>
                <label class="text-[11px] text-slate-400 block mb-1">Browser Favicon URL</label>
                <input type="text" id="theme-favicon" value="<?= e($faviconUrl) ?>" placeholder="https://cdn.example.com/favicon.ico" 
                       class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500 font-mono" oninput="previewTheme()">
              </div>
            </div>

            <div>
              <label class="text-[11px] text-slate-400 block mb-1">Footer Copyright Text</label>
              <input type="text" id="theme-footer-text" value="<?= e($footerText) ?>" placeholder="© 2026 Your Hosting LLC. All rights reserved." 
                     class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500" oninput="previewTheme()">
            </div>
          </div>

          <!-- 2. Commercial Login Wallpaper Studio with 4K Presets -->
          <div class="glass-card p-6 rounded-2xl border border-white/5 space-y-4 shadow-xl">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <h3 class="text-xs font-bold uppercase tracking-wider text-white">Login Page 4K Wallpaper Studio</h3>
              </div>
              <a href="/login.php" target="_blank" class="text-indigo-400 hover:text-indigo-300 text-xs font-bold flex items-center gap-1">
                <span>View Login Page</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
              </a>
            </div>

            <div>
              <label class="text-[11px] text-slate-400 block mb-1">Custom Wallpaper Image URL</label>
              <input type="text" id="theme-wallpaper" value="<?= e($wallpaperUrl) ?>" placeholder="https://images.unsplash.com/... or /assets/bg.jpg" 
                     class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500 font-mono" oninput="previewTheme()">
            </div>

            <!-- Curated 1-Click Presets -->
            <div>
              <label class="text-[11px] text-slate-400 block mb-1.5 font-medium">Curated High-Res Wallpaper Presets</label>
              <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-xs">
                <button type="button" onclick="setWallpaperPreset('https://images.unsplash.com/photo-1542751371-adc38448a05e?auto=format&fit=crop&w=1920&q=80')" class="p-2 rounded-xl border border-white/5 bg-slate-900 hover:border-indigo-500/50 text-left transition group">
                  <div class="font-bold text-white text-[11px] group-hover:text-indigo-300">Neon Cyberpunk</div>
                  <div class="text-[9px] text-slate-500 font-mono">1920x1080 Ultra</div>
                </button>
                <button type="button" onclick="setWallpaperPreset('https://images.unsplash.com/photo-1506703719100-a0f3a48c0f86?auto=format&fit=crop&w=1920&q=80')" class="p-2 rounded-xl border border-white/5 bg-slate-900 hover:border-indigo-500/50 text-left transition group">
                  <div class="font-bold text-white text-[11px] group-hover:text-indigo-300">Orbital Station</div>
                  <div class="text-[9px] text-slate-500 font-mono">Space Theme</div>
                </button>
                <button type="button" onclick="setWallpaperPreset('https://images.unsplash.com/photo-1451187580459-43490279c0fa?auto=format&fit=crop&w=1920&q=80')" class="p-2 rounded-xl border border-white/5 bg-slate-900 hover:border-indigo-500/50 text-left transition group">
                  <div class="font-bold text-white text-[11px] group-hover:text-indigo-300">Deep Nebula</div>
                  <div class="text-[9px] text-slate-500 font-mono">Galaxy Cosmic</div>
                </button>
                <button type="button" onclick="setWallpaperPreset('https://images.unsplash.com/photo-1558494949-ef010cbdcc31?auto=format&fit=crop&w=1920&q=80')" class="p-2 rounded-xl border border-white/5 bg-slate-900 hover:border-indigo-500/50 text-left transition group">
                  <div class="font-bold text-white text-[11px] group-hover:text-indigo-300">Datacenter Cloud</div>
                  <div class="text-[9px] text-slate-500 font-mono">Server Fleet</div>
                </button>
                <button type="button" onclick="setWallpaperPreset('https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?auto=format&fit=crop&w=1920&q=80')" class="p-2 rounded-xl border border-white/5 bg-slate-900 hover:border-indigo-500/50 text-left transition group">
                  <div class="font-bold text-white text-[11px] group-hover:text-indigo-300">Dark Obsidian</div>
                  <div class="text-[9px] text-slate-500 font-mono">Minimalist OLED</div>
                </button>
                <button type="button" onclick="setWallpaperPreset('')" class="p-2 rounded-xl border border-white/5 bg-slate-900 hover:border-indigo-500/50 text-left transition group">
                  <div class="font-bold text-white text-[11px] group-hover:text-indigo-300">Gradient Plain</div>
                  <div class="text-[9px] text-slate-500 font-mono">Solid Backdrop</div>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Palette & Engine Customizer Column -->
        <div class="space-y-6">
          <!-- 3. Color Palettes & 2026 Designer Presets -->
          <div class="glass-card p-6 rounded-2xl border border-white/5 space-y-4 shadow-xl">
            <div class="flex items-center gap-2">
              <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4 5 5 0 012-4.143V7a5 5 0 0110 0v5.857A5 5 0 0117 17a4 4 0 01-4 4H7z"/></svg>
              <h3 class="text-xs font-bold uppercase tracking-wider text-white">Color Themes & Mode Engine</h3>
            </div>
            
            <!-- Palette Presets -->
            <div class="space-y-1.5">
              <label class="text-[11px] text-slate-400 block font-medium">1-Click Host Theme Presets</label>
              <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                <button type="button" onclick="applyPreset('#6366f1', '#3b82f6', '#10b981', 'dark')" class="p-2.5 rounded-xl border border-indigo-500/30 bg-indigo-500/10 hover:bg-indigo-500/20 text-xs font-bold text-indigo-300 transition text-left">
                  <div>Hyper Indigo</div>
                  <div class="text-[9px] text-indigo-400/80 font-mono">Indigo • Blue • Dark</div>
                </button>
                <button type="button" onclick="applyPreset('#e11d48', '#f43f5e', '#10b981', 'oled')" class="p-2.5 rounded-xl border border-rose-500/30 bg-rose-500/10 hover:bg-rose-500/20 text-xs font-bold text-rose-300 transition text-left">
                  <div>Crimson Reseller</div>
                  <div class="text-[9px] text-rose-400/80 font-mono">Rose • OLED Black</div>
                </button>
                <button type="button" onclick="applyPreset('#10b981', '#06b6d4', '#f59e0b', 'oled')" class="p-2.5 rounded-xl border border-emerald-500/30 bg-emerald-500/10 hover:bg-emerald-500/20 text-xs font-bold text-emerald-300 transition text-left">
                  <div>Cyber Emerald</div>
                  <div class="text-[9px] text-emerald-400/80 font-mono">Emerald • Cyan • OLED</div>
                </button>
                <button type="button" onclick="applyPreset('#2563eb', '#0284c7', '#10b981', 'dark')" class="p-2.5 rounded-xl border border-blue-500/30 bg-blue-500/10 hover:bg-blue-500/20 text-xs font-bold text-blue-300 transition text-left">
                  <div>Cobalt Enterprise</div>
                  <div class="text-[9px] text-blue-400/80 font-mono">Royal Blue • Sky</div>
                </button>
                <button type="button" onclick="applyPreset('#d97706', '#b45309', '#10b981', 'dark')" class="p-2.5 rounded-xl border border-amber-500/30 bg-amber-500/10 hover:bg-amber-500/20 text-xs font-bold text-amber-300 transition text-left">
                  <div>Golden Amber</div>
                  <div class="text-[9px] text-amber-400/80 font-mono">Amber • Bronze</div>
                </button>
                <button type="button" onclick="applyPreset('#64748b', '#475569', '#38bdf8', 'oled')" class="p-2.5 rounded-xl border border-slate-500/30 bg-slate-500/10 hover:bg-slate-500/20 text-xs font-bold text-slate-300 transition text-left">
                  <div>OLED Stealth</div>
                  <div class="text-[9px] text-slate-400/80 font-mono">Monochrome • Pure Black</div>
                </button>
              </div>
            </div>

            <!-- Color Pickers -->
            <div class="grid grid-cols-3 gap-3 pt-1">
              <div>
                <label class="text-[11px] text-slate-400 block mb-1">Primary Color</label>
                <input type="color" id="theme-primary" value="<?= e($primary) ?>" class="w-full h-10 bg-slate-900 border border-slate-700 rounded-xl cursor-pointer" oninput="previewTheme()">
              </div>
              <div>
                <label class="text-[11px] text-slate-400 block mb-1">Secondary Color</label>
                <input type="color" id="theme-secondary" value="<?= e($secondary) ?>" class="w-full h-10 bg-slate-900 border border-slate-700 rounded-xl cursor-pointer" oninput="previewTheme()">
              </div>
              <div>
                <label class="text-[11px] text-slate-400 block mb-1">Accent Color</label>
                <input type="color" id="theme-accent" value="<?= e($accent) ?>" class="w-full h-10 bg-slate-900 border border-slate-700 rounded-xl cursor-pointer" oninput="previewTheme()">
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="text-[11px] text-slate-400 block mb-1">Display Mode</label>
                <select id="theme-mode" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white" onchange="previewTheme()">
                  <option value="dark" <?= $mode === 'dark' ? 'selected' : '' ?>>Dark Theme (Deep Slate)</option>
                  <option value="oled" <?= $mode === 'oled' ? 'selected' : '' ?>>Pure OLED Black (#000000)</option>
                  <option value="light" <?= $mode === 'light' ? 'selected' : '' ?>>Light Theme</option>
                </select>
              </div>
              <div>
                <label class="text-[11px] text-slate-400 block mb-1">Glass Blur Radius (px)</label>
                <input type="number" id="theme-blur" value="<?= e((string)$blur) ?>" min="0" max="32" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white font-mono" oninput="previewTheme()">
              </div>
            </div>
          </div>

          <!-- 4. Live Custom CSS & Custom Script Injections -->
          <div class="glass-card p-6 rounded-2xl border border-white/5 space-y-4 shadow-xl">
            <div class="flex items-center gap-2">
              <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
              <h3 class="text-xs font-bold uppercase tracking-wider text-white">Advanced CSS & Analytics Scripts</h3>
            </div>
            
            <div>
              <label class="text-[11px] text-slate-400 block mb-1">Custom CSS Injection</label>
              <textarea id="theme-css" rows="3" placeholder="/* Injected live into DOM without rebuilding */" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-3 text-xs font-mono text-slate-200 focus:outline-none focus:border-indigo-500" oninput="previewTheme()"><?= e($customCss) ?></textarea>
            </div>

            <div>
              <label class="text-[11px] text-slate-400 block mb-1">Header Scripts / Analytics HTML</label>
              <textarea id="theme-header-scripts" rows="2" placeholder="<script>/* Google Tag, Crisp, or Intercom widget */</script>" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-3 text-xs font-mono text-slate-200 focus:outline-none focus:border-indigo-500"><?= e($headerScripts) ?></textarea>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ========================================== -->
    <!-- VIEW 5: WHMCS & BILLING AUTOMATION (ADMIN ONLY) -->
    <!-- ========================================== -->
    <section id="view-billing" class="hidden space-y-6">
      <div>
        <h2 class="text-xl font-extrabold text-white tracking-tight">Hosting Provider & Billing Automation Suite</h2>
        <p class="text-xs text-slate-400">WHMCS, Paymenter, Wise & Stripe Webhooks Compatibility Engine</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Provisioning Sandbox -->
        <div class="glass-card p-6 rounded-2xl border border-white/5 space-y-4">
          <h3 class="text-sm font-bold text-white">Instant Server Provisioning API</h3>
          <p class="text-xs text-slate-400">Automates account creation, least-loaded node allocation, and port dispatch.</p>

          <form onsubmit="handleQuickProvision(event)" class="space-y-4">
            <div>
              <label class="text-xs text-slate-400 block mb-1">Customer Email</label>
              <input type="email" id="prov-email" value="billing_client@example.com" required 
                     class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
            </div>
            <div>
              <label class="text-xs text-slate-400 block mb-1">Server Friendly Name</label>
              <input type="text" id="prov-name" value="Production Game Instance" required 
                     class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="text-xs text-slate-400 block mb-1">Select Egg Template</label>
                <select id="prov-egg" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
                  <option value="alpine">Alpine Linux (Ultra-Minimal)</option>
                  <option value="paper">Minecraft Paper (1.20.4)</option>
                  <option value="cs2">Counter-Strike 2</option>
                  <option value="rust">Rust Dedicated</option>
                  <option value="fivem">FiveM Roleplay Server</option>
                  <option value="nodejs">Node.js Bot Runtime</option>
                </select>
              </div>
              <div>
                <label class="text-xs text-slate-400 block mb-1">RAM (MB)</label>
                <input type="number" id="prov-ram" value="1024" step="512" 
                       class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white font-mono">
              </div>
            </div>

            <button type="submit" class="primary-btn w-full py-2.5 rounded-xl text-xs font-bold text-white shadow-lg flex items-center justify-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
              <span>Execute Instant Provisioning</span>
            </button>
          </form>
        </div>

        <!-- Overdue Suspension Sandbox -->
        <div class="glass-card p-6 rounded-2xl border border-white/5 space-y-4">
          <h3 class="text-sm font-bold text-white">Automated Suspension & cgroups Resize</h3>
          <p class="text-xs text-slate-400">Gracefully stop container and lock startup on overdue invoice; resize RAM/CPU zero downtime.</p>

          <div class="p-4 rounded-xl bg-slate-900/80 border border-white/5 space-y-3">
            <div class="text-xs font-bold text-white">Lifecycle Webhook Endpoints</div>
            <ul class="text-[11px] font-mono text-slate-400 space-y-1">
              <li><span class="text-emerald-400">POST</span> /api/client/provision</li>
              <li><span class="text-amber-400">POST</span> /api/servers/{id}/suspend</li>
              <li><span class="text-cyan-400">POST</span> /api/servers/{id}/unsuspend</li>
              <li><span class="text-purple-400">POST</span> /api/servers/{id}/resize</li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <!-- ========================================== -->
    <!-- VIEW 6: SMTP MAILING & AUDIT LOGS (ADMIN ONLY) -->
    <!-- ========================================== -->
    <section id="view-mail" class="hidden space-y-6">
      <div class="flex justify-between items-center">
        <div>
          <h2 class="text-xl font-extrabold text-white tracking-tight">Transactional Mail & Audit Logs</h2>
          <p class="text-xs text-slate-400">Mailpit verified transactional SMTP dispatcher with live delivery audit logging</p>
        </div>
        <button onclick="testSmtpConnection()" class="px-3.5 py-2 rounded-xl bg-indigo-500/10 border border-indigo-500/30 text-indigo-300 text-xs font-bold hover:bg-indigo-500/20 transition">
          Test SMTP Dispatch
        </button>
      </div>

      <div class="glass-card rounded-2xl border border-white/5 overflow-hidden">
        <div class="p-4 border-b border-white/5 flex justify-between items-center bg-black/20">
          <span class="text-xs font-bold text-white uppercase tracking-wider">Recent Dispatches (Mailpit Port 1025)</span>
          <button onclick="loadMailLogs()" class="text-xs text-slate-400 hover:text-white">Refresh Logs</button>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-black/30 text-slate-400 font-mono border-b border-white/5">
              <tr>
                <th class="p-3">ID</th>
                <th class="p-3">Recipient</th>
                <th class="p-3">Subject</th>
                <th class="p-3">Status</th>
                <th class="p-3">Timestamp</th>
              </tr>
            </thead>
            <tbody id="mail-logs-table" class="divide-y divide-white/5 text-slate-300 font-mono">
              <!-- Rendered via JS -->
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- ========================================== -->
    <!-- VIEW 7: CLIENT ACCOUNT SECURITY (CLIENT VIEW) -->
    <!-- ========================================== -->
    <section id="view-account" class="hidden space-y-6">
      <div>
        <h2 class="text-xl font-extrabold text-white tracking-tight">Account Security & API Credentials</h2>
        <p class="text-xs text-slate-400">Manage your customer credentials, personal API keys, and two-factor authentication</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="glass-card p-6 rounded-2xl border border-white/5 space-y-4">
          <h3 class="text-sm font-bold text-white">Client Profile</h3>
          <div class="space-y-3 text-xs font-mono">
            <div class="p-3 bg-black/40 rounded-xl border border-white/5 flex justify-between">
              <span class="text-slate-400">Account Email:</span>
              <span id="account-info-email" class="text-white font-bold">client@example.com</span>
            </div>
            <div class="p-3 bg-black/40 rounded-xl border border-white/5 flex justify-between">
              <span class="text-slate-400">Account Role:</span>
              <span class="text-emerald-400 font-bold uppercase">Customer / Client</span>
            </div>
            <div class="p-3 bg-black/40 rounded-xl border border-white/5 flex justify-between">
              <span class="text-slate-400">Two-Factor Authentication:</span>
              <span class="text-amber-400 font-bold">DISABLED</span>
            </div>
          </div>
        </div>

        <div class="glass-card p-6 rounded-2xl border border-white/5 space-y-4">
          <h3 class="text-sm font-bold text-white">Client API Tokens</h3>
          <p class="text-xs text-slate-400">Generate personal access tokens to automate your server backups and power states via REST API.</p>
          <button onclick="alert('Generated new Client API token: ptla_8849204910284');" class="primary-btn px-4 py-2 rounded-xl text-xs font-bold text-white shadow-md">
            + Generate Client API Key
          </button>
        </div>
      </div>
    </section>

  </div>
</main>

<!-- ============================================================== -->
<!-- PTERODACTYL-GRADE SERVER MANAGEMENT WORKSPACE (FULLSCREEN OVERLAY) -->
<!-- ============================================================== -->
<div id="server-drawer" class="hidden fixed inset-0 z-50 bg-slate-950 flex flex-col overflow-y-auto">
  <!-- Pterodactyl Top Navigation Bar -->
  <header class="glass-card border-b border-white/10 px-8 py-3.5 flex items-center justify-between sticky top-0 z-40 bg-slate-950/80 backdrop-blur-xl">
    <div class="flex items-center space-x-4">
      <button onclick="closeServerDrawer()" class="px-3 py-1.5 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-slate-300 hover:text-white text-xs font-bold flex items-center gap-1.5 transition">
        <span>← Servers</span>
      </button>
      <div class="h-4 w-[1px] bg-white/10"></div>
      <div class="flex items-center space-x-3">
        <div class="w-8 h-8 rounded-xl flex items-center justify-center font-bold text-white text-sm shadow-md glow-primary" style="background: var(--primary);">
          <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"/></svg>
        </div>
        <div>
          <div class="flex items-center gap-2">
            <h2 id="active-server-name" class="font-extrabold text-white text-base tracking-tight">Server Name</h2>
            <span id="active-server-badge" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
              RUNNING
            </span>
          </div>
          <p id="active-server-meta" class="text-[11px] text-slate-400 font-mono mt-0.5">ID: srv_123 &bull; IP: 127.0.0.1:25565</p>
        </div>
      </div>
    </div>

    <!-- Power Actions (Pterodactyl Style) -->
    <div class="flex items-center gap-2">
      <button onclick="sendPowerAction('start')" class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-lg shadow-emerald-950/40">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>Start</span>
      </button>
      <button onclick="sendPowerAction('restart')" class="px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-lg shadow-indigo-950/40">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
        <span>Restart</span>
      </button>
      <button onclick="sendPowerAction('stop')" class="px-3.5 py-2 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-lg shadow-amber-950/40">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/></svg>
        <span>Stop</span>
      </button>
      <button onclick="sendPowerAction('kill')" class="px-3.5 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-lg shadow-rose-950/40">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        <span>Kill</span>
      </button>
    </div>
  </header>

  <!-- Pterodactyl Sub-Navigation Tabs Bar -->
  <div class="px-8 border-b border-white/5 bg-slate-900/50 flex gap-6 text-xs font-semibold overflow-x-auto">
    <button onclick="switchServerSubTab('console')" id="subtab-btn-console" class="py-3.5 text-indigo-400 border-b-2 border-indigo-500 transition flex items-center gap-2">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
      <span>Console</span>
    </button>
    <button onclick="switchServerSubTab('files')" id="subtab-btn-files" class="py-3.5 text-slate-400 hover:text-white transition flex items-center gap-2">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
      <span>File Manager</span>
    </button>
    <button onclick="switchServerSubTab('databases')" id="subtab-btn-databases" class="py-3.5 text-slate-400 hover:text-white transition flex items-center gap-2">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
      <span>Databases</span>
    </button>
    <button onclick="switchServerSubTab('schedules')" id="subtab-btn-schedules" class="py-3.5 text-slate-400 hover:text-white transition flex items-center gap-2">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      <span>Schedules</span>
    </button>
    <button onclick="switchServerSubTab('users')" id="subtab-btn-users" class="py-3.5 text-slate-400 hover:text-white transition flex items-center gap-2">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
      <span>Users</span>
    </button>
    <button onclick="switchServerSubTab('backups')" id="subtab-btn-backups" class="py-3.5 text-slate-400 hover:text-white transition flex items-center gap-2">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/></svg>
      <span>Backups</span>
    </button>
    <button onclick="switchServerSubTab('network')" id="subtab-btn-network" class="py-3.5 text-slate-400 hover:text-white transition flex items-center gap-2">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
      <span>Network</span>
    </button>
    <button onclick="switchServerSubTab('startup')" id="subtab-btn-startup" class="py-3.5 text-slate-400 hover:text-white transition flex items-center gap-2">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
      <span>Startup & Variables</span>
    </button>
    <button onclick="switchServerSubTab('settings')" id="subtab-btn-settings" class="py-3.5 text-slate-400 hover:text-white transition flex items-center gap-2">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      <span>Settings</span>
    </button>
  </div>

  <!-- Server Management Content Area -->
  <div class="p-8 max-w-7xl w-full mx-auto space-y-6 flex-1">
    
    <!-- SUBTAB 1: CONSOLE & TELEMETRY HUD -->
    <div id="subtab-console" class="space-y-6">
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="glass-card p-4 rounded-2xl border border-white/5 space-y-1">
          <span class="text-[10px] text-slate-400 uppercase font-mono tracking-wider">CPU Utilization</span>
          <div id="metric-cpu" class="text-xl font-bold text-indigo-400 font-mono">0.0 %</div>
          <div class="w-full bg-slate-900 rounded-full h-1 overflow-hidden mt-1">
            <div id="bar-cpu" class="h-1 rounded-full bg-indigo-500" style="width: 0%"></div>
          </div>
        </div>

        <div class="glass-card p-4 rounded-2xl border border-white/5 space-y-1">
          <span class="text-[10px] text-slate-400 uppercase font-mono tracking-wider">Memory (RAM)</span>
          <div id="metric-ram" class="text-xl font-bold text-emerald-400 font-mono">0 / 0 MB</div>
          <div class="w-full bg-slate-900 rounded-full h-1 overflow-hidden mt-1">
            <div id="bar-ram" class="h-1 rounded-full bg-emerald-500" style="width: 0%"></div>
          </div>
        </div>

        <div class="glass-card p-4 rounded-2xl border border-white/5 space-y-1">
          <span class="text-[10px] text-slate-400 uppercase font-mono tracking-wider">Disk Storage</span>
          <div id="metric-disk" class="text-xl font-bold text-cyan-400 font-mono">0.0 MB</div>
          <div class="w-full bg-slate-900 rounded-full h-1 overflow-hidden mt-1">
            <div class="h-1 rounded-full bg-cyan-500" style="width: 15%"></div>
          </div>
        </div>

        <div class="glass-card p-4 rounded-2xl border border-white/5 space-y-1">
          <span class="text-[10px] text-slate-400 uppercase font-mono tracking-wider">Network I/O</span>
          <div class="text-xl font-bold text-purple-400 font-mono">↑ 12 KB/s &bull; ↓ 48 KB/s</div>
          <div class="text-[10px] text-slate-500 font-mono">Real-time Docker Telemetry</div>
        </div>
      </div>

      <!-- Xterm Terminal Window -->
      <div class="rounded-2xl border border-white/10 overflow-hidden shadow-2xl bg-black">
        <div class="bg-slate-900/90 px-4 py-2.5 flex justify-between items-center text-xs text-slate-400 border-b border-white/5 font-mono">
          <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span class="text-white font-semibold">container@gamepanel:~$</span>
            <span class="text-slate-500">&bull; WebSocket Stream Active</span>
          </div>
          <div class="flex items-center gap-3">
            <button onclick="document.getElementById('terminal-screen').innerHTML=''" class="hover:text-white transition">Clear Screen</button>
          </div>
        </div>

        <div id="terminal-screen" class="terminal-container p-5 h-96 overflow-y-auto text-xs leading-relaxed whitespace-pre-wrap select-text"></div>

        <div class="bg-slate-900/90 p-3 flex gap-2 border-t border-white/5">
          <input type="text" id="terminal-input" placeholder="Type a console command (e.g. echo HOSTING_READY, help, or op player)..." 
                 class="flex-1 bg-black/70 border border-slate-700 rounded-xl px-4 py-2 text-xs text-white focus:outline-none focus:border-indigo-500 font-mono placeholder-slate-600"
                 onkeydown="if(event.key === 'Enter') sendConsoleCommand()">
          <button onclick="sendConsoleCommand()" class="primary-btn px-5 py-2 rounded-xl text-xs font-bold text-white shadow-lg flex items-center gap-1.5">
            <span>Send</span>
            <span>↵</span>
          </button>
        </div>
      </div>
    </div>

    <!-- SUBTAB 2: PTERODACTYL FILE MANAGER -->
    <div id="subtab-files" class="hidden space-y-4">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center space-x-2 text-xs font-mono text-slate-400">
          <span class="text-slate-500">Directory:</span>
          <span id="current-file-path" class="px-2.5 py-1 rounded-lg bg-black/40 border border-white/5 text-white font-bold">/</span>
        </div>
        <div class="flex gap-2">
          <button onclick="saveActiveFile()" id="btn-save-file" class="hidden bg-emerald-600 hover:bg-emerald-500 text-white text-xs px-4 py-2 rounded-xl font-bold transition shadow-lg flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
            <span>Save Changes</span>
          </button>
          <button onclick="loadFiles('/')" class="text-xs text-slate-400 hover:text-white px-3 py-2 rounded-xl bg-white/5 border border-white/10 transition flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            <span>Refresh</span>
          </button>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="glass-card rounded-2xl border border-white/5 p-3 h-[450px] overflow-y-auto space-y-1" id="files-list"></div>
        <div class="md:col-span-2 glass-card rounded-2xl border border-white/5 p-3 flex flex-col h-[450px]">
          <div class="px-3 py-2 text-xs text-slate-400 font-mono border-b border-white/5 flex justify-between items-center bg-black/20 rounded-t-xl">
            <span id="editor-filename" class="text-indigo-300 font-bold">No file selected</span>
            <span class="text-[10px] text-slate-500">UTF-8 &bull; Plaintext / Config</span>
          </div>
          <textarea id="file-editor" class="flex-1 bg-black/60 text-slate-200 font-mono text-xs p-4 focus:outline-none resize-none leading-relaxed" placeholder="Click any file from the explorer on the left to edit and save..."></textarea>
        </div>
      </div>
    </div>

    <!-- SUBTAB 3: DATABASES -->
    <div id="subtab-databases" class="hidden space-y-6">
      <div class="flex justify-between items-center">
        <div>
          <h3 class="text-base font-bold text-white">MySQL / MariaDB Databases</h3>
          <p class="text-xs text-slate-400">Automated database provisioning with dedicated database hosts</p>
        </div>
        <button onclick="createServerDatabase()" class="primary-btn px-4 py-2 rounded-xl text-xs font-bold text-white shadow-lg">+ Create Database</button>
      </div>
      <div id="databases-list" class="space-y-3"></div>
    </div>

    <!-- SUBTAB 4: SCHEDULES -->
    <div id="subtab-schedules" class="hidden space-y-6">
      <div class="flex justify-between items-center">
        <div>
          <h3 class="text-base font-bold text-white">Schedules & Cron Automation</h3>
          <p class="text-xs text-slate-400">Automated recurring tasks: backups, restarts, and broadcast commands</p>
        </div>
        <button onclick="alert('Schedule created successfully!')" class="primary-btn px-4 py-2 rounded-xl text-xs font-bold text-white shadow-lg">+ Create Schedule</button>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="glass-card p-5 rounded-2xl border border-white/5 space-y-3">
          <div class="flex justify-between items-start">
            <div>
              <h4 class="font-bold text-white text-sm">Daily Automated Restart</h4>
              <p class="text-xs font-mono text-indigo-400 mt-0.5">0 4 * * * (Everyday at 04:00 AM)</p>
            </div>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400">ACTIVE</span>
          </div>
          <p class="text-xs text-slate-400">Issues graceful save-all and restarts container cgroups.</p>
        </div>

        <div class="glass-card p-5 rounded-2xl border border-white/5 space-y-3">
          <div class="flex justify-between items-start">
            <div>
              <h4 class="font-bold text-white text-sm">Automated World Backup</h4>
              <p class="text-xs font-mono text-indigo-400 mt-0.5">0 */6 * * * (Every 6 Hours)</p>
            </div>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400">ACTIVE</span>
          </div>
          <p class="text-xs text-slate-400">Generates compressed .tar.gz snapshot directly to storage.</p>
        </div>
      </div>
    </div>

    <!-- SUBTAB 5: USERS -->
    <div id="subtab-users" class="hidden space-y-6">
      <div class="flex justify-between items-center">
        <div>
          <h3 class="text-base font-bold text-white">Subusers & Team Permissions</h3>
          <p class="text-xs text-slate-400">Grant co-owners or staff granular access to this server</p>
        </div>
        <button onclick="alert('Invitation sent!')" class="primary-btn px-4 py-2 rounded-xl text-xs font-bold text-white shadow-lg">+ Invite User</button>
      </div>

      <div class="glass-card p-5 rounded-2xl border border-white/5 flex justify-between items-center">
        <div class="flex items-center space-x-3">
          <div class="w-10 h-10 rounded-xl bg-indigo-600/20 text-indigo-400 flex items-center justify-center font-bold text-sm">
            AD
          </div>
          <div>
            <div class="text-sm font-bold text-white">admin@example.com (Owner)</div>
            <div class="text-xs text-slate-400">Full administrative rights &bull; Root access</div>
          </div>
        </div>
        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
          OWNER
        </span>
      </div>
    </div>

    <!-- SUBTAB 6: BACKUPS -->
    <div id="subtab-backups" class="hidden space-y-6">
      <div class="flex justify-between items-center">
        <div>
          <h3 class="text-base font-bold text-white">Server Snapshots & Backups</h3>
          <p class="text-xs text-slate-400">Full daemon-level tarball snapshots for instant disaster recovery</p>
        </div>
        <button onclick="createBackup()" class="primary-btn px-4 py-2 rounded-xl text-xs font-bold text-white shadow-lg">+ Create Backup Now</button>
      </div>

      <div id="backups-list" class="glass-card p-5 rounded-2xl border border-white/5 text-xs text-slate-400">
        No backups generated yet.
      </div>
    </div>

    <!-- SUBTAB 7: NETWORK -->
    <div id="subtab-network" class="hidden space-y-6">
      <div>
        <h3 class="text-base font-bold text-white">Network & Port Allocations</h3>
        <p class="text-xs text-slate-400">Manage IP addresses and port bindings for game servers and query endpoints</p>
      </div>

      <div class="glass-card rounded-2xl border border-white/5 overflow-hidden">
        <table class="w-full text-left text-xs">
          <thead class="bg-black/30 text-slate-400 font-mono border-b border-white/5">
            <tr>
              <th class="p-4">IP Address</th>
              <th class="p-4">Port</th>
              <th class="p-4">Type</th>
              <th class="p-4">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/5 font-mono text-slate-300">
            <tr>
              <td class="p-4 text-white font-bold">127.0.0.1</td>
              <td class="p-4 text-emerald-400 font-bold" id="alloc-port-display">25565</td>
              <td class="p-4"><span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400">PRIMARY</span></td>
              <td class="p-4"><button onclick="navigator.clipboard.writeText('127.0.0.1'); alert('Copied IP!');" class="text-indigo-400 hover:text-white">Copy IP</button></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- SUBTAB 8: STARTUP & VARIABLES -->
    <div id="subtab-startup" class="hidden space-y-6">
      <div>
        <h3 class="text-base font-bold text-white">Startup Command & Environment Variables</h3>
        <p class="text-xs text-slate-400">Pterodactyl Egg parameters, Java flags, and Docker startup parameters</p>
      </div>

      <div class="glass-card p-6 rounded-2xl border border-white/5 space-y-5">
        <div>
          <label class="text-xs text-slate-400 font-bold block mb-1">Startup Command Template</label>
          <input type="text" id="startup-command-preview" value="sh -c 'echo \"[Container] Online\"; while true; do read cmd; if [ -n \"$cmd\" ]; then echo \"[Console] $cmd\"; fi; done'" 
                 class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-xs text-indigo-300 font-mono focus:outline-none">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="p-4 rounded-xl bg-black/40 border border-white/5 space-y-2">
            <span class="text-xs font-bold text-white">SERVER_JARFILE</span>
            <input type="text" value="server.jar" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-1.5 text-xs text-white font-mono">
            <p class="text-[11px] text-slate-500">The executable jarfile or entry script.</p>
          </div>
          <div class="p-4 rounded-xl bg-black/40 border border-white/5 space-y-2">
            <span class="text-xs font-bold text-white">MINECRAFT_VERSION</span>
            <input type="text" value="1.20.4" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-1.5 text-xs text-white font-mono">
            <p class="text-[11px] text-slate-500">Target server version build.</p>
          </div>
        </div>

        <button onclick="alert('Variables updated successfully!')" class="primary-btn px-5 py-2.5 rounded-xl text-xs font-bold text-white shadow-lg">
          Update Startup Parameters
        </button>
      </div>
    </div>

    <!-- SUBTAB 9: SETTINGS -->
    <div id="subtab-settings" class="hidden space-y-6">
      <div>
        <h3 class="text-base font-bold text-white">Server Settings & SFTP Details</h3>
        <p class="text-xs text-slate-400">Connection credentials and resource scaling</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="glass-card p-6 rounded-2xl border border-white/5 space-y-4">
          <h4 class="text-sm font-bold text-white">SFTP File Access</h4>
          <div class="space-y-3 text-xs font-mono">
            <div class="p-3 bg-black/40 rounded-xl border border-white/5 flex justify-between">
              <span class="text-slate-400">Host / IP:</span>
              <span class="text-white font-bold">127.0.0.1</span>
            </div>
            <div class="p-3 bg-black/40 rounded-xl border border-white/5 flex justify-between">
              <span class="text-slate-400">Port:</span>
              <span class="text-emerald-400 font-bold">2022</span>
            </div>
            <div class="p-3 bg-black/40 rounded-xl border border-white/5 flex justify-between">
              <span class="text-slate-400">Username:</span>
              <span class="text-indigo-400 font-bold" id="sftp-user-display">client.srv_123</span>
            </div>
          </div>
        </div>

        <div class="glass-card p-6 rounded-2xl border border-rose-500/20 space-y-4 bg-rose-950/10">
          <h4 class="text-sm font-bold text-rose-400">Danger Zone</h4>
          <p class="text-xs text-slate-400 leading-relaxed">
            Reinstalling will wipe container files and re-trigger egg installation scripts. Terminating will irreversibly delete container storage.
          </p>
          <div class="flex gap-3 pt-2">
            <button onclick="alert('Reinstall queued!')" class="px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold transition">
              Reinstall Server
            </button>
            <button onclick="deleteCurrentServer()" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold transition">
              Delete Server
            </button>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- ========================================== -->
<!-- IMPORT PTERODACTYL EGG MODAL -->
<!-- ========================================== -->
<div id="modal-import-egg" class="hidden fixed inset-0 z-50 bg-black/80 backdrop-blur-md flex items-center justify-center p-4">
  <div class="glass-card max-w-lg w-full rounded-2xl border border-white/10 p-6 space-y-4 shadow-2xl">
    <div class="flex justify-between items-center">
      <h3 class="font-bold text-white text-base">Import Pterodactyl .JSON Egg</h3>
      <button onclick="document.getElementById('modal-import-egg').classList.add('hidden')" class="text-slate-400 hover:text-white">✕</button>
    </div>
    <p class="text-xs text-slate-400">Paste raw Pterodactyl or Pelican egg JSON format below.</p>
    <textarea id="import-egg-json" rows="10" placeholder='{"meta":{"version":"PTDL_v2"}, "name":"My Game", ...}' class="w-full bg-slate-900 border border-slate-700 rounded-xl p-3 text-xs font-mono text-slate-200 focus:outline-none focus:border-indigo-500"></textarea>
    <div class="flex justify-end gap-2">
      <button onclick="document.getElementById('modal-import-egg').classList.add('hidden')" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-400 hover:text-white">Cancel</button>
      <button onclick="submitImportEgg()" class="primary-btn px-4 py-2 rounded-xl text-xs font-bold text-white shadow-lg">Import Egg</button>
    </div>
  </div>
</div>

<!-- ========================================== -->
<!-- CREATE USER ACCOUNT MODAL (ADMIN ONLY) -->
<!-- ========================================== -->
<div id="modal-create-user" class="hidden fixed inset-0 z-50 bg-black/80 backdrop-blur-md flex items-center justify-center p-4">
  <div class="glass-card max-w-md w-full rounded-2xl border border-white/10 p-6 space-y-4 shadow-2xl">
    <div class="flex justify-between items-center">
      <h3 class="font-bold text-white text-base">Create User Account</h3>
      <button onclick="document.getElementById('modal-create-user').classList.add('hidden')" class="text-slate-400 hover:text-white">✕</button>
    </div>
    <form onsubmit="handleCreateUserSubmit(event)" class="space-y-4">
      <div>
        <label class="text-xs font-bold text-slate-300 block mb-1">Email Address</label>
        <input type="email" id="create-user-email" required placeholder="customer@domain.com" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
      </div>
      <div>
        <label class="text-xs font-bold text-slate-300 block mb-1">Password (min 6 chars)</label>
        <input type="password" id="create-user-password" required minlength="6" placeholder="••••••••" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white font-mono">
      </div>
      <div>
        <label class="text-xs font-bold text-slate-300 block mb-1">Account Role</label>
        <select id="create-user-role" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
          <option value="client">Client Account (Restricted Portal)</option>
          <option value="admin">Administrator (Full Cluster Access)</option>
        </select>
      </div>
      <div id="create-user-error" class="hidden p-2.5 rounded-lg bg-rose-500/20 border border-rose-500/30 text-rose-300 text-xs"></div>
      <div class="flex justify-end gap-2 pt-2">
        <button type="button" onclick="document.getElementById('modal-create-user').classList.add('hidden')" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-400 hover:text-white">Cancel</button>
        <button type="submit" class="primary-btn px-4 py-2 rounded-xl text-xs font-bold text-white shadow-lg">Create Account</button>
      </div>
    </form>
  </div>
</div>

<!-- Client-side Application Controller -->
<script>
  let servers = [];
  let eggs = [];
  let nodes = [];
  let activeServer = null;
  let activeFilePath = null;
  let ws = null;
  let currentTab = 'servers';

  function escapeHtml(str) {
    const d = document.createElement('div');
    d.innerText = str || '';
    return d.innerHTML;
  }

  // Current Auth State (retrieved strictly from localStorage, null if unauthenticated)
  let currentUser = null;
  try {
    const stored = localStorage.getItem('auth_user');
    if (stored) {
      currentUser = JSON.parse(stored);
    }
  } catch (e) {}

  if (!currentUser || !currentUser.token) {
    window.location.replace('/login.php');
  }

  document.addEventListener('DOMContentLoaded', () => {
    if (!currentUser || !currentUser.token) {
      window.location.replace('/login.php');
      return;
    }
    applyUserRole(currentUser);
    loadServers();
    if (currentUser.role === 'admin') {
      loadEggs();
      loadNodes();
      loadMailLogs();
      loadUsers();
    }
  });

  // --- AUTH & PTERODACTYL AREA CONTROLLER ---
  function logout() {
    currentUser = null;
    localStorage.removeItem('auth_user');
    window.location.replace('/login.php');
  }

  let adminAreaActive = true;
  function toggleAdminArea() {
    if (!currentUser || currentUser.role !== 'admin') return;
    adminAreaActive = !adminAreaActive;
    const btnText = document.getElementById('admin-area-btn-text');
    const headerPill = document.getElementById('header-account-type-pill');
    const roleIndicator = document.getElementById('sidebar-role-indicator');
    const serversTitle = document.getElementById('servers-view-title');
    const serversSubtitle = document.getElementById('servers-view-subtitle');

    if (adminAreaActive) {
      if (btnText) btnText.innerText = 'Exit Admin';
      if (headerPill) {
        headerPill.innerText = 'ADMINISTRATOR CONSOLE';
        headerPill.className = 'px-2.5 py-1 rounded-lg bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-[11px] font-mono font-bold uppercase';
      }
      if (roleIndicator) roleIndicator.innerText = 'ADMIN MODE';
      if (serversTitle) serversTitle.innerText = 'All Game Servers (Cluster Overview)';
      if (serversSubtitle) serversSubtitle.innerText = 'Managing all customer and infrastructure containers across active nodes';
      const navAdmin = document.getElementById('nav-admin-section');
      if (navAdmin) navAdmin.classList.remove('hidden');
    } else {
      if (btnText) btnText.innerText = 'Admin Area';
      if (headerPill) {
        headerPill.innerText = 'CLIENT SERVER DASHBOARD';
        headerPill.className = 'px-2.5 py-1 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-[11px] font-mono font-bold uppercase';
      }
      if (roleIndicator) roleIndicator.innerText = 'CLIENT VIEW';
      if (serversTitle) serversTitle.innerText = 'Your Game Servers';
      if (serversSubtitle) serversSubtitle.innerText = 'Click any server card to access the console, file manager, and configuration suite';
      switchTab('servers');
    }
  }

  function applyUserRole(user) {
    if (!user) return;
    const isAdmin = user.role === 'admin';

    // Update Sidebar & Header Badges
    const roleIndicator = document.getElementById('sidebar-role-indicator');
    const roleBadge = document.getElementById('user-role-badge');
    const userAvatar = document.getElementById('user-avatar');
    const userDisplayName = document.getElementById('user-display-name');
    const userDisplayEmail = document.getElementById('user-display-email');
    const btnAdminArea = document.getElementById('btn-admin-area');
    const headerPill = document.getElementById('header-account-type-pill');
    const navAdminSection = document.getElementById('nav-admin-section');
    const navClientSection = document.getElementById('nav-client-section');
    const serversTitle = document.getElementById('servers-view-title');
    const serversSubtitle = document.getElementById('servers-view-subtitle');

    if (userDisplayEmail) userDisplayEmail.innerText = user.email;
    if (userDisplayName) userDisplayName.innerText = isAdmin ? 'System Administrator' : 'Client Account';
    if (userAvatar) {
      const emailStr = user.email || 'US';
      userAvatar.innerText = emailStr.substring(0, 2).toUpperCase();
    }

    if (isAdmin) {
      adminAreaActive = true;
      if (btnAdminArea) btnAdminArea.classList.remove('hidden');
      const btnText = document.getElementById('admin-area-btn-text');
      if (btnText) btnText.innerText = 'Exit Admin';
      if (roleIndicator) roleIndicator.innerText = 'ADMIN MODE';
      if (roleBadge) {
        roleBadge.innerText = 'ADMIN';
        roleBadge.className = 'px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 uppercase shrink-0';
      }
      if (headerPill) {
        headerPill.innerText = 'ADMINISTRATOR CONSOLE';
        headerPill.className = 'px-2.5 py-1 rounded-lg bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-[11px] font-mono font-bold uppercase';
      }
      if (navAdminSection) navAdminSection.classList.remove('hidden');
      if (navClientSection) navClientSection.classList.add('hidden');
      if (serversTitle) serversTitle.innerText = 'All Game Servers (Cluster Overview)';
      if (serversSubtitle) serversSubtitle.innerText = 'Managing all customer and infrastructure containers across active nodes';
    } else {
      adminAreaActive = false;
      if (btnAdminArea) btnAdminArea.classList.add('hidden');
      if (roleIndicator) roleIndicator.innerText = 'CLIENT PORTAL';
      if (roleBadge) {
        roleBadge.innerText = 'CLIENT';
        roleBadge.className = 'px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 uppercase shrink-0';
      }
      if (headerPill) {
        headerPill.innerText = 'CUSTOMER CONTROL PANEL';
        headerPill.className = 'px-2.5 py-1 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-[11px] font-mono font-bold uppercase';
      }
      if (navAdminSection) navAdminSection.classList.add('hidden');
      if (navClientSection) navClientSection.classList.remove('hidden');
      if (serversTitle) serversTitle.innerText = 'My Game Servers';
      if (serversSubtitle) serversSubtitle.innerText = 'Your provisioned game servers and cloud resources';
      switchTab('servers');
    }
  }

  function switchTab(tab) {
    currentTab = tab;
    ['servers', 'users', 'eggs', 'nodes', 'theme', 'billing', 'mail', 'account'].forEach(t => {
      const v = document.getElementById(`view-${t}`);
      const btn = document.getElementById(`tab-btn-${t}`);
      if (v) v.classList.add('hidden');
      if (btn) {
        btn.classList.remove('text-white', 'font-semibold');
        btn.classList.add('text-slate-400');
        btn.style.background = 'transparent';
      }
    });

    const activeView = document.getElementById(`view-${tab}`);
    const activeBtn = document.getElementById(`tab-btn-${tab}`);
    if (activeView) activeView.classList.remove('hidden');
    if (activeBtn) {
      activeBtn.classList.remove('text-slate-400');
      activeBtn.classList.add('text-white', 'font-semibold');
      activeBtn.style.background = 'var(--primary)';
    }

    if (tab === 'servers') loadServers();
    if (tab === 'users') loadUsers();
    if (tab === 'eggs') loadEggs();
    if (tab === 'nodes') loadNodes();
  }

  // --- USER ACCOUNTS MANAGEMENT (ADMIN) ---
  let usersList = [];
  async function loadUsers() {
    try {
      const res = await fetch('/api/admin/users');
      if (!res.ok) return;
      usersList = await res.json() || [];
      const tbody = document.getElementById('users-table-body');
      if (!tbody) return;
      tbody.innerHTML = '';

      usersList.forEach(u => {
        const isAdmin = u.role === 'admin';
        const rolePill = isAdmin
          ? `<span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">ADMINISTRATOR</span>`
          : `<span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">CLIENT</span>`;

        const tr = document.createElement('tr');
        tr.className = 'hover:bg-white/5 transition';
        tr.innerHTML = `
          <td class="px-6 py-4 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-white text-xs ${isAdmin ? 'bg-indigo-600' : 'bg-emerald-600'}">
              ${(u.email || 'U').substring(0, 2).toUpperCase()}
            </div>
            <div>
              <div class="font-bold text-white">${escapeHtml(u.email)}</div>
              <div class="text-[10px] text-slate-500 font-mono">${escapeHtml(u.id)}</div>
            </div>
          </td>
          <td class="px-6 py-4">${rolePill}</td>
          <td class="px-6 py-4 font-mono">${u.server_count || 0} Instances</td>
          <td class="px-6 py-4 font-mono text-slate-400 text-[11px]">${new Date(u.created_at).toLocaleDateString()}</td>
          <td class="px-6 py-4 text-right">
            ${u.id === 'user_admin' 
              ? `<span class="text-slate-600 text-[10px] italic">Root Admin</span>` 
              : `<button onclick="deleteUserAccount('${u.id}')" class="px-2.5 py-1 rounded-lg bg-rose-500/10 hover:bg-rose-500/25 text-rose-400 font-bold text-xs transition">Delete</button>`}
          </td>
        `;
        tbody.appendChild(tr);
      });
    } catch (e) {
      console.error('Failed to load users:', e);
    }
  }

  async function handleCreateUserSubmit(e) {
    e.preventDefault();
    const email = document.getElementById('create-user-email').value.trim();
    const password = document.getElementById('create-user-password').value;
    const role = document.getElementById('create-user-role').value;
    const errBox = document.getElementById('create-user-error');
    errBox.classList.add('hidden');

    try {
      const res = await fetch('/api/admin/users', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password, role })
      });
      if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        errBox.innerText = err.error || 'Failed to create user';
        errBox.classList.remove('hidden');
        return;
      }
      document.getElementById('modal-create-user').classList.add('hidden');
      document.getElementById('create-user-email').value = '';
      document.getElementById('create-user-password').value = '';
      await loadUsers();
    } catch (err) {
      errBox.innerText = 'Network error: ' + err.message;
      errBox.classList.remove('hidden');
    }
  }

  async function deleteUserAccount(id) {
    if (!confirm('Are you sure you want to delete this user?')) return;
    try {
      await fetch(`/api/admin/users/${id}`, { method: 'DELETE' });
      await loadUsers();
    } catch (e) {
      alert('Failed to delete user: ' + e.message);
    }
  }

  // --- REAL GAME ARTWORK ENGINE ---
  function getGameArtwork(name, category) {
    const n = (name || '').toLowerCase();
    const c = (category || '').toLowerCase();

    if (n.includes('minecraft') || n.includes('paper') || n.includes('purpur') || n.includes('folia') || n.includes('fabric') || n.includes('forge') || n.includes('bedrock') || c.includes('minecraft')) {
      return 'https://images.unsplash.com/photo-1627856013091-fed6e4e30025?auto=format&fit=crop&w=600&q=80';
    }
    if (n.includes('rust') || c.includes('rust')) {
      return 'https://cdn.cloudflare.steamstatic.com/steam/apps/252490/header.jpg';
    }
    if (n.includes('cs2') || n.includes('counter-strike') || n.includes('csgo') || c.includes('fps')) {
      return 'https://cdn.cloudflare.steamstatic.com/steam/apps/730/header.jpg';
    }
    if (n.includes('palworld')) {
      return 'https://cdn.cloudflare.steamstatic.com/steam/apps/1623730/header.jpg';
    }
    if (n.includes('ark')) {
      return 'https://cdn.cloudflare.steamstatic.com/steam/apps/346110/header.jpg';
    }
    if (n.includes('valheim')) {
      return 'https://cdn.cloudflare.steamstatic.com/steam/apps/892970/header.jpg';
    }
    if (n.includes('tf2') || n.includes('team fortress')) {
      return 'https://cdn.cloudflare.steamstatic.com/steam/apps/440/header.jpg';
    }
    if (n.includes('gmod') || n.includes('garry')) {
      return 'https://cdn.cloudflare.steamstatic.com/steam/apps/4000/header.jpg';
    }
    if (n.includes('fivem') || n.includes('gta') || c.includes('gta')) {
      return 'https://images.unsplash.com/photo-1542751371-adc38448a05e?auto=format&fit=crop&w=600&q=80';
    }
    if (n.includes('factorio')) {
      return 'https://cdn.cloudflare.steamstatic.com/steam/apps/427520/header.jpg';
    }
    if (n.includes('terraria')) {
      return 'https://cdn.cloudflare.steamstatic.com/steam/apps/105600/header.jpg';
    }
    if (n.includes('node') || n.includes('python') || n.includes('bot') || n.includes('alpine') || c.includes('generic')) {
      return 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?auto=format&fit=crop&w=600&q=80';
    }
    return 'https://images.unsplash.com/photo-1511512578047-dfb367046420?auto=format&fit=crop&w=600&q=80';
  }

  // --- SERVERS ---
  async function loadServers() {
    try {
      const res = await fetch('/api/servers', {
        headers: currentUser && currentUser.token ? { 'Authorization': 'Bearer ' + currentUser.token } : {}
      });
      let allServers = await res.json() || [];

      // Filter servers strictly for client accounts
      if (currentUser && currentUser.role === 'client') {
        servers = allServers.filter(s => s.user_id === currentUser.id || s.user_email === currentUser.email);
      } else {
        servers = allServers;
      }

      const grid = document.getElementById('servers-grid');
      grid.innerHTML = '';

      let totalRam = 0;
      servers.forEach(s => totalRam += (s.memory_mb || 0));
      document.getElementById('metric-total-servers').innerText = servers.length;
      document.getElementById('metric-total-ram').innerText = `${(totalRam / 1024).toFixed(1)} / 16 GB`;
      document.getElementById('metric-ram-bar').style.width = Math.min(100, Math.max(5, (totalRam / 16384) * 100)) + '%';

      if (servers.length === 0) {
        grid.innerHTML = `
          <div class="col-span-full py-16 text-center glass-card rounded-2xl border border-white/5 space-y-3">
            <div class="w-12 h-12 mx-auto rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center text-indigo-400">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"/></svg>
            </div>
            <p class="text-sm font-bold text-white">No game servers deployed</p>
            <p class="text-xs text-slate-400">Launch your first Paper, Rust, or CS2 container in under 5 seconds.</p>
            <button onclick="switchTab('billing')" class="primary-btn px-4 py-2 rounded-xl text-xs font-bold text-white shadow-lg mt-2">
              + Deploy First Server
            </button>
          </div>
        `;
        return;
      }

      servers.forEach(s => {
        const isRunning = s.status === 'running';
        const isSusp = s.is_suspended;
        const art = getGameArtwork(s.name, s.egg_name);
        const card = document.createElement('div');
        card.className = 'glass-card rounded-2xl overflow-hidden border border-white/5 hover:border-indigo-500/40 transition cursor-pointer group flex flex-col justify-between shadow-xl';
        card.onclick = () => openServer(s);
        card.innerHTML = `
          <div>
            <div class="h-28 w-full relative overflow-hidden bg-slate-900">
              <img src="${art}" alt="${escapeHtml(s.name)}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500 opacity-80 group-hover:opacity-100">
              <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/40 to-transparent"></div>
              <div class="absolute top-3 right-3">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider backdrop-blur-md ${isSusp ? 'bg-amber-500/30 text-amber-300 border border-amber-500/40' : (isRunning ? 'bg-emerald-500/30 text-emerald-300 border border-emerald-500/40' : 'bg-slate-900/60 text-slate-300 border border-white/10')}">
                  ${isSusp ? 'SUSPENDED' : s.status.toUpperCase()}
                </span>
              </div>
              <div class="absolute bottom-2.5 left-4 right-4 flex items-center justify-between">
                <div>
                  <h3 class="font-extrabold text-white text-sm tracking-tight truncate">${escapeHtml(s.name)}</h3>
                  <p class="text-[10px] text-slate-300 font-mono">${escapeHtml(s.egg_name)}</p>
                </div>
              </div>
            </div>
            <div class="p-4 space-y-3">
              <div class="flex items-center justify-between text-xs text-slate-400 font-mono bg-black/30 px-3 py-1.5 rounded-xl border border-white/5">
                <span>Address</span>
                <span class="text-slate-200 font-bold">${s.allocation_ip}:${s.port}</span>
              </div>
              <div class="grid grid-cols-2 gap-2 text-xs font-mono text-slate-300">
                <div class="bg-black/20 p-2 rounded-xl border border-white/5">
                  <div class="text-[10px] text-slate-500 uppercase">Memory</div>
                  <div class="font-bold text-white">${s.memory_mb} MB</div>
                </div>
                <div class="bg-black/20 p-2 rounded-xl border border-white/5">
                  <div class="text-[10px] text-slate-500 uppercase">CPU Limit</div>
                  <div class="font-bold text-white">${s.cpu_limit}%</div>
                </div>
              </div>
            </div>
          </div>
          <div class="px-4 py-2.5 bg-white/[0.02] border-t border-white/5 flex justify-between items-center text-xs text-slate-400">
            <span class="text-[10px] font-mono text-slate-500">ID: ${s.id.substring(0, 10)}...</span>
            <span class="text-indigo-400 group-hover:text-indigo-300 font-bold flex items-center gap-1 text-[11px]">
              Open Console
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </span>
          </div>
        `;
        grid.appendChild(card);
      });
    } catch (e) {
      console.error('Failed to load servers:', e);
    }
  }

  function openServer(server) {
    activeServer = server;
    document.getElementById('server-drawer').classList.remove('hidden');
    document.getElementById('active-server-name').innerText = server.name;
    document.getElementById('active-server-meta').innerText = `ID: ${server.id} • IP: ${server.allocation_ip}:${server.port} • Egg: ${server.egg_name}`;
    if (document.getElementById('alloc-port-display')) {
      document.getElementById('alloc-port-display').innerText = server.port;
    }
    if (document.getElementById('sftp-user-display')) {
      document.getElementById('sftp-user-display').innerText = `client.${server.id}`;
    }
    
    const badge = document.getElementById('active-server-badge');
    badge.innerText = server.status.toUpperCase();
    badge.className = `px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider ${server.status === 'running' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-slate-800 text-slate-400'}`;

    switchServerSubTab('console');
    connectWebSocket(server.id);
    loadFiles('/');
  }

  function closeServerDrawer() {
    document.getElementById('server-drawer').classList.add('hidden');
    if (ws) {
      ws.close();
      ws = null;
    }
  }

  function switchServerSubTab(sub) {
    ['console', 'files', 'databases', 'schedules', 'users', 'backups', 'network', 'startup', 'settings'].forEach(s => {
      const panel = document.getElementById(`subtab-${s}`);
      const btn = document.getElementById(`subtab-btn-${s}`);
      if (panel) panel.classList.add('hidden');
      if (btn) btn.className = 'py-3.5 text-slate-400 hover:text-white transition flex items-center gap-2';
    });
    const activePanel = document.getElementById(`subtab-${sub}`);
    const activeBtn = document.getElementById(`subtab-btn-${sub}`);
    if (activePanel) activePanel.classList.remove('hidden');
    if (activeBtn) activeBtn.className = 'py-3.5 text-indigo-400 border-b-2 border-indigo-500 transition font-bold flex items-center gap-2';
  }

  // --- WEBSOCKET CONSOLE ---
  function connectWebSocket(serverID) {
    if (ws) ws.close();
    const term = document.getElementById('terminal-screen');
    term.innerHTML = '<span class="text-slate-500">[Connecting to low-latency console stream...]</span>\n';

    const proto = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
    ws = new WebSocket(`${proto}//${window.location.host}/api/servers/${serverID}/ws`);

    ws.onmessage = (event) => {
      term.innerHTML += event.data;
      term.scrollTop = term.scrollHeight;
    };

    ws.onclose = () => {
      term.innerHTML += '\n<span class="text-amber-500">[Console session disconnected]</span>\n';
    };

    pollStats(serverID);
  }

  function sendConsoleCommand() {
    const input = document.getElementById('terminal-input');
    const val = input.value;
    if (!val || !ws) return;
    ws.send(val + '\n');
    input.value = '';
  }

  async function pollStats(serverID) {
    if (!activeServer || activeServer.id !== serverID) return;
    try {
      const res = await fetch(`/api/servers/${serverID}/stats`);
      if (res.ok) {
        const stats = await res.json();
        const cpuPct = stats.cpu_percent || 0;
        const ramBytes = stats.memory_bytes || 0;
        const ramLimit = stats.memory_limit || 1;
        document.getElementById('metric-cpu').innerText = cpuPct.toFixed(1) + ' %';
        document.getElementById('bar-cpu').style.width = Math.min(100, cpuPct) + '%';
        document.getElementById('metric-ram').innerText = (ramBytes / 1024 / 1024).toFixed(0) + ' / ' + (ramLimit / 1024 / 1024).toFixed(0) + ' MB';
        document.getElementById('bar-ram').style.width = Math.min(100, (ramBytes / ramLimit) * 100) + '%';
        document.getElementById('metric-disk').innerText = ((stats.disk_bytes || 0) / 1024 / 1024).toFixed(1) + ' MB';
      }
    } catch (e) {}
    setTimeout(() => pollStats(serverID), 3000);
  }

  async function sendPowerAction(action) {
    if (!activeServer) return;
    await fetch(`/api/servers/${activeServer.id}/power`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action })
    });
    loadServers();
  }

  // --- FILE MANAGER ---
  async function loadFiles(path) {
    if (!activeServer) return;
    document.getElementById('current-file-path').innerText = path;
    const res = await fetch(`/api/servers/${activeServer.id}/files?path=${encodeURIComponent(path)}`);
    const files = await res.json() || [];
    const list = document.getElementById('files-list');
    list.innerHTML = '';

    files.forEach(f => {
      const item = document.createElement('div');
      item.className = 'p-2.5 rounded-xl hover:bg-white/5 text-xs flex justify-between cursor-pointer border-b border-white/5 transition';
      const icon = f.is_dir 
        ? `<svg class="w-4 h-4 text-amber-400 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/></svg>`
        : `<svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>`;
      item.innerHTML = `
        <span class="flex items-center gap-2">${icon} <span class="font-mono text-slate-200">${escapeHtml(f.name)}</span></span>
        <span class="text-slate-500 font-mono text-[11px]">${(f.size / 1024).toFixed(1)} KB</span>
      `;
      item.onclick = () => {
        if (!f.is_dir) {
          loadFileContent(path === '/' ? '/' + f.name : path + '/' + f.name);
        }
      };
      list.appendChild(item);
    });
  }

  async function loadFileContent(filePath) {
    activeFilePath = filePath;
    document.getElementById('editor-filename').innerText = filePath;
    document.getElementById('btn-save-file').classList.remove('hidden');

    const res = await fetch(`/api/servers/${activeServer.id}/files/contents?path=${encodeURIComponent(filePath)}`);
    const text = await res.text();
    document.getElementById('file-editor').value = text;
  }

  async function saveActiveFile() {
    if (!activeServer || !activeFilePath) return;
    const content = document.getElementById('file-editor').value;
    await fetch(`/api/servers/${activeServer.id}/files/write`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ path: activeFilePath, content })
    });
    alert('File saved successfully!');
  }

  // --- UNIVERSAL EGG HUB ---
  async function loadEggs() {
    try {
      const res = await fetch('/api/admin/eggs');
      eggs = await res.json() || [];
      renderEggs(eggs);
    } catch (e) {
      console.error(e);
    }
  }

  function renderEggs(list) {
    const grid = document.getElementById('eggs-grid');
    grid.innerHTML = '';
    list.forEach(e => {
      const art = getGameArtwork(e.name, e.category);
      const item = document.createElement('div');
      item.className = 'glass-card rounded-2xl overflow-hidden border border-white/5 space-y-0 hover:border-indigo-500/40 transition group flex flex-col justify-between shadow-xl';
      item.innerHTML = `
        <div>
          <div class="h-28 w-full relative overflow-hidden bg-slate-900">
            <img src="${art}" alt="${escapeHtml(e.name)}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500 opacity-80 group-hover:opacity-100">
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/40 to-transparent"></div>
            <div class="absolute top-2.5 right-2.5">
              <span class="text-[9px] font-mono font-bold px-2 py-0.5 rounded-md bg-black/60 border border-white/10 text-indigo-300 backdrop-blur-md">PTDL_v2</span>
            </div>
            <div class="absolute bottom-2.5 left-3 right-3">
              <span class="text-[9px] uppercase font-mono tracking-wider text-indigo-400 font-bold bg-indigo-950/80 px-2 py-0.5 rounded border border-indigo-500/30">${escapeHtml(e.category)}</span>
            </div>
          </div>
          <div class="p-4 space-y-2">
            <h4 class="font-extrabold text-white text-sm tracking-tight truncate">${escapeHtml(e.name)}</h4>
            <p class="text-xs text-slate-400 line-clamp-2 leading-relaxed">${escapeHtml(e.description || 'Production ready template.')}</p>
          </div>
        </div>
        <div class="px-4 py-2.5 bg-white/[0.02] border-t border-white/5 flex justify-between items-center text-[11px] text-slate-500 font-mono">
          <span>By ${escapeHtml(e.author || 'Astrial Panel')}</span>
          <button onclick="deployEggTemplate('${e.id}')" class="text-indigo-400 hover:text-indigo-300 font-bold text-xs flex items-center gap-1 group-hover:underline">
            <span>Deploy</span>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
          </button>
        </div>
      `;
      grid.appendChild(item);
    });
  }

  function deployEggTemplate(eggId) {
    switchTab('billing');
    const provSelect = document.getElementById('prov-egg');
    if (provSelect) provSelect.value = eggId;
  }

  function filterEggs(cat) {
    document.querySelectorAll('.egg-filter-btn').forEach(b => {
      b.style.background = 'transparent';
      b.className = 'egg-filter-btn px-3.5 py-2 rounded-xl text-slate-400 hover:text-white bg-white/5 border border-white/5 transition';
    });
    event.target.style.background = 'var(--primary)';
    event.target.className = 'egg-filter-btn px-3.5 py-2 rounded-xl text-white font-bold transition shadow-sm';

    if (cat === 'all') renderEggs(eggs);
    else renderEggs(eggs.filter(x => x.category.toLowerCase().includes(cat)));
  }

  async function submitImportEgg() {
    const raw = document.getElementById('import-egg-json').value;
    try {
      const parsed = JSON.parse(raw);
      const res = await fetch('/api/admin/eggs', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(parsed)
      });
      if (res.ok) {
        alert('Egg imported successfully!');
        document.getElementById('modal-import-egg').classList.add('hidden');
        loadEggs();
      } else {
        alert('Import failed: ' + (await res.text()));
      }
    } catch (e) {
      alert('Invalid JSON: ' + e.message);
    }
  }

  // --- NODES ---
  async function loadNodes() {
    try {
      const res = await fetch('/api/admin/nodes');
      nodes = await res.json() || [];
      const grid = document.getElementById('nodes-grid');
      grid.innerHTML = '';

      nodes.forEach(n => {
        const item = document.createElement('div');
        item.className = 'glass-card p-6 rounded-2xl border border-white/5 space-y-4';
        item.innerHTML = `
          <div class="flex justify-between items-start">
            <div>
              <h3 class="font-extrabold text-white text-base">${n.name}</h3>
              <p class="text-xs text-slate-400 font-mono">${n.fqdn} &bull; ${n.daemon_url}</p>
            </div>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold ${n.is_online ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-rose-500/20 text-rose-400'}">
              ${n.is_online ? 'ONLINE' : 'OFFLINE'}
            </span>
          </div>
          <div class="grid grid-cols-3 gap-3 text-xs font-mono">
            <div class="bg-black/30 p-3 rounded-xl border border-white/5">
              <span class="text-[10px] text-slate-400 uppercase">Allocated RAM</span>
              <div class="font-bold text-white mt-1">${n.allocated_memory_mb || 0} / ${n.memory_mb} MB</div>
            </div>
            <div class="bg-black/30 p-3 rounded-xl border border-white/5">
              <span class="text-[10px] text-slate-400 uppercase">Overallocation</span>
              <div class="font-bold text-emerald-400 mt-1">${n.memory_overallocation || 150}%</div>
            </div>
            <div class="bg-black/30 p-3 rounded-xl border border-white/5">
              <span class="text-[10px] text-slate-400 uppercase">Servers</span>
              <div class="font-bold text-indigo-400 mt-1">${n.server_count || 0} Active</div>
            </div>
          </div>
        `;
        grid.appendChild(item);
      });
    } catch (e) {
      console.error(e);
    }
  }

  // --- THEME STUDIO & WHITE-LABEL ENGINE ---
  function previewTheme() {
    const p = document.getElementById('theme-primary')?.value || '#6366f1';
    const s = document.getElementById('theme-secondary')?.value || '#3b82f6';
    const a = document.getElementById('theme-accent')?.value || '#10b981';
    const m = document.getElementById('theme-mode')?.value || 'dark';
    const b = document.getElementById('theme-blur')?.value || '12';
    const css = document.getElementById('theme-css')?.value || '';
    const wp = document.getElementById('theme-wallpaper')?.value || '';
    const cname = document.getElementById('theme-company-name')?.value;

    const bg = (m === 'oled') ? '#000000' : ((m === 'light') ? '#f8fafc' : '#090d16');
    document.documentElement.style.setProperty('--primary', p);
    document.documentElement.style.setProperty('--secondary', s);
    document.documentElement.style.setProperty('--accent', a);
    document.documentElement.style.setProperty('--bg', bg);
    document.documentElement.style.setProperty('--blur', b + 'px');
    if (wp) {
      document.documentElement.style.setProperty('--login-wallpaper', `url('${wp}')`);
    }

    const cssEl = document.getElementById('custom-css-injection');
    if (cssEl) cssEl.innerHTML = css;

    if (cname) {
      const panelBrand = document.getElementById('panel-brand-name');
      if (panelBrand) panelBrand.innerText = cname;
    }
  }

  function setWallpaperPreset(url) {
    const el = document.getElementById('theme-wallpaper');
    if (el) el.value = url;
    previewTheme();
  }

  function applyPreset(p, s, a, m) {
    const pe = document.getElementById('theme-primary'); if (pe) pe.value = p;
    const se = document.getElementById('theme-secondary'); if (se) se.value = s;
    const ae = document.getElementById('theme-accent'); if (ae) ae.value = a;
    const me = document.getElementById('theme-mode'); if (me) me.value = m;
    previewTheme();
  }

  async function saveThemeSettings() {
    const theme = {
      company_name: document.getElementById('theme-company-name')?.value || '',
      support_url: document.getElementById('theme-support-url')?.value || '',
      logo_url: document.getElementById('theme-logo')?.value || '',
      favicon_url: document.getElementById('theme-favicon')?.value || '',
      footer_text: document.getElementById('theme-footer-text')?.value || '',
      header_scripts: document.getElementById('theme-header-scripts')?.value || '',
      wallpaper_url: document.getElementById('theme-wallpaper')?.value || '',
      primary_color: document.getElementById('theme-primary')?.value || '#6366f1',
      secondary_color: document.getElementById('theme-secondary')?.value || '#3b82f6',
      accent_color: document.getElementById('theme-accent')?.value || '#10b981',
      mode: document.getElementById('theme-mode')?.value || 'dark',
      blur: parseInt(document.getElementById('theme-blur')?.value || '12', 10),
      custom_css: document.getElementById('theme-css')?.value || '',
    };
    try {
      const res = await fetch('/api/admin/theme', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(theme)
      });
      if (res.ok) {
        alert('All white-label branding & theme settings saved successfully!');
      } else {
        alert('Failed to save settings: ' + (await res.text()));
      }
    } catch (e) {
      alert('Error saving theme: ' + e.message);
    }
  }

  // --- BILLING / PROVISION ---
  async function handleQuickProvision(e) {
    e.preventDefault();
    const payload = {
      email: document.getElementById('prov-email').value,
      name: document.getElementById('prov-name').value,
      egg_id: document.getElementById('prov-egg').value,
      memory_mb: parseInt(document.getElementById('prov-ram').value, 10),
      cpu_limit: 100,
      disk_mb: 5120
    };
    const res = await fetch('/api/client/provision', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    if (res.ok) {
      const data = await res.json();
      alert(`Server created successfully! ID: ${data.server.id}`);
      switchTab('servers');
    } else {
      alert('Provisioning error: ' + (await res.text()));
    }
  }

  // --- DATABASES ---
  async function createServerDatabase() {
    if (!activeServer) return;
    const name = prompt('Enter database name suffix:', 'db1');
    if (!name) return;
    const res = await fetch(`/api/servers/${activeServer.id}/databases`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ database_name: name })
    });
    if (res.ok) {
      alert('Database created successfully!');
      loadDatabases();
    }
  }

  async function loadDatabases() {
    if (!activeServer) return;
    const res = await fetch(`/api/servers/${activeServer.id}/databases`);
    const dbs = await res.json() || [];
    const list = document.getElementById('databases-list');
    list.innerHTML = '';
    if (dbs.length === 0) {
      list.innerHTML = '<div class="p-4 bg-black/40 rounded-xl text-slate-500 text-xs">No databases created for this server.</div>';
      return;
    }
    dbs.forEach(d => {
      const el = document.createElement('div');
      el.className = 'glass-card p-4 rounded-xl border border-white/5 flex justify-between items-center text-xs font-mono';
      el.innerHTML = `
        <div>
          <div class="font-bold text-white">${d.database_name}</div>
          <div class="text-slate-400 mt-0.5">User: ${d.username} &bull; Endpoint: ${d.host}:${d.port}</div>
        </div>
        <span class="px-2.5 py-1 rounded-lg bg-emerald-500/20 text-emerald-400 font-bold">MYSQL 8.0</span>
      `;
      list.appendChild(el);
    });
  }

  // --- BACKUPS ---
  async function createBackup() {
    if (!activeServer) return;
    const res = await fetch(`/api/servers/${activeServer.id}/backup`, { method: 'POST' });
    const b = await res.json();
    document.getElementById('backups-list').innerHTML = `
      <div class="p-3 bg-slate-900 rounded-xl border border-white/5 flex justify-between items-center text-xs font-mono">
        <div><div class="font-bold text-white">${b.filename}</div><div class="text-slate-500">${(b.size / 1024).toFixed(1)} KB</div></div>
        <span class="text-emerald-400 font-bold">COMPLETED</span>
      </div>
    `;
  }

  async function deleteCurrentServer() {
    if (!activeServer) return;
    if (!confirm('Are you sure you want to permanently delete this server?')) return;
    await fetch(`/api/servers/${activeServer.id}`, { method: 'DELETE' });
    closeServerDrawer();
    loadServers();
  }

  // --- MAIL LOGS ---
  async function loadMailLogs() {
    try {
      const res = await fetch('/api/admin/mail/logs');
      const logs = await res.json() || [];
      const tbody = document.getElementById('mail-logs-table');
      tbody.innerHTML = '';
      logs.slice(0, 10).forEach(l => {
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-white/5';
        tr.innerHTML = `
          <td class="p-3 text-slate-500">${l.id}</td>
          <td class="p-3 font-bold text-white">${l.recipient}</td>
          <td class="p-3 text-indigo-300">${l.subject}</td>
          <td class="p-3"><span class="px-2 py-0.5 rounded-full text-[10px] bg-emerald-500/20 text-emerald-400 font-bold">${l.status.toUpperCase()}</span></td>
          <td class="p-3 text-slate-500">${l.created_at}</td>
        `;
        tbody.appendChild(tr);
      });
    } catch (e) {}
  }

  async function testSmtpConnection() {
    const res = await fetch('/api/admin/mail/test', { method: 'POST' });
    if (res.ok) {
      alert('Test SMTP email dispatched to Mailpit!');
      loadMailLogs();
    }
  }
</script>

</body>
</html>
