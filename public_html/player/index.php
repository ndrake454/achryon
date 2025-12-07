<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requirePlayer();

// Get player's characters
$stmt = $conn->prepare("
    SELECT c.*, cs.*
    FROM characters c
    JOIN players p ON c.player_id = p.id
    LEFT JOIN character_stats cs ON c.id = cs.character_id
    WHERE p.user_id = ?
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$characters = $stmt->get_result();

$selected_char_id = $_GET['char'] ?? null;
$selected_tab = $_GET['tab'] ?? 'character';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Player View - D&D Manager</title>
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-white">Player View</h1>
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

    <?php if ($characters->num_rows === 0): ?>
        <!-- No Characters -->
        <div class="container mx-auto px-4 py-20 text-center">
            <svg class="w-20 h-20 text-gray-700 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <h2 class="text-2xl font-bold text-white mb-2">No Characters Assigned</h2>
            <p class="text-gray-400">Your DM hasn't assigned you any characters yet.</p>
        </div>
    <?php else: ?>
        <!-- Navigation Tabs -->
        <nav class="bg-gray-900 border-b border-gray-800 sticky top-[73px] z-40">
            <div class="container mx-auto px-4">
                <div class="flex space-x-1 overflow-x-auto">
                    <a href="?tab=character<?php echo $selected_char_id ? '&char='.$selected_char_id : ''; ?>" class="nav-tab <?php echo $selected_tab === 'character' ? 'active' : ''; ?>">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span>Character</span>
                    </a>
                    
                    <a href="?tab=battle<?php echo $selected_char_id ? '&char='.$selected_char_id : ''; ?>" class="nav-tab <?php echo $selected_tab === 'battle' ? 'active' : ''; ?>">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Battle</span>
                    </a>
                    
                    <a href="?tab=lore<?php echo $selected_char_id ? '&char='.$selected_char_id : ''; ?>" class="nav-tab <?php echo $selected_tab === 'lore' ? 'active' : ''; ?>">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        <span>Lore</span>
                    </a>
                    
                    <a href="?tab=rules<?php echo $selected_char_id ? '&char='.$selected_char_id : ''; ?>" class="nav-tab <?php echo $selected_tab === 'rules' ? 'active' : ''; ?>">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>Rules</span>
                    </a>
                    
                    <a href="?tab=messages<?php echo $selected_char_id ? '&char='.$selected_char_id : ''; ?>" class="nav-tab <?php echo $selected_tab === 'messages' ? 'active' : ''; ?>">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        <span>Messages</span>
                    </a>
                </div>
            </div>
        </nav>

        <!-- Content -->
        <main class="container mx-auto px-4 py-8">
            <?php if ($selected_tab === 'character'): 
                include 'player-tabs/character.php';
            elseif ($selected_tab === 'battle'):
                include 'player-tabs/battle.php';
            elseif ($selected_tab === 'lore'):
                include 'player-tabs/lore.php';
            elseif ($selected_tab === 'rules'):
                include 'player-tabs/rules.php';
            elseif ($selected_tab === 'messages'):
                include 'player-tabs/messages.php';
            endif; ?>
        </main>
    <?php endif; ?>

    <style>
        /* Add transitions after page load to prevent flash */
        .nav-tab {
            transition: all 0.2s;
        }
        
        .nav-tab:hover {
            color: #f97316;
            background: rgba(249, 115, 22, 0.05);
        }
    </style>
    
    <script src="/js/polling.js"></script>
</body>
</html>
