<!DOCTYPE html>
<html>
<head>
    <title>Adxsway POS</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; font-family: -apple-system, sans-serif; background: #f6f6f7; }

        .topbar {
            position: fixed; top: 0; left: 0; right: 0; height: 60px;
            background: #111; color: white; display: flex; align-items: center;
            justify-content: space-between; padding: 0 20px; z-index: 200;
        }
        .topbar-left { display: flex; align-items: center; gap: 15px; min-width: 0; }
        .hamburger { background: none; border: none; color: white; font-size: 22px; cursor: pointer; padding: 4px 8px; flex-shrink: 0; }
        .topbar .shop-name { font-weight: 700; font-size: 16px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        .user-menu { position: relative; flex-shrink: 0; }
        .user-icon-btn { width: 36px; height: 36px; border-radius: 50%; background: #008060; color: white; border: none; cursor: pointer; font-size: 15px; font-weight: 600; display:flex; align-items:center; justify-content:center; }
        .user-dropdown { display: none; position: absolute; right: 0; top: 44px; background: white; border-radius: 6px; box-shadow: 0 4px 16px rgba(0,0,0,0.15); min-width: 170px; overflow: hidden; z-index: 300; }
        .user-dropdown.open { display: block; }
        .user-dropdown a, .user-dropdown button { display: block; width: 100%; text-align: left; padding: 12px 16px; font-size: 14px; color: #333; text-decoration: none; background: none; border: none; cursor: pointer; }
        .user-dropdown a:hover, .user-dropdown button:hover { background: #f5f5f5; }

        .sidebar-backdrop {
            display: none; position: fixed; top: 60px; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.4); z-index: 140;
        }
        .sidebar-backdrop.show { display: block; }

        .sidebar {
            position: fixed; top: 60px; left: 0; bottom: 0; width: 220px;
            background: #1a1a1a; color: white; padding: 20px 0; overflow-y: auto;
            transition: transform 0.25s ease; z-index: 150;
        }
        .sidebar.collapsed { transform: translateX(-220px); }
        .menu-title { padding: 12px 20px; color: #888; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 10px; }
        .sidebar a { display: block; padding: 10px 20px 10px 32px; color: #ccc; text-decoration: none; font-size: 14px; }
        .sidebar a:hover { background: #2a2a2a; color: white; }

        .main-wrap {
            margin-left: 220px; margin-top: 60px; min-height: calc(100vh - 60px);
            display: flex; flex-direction: column; transition: margin-left 0.25s ease;
        }
        .main-wrap.expanded { margin-left: 0; }

        .content { flex: 1; padding: 30px; padding-bottom: 60px; max-width: 100%; overflow-x: hidden; }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px; }

        .app-footer {
            position: fixed; bottom: 0; left: 0; right: 0; height: 44px;
            background: #111; color: #ccc; text-align: center;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; z-index: 100;
        }

        /* ===== Global responsive fixes for content built by other pages ===== */

        /* Any grid of cards (dashboard stats, report stats) collapses gracefully */
        [class*="-grid"] { display: grid; gap: 15px; }

        /* Any 2-column chart/report row stacks on smaller screens */
        .chart-row { display: grid; gap: 18px; }

        /* Filter bars wrap and inputs go full width on mobile */
        .filter-bar { flex-wrap: wrap; }
        .filter-bar input, .filter-bar select { min-width: 0; }

        /* Forms with .row (label+input pairs) stack on mobile */
        .row { flex-wrap: wrap; }
        .row > div { min-width: 200px; }

        /* Make every table horizontally scrollable without breaking layout */
        .table-responsive-wrap {
            width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch;
            border-radius: 8px; position: relative;
        }
        .table-responsive-wrap table { min-width: 600px; margin-bottom: 0; }
        .table-responsive-wrap::-webkit-scrollbar { height: 6px; }
        .table-responsive-wrap::-webkit-scrollbar-thumb { background: #ccc; border-radius: 3px; }

        @media (max-width: 900px) {
            .table-responsive-wrap { box-shadow: inset -12px 0 10px -10px rgba(0,0,0,0.08); }
            .table-responsive-wrap table th,
            .table-responsive-wrap table td {
                padding: 8px 10px !important;
                font-size: 13px !important;
                white-space: nowrap;
            }
            .table-responsive-wrap table th:first-child,
            .table-responsive-wrap table td:first-child {
                position: sticky; left: 0; background: white; z-index: 1;
            }
            .table-responsive-wrap table thead th:first-child {
                background: #f5f5f5;
            }
            .table-responsive-wrap .action-link,
            .table-responsive-wrap button {
                padding: 5px 8px !important;
                font-size: 12px !important;
            }
        }

        @media (max-width: 900px) {
            .main-wrap { margin-left: 0 !important; }
            .sidebar { transform: translateX(-220px); }
            .sidebar.mobile-open { transform: translateX(0); }
            .content { padding: 18px; padding-bottom: 60px; }
            .topbar { padding: 0 12px; }
            .topbar .shop-name { font-size: 14px; max-width: 45vw; }
            [class*="-grid"] { grid-template-columns: 1fr !important; }
            .chart-row { grid-template-columns: 1fr !important; }
            .row { flex-direction: column; gap: 10px !important; }
        }

        @media (min-width: 901px) and (max-width: 1200px) {
            [class*="-grid"] { grid-template-columns: repeat(2, 1fr) !important; }
            .chart-row { grid-template-columns: 1fr !important; }
        }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="topbar-left">
            <button type="button" class="hamburger" onclick="toggleSidebar()">☰</button>
            <div class="shop-name">{{ Auth::user()->app_display_name ?: (Auth::user()->name ?? 'Adxsway POS') }}</div>
            <div class="shop-domain-badge" style="font-size:11px;color:#888;margin-top:-4px;">{{ Auth::user()->name ?? "Not connected" }}</div>
        </div>
        <div class="user-menu">
            <button type="button" class="user-icon-btn" onclick="document.getElementById('userDropdown').classList.toggle('open')">
                {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
            </button>
            <div class="user-dropdown" id="userDropdown">
                <a href="{{ route('profile') }}">Profile</a>
        <a href="{{ route('staff.index') }}">Staff Logins</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">Logout</button>
                </form>
            </div>
        </div>
    </div>

    <div class="sidebar-backdrop" id="sidebarBackdrop" onclick="toggleSidebar()"></div>

    <div class="sidebar" id="sidebar">
        <a href="{{ route('dashboard') }}" style="font-weight:600;">Dashboard</a>

        <div class="menu-title">Inventory</div>
        <a href="{{ route('products.index') }}">Products <span style="float:right; background:#333; color:#fff; border-radius:10px; padding:1px 8px; font-size:11px;">{{ $sidebarProductsCount ?? 0 }}</span></a>
        <a href="{{ route('collections.index') }}">Collections <span style="float:right; background:#333; color:#fff; border-radius:10px; padding:1px 8px; font-size:11px;">{{ $sidebarCollectionsCount ?? 0 }}</span></a>

        <div class="menu-title">Sales</div>
        <a href="{{ route('sales.index') }}">Orders <span style="float:right; background:#333; color:#fff; border-radius:10px; padding:1px 8px; font-size:11px;">{{ $sidebarOrdersCount ?? 0 }}</span></a>
        <a href="{{ route('sales.customers') }}">Customers <span style="float:right; background:#333; color:#fff; border-radius:10px; padding:1px 8px; font-size:11px;">{{ $sidebarCustomersCount ?? 0 }}</span></a>

        <div class="menu-title">Expenses</div>
        <a href="{{ route('expenses.index') }}">All Expenses</a>
        <a href="{{ route('expense-categories.index') }}">Categories</a>

        <div class="menu-title">Purchases</div>
        <a href="{{ route('purchases.index') }}">All Purchases</a>
        <a href="{{ route('purchases.create') }}">+ New Purchase</a>

        <div class="menu-title">Reports</div>
        <a href="{{ route('reports.products') }}">Total Products</a>
        <a href="{{ route('reports.sales') }}">Total Sales</a>
        <a href="{{ route('reports.daily-sales') }}">Daily Sales</a>
        <a href="{{ route('reports.expenses') }}">Expense Report</a>
        <a href="{{ route('reports.slow-moving') }}">Slow Moving</a>
        <a href="{{ route('reports.fast-moving') }}">Fast Moving</a>
        <a href="{{ route('reports.pnl') }}">P&amp;L Report</a>
        <a href="{{ route('reports.stock-value') }}">Stock Value</a>
        <a href="{{ route('reports.category-stock') }}">Category-wise Stock</a>
        <a href="{{ route('reports.barcode-inventory') }}">Barcode Inventory</a>
        <a href="{{ route('reports.payment-type') }}">Payment Type</a>
        <a href="{{ route('reports.returns') }}">Product Returns</a>

        <div class="menu-title">Settings</div>
        <a href="{{ route('settings.index') }}">Sync Settings</a>
        <a href="{{ route('settings.currency') }}">Currency</a>
        <a href="{{ route('settings.app-name') }}">App Name</a>
        <a href="{{ route('profile') }}">Profile</a>
        <a href="{{ route('staff.index') }}">Staff Logins</a>
    </div>

    <div class="main-wrap" id="mainWrap">
        <div class="content">
            @if (session('success'))
                <div class="success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="error">{{ session('error') }}</div>
            @endif
            @yield('content')
        </div>

        <div class="app-footer">Built by Adxsway</div>
    </div>

    <script>
    function isMobile() {
        return window.innerWidth <= 900;
    }

    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        const mainWrap = document.getElementById('mainWrap');

        if (isMobile()) {
            sidebar.classList.toggle('mobile-open');
            backdrop.classList.toggle('show');
        } else {
            sidebar.classList.toggle('collapsed');
            mainWrap.classList.toggle('expanded');
        }
    }

    document.addEventListener('click', function(e) {
        const menu = document.getElementById('userDropdown');
        const btn = document.querySelector('.user-icon-btn');
        if (menu && !menu.contains(e.target) && e.target !== btn) {
            menu.classList.remove('open');
        }
    });

    // Auto-wrap every table in the content area so it can scroll horizontally on small screens
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.content table').forEach(function(table) {
            if (table.closest('.table-responsive-wrap')) return;
            const wrapper = document.createElement('div');
            wrapper.className = 'table-responsive-wrap';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);

            // Remove the scroll shadow hint once user scrolls to the end
            wrapper.addEventListener('scroll', function() {
                const atEnd = wrapper.scrollLeft + wrapper.clientWidth >= wrapper.scrollWidth - 2;
                wrapper.style.boxShadow = atEnd ? 'none' : '';
            });
        });
    });
    </script>
    <div id="globalSyncOverlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
        <div style="background:white; border-radius:12px; padding:35px 45px; text-align:center; min-width:280px; box-shadow:0 10px 40px rgba(0,0,0,0.2);">
            <div style="width:44px; height:44px; border:4px solid #e0e0e0; border-top-color:#008060; border-radius:50%; margin:0 auto 18px; animation:globalSyncSpin 0.8s linear infinite;"></div>
            <div id="globalSyncTitle" style="font-size:16px; font-weight:600; color:#222; margin-bottom:6px;">Syncing from Shopify...</div>
            <div id="globalSyncSubtitle" style="font-size:13px; color:#888;">This may take a moment. Please don't close this page.</div>
        </div>
    </div>
    <style>
    @keyframes globalSyncSpin { to { transform: rotate(360deg); } }
    </style>
    <script>
    (function() {
        function showGlobalSyncOverlay(title, subtitle) {
            const overlay = document.getElementById('globalSyncOverlay');
            if (!overlay) return;
            if (title) document.getElementById('globalSyncTitle').textContent = title;
            if (subtitle) document.getElementById('globalSyncSubtitle').textContent = subtitle;
            overlay.style.display = 'flex';
        }

        // Attach to every form whose action contains "sync" (covers all sync buttons app-wide)
        document.addEventListener('submit', function(e) {
            const form = e.target;
            if (form.tagName === 'FORM' && form.action && form.action.includes('sync')) {
                showGlobalSyncOverlay('Syncing from Shopify...', 'This may take a moment. Please don\'t close this page.');
            }
        }, true);

        // Also cover AJAX-based auto-sync timers used on report/list pages
        window.showGlobalSyncOverlay = showGlobalSyncOverlay;
    })();
    </script>
</body>
</html>
