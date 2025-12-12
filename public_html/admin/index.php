<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireDM();

$current_tab = $_GET['tab'] ?? 'players';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DM Dashboard - D&D Manager</title>
    <style>
        /* Prevent flash of unstyled content */
        html {
            visibility: visible;
            opacity: 1;
        }
        body {
            background-color: #030712;
            min-height: 100vh;
        }
        header {
            background-color: rgba(17, 24, 39, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(31, 41, 55, 1);
            position: sticky;
            top: 0;
            z-index: 50;
        }
        nav {
            border-bottom: 2px solid rgba(31, 41, 55, 1);
        }
        /* Pre-style nav tabs to prevent animation flash */
        .nav-tab {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            color: #9ca3af;
            font-size: 0.875rem;
            font-weight: 500;
            white-space: nowrap;
            border-bottom: 2px solid transparent;
        }
        .nav-tab.active {
            color: #f97316;
            border-bottom-color: #f97316;
            background: rgba(249, 115, 22, 0.1);
        }
    </style>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#f97316',
                        'primary-dark': '#ea580c',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-950 min-h-screen">
    <!-- Header -->
    <header class="bg-gray-900 border-b border-gray-800 sticky top-0 z-50">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between py-4">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-primary/20 rounded-lg border border-primary flex items-center justify-center">
                        <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-white">DM Dashboard</h1>
                        <p class="text-sm text-gray-400">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></p>
                    </div>
                </div>
                <a href="/logout.php" class="flex items-center space-x-2 text-gray-400 hover:text-white transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Navigation Tabs -->
    <nav class="bg-gray-900 border-b border-gray-800 sticky top-[73px] z-40">
        <div class="container mx-auto px-4">
            <div class="flex space-x-1 overflow-x-auto scrollbar-hide">
                <a href="?tab=players" class="nav-tab <?php echo $current_tab === 'players' ? 'active' : ''; ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <span>Players</span>
                </a>
                
                <a href="?tab=characters" class="nav-tab <?php echo $current_tab === 'characters' ? 'active' : ''; ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span>Characters</span>
                </a>
                
                <a href="?tab=monsters" class="nav-tab <?php echo $current_tab === 'monsters' ? 'active' : ''; ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Monsters</span>
                </a>
                
                <a href="?tab=items" class="nav-tab <?php echo $current_tab === 'items' ? 'active' : ''; ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <span>Items</span>
                </a>
                
                <a href="?tab=spells" class="nav-tab <?php echo $current_tab === 'spells' ? 'active' : ''; ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                    </svg>
                    <span>Spells</span>
                </a>
                
                <a href="?tab=lore" class="nav-tab <?php echo $current_tab === 'lore' ? 'active' : ''; ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    <span>Lore</span>
                </a>
                
                <a href="?tab=rules" class="nav-tab <?php echo $current_tab === 'rules' ? 'active' : ''; ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>Rules</span>
                </a>
                
                <a href="?tab=messages" class="nav-tab <?php echo $current_tab === 'messages' ? 'active' : ''; ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    <span>Messages</span>
                </a>
                
                <a href="?tab=gamestate" class="nav-tab <?php echo $current_tab === 'gamestate' ? 'active' : ''; ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Game State</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Content Area -->
    <main class="container mx-auto px-4 py-8">
        <?php
        // Include the appropriate tab content
        $tab_file = "tabs/{$current_tab}.php";
        if (file_exists($tab_file)) {
            include $tab_file;
        } else {
            include 'tabs/players.php';
        }
        ?>
    </main>

    <style>
        /* Add transitions after page load to prevent flash */
        .nav-tab {
            transition: all 0.2s;
        }
        
        .nav-tab:hover {
            color: #f97316;
            background: rgba(249, 115, 22, 0.05);
        }
        
        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }
        
        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
    
    <script src="/js/polling.js"></script>
</body>
</html>
