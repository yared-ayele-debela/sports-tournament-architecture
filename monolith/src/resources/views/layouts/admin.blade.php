<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link rel="icon" type="{{ asset('build/assets/favicon.png') }}" href="{{ asset('build/assets/logo.png') }}">
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>{{ config('app.name', 'Admin') }} - @yield('title', 'Dashboard')</title>

  <!-- Tailwind + App assets (Vite) -->
  @vite(['resources/css/app.css', 'resources/js/app.js'])

  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- Chart.js for dashboard charts -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <!-- Alpine.js for dropdown functionality -->
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

  <style>
    /* Sidebar sizes & behavior */
    .sidebar-expanded { width: 16rem; }        /* w-64 */
    .sidebar-collapsed { width: 4rem; }        /* w-16 */
    .sidebar-collapsed .label { display: none; } /* hide labels when collapsed */
    .sidebar-transition { transition: width .18s ease, transform .2s ease; }
    /* Off-canvas mobile */
    @media (max-width: 768px) {
      .sidebar-mobile { transform: translateX(-110%); }
      .sidebar-mobile.open { transform: translateX(0); }
    }
  </style>
</head>
<body class="min-h-screen bg-gray-100 font-inter text-gray-800">
  <div class="min-h-screen flex">

    <!-- Mobile overlay -->
    <div id="mobile-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden"></div>

    <!-- Sidebar -->
    <aside id="sidebar"
           class="fixed z-50 left-0 top-0 h-full bg-slate-800 text-slate-100 sidebar-transition sidebar-expanded sidebar-mobile shadow-lg">
      @include('components.admin.sidebar')
    </aside>

    <!-- Main area -->
    <div id="main" class="flex-1 min-h-screen flex flex-col md:pl-64">
      <!-- Topbar -->
      <header class="sticky top-0 z-30 bg-white border-b border-slate-200">
        @include('components.admin.topbar')
      </header>

      <!-- Page content -->
      <main class="flex-1 p-6">
        @yield('content')
      </main>
    </div>
  </div>

  <script>
    (function() {
      const sidebar = document.getElementById('sidebar');
      const overlay = document.getElementById('mobile-overlay');
      const toggleBtn = document.getElementById('sidebar-toggle');
      const closeMobileBtn = document.getElementById('sidebar-close');
      const userBtn = document.getElementById('user-menu-btn');
      const userMenu = document.getElementById('user-menu');

      function isMobile() { return window.innerWidth < 768; }

      // Initialize state: desktop expanded
      if (!sidebar.classList.contains('sidebar-expanded') && !sidebar.classList.contains('sidebar-collapsed')) {
        sidebar.classList.add('sidebar-expanded');
      }

      // Toggle handler (works dual: mobile open / desktop collapse)
      toggleBtn?.addEventListener('click', function(e) {
        const mobile = isMobile();
        if (mobile) {
          sidebar.classList.add('open');
          overlay.classList.remove('hidden');
          overlay.classList.add('block');
        } else {
          if (sidebar.classList.contains('sidebar-collapsed')) {
            sidebar.classList.remove('sidebar-collapsed');
            sidebar.classList.add('sidebar-expanded');
          } else {
            sidebar.classList.remove('sidebar-expanded');
            sidebar.classList.add('sidebar-collapsed');
          }
        }
      });

      // Mobile close (X inside sidebar)
      closeMobileBtn?.addEventListener('click', function() {
        sidebar.classList.remove('open');
        overlay.classList.add('hidden');
        overlay.classList.remove('block');
      });

      // Clicking overlay closes mobile sidebar
      overlay?.addEventListener('click', function() {
        sidebar.classList.remove('open');
        overlay.classList.add('hidden');
        overlay.classList.remove('block');
      });

      // Close mobile sidebar on escape
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
          sidebar.classList.remove('open');
          overlay.classList.add('hidden');
          overlay.classList.remove('block');
          userMenu?.classList.add('hidden');
        }
      });

      // User dropdown toggle and outside click handling
      userBtn?.addEventListener('click', function(e) {
        e.stopPropagation();
        userMenu?.classList.toggle('hidden');
      });

      // Prevent menu clicks from closing
      userMenu?.addEventListener('click', function(e) { e.stopPropagation(); });

      // Close on outside click
      document.addEventListener('click', function() {
        if (userMenu && !userMenu.classList.contains('hidden')) {
          userMenu.classList.add('hidden');
        }
      });

      // Keep layout correct on resize: hide overlay and mobile open when switching to desktop
      window.addEventListener('resize', function() {
        if (!isMobile()) {
          overlay?.classList.add('hidden');
          overlay?.classList.remove('block');
          sidebar.classList.remove('open');
          // ensure at least one size class exists
          if (!sidebar.classList.contains('sidebar-expanded') && !sidebar.classList.contains('sidebar-collapsed')) {
            sidebar.classList.add('sidebar-expanded');
          }
          // main padding for sidebar width
          document.getElementById('main').classList.toggle('md:pl-64', true);
        }
      });
    })();
  </script>
</body>
</html>
