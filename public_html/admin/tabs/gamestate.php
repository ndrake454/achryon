<?php
// Get active combat session
$active_session = $conn->query("SELECT * FROM combat_sessions WHERE is_active = 1 LIMIT 1")->fetch_assoc();

if ($active_session) {
    // Get combat participants
    $participants = $conn->query("
        SELECT 
            cp.*,
            c.name as character_name,
            cs.current_hp as char_current_hp,
            cs.max_hp as char_max_hp,
            cs.armor_class as char_ac,
            cs.strength as char_str,
            cs.dexterity as char_dex,
            cs.constitution as char_con,
            cs.intelligence as char_int,
            cs.wisdom as char_wis,
            cs.charisma as char_cha,
            m.name as monster_name,
            m.current_hp as mon_current_hp,
            m.max_hp as mon_max_hp,
            m.armor_class as mon_ac,
            m.strength as mon_str,
            m.dexterity as mon_dex,
            m.constitution as mon_con,
            m.intelligence as mon_int,
            m.wisdom as mon_wis,
            m.charisma as mon_cha,
            p.user_id
        FROM combat_participants cp
        LEFT JOIN characters c ON cp.entity_type = 'character' AND cp.entity_id = c.id
        LEFT JOIN character_stats cs ON c.id = cs.character_id
        LEFT JOIN monsters m ON cp.entity_type = 'monster' AND cp.entity_id = m.id
        LEFT JOIN players p ON c.player_id = p.id
        WHERE cp.session_id = {$active_session['id']}
        ORDER BY cp.initiative DESC, cp.turn_order
    ");
}

// Get available characters and monsters for adding
$available_characters = $conn->query("
    SELECT c.id, c.name, u.username as player_name
    FROM characters c
    JOIN players p ON c.player_id = p.id
    JOIN users u ON p.user_id = u.id
    WHERE c.id NOT IN (
        SELECT entity_id FROM combat_participants 
        WHERE entity_type = 'character' 
        AND session_id = " . ($active_session['id'] ?? 0) . "
    )
    ORDER BY c.name
");

$available_monsters = $conn->query("
    SELECT id, name, challenge_rating
    FROM monsters
    ORDER BY name
");
?>

<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-white flex items-center">
                <svg class="w-8 h-8 text-primary mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                </svg>
                Combat Tracker
            </h2>
            <p class="text-gray-400 ml-11">Manage initiative order and combat state</p>
        </div>
        
        <div class="flex gap-3">
            <?php if ($active_session): ?>
                <button onclick="showAddParticipantModal()" class="bg-gray-800 hover:bg-gray-700 text-white font-bold py-3 px-6 rounded-lg transition flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Add Participant</span>
                </button>
                <button onclick="endCombat()" class="bg-red-500/20 hover:bg-red-500/30 text-red-400 font-bold py-3 px-6 rounded-lg transition flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    <span>End Combat</span>
                </button>
            <?php else: ?>
                <button onclick="startCombat()" class="bg-primary hover:bg-primary-dark text-white font-bold py-3 px-6 rounded-lg transition flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Start Combat</span>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!$active_session): ?>
        <!-- No Active Combat -->
        <div class="text-center py-20">
            <svg class="w-20 h-20 text-gray-700 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
            </svg>
            <h3 class="text-xl font-bold text-gray-600 mb-2">No Active Combat</h3>
            <p class="text-gray-500 mb-4">Start a combat encounter to begin tracking initiative</p>
            <button onclick="startCombat()" class="bg-primary hover:bg-primary-dark text-white font-bold py-2 px-6 rounded-lg transition">
                Start Combat
            </button>
        </div>
    <?php else: ?>
        <!-- Combat Visibility Controls -->
        <div class="bg-gray-900/50 border border-gray-800 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-white font-bold mb-1">Player Visibility Settings</h3>
                    <p class="text-sm text-gray-400">Control what players can see during combat</p>
                </div>
                
                <div class="flex gap-6">
                    <label class="flex items-center space-x-3 cursor-pointer">
                        <span class="text-gray-300">Show HP to Players</span>
                        <div class="relative inline-flex items-center cursor-pointer">
                            <input 
                                type="checkbox" 
                                id="globalHpVisibility"
                                onchange="toggleGlobalVisibility('hp', this.checked)"
                                class="sr-only peer"
                            >
                            <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                        </div>
                    </label>
                    
                    <label class="flex items-center space-x-3 cursor-pointer">
                        <span class="text-gray-300">Show Stats to Players</span>
                        <div class="relative inline-flex items-center cursor-pointer">
                            <input 
                                type="checkbox" 
                                id="globalStatsVisibility"
                                onchange="toggleGlobalVisibility('stats', this.checked)"
                                class="sr-only peer"
                            >
                            <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Initiative Order -->
        <div class="space-y-3">
            <?php $turn = 1; while ($participant = $participants->fetch_assoc()): 
                $is_character = $participant['entity_type'] === 'character';
                $name = $is_character ? $participant['character_name'] : $participant['monster_name'];
                $current_hp = $is_character ? $participant['char_current_hp'] : $participant['mon_current_hp'];
                $max_hp = $is_character ? $participant['char_max_hp'] : $participant['mon_max_hp'];
                $ac = $is_character ? $participant['char_ac'] : $participant['mon_ac'];
                
                $hp_percent = ($max_hp > 0) ? ($current_hp / $max_hp) * 100 : 0;
                $hp_color = $hp_percent > 50 ? 'bg-green-500' : ($hp_percent > 25 ? 'bg-yellow-500' : 'bg-red-500');
            ?>
            <div class="bg-gray-900/50 border-2 border-gray-800 rounded-lg p-5 hover:border-primary/30 transition" data-participant-id="<?php echo $participant['id']; ?>">
                <div class="flex items-center gap-4">
                    <!-- Turn Order -->
                    <div class="flex-shrink-0 w-12 h-12 bg-primary/20 rounded-lg border-2 border-primary flex items-center justify-center">
                        <span class="text-primary font-bold text-lg"><?php echo $turn++; ?></span>
                    </div>
                    
                    <!-- Entity Info -->
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <h3 class="text-xl font-bold text-white"><?php echo htmlspecialchars($name); ?></h3>
                            <span class="px-2 py-1 text-xs rounded <?php echo $is_character ? 'bg-blue-500/20 text-blue-400' : 'bg-red-500/20 text-red-400'; ?>">
                                <?php echo $is_character ? 'Player' : 'Monster'; ?>
                            </span>
                            <?php if ($is_character): ?>
                            <a href="/admin/character.php?id=<?php echo $participant['entity_id']; ?>" target="_blank" class="px-2 py-1 text-xs bg-primary/20 text-primary hover:bg-primary/30 rounded transition">
                                Edit
                            </a>
                            <?php 
                            // Show status effects for characters
                            $status_query = $conn->query("SELECT * FROM character_status_effects WHERE character_id = {$participant['entity_id']}");
                            while ($status = $status_query->fetch_assoc()):
                            ?>
                            <span class="px-2 py-1 text-xs bg-red-900/30 text-red-400 border border-red-500/50 rounded flex items-center gap-1" title="<?php echo htmlspecialchars($status['description'] ?? ''); ?>">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <?php echo htmlspecialchars($status['status_name']); ?>
                            </span>
                            <?php endwhile; ?>
                            <?php else: ?>
                            <button onclick="editMonsterFromBattle(<?php echo $participant['entity_id']; ?>)" class="px-2 py-1 text-xs bg-primary/20 text-primary hover:bg-primary/30 rounded transition">
                                Edit
                            </button>
                            <?php 
                            // Show status effects for monsters
                            $status_query = $conn->query("SELECT * FROM monster_status_effects WHERE monster_id = {$participant['entity_id']}");
                            while ($status = $status_query->fetch_assoc()):
                            ?>
                            <span class="px-2 py-1 text-xs bg-red-900/30 text-red-400 border border-red-500/50 rounded flex items-center gap-1" title="<?php echo htmlspecialchars($status['description'] ?? ''); ?>">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <?php echo htmlspecialchars($status['status_name']); ?>
                            </span>
                            <?php endwhile; ?>
                            <?php endif; ?>
                            <span class="px-3 py-1 bg-gray-800 text-gray-300 text-sm rounded font-mono">
                                Init: <?php echo $participant['initiative']; ?>
                            </span>
                        </div>
                        
                        <!-- HP Bar -->
                        <div class="flex items-center gap-4">
                            <div class="flex-1">
                                <div class="flex justify-between text-xs text-gray-400 mb-1">
                                    <span>HP</span>
                                    <span><?php echo $current_hp; ?> / <?php echo $max_hp; ?></span>
                                </div>
                                <div class="w-full bg-gray-800 rounded-full h-3 overflow-hidden">
                                    <div class="<?php echo $hp_color; ?> h-full transition-all" style="width: <?php echo $hp_percent; ?>%"></div>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-2">
                                <button onclick="adjustHP(<?php echo $participant['id']; ?>, '<?php echo $participant['entity_type']; ?>', <?php echo $participant['entity_id']; ?>, -5)" class="w-8 h-8 bg-red-500/20 hover:bg-red-500/30 text-red-400 rounded flex items-center justify-center transition">
                                    <span class="text-sm font-bold">-5</span>
                                </button>
                                <button onclick="adjustHP(<?php echo $participant['id']; ?>, '<?php echo $participant['entity_type']; ?>', <?php echo $participant['entity_id']; ?>, -1)" class="w-8 h-8 bg-red-500/20 hover:bg-red-500/30 text-red-400 rounded flex items-center justify-center transition">
                                    <span class="text-sm font-bold">-1</span>
                                </button>
                                <button onclick="adjustHP(<?php echo $participant['id']; ?>, '<?php echo $participant['entity_type']; ?>', <?php echo $participant['entity_id']; ?>, 1)" class="w-8 h-8 bg-green-500/20 hover:bg-green-500/30 text-green-400 rounded flex items-center justify-center transition">
                                    <span class="text-sm font-bold">+1</span>
                                </button>
                                <button onclick="adjustHP(<?php echo $participant['id']; ?>, '<?php echo $participant['entity_type']; ?>', <?php echo $participant['entity_id']; ?>, 5)" class="w-8 h-8 bg-green-500/20 hover:bg-green-500/30 text-green-400 rounded flex items-center justify-center transition">
                                    <span class="text-sm font-bold">+5</span>
                                </button>
                            </div>
                            
                            <div class="px-3 py-2 bg-gray-800 rounded text-center">
                                <div class="text-xs text-gray-400">AC</div>
                                <div class="text-lg font-bold text-white"><?php echo $ac; ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="flex-shrink-0 flex flex-col gap-2">
                        <button onclick="editInitiative(<?php echo $participant['id']; ?>, <?php echo $participant['initiative']; ?>)" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-white text-sm rounded transition">
                            Edit Init
                        </button>
                        
                        <?php if (!$is_character): ?>
                        <button onclick="viewMonsterStatBlock(<?php echo $participant['entity_id']; ?>)" class="px-4 py-2 bg-blue-500/20 hover:bg-blue-500/30 text-blue-400 text-sm rounded transition">
                            View Stats
                        </button>
                        <?php endif; ?>
                        
                        <button onclick="showAddStatusFromBattle('<?php echo $participant['entity_type']; ?>', <?php echo $participant['entity_id']; ?>)" class="px-4 py-2 bg-yellow-500/20 hover:bg-yellow-500/30 text-yellow-400 text-sm rounded transition">
                            Add Status
                        </button>
                        
                        <label class="flex items-center justify-center px-4 py-2 bg-gray-800 hover:bg-gray-700 rounded transition cursor-pointer text-sm">
                            <input 
                                type="checkbox" 
                                <?php echo $participant['hp_visible'] ? 'checked' : ''; ?>
                                onchange="toggleParticipantVisibility(<?php echo $participant['id']; ?>, 'hp', this.checked)"
                                class="mr-2"
                            >
                            <span class="text-gray-300">HP</span>
                        </label>
                        
                        <label class="flex items-center justify-center px-4 py-2 bg-gray-800 hover:bg-gray-700 rounded transition cursor-pointer text-sm">
                            <input 
                                type="checkbox" 
                                <?php echo $participant['stats_visible'] ? 'checked' : ''; ?>
                                onchange="toggleParticipantVisibility(<?php echo $participant['id']; ?>, 'stats', this.checked)"
                                class="mr-2"
                            >
                            <span class="text-gray-300">Stats</span>
                        </label>
                        
                        <button onclick="removeParticipant(<?php echo $participant['id']; ?>)" class="px-4 py-2 bg-red-500/20 hover:bg-red-500/30 text-red-400 text-sm rounded transition">
                            Remove
                        </button>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Add Participant Modal -->
<div id="addParticipantModal" class="hidden fixed inset-0 bg-black/75 backdrop-blur-sm flex items-center justify-center p-4 z-50">
    <div class="bg-gray-900 border border-gray-800 rounded-xl shadow-2xl w-full max-w-2xl">
        <div class="p-6 border-b border-gray-800">
            <h3 class="text-2xl font-bold text-white">Add to Combat</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-2 gap-4 mb-6">
                <button onclick="showTab('characters')" id="tabCharacters" class="tab-btn active">
                    Characters
                </button>
                <button onclick="showTab('monsters')" id="tabMonsters" class="tab-btn">
                    Monsters
                </button>
            </div>
            
            <!-- Characters Tab -->
            <div id="charactersTab" class="tab-content">
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    <?php if ($available_characters->num_rows === 0): ?>
                        <p class="text-gray-500 text-center py-8">All characters already in combat</p>
                    <?php else: ?>
                        <?php while ($char = $available_characters->fetch_assoc()): ?>
                        <button 
                            onclick="addToCombat('character', <?php echo $char['id']; ?>, '<?php echo htmlspecialchars($char['name'], ENT_QUOTES); ?>')"
                            class="w-full p-4 bg-gray-800 hover:bg-gray-700 rounded-lg transition text-left"
                        >
                            <div class="text-white font-bold"><?php echo htmlspecialchars($char['name']); ?></div>
                            <div class="text-sm text-gray-400">Player: <?php echo htmlspecialchars($char['player_name']); ?></div>
                        </button>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Monsters Tab -->
            <div id="monstersTab" class="tab-content hidden">
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    <?php if ($available_monsters->num_rows === 0): ?>
                        <p class="text-gray-500 text-center py-8">No monsters available</p>
                    <?php else: ?>
                        <?php while ($monster = $available_monsters->fetch_assoc()): ?>
                        <button 
                            onclick="addToCombat('monster', <?php echo $monster['id']; ?>, '<?php echo htmlspecialchars($monster['name'], ENT_QUOTES); ?>')"
                            class="w-full p-4 bg-gray-800 hover:bg-gray-700 rounded-lg transition text-left"
                        >
                            <div class="flex items-center justify-between">
                                <div class="text-white font-bold"><?php echo htmlspecialchars($monster['name']); ?></div>
                                <?php if ($monster['challenge_rating']): ?>
                                <div class="text-sm text-gray-400">CR <?php echo htmlspecialchars($monster['challenge_rating']); ?></div>
                                <?php endif; ?>
                            </div>
                        </button>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="p-6 border-t border-gray-800">
            <button onclick="hideAddParticipantModal()" class="w-full bg-gray-800 hover:bg-gray-700 text-white font-bold py-3 rounded-lg transition">
                Cancel
            </button>
        </div>
    </div>
</div>

<!-- Monster Stat Block Modal -->
<div id="statBlockModal" class="hidden fixed inset-0 bg-black/75 backdrop-blur-sm flex items-center justify-center p-4 z-50 overflow-y-auto">
    <div class="bg-gray-900 border-2 border-primary rounded-xl shadow-2xl w-full max-w-2xl my-8">
        <div class="sticky top-0 bg-gray-900 p-6 border-b-2 border-primary rounded-t-xl flex justify-between items-center z-10">
            <h3 class="text-2xl font-bold text-primary" id="statBlockTitle">Monster Stat Block</h3>
            <button onclick="hideStatBlockModal()" class="text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="p-6" id="statBlockContent">
            <!-- Content will be populated by JavaScript -->
        </div>
    </div>
</div>

<style>
.tab-btn {
    padding: 0.75rem 1.5rem;
    background: transparent;
    border: 2px solid #374151;
    color: #9ca3af;
    border-radius: 0.5rem;
    font-weight: 600;
    transition: all 0.2s;
}

.tab-btn:hover {
    border-color: #f97316;
    color: #f97316;
}

.tab-btn.active {
    background: #f97316;
    border-color: #f97316;
    color: white;
}
</style>

<!-- Monster Edit Modal (same as monsters tab) -->
<div id="monsterModalBattle" class="hidden fixed inset-0 bg-black/75 backdrop-blur-sm flex items-center justify-center p-4 z-50 overflow-y-auto">
    <div class="bg-gray-900 border border-gray-800 rounded-xl shadow-2xl w-full max-w-4xl my-8">
        <div class="sticky top-0 bg-gray-900 p-6 border-b border-gray-800 rounded-t-xl flex justify-between items-center">
            <h3 class="text-2xl font-bold text-white">Edit Monster</h3>
            <button onclick="hideMonsterModal()" class="text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form id="monsterFormBattle" onsubmit="saveMonsterFromBattle(event)" class="p-6">
            <input type="hidden" id="monsterIdBattle">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Left Column -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-300 mb-2 text-sm font-medium">Monster Name *</label>
                        <input type="text" id="monsterNameBattle" required class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 mb-2 text-sm font-medium">Type</label>
                        <input type="text" id="monsterTypeBattle" placeholder="e.g., Dragon, Undead, Beast" class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                    </div>
                    
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-gray-300 mb-2 text-sm font-medium">CR</label>
                            <input type="text" id="monsterCRBattle" placeholder="1/4, 1, 5..." class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-gray-300 mb-2 text-sm font-medium">AC *</label>
                            <input type="number" id="monsterACBattle" value="10" required class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-gray-300 mb-2 text-sm font-medium">Max HP *</label>
                            <input type="number" id="monsterHPBattle" value="10" required class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                        </div>
                    </div>

                    <div>
                        <label class="block text-gray-300 mb-2 text-sm font-medium">Description</label>
                        <textarea id="monsterDescBattle" rows="3" class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary resize-none"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 mb-2 text-sm font-medium">Attacks</label>
                        <textarea id="monsterAttacksBattle" rows="3" class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary resize-none" placeholder="e.g., Bite: +4 to hit, 1d8+2 piercing damage"></textarea>
                    </div>
                </div>

                <!-- Right Column - Ability Scores -->
                <div class="space-y-4">
                    <div class="bg-gray-800/50 rounded-lg p-4">
                        <h4 class="text-white font-bold mb-4">Ability Scores</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-400 mb-1 text-xs">STR</label>
                                <input type="number" id="monsterSTRBattle" value="10" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white text-center focus:outline-none focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-gray-400 mb-1 text-xs">DEX</label>
                                <input type="number" id="monsterDEXBattle" value="10" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white text-center focus:outline-none focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-gray-400 mb-1 text-xs">CON</label>
                                <input type="number" id="monsterCONBattle" value="10" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white text-center focus:outline-none focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-gray-400 mb-1 text-xs">INT</label>
                                <input type="number" id="monsterINTBattle" value="10" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white text-center focus:outline-none focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-gray-400 mb-1 text-xs">WIS</label>
                                <input type="number" id="monsterWISBattle" value="10" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white text-center focus:outline-none focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-gray-400 mb-1 text-xs">CHA</label>
                                <input type="number" id="monsterCHABattle" value="10" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white text-center focus:outline-none focus:border-primary">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Status Effects -->
                    <div class="bg-gray-800/50 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-white font-bold flex items-center">
                                <svg class="w-5 h-5 text-primary mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                Status Effects
                            </h4>
                            <button type="button" onclick="showAddMonsterStatusBattle()" class="px-3 py-1 bg-primary/20 hover:bg-primary/30 text-primary text-sm rounded transition">
                                Add Status
                            </button>
                        </div>
                        <div id="monsterStatusListBattle" class="space-y-2">
                            <p class="text-gray-500 text-sm text-center py-4">No status effects</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6 pt-6 border-t border-gray-800">
                <button type="button" onclick="hideMonsterModal()" class="px-6 py-3 bg-gray-800 hover:bg-gray-700 text-white rounded-lg transition">
                    Cancel
                </button>
                <button type="submit" class="px-6 py-3 bg-primary hover:bg-primary-dark text-white font-bold rounded-lg transition">
                    Save Monster
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Add Status Modal (for battle manager) -->
<div id="addStatusBattleModal" class="hidden fixed inset-0 bg-black/75 backdrop-blur-sm flex items-center justify-center p-4 z-50">
    <div class="bg-gray-900 border border-gray-800 rounded-xl shadow-2xl w-full max-w-lg">
        <div class="p-6 border-b border-gray-800">
            <h3 class="text-xl font-bold text-white">Add Status Effect</h3>
        </div>
        <form onsubmit="addStatusFromBattle(event)" class="p-6 space-y-4">
            <input type="hidden" id="battleStatusEntityType">
            <input type="hidden" id="battleStatusEntityId">
            
            <div>
                <label class="block text-gray-300 mb-2 text-sm">Status Name *</label>
                <select id="battleStatusName" required class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                    <option value="">Select a status...</option>
                    <option value="Blinded">Blinded</option>
                    <option value="Charmed">Charmed</option>
                    <option value="Deafened">Deafened</option>
                    <option value="Frightened">Frightened</option>
                    <option value="Grappled">Grappled</option>
                    <option value="Incapacitated">Incapacitated</option>
                    <option value="Invisible">Invisible</option>
                    <option value="Paralyzed">Paralyzed</option>
                    <option value="Petrified">Petrified</option>
                    <option value="Poisoned">Poisoned</option>
                    <option value="Prone">Prone</option>
                    <option value="Restrained">Restrained</option>
                    <option value="Stunned">Stunned</option>
                    <option value="Unconscious">Unconscious</option>
                    <option value="Exhaustion">Exhaustion</option>
                    <option value="Concentrating">Concentrating</option>
                    <option value="Custom">Custom...</option>
                </select>
            </div>
            <div id="battleCustomStatusDiv" class="hidden">
                <label class="block text-gray-300 mb-2 text-sm">Custom Status Name *</label>
                <input type="text" id="battleCustomStatusName" class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
            </div>
            <div>
                <label class="block text-gray-300 mb-2 text-sm">Description/Notes</label>
                <textarea id="battleStatusDesc" rows="2" class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary" placeholder="Optional notes (e.g., duration, source)"></textarea>
            </div>
            
            <div class="flex gap-3">
                <button type="submit" class="flex-1 bg-primary hover:bg-primary-dark text-white font-bold py-3 rounded-lg transition">
                    Add Status
                </button>
                <button type="button" onclick="hideAddStatusBattleModal()" class="px-6 bg-gray-800 hover:bg-gray-700 text-white font-bold py-3 rounded-lg transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showTab(tab) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.add('hidden'));
    
    document.getElementById('tab' + tab.charAt(0).toUpperCase() + tab.slice(1)).classList.add('active');
    document.getElementById(tab + 'Tab').classList.remove('hidden');
}

async function startCombat() {
    const formData = new FormData();
    formData.append('action', 'start_combat');
    
    try {
        const response = await fetch('/admin/api.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        if (result.success) {
            location.reload();
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

async function endCombat() {
    if (!confirm('End combat? This will clear all participants.')) return;
    
    const formData = new FormData();
    formData.append('action', 'end_combat');
    
    try {
        const response = await fetch('/admin/api.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        if (result.success) {
            location.reload();
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function showAddParticipantModal() {
    document.getElementById('addParticipantModal').classList.remove('hidden');
}

function hideAddParticipantModal() {
    document.getElementById('addParticipantModal').classList.add('hidden');
}

async function addToCombat(type, entityId, name) {
    const initiative = prompt(`Enter initiative for ${name}:`, '10');
    if (!initiative) return;
    
    const formData = new FormData();
    formData.append('action', 'add_to_combat');
    formData.append('entity_type', type);
    formData.append('entity_id', entityId);
    formData.append('initiative', initiative);
    
    try {
        const response = await fetch('/admin/api.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        if (result.success) {
            location.reload();
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

async function removeParticipant(participantId) {
    if (!confirm('Remove from combat?')) return;
    
    const formData = new FormData();
    formData.append('action', 'remove_from_combat');
    formData.append('participant_id', participantId);
    
    try {
        const response = await fetch('/admin/api.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        if (result.success) {
            location.reload();
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

async function adjustHP(participantId, entityType, entityId, change) {
    const formData = new FormData();
    formData.append('action', 'adjust_combat_hp');
    formData.append('participant_id', participantId);
    formData.append('entity_type', entityType);
    formData.append('entity_id', entityId);
    formData.append('change', change);
    
    try {
        const response = await fetch('/admin/api.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        if (result.success) {
            location.reload();
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

async function editInitiative(participantId, currentInit) {
    const newInit = prompt('Enter new initiative:', currentInit);
    if (!newInit || newInit === currentInit.toString()) return;
    
    const formData = new FormData();
    formData.append('action', 'update_initiative');
    formData.append('participant_id', participantId);
    formData.append('initiative', newInit);
    
    try {
        const response = await fetch('/admin/api.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        if (result.success) {
            location.reload();
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

async function toggleParticipantVisibility(participantId, type, visible) {
    const formData = new FormData();
    formData.append('action', 'toggle_participant_visibility');
    formData.append('participant_id', participantId);
    formData.append('visibility_type', type);
    formData.append('visible', visible ? 1 : 0);
    
    try {
        const response = await fetch('/admin/api.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        if (!result.success) {
            alert('Failed to update visibility');
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

async function toggleGlobalVisibility(type, visible) {
    const formData = new FormData();
    formData.append('action', 'toggle_global_visibility');
    formData.append('visibility_type', type);
    formData.append('visible', visible ? 1 : 0);
    
    try {
        const response = await fetch('/admin/api.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        if (result.success) {
            location.reload();
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Close modal on outside click
document.getElementById('addParticipantModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        hideAddParticipantModal();
    }
});

// Monster editing from battle manager
async function editMonsterFromBattle(monsterId) {
    try {
        const response = await fetch(`/admin/api.php?action=get_monster&id=${monsterId}`);
        const result = await response.json();
        
        if (result.success && result.monster) {
            const m = result.monster;
            
            document.getElementById('monsterIdBattle').value = m.id;
            document.getElementById('monsterNameBattle').value = m.name;
            document.getElementById('monsterTypeBattle').value = m.type || '';
            document.getElementById('monsterCRBattle').value = m.challenge_rating || '';
            document.getElementById('monsterACBattle').value = m.armor_class;
            document.getElementById('monsterHPBattle').value = m.max_hp;
            document.getElementById('monsterDescBattle').value = m.description || '';
            document.getElementById('monsterAttacksBattle').value = m.attacks || '';
            document.getElementById('monsterSTRBattle').value = m.strength;
            document.getElementById('monsterDEXBattle').value = m.dexterity;
            document.getElementById('monsterCONBattle').value = m.constitution;
            document.getElementById('monsterINTBattle').value = m.intelligence;
            document.getElementById('monsterWISBattle').value = m.wisdom;
            document.getElementById('monsterCHABattle').value = m.charisma;
            
            // Load status effects
            loadMonsterStatusEffectsBattle(m.id);
            
            document.getElementById('monsterModalBattle').classList.remove('hidden');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to load monster');
    }
}

function hideMonsterModal() {
    document.getElementById('monsterModalBattle').classList.add('hidden');
}

async function saveMonsterFromBattle(event) {
    event.preventDefault();
    
    const formData = new FormData();
    formData.append('action', 'update_monster');
    formData.append('id', document.getElementById('monsterIdBattle').value);
    formData.append('name', document.getElementById('monsterNameBattle').value);
    formData.append('type', document.getElementById('monsterTypeBattle').value);
    formData.append('challenge_rating', document.getElementById('monsterCRBattle').value);
    formData.append('armor_class', document.getElementById('monsterACBattle').value);
    formData.append('max_hp', document.getElementById('monsterHPBattle').value);
    formData.append('strength', document.getElementById('monsterSTRBattle').value);
    formData.append('dexterity', document.getElementById('monsterDEXBattle').value);
    formData.append('constitution', document.getElementById('monsterCONBattle').value);
    formData.append('intelligence', document.getElementById('monsterINTBattle').value);
    formData.append('wisdom', document.getElementById('monsterWISBattle').value);
    formData.append('charisma', document.getElementById('monsterCHABattle').value);
    formData.append('attacks', document.getElementById('monsterAttacksBattle').value);
    formData.append('description', document.getElementById('monsterDescBattle').value);
    
    try {
        const response = await fetch('/admin/api.php', {
            method: 'POST',
            body: formData
        });
        
        const text = await response.text();
        let result;
        
        try {
            result = JSON.parse(text);
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Response text:', text);
            alert('Server returned invalid response. Check console for details.');
            return;
        }
        
        if (result.success) {
            hideMonsterModal();
            location.reload();
        } else {
            alert('Failed to save monster. Check console for details.');
            console.error('Save failed:', result);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to save monster. Check console for details.');
    }
}

// Status Effects Management
function showAddStatusFromBattle(entityType, entityId) {
    document.getElementById('battleStatusEntityType').value = entityType;
    document.getElementById('battleStatusEntityId').value = entityId;
    document.getElementById('battleStatusName').value = '';
    document.getElementById('battleCustomStatusName').value = '';
    document.getElementById('battleStatusDesc').value = '';
    document.getElementById('battleCustomStatusDiv').classList.add('hidden');
    document.getElementById('addStatusBattleModal').classList.remove('hidden');
}

function hideAddStatusBattleModal() {
    document.getElementById('addStatusBattleModal').classList.add('hidden');
}

document.getElementById('battleStatusName').addEventListener('change', function() {
    const customDiv = document.getElementById('battleCustomStatusDiv');
    const customInput = document.getElementById('battleCustomStatusName');
    if (this.value === 'Custom') {
        customDiv.classList.remove('hidden');
        customInput.required = true;
    } else {
        customDiv.classList.add('hidden');
        customInput.required = false;
    }
});

async function addStatusFromBattle(event) {
    event.preventDefault();
    
    const entityType = document.getElementById('battleStatusEntityType').value;
    const entityId = document.getElementById('battleStatusEntityId').value;
    
    let statusName = document.getElementById('battleStatusName').value;
    if (statusName === 'Custom') {
        statusName = document.getElementById('battleCustomStatusName').value;
    }
    
    const formData = new FormData();
    if (entityType === 'character') {
        formData.append('action', 'add_status');
        formData.append('character_id', entityId);
    } else {
        formData.append('action', 'add_monster_status');
        formData.append('monster_id', entityId);
    }
    formData.append('status_name', statusName);
    formData.append('description', document.getElementById('battleStatusDesc').value);
    
    try {
        const response = await fetch('/admin/api.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        if (result.success) {
            hideAddStatusBattleModal();
            location.reload();
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to add status');
    }
}

// Battle Manager Monster Status Effects
async function loadMonsterStatusEffectsBattle(monsterId) {
    try {
        const response = await fetch(`/admin/api.php?action=get_monster_status_effects&monster_id=${monsterId}`);
        
        if (!response.ok) {
            console.error('HTTP error:', response.status);
            return;
        }
        
        const text = await response.text();
        let result;
        
        try {
            result = JSON.parse(text);
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Response text:', text);
            return;
        }
        
        const container = document.getElementById('monsterStatusListBattle');
        if (!container) return;
        
        if (result.success && result.statuses && result.statuses.length > 0) {
            container.innerHTML = result.statuses.map(status => `
                <div class="flex items-start space-x-4 p-3 bg-gray-700/50 rounded-lg border-l-4 border-yellow-500">
                    <div class="flex-1">
                        <h5 class="text-white font-bold text-sm">${escapeHtmlBattle(status.status_name)}</h5>
                        ${status.description ? `<p class="text-xs text-gray-400 mt-1">${escapeHtmlBattle(status.description)}</p>` : ''}
                    </div>
                    <button type="button" onclick="deleteMonsterStatusBattle(${status.id})" class="text-red-400 hover:text-red-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<p class="text-gray-500 text-sm text-center py-4">No status effects</p>';
        }
    } catch (error) {
        console.error('Error loading monster status effects:', error);
    }
}

function showAddMonsterStatusBattle() {
    const monsterId = document.getElementById('monsterIdBattle').value;
    if (!monsterId) {
        alert('Please save the monster first');
        return;
    }
    
    const statusName = prompt('Status name (e.g., Poisoned, Frightened):');
    if (!statusName) return;
    
    const description = prompt('Description/notes (optional):');
    
    addMonsterStatusBattle(monsterId, statusName, description);
}

async function addMonsterStatusBattle(monsterId, statusName, description) {
    const formData = new FormData();
    formData.append('action', 'add_monster_status');
    formData.append('monster_id', monsterId);
    formData.append('status_name', statusName);
    formData.append('description', description || '');
    
    try {
        const response = await fetch('/admin/api.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        if (result.success) {
            loadMonsterStatusEffectsBattle(monsterId);
            setTimeout(() => location.reload(), 500);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to add status');
    }
}

async function deleteMonsterStatusBattle(statusId) {
    if (!confirm('Remove this status effect?')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_monster_status');
    formData.append('id', statusId);
    
    try {
        const response = await fetch('/admin/api.php', {
            method: 'POST',
            body: formData
        });
        
        const text = await response.text();
        let result;
        
        try {
            result = JSON.parse(text);
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Response text:', text);
            alert('Server returned invalid response. Check console for details.');
            return;
        }
        
        if (result.success) {
            const monsterId = document.getElementById('monsterIdBattle').value;
            loadMonsterStatusEffectsBattle(monsterId);
            setTimeout(() => location.reload(), 500);
        } else {
            alert('Failed to delete status. Check console for details.');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to delete status. Check console for details.');
    }
}

function escapeHtmlBattle(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Stat Block Viewer
async function viewMonsterStatBlock(monsterId) {
    try {
        const response = await fetch(`/admin/api.php?action=get_monster&id=${monsterId}`);
        const result = await response.json();
        
        if (result.success && result.monster) {
            const m = result.monster;
            
            document.getElementById('statBlockTitle').textContent = m.name;
            
            // Calculate ability modifiers
            const calcMod = (score) => {
                const mod = Math.floor((score - 10) / 2);
                return mod >= 0 ? `+${mod}` : mod;
            };
            
            const strMod = calcMod(m.strength);
            const dexMod = calcMod(m.dexterity);
            const conMod = calcMod(m.constitution);
            const intMod = calcMod(m.intelligence);
            const wisMod = calcMod(m.wisdom);
            const chaMod = calcMod(m.charisma);
            
            // Build stat block HTML
            const statBlockHTML = `
                <div class="stat-block space-y-4">
                    <!-- Name and Type -->
                    <div class="text-center border-b-2 border-primary pb-3">
                        <h2 class="text-3xl font-bold text-primary mb-2">${escapeHtml(m.name)}</h2>
                        ${m.type ? `<p class="text-gray-400 italic">${escapeHtml(m.type)}${m.challenge_rating ? `, CR ${escapeHtml(m.challenge_rating)}` : ''}</p>` : ''}
                    </div>
                    
                    <!-- Core Stats -->
                    <div class="grid grid-cols-2 gap-4 bg-gray-800/50 rounded-lg p-4">
                        <div>
                            <span class="text-primary font-bold">Armor Class:</span>
                            <span class="text-white ml-2">${m.armor_class}</span>
                        </div>
                        <div>
                            <span class="text-primary font-bold">Hit Points:</span>
                            <span class="text-white ml-2">${m.current_hp} / ${m.max_hp}</span>
                        </div>
                    </div>
                    
                    <!-- Ability Scores -->
                    <div class="border-t border-b border-gray-700 py-4">
                        <div class="grid grid-cols-6 gap-2 text-center">
                            <div>
                                <div class="text-primary font-bold text-sm">STR</div>
                                <div class="text-white font-bold text-lg">${m.strength}</div>
                                <div class="text-gray-400 text-sm">${strMod}</div>
                            </div>
                            <div>
                                <div class="text-primary font-bold text-sm">DEX</div>
                                <div class="text-white font-bold text-lg">${m.dexterity}</div>
                                <div class="text-gray-400 text-sm">${dexMod}</div>
                            </div>
                            <div>
                                <div class="text-primary font-bold text-sm">CON</div>
                                <div class="text-white font-bold text-lg">${m.constitution}</div>
                                <div class="text-gray-400 text-sm">${conMod}</div>
                            </div>
                            <div>
                                <div class="text-primary font-bold text-sm">INT</div>
                                <div class="text-white font-bold text-lg">${m.intelligence}</div>
                                <div class="text-gray-400 text-sm">${intMod}</div>
                            </div>
                            <div>
                                <div class="text-primary font-bold text-sm">WIS</div>
                                <div class="text-white font-bold text-lg">${m.wisdom}</div>
                                <div class="text-gray-400 text-sm">${wisMod}</div>
                            </div>
                            <div>
                                <div class="text-primary font-bold text-sm">CHA</div>
                                <div class="text-white font-bold text-lg">${m.charisma}</div>
                                <div class="text-gray-400 text-sm">${chaMod}</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Description -->
                    ${m.description ? `
                        <div class="bg-gray-800/30 rounded-lg p-4">
                            <h4 class="text-primary font-bold mb-2">Description</h4>
                            <p class="text-gray-300 whitespace-pre-wrap">${escapeHtml(m.description)}</p>
                        </div>
                    ` : ''}
                    
                    <!-- Attacks -->
                    ${m.attacks ? `
                        <div class="bg-gray-800/30 rounded-lg p-4">
                            <h4 class="text-primary font-bold mb-2 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                                </svg>
                                Attacks
                            </h4>
                            <div class="text-gray-300 whitespace-pre-wrap font-mono text-sm">${escapeHtml(m.attacks)}</div>
                        </div>
                    ` : ''}
                    
                    <!-- Status Effects -->
                    <div id="statBlockStatusEffects"></div>
                </div>
            `;
            
            document.getElementById('statBlockContent').innerHTML = statBlockHTML;
            
            // Load status effects
            loadStatBlockStatusEffects(m.id);
            
            document.getElementById('statBlockModal').classList.remove('hidden');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to load stat block');
    }
}

async function loadStatBlockStatusEffects(monsterId) {
    try {
        const response = await fetch(`/admin/api.php?action=get_monster_status_effects&monster_id=${monsterId}`);
        const text = await response.text();
        let result;
        
        try {
            result = JSON.parse(text);
        } catch (e) {
            console.error('JSON parse error:', e);
            return;
        }
        
        const container = document.getElementById('statBlockStatusEffects');
        if (!container) return;
        
        if (result.success && result.statuses && result.statuses.length > 0) {
            container.innerHTML = `
                <div class="bg-red-900/20 border border-red-500/50 rounded-lg p-4">
                    <h4 class="text-red-400 font-bold mb-3 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        Active Status Effects
                    </h4>
                    <div class="space-y-2">
                        ${result.statuses.map(status => `
                            <div class="bg-gray-800/50 rounded p-3">
                                <div class="text-white font-bold">${escapeHtml(status.status_name)}</div>
                                ${status.description ? `<div class="text-gray-400 text-sm mt-1">${escapeHtml(status.description)}</div>` : ''}
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading status effects:', error);
    }
}

function hideStatBlockModal() {
    document.getElementById('statBlockModal').classList.add('hidden');
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Close modals on outside click
document.getElementById('statBlockModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        hideStatBlockModal();
    }
});
</script>