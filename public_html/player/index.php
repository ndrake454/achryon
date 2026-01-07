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
        /* Accordion animations */
        .accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }
        .accordion-content.open {
            max-height: 2000px;
            transition: max-height 0.5s ease-in;
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
        <!-- No Characters - Campaign Introduction -->
        <div class="container mx-auto px-4 py-12 max-w-4xl">
            <!-- Header -->
            <div class="text-center mb-8">

                <h2 class="text-3xl font-bold text-white mb-3">Welcome to Achryon!</h2>
            </div>

            <!-- Status Message -->
            <div class="mb-8 bg-gray-800 border border-gray-700 rounded-lg p-4 flex items-start gap-3">
                <svg class="w-6 h-6 text-primary mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="text-white font-medium mb-1 text-lg">Waiting for Character Assignment</p>
                    <p class="text-base text-gray-400">I'll work with you to create your character and assign it to your account. Once assigned, your character sheet and all campaign resources will appear here. You can get started by reading the dropdowns below, you just click on 'em to make them expand.</p>
                </div>
            </div>

            <!-- Campaign Introduction -->
            <div class="bg-gray-900 border border-gray-800 rounded-lg p-8 space-y-4">

                <!-- Campaign Information Section -->
                <div class="border-t border-gray-700 pt-4 space-y-4">
                    <h3 class="text-2xl font-semibold text-primary mb-4 flex items-center gap-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Adventure Information
                    </h3>
                    <!-- Accordion: New to D&D? -->
                    <div class="border border-gray-700 rounded-lg overflow-hidden">
                        <button onclick="toggleAccordion('newbie')" class="w-full flex items-center justify-between p-4 bg-gray-800 hover:bg-gray-750 transition text-left">
                            <span class="font-semibold text-white flex items-center gap-2 text-xl">
                                <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                                New to D&D?
                            </span>
                            <svg id="newbie-icon" class="w-5 h-5 text-gray-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div id="newbie-content" class="accordion-content">
                            <div class="p-4 bg-gray-800/50 space-y-3 text-lg">
                                <div>
                                    <p class="font-medium text-white mb-1">No Experience Required</p>
                                    <p class="text-gray-400">Never played D&D before? Perfect! This adventure is new-player friendly. I'll teach you everything you need to know as we go. I'll guide you through the mechanics as they come up. Don't worry about knowing every rule, nobody does, and half the fun is learning together.</p>
                                </div><br>
                                <div>
                                    <p class="font-medium text-white mb-1">The Basics</p>
                                    <p class="text-gray-400">D&D is collaborative storytelling with dice. I describe situations, you describe what your character does, and we roll dice to see what happens. That's about it.</p>
                                </div><br>
                                <div>
                                    <p class="font-medium text-white mb-1">What You'll Need</p>
                                    <p class="text-gray-400">Just yourself and a way to roll dice (I have plenty of extra physical dice, but you're welcome to use your own). Your character sheet can be managed through this website, but if you'd prefer pen / paper I'll have some extra character sheets.</p>
                                </div><br>
                                <div>
                                    <p class="font-medium text-white mb-1">Resources</p>
                                    <p class="text-gray-400">The <a href="https://www.dndbeyond.com/sources/basic-rules" target="_blank" class="text-primary hover:text-primary-dark underline">D&D 5E Basic Rules</a> are free online if you want to read ahead, but it's not required. I also have some annotated rules provided on this site once your character is made.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                    <!-- Accordion: Vibe -->
                    <div class="border border-gray-700 rounded-lg overflow-hidden">
                        <button onclick="toggleAccordion('vibe')" class="w-full flex items-center justify-between p-4 bg-gray-800 hover:bg-gray-750 transition text-left">
                            <span class="font-semibold text-white flex items-center gap-2 text-xl">
                                <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Tone
                            </span>
                            <svg id="vibe-icon" class="w-5 h-5 text-gray-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div id="vibe-content" class="accordion-content">
                            <div class="p-4 bg-gray-800/50 text-lg text-gray-300">
                                <div>
                                    <p class="font-medium text-white mb-1">General Vibe</p>
                                    <p class="text-gray-400">This is a relaxed, character-driven adventure using D&D 5E 2024 rules as a general framework. We'll focus on story and fun over rulebook minutiae / technicalities. The campaign begins as a self-contained 1-2 session adventure, though it has the potential to grow into something larger if the group wants to continue.</p>
                                </div><br>
                                <div>
                                    <p class="font-medium text-white mb-1">Mood & Atmosphere</p>
                                    <p class="text-gray-400">The world feels lived-in and slightly off-kilter. There's room for humor and heartfelt moments alongside tension and danger. Not grimdark, but not consequence-free either.</p>
                                </div><br>
                                <div>
                                    <p class="font-medium text-white mb-1">Themes</p>
                                    <p class="text-gray-400">Community under pressure, the cost of peace, what happens when the quiet life gets interrupted, and how ordinary people respond to extraordinary circumstances.</p>
                                </div>
                            </div>
                            
                        </div>
                    </div>

                    <!-- Accordion: Safety Tools & Table Culture -->
                    <div class="border border-gray-700 rounded-lg overflow-hidden">
                        <button onclick="toggleAccordion('safety')" class="w-full flex items-center justify-between p-4 bg-gray-800 hover:bg-gray-750 transition text-left">
                            <span class="font-semibold text-white flex items-center gap-2 text-xl">
                                <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                Table Etiquette
                            </span>
                            <svg id="safety-icon" class="w-5 h-5 text-gray-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div id="safety-content" class="accordion-content">
                            <div class="p-4 bg-gray-800/50 space-y-3 text-lg">
                                <div>
                                    <p class="font-medium text-white mb-1">Respect & Collaboration</p>
                                    <p class="text-gray-400">DND is built around collaborative storytelling. Be respectful of other players' choices, spotlight time, and character moments.</p>
                                </div><br>
                                <div>
                                    <p class="font-medium text-white mb-1">PvP & Conflict</p>
                                    <p class="text-gray-400">Player vs. Player conflict should be rare and consensual. Characters can disagree, but do your best to keep the party together.</p>
                                </div><br>
                                <div>
                                    <p class="font-medium text-white mb-1">Character Death</p>
                                    <p class="text-gray-400">Character death is possible, but not the goal. We'll use standard 5E death saves, and I'll work with you to make deaths meaningful if they happen.</p>
                                </div><br>
                                <div>
                                <p class="font-medium text-white mb-1">Content Skip</p>
                                    <p class="text-gray-400">I don't anticipate this coming up, but if any content makes you uncomfortable / is triggering, we can skip that content, rework it, whatever you need. No judgement / questions asked.</p>
                                </div>                                
                            </div>
                        </div>
                    </div>
<br>
            <!-- Story Background Section -->
                <div class="space-y-4">
                    <h3 class="text-2xl font-semibold text-primary mb-4 flex items-center gap-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        Story Background
                    </h3>

                    <!-- Accordion: Achryon (The World) -->
                    <div class="border border-gray-700 rounded-lg overflow-hidden">
                        <button onclick="toggleAccordion('achryon')" class="w-full flex items-center justify-between p-4 bg-gray-800 hover:bg-gray-750 transition text-left">
                            <span class="font-semibold text-white flex items-center gap-2 text-xl">
                                <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Achryon
                            </span>
                            <svg id="achryon-icon" class="w-5 h-5 text-gray-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div id="achryon-content" class="accordion-content">
                            <div class="p-4 bg-gray-800/50 text-lg text-gray-300 space-y-6">
                                <p>
                                    This adventure is set in Achryon, a world that feels slightly wrong at the edges. The sky is dulling, the stars are drifting out of place, and people argue about whether the world is dying, changing, or just waking up.
                                </p>

                                <!-- Wayrest Subsection -->
                                <div class="ml-4 pl-4 border-l-2 border-primary/30">
                                    <h4 class="text-xl font-semibold text-white mb-3 flex items-center gap-2">
                                        <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                        Wayrest
                                    </h4>
                                    <p class="mb-4">
                                        Your story begins in Wayrest, a small, peaceful hamlet high in the mountains above the sea. Wayrest sits on the edge of a wide, clear reservoir that feeds the great aqueduct running down to the city of Bastionford. The days here are quiet and steady: goats on the slopes, mist on the water in the mornings, simple stone and timber houses. Everyone lives in a communal setting, sharing resources and distributing the daily workloads amongst the 11 inhabitants.
                                    </p>
                                    <p>
                                        Most people in Wayrest are retired from something dangerous. They were adventurers, soldiers, scouts, smugglers, or worse, and they came here to stop running and finally live a calm life.
                                    </p>
                                </div>

                                <!-- Bastionford Subsection -->
                                <div class="ml-4 pl-4 border-l-2 border-primary/30">
                                    <h4 class="text-xl font-semibold text-white mb-3 flex items-center gap-2">
                                        <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                        Bastionford
                                    </h4>
                                    <p class="mb-4">
                                    Bastionford is a big, loud, sea-side city built around a deep natural harbor. A line of old stone walls give it its name. From a distance, you see layered defenses and tall watchtowers facing both land and sea. Ships from all along the coast used to come and go at all hours. The air smells of salt, smoke, and fish.
                                    </p>
                                    <p class="mb-4">
                                    Fresh water is Bastionford’s weak point and lifeline. Almost none of it comes from local wells. Instead, clean mountain water flows down from the reservoir above Wayrest through a network of narrow aqueducts. You can see the main channel as it enters the city: a thick stone spine that crosses the outer wall and splits into branches that feed public fountains and temple cisterns. Any time the flow drops or the color of the water changes, the whole city feels it. People stand in long lines at the big civic basins, nervously watching the level of the water.
                                    </p>
                                </div>

                                <!-- The Lantern Bearers Subsection -->
                                <div class="ml-4 pl-4 border-l-2 border-primary/30">
                                    <h4 class="text-xl font-semibold text-white mb-3 flex items-center gap-2">
                                        <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                        </svg>
                                        The Lantern Bearers
                                    </h4>
                                    <p class="mb-4">
                                        Below the mountains, Bastionford is changing. The Lantern Bearers, a strict religious movement, have taken control of the city. They fear arcane magic, distrust outsiders, and want the world to fit into a narrow idea of what is "pure" and "proper." For now, their influence reaches Wayrest only in the form of official envoys, tax requests, and nervous rumors that drift up the mountain road with traders.
                                    </p>
                                    <p>
                                        But everyone in Wayrest knows one simple fact: <span class="text-white font-medium">Bastionford cannot live without the water that Wayrest protects.</span> That makes your quiet village much more important, and much more vulnerable, than it looks at first glance.
                                    </p>
                                </div>

                                <p>
                                    ( There is plenty more lore that will be provided once we've drafted up your character, and as always, if you have questions please feel free to send me a message. )
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Accordion: Character Creation -->
                    <div class="border border-gray-700 rounded-lg overflow-hidden">
                        <button onclick="toggleAccordion('character-creation')" class="w-full flex items-center justify-between p-4 bg-gray-800 hover:bg-gray-750 transition text-left">
                            <span class="font-semibold text-white flex items-center gap-2 text-xl">
                                <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                Character Creation
                            </span>
                            <svg id="character-creation-icon" class="w-5 h-5 text-gray-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div id="character-creation-content" class="accordion-content">
                            <div class="p-4 bg-gray-800/50 text-lg text-gray-300 space-y-6">
                                <p>
                                    For character creation: choose your race, class, and background, and decide who your character is as a person. Think about what kind of life they led before settling in Wayrest, and what they hope to find here. What's their personality, background, and what brought them to Wayrest? Who are they as a person? What do they want? What do they look like?
                                </p>
                                <p>
                                    I can roll up your attributes for you and choose most of your spells, so they fit both your concept and the world's current tensions around magic (as well as my ability as a DM). You focus on building a character with clear personality, history, and goals; I'll turn that into a complete, ready-to-play sheet that drops straight into Wayrest on day one. If you'd greatly prefer to roll up all your own attributes, feel free, and just send me the character sheet!
                                </p>
                                <!-- Your Place in Wayrest Subsection -->
                                <div class="ml-4 pl-4 border-l-2 border-primary/30 mb-6">
                                    <h4 class="text-xl font-semibold text-white mb-3 flex items-center gap-2">

                                        Your Place in Wayrest
                                    </h4>
                                    <p class="mb-4">
                                        All of your characters live in Wayrest. Maybe you are one of the many retired adventurers who hung up your weapons and came here for peace. Maybe you were born in the hamlet to parents who lived loud lives before you. Maybe you fled Bastionford's tightening rules and climbed the mountain looking for a fresh start.
                                    </p>
                                    <p>
                                        Whatever your story, <span class="text-white font-medium">Wayrest is home now.</span> You know its paths, its people, and the feel of the reservoir wind on your face. Consider how your character fits into the hamlet. What do they do to contribute to the community? Tend goats? Help maintain the reservoir?
                                    </p>
                                </div>

                                <!-- Party Cohesion Subsection -->
                                <div class="ml-4 pl-4 border-l-2 border-primary/30">
                                    <h4 class="text-xl font-semibold text-white mb-3 flex items-center gap-2">
                                        The Party
                                    </h4>
                                    <p>
                                        All player characters are at least familiar and cordial with one another from living and working in Wayrest. Your backstories of your connections don't have to be elaborate, simple acquaintance is enough. As players develop their characters I will share them ahead of time so that you have some familiarity ahead of time. Maybe you've crossed paths in the community, helped with the seasonal harvest, or nodded to each other on the mountain trails. Either way when the story begins, you're not strangers.
                                    </p>
                                </div>

                                <!-- Character Submission Subsection -->
                                <div class="ml-4 pl-4 border-l-2 border-primary/30">
                                    <h4 class="text-xl font-semibold text-white mb-3 flex items-center gap-2">
                                        Submitting Your Character
                                    </h4>
                                    <p>
                                    Once you've drafted up a character description, feel free to either e-mail me: <a href="mailto:natedrake454@gmail.com">natedrake454@gmail.com</a>, text / call me, or send me a carrier pigeon. I'll get it built into the system and let you know when it's ready for you to look at.
                                    </p>
                                </div>
                            </div>
                        </div>                        
                    </div>
                    <!-- Accordion: Starting Assumptions -->
                    <div class="border border-gray-700 rounded-lg overflow-hidden">
                        <button onclick="toggleAccordion('starting')" class="w-full flex items-center justify-between p-4 bg-gray-800 hover:bg-gray-750 transition text-left">
                            <span class="font-semibold text-white flex items-center gap-2 text-xl">
                                <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                Starting Assumptions
                            </span>
                            <svg id="starting-icon" class="w-5 h-5 text-gray-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div id="starting-content" class="accordion-content">
                            <div class="p-4 bg-gray-800/50 space-y-3 text-lg">
                                <div>
                                    <p class="font-medium text-white mb-1">Starting Level</p>
                                    <p class="text-gray-400">All characters begin at Level 2. You're capable, but not legendary heroes, yet.</p>
                                </div><br>
                                <div>
                                    <p class="font-medium text-white mb-1">Available Races & Classes</p>
                                    <p class="text-gray-400">All standard D&D 5E 2024 races and classes are available. If you want to play something homebrewed / exotic, go for it so long as it fits Wayrest. If you need for me to dream up a whole new class for you, just let me know!</p>
                                </div><br>
                                <div>
                                    <p class="font-medium text-white mb-1">Starting Equipment</p>
                                    <p class="text-gray-400">You'll get the standard fair of starting equipment and such, but if you'd like a special heirloom or something just let me know and we'll work it in. Otherwise don't worry about selecting specific items, they'll be provided.</p>
                                </div><br>
                                <div>
                                    <p class="font-medium text-white mb-1">Spell Selection</p>
                                    <p class="text-gray-400">I'll help you choose spells that fit your character concept and the setting for the most part. Given the tensions around magic in Achryon, we'll discuss how your spellcasting fits into the world.</p>
                                </div><br>
                                <div>
                                    <p class="font-medium text-white mb-1">Pets</p>
                                    <p class="text-gray-400">Totally allowed, but I'll probably ask that you make a tradeoff with some class features or something.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
    
    <script>
        function toggleAccordion(id) {
            const content = document.getElementById(id + '-content');
            const icon = document.getElementById(id + '-icon');
            
            if (content.classList.contains('open')) {
                content.classList.remove('open');
                icon.style.transform = 'rotate(0deg)';
            } else {
                content.classList.add('open');
                icon.style.transform = 'rotate(180deg)';
            }
        }
    </script>
    
    <script src="/js/polling.js"></script>
</body>
</html>