<?php
// Re-fetch characters for the character display
$stmt = $conn->prepare("
    SELECT c.*, cs.*
    FROM characters c
    JOIN players p ON c.player_id = p.id
    LEFT JOIN character_stats cs ON c.id = cs.character_id
    WHERE p.user_id = ?
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$chars = $stmt->get_result();

// If specific character selected, show that one
if ($selected_char_id) {
    $stmt = $conn->prepare("
        SELECT c.*, cs.*
        FROM characters c
        JOIN players p ON c.player_id = p.id
        LEFT JOIN character_stats cs ON c.id = cs.character_id
        WHERE c.id = ? AND p.user_id = ?
    ");
    $stmt->bind_param("ii", $selected_char_id, $_SESSION['user_id']);
    $stmt->execute();
    $char = $stmt->get_result()->fetch_assoc();
} else {
    // Show first character
    $chars->data_seek(0);
    $char = $chars->fetch_assoc();
}

if (!$char) {
    echo '<div class="text-center py-20"><p class="text-gray-400">Character not found</p></div>';
    return;
}
?>

<div class="max-w-7xl mx-auto">
    <!-- Character Header -->
    <div class="bg-gray-900/50 border border-gray-800 rounded-xl p-6 mb-6">
        <div class="flex items-start justify-between mb-4">
            <div class="flex items-start space-x-4 flex-1">
                <!-- Character Portrait -->
                <?php if (!empty($char['image_url'])): ?>
                    <img src="<?php echo htmlspecialchars($char['image_url']); ?>" 
                        alt="<?php echo htmlspecialchars($char['name']); ?>" 
                        class="w-24 h-24 object-cover rounded-lg border-2 border-primary flex-shrink-0">
                <?php else: ?>
                    <div class="w-24 h-24 bg-gray-800 rounded-lg border-2 border-gray-700 flex-shrink-0 flex items-center justify-center">
                        <svg class="w-12 h-12 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                <?php endif; ?>
                
                <!-- Character Info -->
                <div class="flex-1">
                    <h2 class="text-3xl font-bold text-white mb-1"><?php echo htmlspecialchars($char['name']); ?></h2>
                    <p class="text-gray-400">Level <?php echo $char['level']; ?> <?php echo htmlspecialchars($char['race']); ?> <?php echo htmlspecialchars($char['class']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- HP Section -->
    <div class="bg-gray-900/50 border border-gray-800 rounded-xl p-6 mb-6">
        <h3 class="text-lg font-bold text-white mb-4 flex items-center">
            <svg class="w-5 h-5 text-primary mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
            </svg>
            Hit Points
        </h3>
        <div class="text-center mb-4">
            <p id="currentHP" class="text-5xl font-bold text-white"><?php echo $char['current_hp'] ?? 0; ?></p>
            <p class="text-gray-400 text-lg">/ <?php echo $char['max_hp'] ?? 0; ?></p>
        </div>
        <div class="w-full bg-gray-800 rounded-full h-3">
            <?php 
            $hp_percent = ($char['max_hp'] > 0) ? ($char['current_hp'] / $char['max_hp']) * 100 : 0;
            $hp_color = $hp_percent > 50 ? 'bg-green-500' : ($hp_percent > 25 ? 'bg-yellow-500' : 'bg-red-500');
            ?>
            <div id="hpBar" class="<?php echo $hp_color; ?> h-full transition-all" style="width: <?php echo $hp_percent; ?>%"></div>
        </div>
<div class="flex items-center justify-center pt-4">
    <div class="text-center bg-gray-800/50 border-2 border-primary/50 rounded-xl px-6 py-4">
        <div class="text-sm text-gray-400 mb-1 font-semibold">ARMOR CLASS</div>
        <div class="text-5xl font-bold text-primary" id="armorClass"><?php echo $char['armor_class'] ?? 10; ?></div>
    </div>
</div>
    </div>

    <!-- Ability Scores -->
    <div class="bg-gray-900/50 border border-gray-800 rounded-xl p-6 mb-6">
        <h3 class="text-lg font-bold text-white mb-4">Abilities</h3>
        <div class="grid grid-cols-3 md:grid-cols-6 gap-4">
            <?php 
            $abilities = [
                'STR' => ['strength', $char['strength'] ?? 10],
                'DEX' => ['dexterity', $char['dexterity'] ?? 10],
                'CON' => ['constitution', $char['constitution'] ?? 10],
                'INT' => ['intelligence', $char['intelligence'] ?? 10],
                'WIS' => ['wisdom', $char['wisdom'] ?? 10],
                'CHA' => ['charisma', $char['charisma'] ?? 10]
            ];
            foreach ($abilities as $abbr => $data):
                $score = $data[1];
                $modifier = floor(($score - 10) / 2);
                $mod_color = $modifier >= 0 ? 'text-green-400' : 'text-red-400';
            ?>
            <div class="bg-gray-800/50 rounded-lg p-4 text-center">
                <p class="text-xs text-gray-500 uppercase mb-1"><?php echo $abbr; ?></p>
                <p class="text-3xl font-bold text-white mb-1"><?php echo $score; ?></p>
                <p class="text-lg <?php echo $mod_color; ?> font-bold"><?php echo $modifier >= 0 ? '+' : ''; ?><?php echo $modifier; ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Feats Section -->
    <div class="bg-gray-900/50 border border-gray-800 rounded-xl p-6 mb-6">
        <h3 class="text-xl font-bold text-white mb-4">Feats</h3>
        <?php
        $feats = $conn->query("SELECT * FROM character_feats WHERE character_id = {$char['id']} ORDER BY name");
        
        if ($feats->num_rows > 0):
        ?>
        <div class="space-y-3">
            <?php while ($feat = $feats->fetch_assoc()): ?>
            <div class="bg-gray-800/50 border border-gray-700 rounded-lg p-4">
                <h4 class="text-white font-bold text-lg mb-2"><?php echo htmlspecialchars($feat['name']); ?></h4>
                <?php if ($feat['description']): ?>
                <p class="text-gray-400 text-sm leading-relaxed"><?php echo htmlspecialchars($feat['description']); ?></p>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <p class="text-gray-500 text-center py-8">No feats</p>
        <?php endif; ?>
    </div>
    
    <!-- Status Effects Section -->
    <?php
    $statuses = $conn->query("SELECT * FROM character_status_effects WHERE character_id = {$char['id']} ORDER BY created_at DESC");
    if ($statuses->num_rows > 0):
    ?>
    <div class="bg-gray-900/50 border border-red-900/30 rounded-xl p-6 mb-6">
        <h3 class="text-xl font-bold text-white mb-4 flex items-center">
            <svg class="w-5 h-5 text-red-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            Active Status Effects
        </h3>
        <div class="space-y-3">
            <?php while ($status = $statuses->fetch_assoc()): ?>
            <div class="bg-red-900/10 border-l-4 border-red-500 rounded-lg p-4">
                <h4 class="text-red-400 font-bold text-lg mb-1"><?php echo htmlspecialchars($status['status_name']); ?></h4>
                <?php if ($status['description']): ?>
                <p class="text-gray-400 text-sm"><?php echo htmlspecialchars($status['description']); ?></p>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>    
    <!-- Equipment Section - Inline Display -->
    <div class="bg-gray-900/50 border border-gray-800 rounded-xl p-6 mb-6">
        <h3 class="text-xl font-bold text-white mb-4">Equipment & Inventory</h3>
        
        <?php
        $equipment = $conn->query("SELECT * FROM character_equipment WHERE character_id = {$char['id']} ORDER BY is_equipped DESC, item_name");
        
        // DEBUG: Show what we're getting from database
        echo "<!-- DEBUG: Equipment query returned " . $equipment->num_rows . " items -->\n";
        
        if ($equipment->num_rows > 0):
            $equipped_items = [];
            $unequipped_items = [];
            
            while ($item = $equipment->fetch_assoc()) {
                // DEBUG: Show item data
                echo "<!-- DEBUG ITEM: " . htmlspecialchars(json_encode($item)) . " -->\n";
                
                if ($item['is_equipped']) {
                    $equipped_items[] = $item;
                } else {
                    $unequipped_items[] = $item;
                }
            }
        ?>
        
        <?php if (count($equipped_items) > 0): ?>
        <div class="mb-6">
            <h4 class="text-sm font-bold text-primary uppercase tracking-wide mb-3">Equipped</h4>
            <div class="space-y-3">
                <?php foreach ($equipped_items as $item): ?>
                <div class="bg-gray-800/50 border border-primary/30 rounded-lg p-5">
                    <!-- Item Header -->
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <h5 class="text-white font-bold text-xl mb-2 flex items-center">
                                <svg class="w-5 h-5 text-primary mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <?php echo htmlspecialchars($item['item_name']); ?>
                            </h5>
                            <div class="flex items-center gap-2 flex-wrap mb-2">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-700 text-gray-300">
                                    <?php echo $item['type'] ? htmlspecialchars($item['type']) : 'Item'; ?>
                                </span>
                                <?php 
                                $rarity_color = 'bg-gray-700 text-gray-300';
                                $rarity_text = 'Common';
                                if ($item['rarity']) {
                                    $rarity_text = $item['rarity'];
                                    $rarity_lower = strtolower($item['rarity']);
                                    if ($rarity_lower == 'uncommon') $rarity_color = 'bg-green-900/50 text-green-400';
                                    else if ($rarity_lower == 'rare') $rarity_color = 'bg-blue-900/50 text-blue-400';
                                    else if ($rarity_lower == 'very rare') $rarity_color = 'bg-purple-900/50 text-purple-400';
                                    else if ($rarity_lower == 'legendary') $rarity_color = 'bg-orange-900/50 text-orange-400';
                                }
                                ?>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $rarity_color; ?>">
                                    <?php echo htmlspecialchars($rarity_text); ?>
                                </span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="w-12 h-12 bg-primary/20 rounded-lg border border-primary/50 flex items-center justify-center">
                                <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Properties -->
                    <?php if ($item['properties']): ?>
                    <div class="bg-gray-900/50 rounded-lg p-3 border border-gray-700 mb-3">
                        <div class="flex items-center gap-2 mb-1">
                            <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            <span class="text-xs text-gray-400 font-semibold uppercase">Properties</span>
                        </div>
                        <p class="text-white text-sm"><?php echo htmlspecialchars($item['properties']); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Description -->
                    <?php if ($item['description']): ?>
                    <div class="bg-gray-900/30 rounded-lg p-4 border border-gray-700">
                        <p class="text-gray-300 text-sm leading-relaxed"><?php echo htmlspecialchars($item['description']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (count($unequipped_items) > 0): ?>
        <div>
            <h4 class="text-sm font-bold text-gray-400 uppercase tracking-wide mb-3">Items</h4>
            <div class="space-y-3">
                <?php foreach ($unequipped_items as $item): ?>
                <div class="bg-gray-800/30 border border-gray-700 rounded-lg p-5">
                    <!-- Item Header -->
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <h5 class="text-white font-bold text-xl mb-2"><?php echo htmlspecialchars($item['item_name']); ?></h5>
                            <div class="flex items-center gap-2 flex-wrap mb-2">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-700 text-gray-300">
                                    <?php echo $item['type'] ? htmlspecialchars($item['type']) : 'Item'; ?>
                                </span>
                                <?php 
                                $rarity_color = 'bg-gray-700 text-gray-300';
                                $rarity_text = 'Common';
                                if ($item['rarity']) {
                                    $rarity_text = $item['rarity'];
                                    $rarity_lower = strtolower($item['rarity']);
                                    if ($rarity_lower == 'uncommon') $rarity_color = 'bg-green-900/50 text-green-400';
                                    else if ($rarity_lower == 'rare') $rarity_color = 'bg-blue-900/50 text-blue-400';
                                    else if ($rarity_lower == 'very rare') $rarity_color = 'bg-purple-900/50 text-purple-400';
                                    else if ($rarity_lower == 'legendary') $rarity_color = 'bg-orange-900/50 text-orange-400';
                                }
                                ?>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $rarity_color; ?>">
                                    <?php echo htmlspecialchars($rarity_text); ?>
                                </span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="w-12 h-12 bg-gray-800/50 rounded-lg border border-gray-600 flex items-center justify-center">
                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Properties -->
                    <?php if ($item['properties']): ?>
                    <div class="bg-gray-900/50 rounded-lg p-3 border border-gray-700 mb-3">
                        <div class="flex items-center gap-2 mb-1">
                            <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            <span class="text-xs text-gray-400 font-semibold uppercase">Properties</span>
                        </div>
                        <p class="text-white text-sm"><?php echo htmlspecialchars($item['properties']); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Description -->
                    <?php if ($item['description']): ?>
                    <div class="bg-gray-900/30 rounded-lg p-4 border border-gray-700">
                        <p class="text-gray-300 text-sm leading-relaxed"><?php echo htmlspecialchars($item['description']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php else: ?>
        <p class="text-gray-500 text-center py-8">No equipment</p>
        <?php endif; ?>
    </div>

    <!-- Spell Slots Section -->
    <?php 
    $has_spell_slots = false;
    for ($i = 1; $i <= 9; $i++) {
        $slot_col = "spell_slots_$i";
        if (isset($char[$slot_col]) && $char[$slot_col] > 0) {
            $has_spell_slots = true;
            break;
        }
    }
    if ($has_spell_slots): 
    ?>
    <div class="bg-gray-900/50 border border-gray-800 rounded-xl p-6 mb-6">
        <h3 class="text-lg font-bold text-white mb-4 flex items-center">
            <svg class="w-5 h-5 text-primary mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"/>
            </svg>
            Spell Slots
        </h3>
        <div class="grid grid-cols-3 md:grid-cols-5 gap-3">
            <?php for ($i = 1; $i <= 9; $i++): 
                $max_slot_col = "spell_slots_$i";
                $current_slot_col = "current_spell_slots_$i";
                $max_slots = $char[$max_slot_col] ?? 0;
                $current_slots = $char[$current_slot_col] ?? 0;
                if ($max_slots > 0):
            ?>
            <div class="bg-gray-800/50 rounded-lg p-3">
                <p class="text-gray-400 text-xs mb-2 text-center">Level <?php echo $i; ?></p>
                <p class="text-white text-center font-bold mb-1"><?php echo $current_slots; ?> / <?php echo $max_slots; ?></p>
                <div class="flex flex-wrap gap-1 justify-center min-h-[24px]">
                    <?php for ($j = 0; $j < $max_slots; $j++): ?>
                        <div class="w-4 h-4 rounded-full <?php echo $j < $current_slots ? 'bg-primary' : 'bg-gray-700'; ?>"></div>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endif; endfor; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Spells Section - Inline Display -->
    <div class="bg-gray-900/50 border border-gray-800 rounded-xl p-6 mb-6">
        <h3 class="text-xl font-bold text-white mb-4">Spells</h3>
        <?php
        $spells = $conn->query("SELECT * FROM character_spells WHERE character_id = {$char['id']} ORDER BY level, name");
        
        if ($spells->num_rows > 0):
            $spells_by_level = [];
            while ($spell = $spells->fetch_assoc()) {
                $level = $spell['level'];
                if (!isset($spells_by_level[$level])) {
                    $spells_by_level[$level] = [];
                }
                $spells_by_level[$level][] = $spell;
            }
            ksort($spells_by_level);
            
            foreach ($spells_by_level as $level => $level_spells):
                $level_label = $level == 0 ? 'Cantrips' : 'Level ' . $level . ' Spells';
                
                // Level badge colors
                $level_badge_class = 'bg-gray-700 text-gray-300';
                if ($level === 0) $level_badge_class = 'bg-gray-700 text-gray-300';
                else if ($level === 1) $level_badge_class = 'bg-blue-900/50 text-blue-400';
                else if ($level === 2) $level_badge_class = 'bg-blue-800/50 text-blue-300';
                else if ($level === 3) $level_badge_class = 'bg-purple-900/50 text-purple-400';
                else if ($level === 4) $level_badge_class = 'bg-purple-800/50 text-purple-300';
                else if ($level === 5) $level_badge_class = 'bg-pink-900/50 text-pink-400';
                else if ($level === 6) $level_badge_class = 'bg-pink-800/50 text-pink-300';
                else if ($level === 7) $level_badge_class = 'bg-orange-900/50 text-orange-400';
                else if ($level === 8) $level_badge_class = 'bg-orange-800/50 text-orange-300';
                else if ($level === 9) $level_badge_class = 'bg-red-900/50 text-red-400';
        ?>
        <div class="mb-6">
            <h4 class="text-sm font-bold text-gray-400 uppercase tracking-wide mb-3"><?php echo $level_label; ?></h4>
            <div class="space-y-4">
                <?php foreach ($level_spells as $spell): ?>
                <div class="bg-gray-800/50 border border-gray-700 rounded-lg p-5">
                    <!-- Spell Header -->
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <h5 class="text-white font-bold text-xl mb-2"><?php echo htmlspecialchars($spell['name']); ?></h5>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $level_badge_class; ?>">
                                    <?php echo $level == 0 ? 'Cantrip' : 'Level ' . $level; ?>
                                </span>
                                <?php if ($spell['school']): ?>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-700 text-gray-300">
                                    <?php echo htmlspecialchars($spell['school']); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="w-12 h-12 bg-primary/20 rounded-lg border border-primary/50 flex items-center justify-center">
                                <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Spell Properties -->
                    <?php if ($spell['casting_time'] || $spell['range_area'] || $spell['components'] || $spell['duration']): ?>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                        <?php if ($spell['casting_time']): ?>
                        <div class="bg-gray-900/50 rounded-lg p-3 border border-gray-700">
                            <div class="flex items-center gap-2 mb-1">
                                <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="text-xs text-gray-400 font-semibold uppercase">Cast Time</span>
                            </div>
                            <p class="text-white text-sm"><?php echo htmlspecialchars($spell['casting_time']); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($spell['range_area']): ?>
                        <div class="bg-gray-900/50 rounded-lg p-3 border border-gray-700">
                            <div class="flex items-center gap-2 mb-1">
                                <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span class="text-xs text-gray-400 font-semibold uppercase">Range</span>
                            </div>
                            <p class="text-white text-sm"><?php echo htmlspecialchars($spell['range_area']); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($spell['components']): ?>
                        <div class="bg-gray-900/50 rounded-lg p-3 border border-gray-700">
                            <div class="flex items-center gap-2 mb-1">
                                <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                </svg>
                                <span class="text-xs text-gray-400 font-semibold uppercase">Components</span>
                            </div>
                            <p class="text-white text-sm"><?php echo htmlspecialchars($spell['components']); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($spell['duration']): ?>
                        <div class="bg-gray-900/50 rounded-lg p-3 border border-gray-700">
                            <div class="flex items-center gap-2 mb-1">
                                <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                <span class="text-xs text-gray-400 font-semibold uppercase">Duration</span>
                            </div>
                            <p class="text-white text-sm"><?php echo htmlspecialchars($spell['duration']); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Spell Description -->
                    <?php if ($spell['description']): ?>
                    <div class="bg-gray-900/30 rounded-lg p-4 border border-gray-700">
                        <p class="text-gray-300 text-sm leading-relaxed"><?php echo htmlspecialchars($spell['description']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php 
            endforeach;
        else:
        ?>
        <p class="text-gray-500 text-center py-8">No spells</p>
        <?php endif; ?>
    </div>
</div>

<script>
// Get character ID and max HP for polling
const characterId = <?php echo $selected_char_id ?? 0; ?>;
const maxHP = <?php echo $char['max_hp'] ?? 0; ?>;

// Polling for real-time updates
if (characterId > 0) {
    setInterval(async () => {
        try {
            const response = await fetch(`/player/api.php?action=poll_character&character_id=${characterId}`);
            const result = await response.json();
            
            if (result.success && result.character) {
                // Update HP
                const currentHP = document.getElementById('currentHP');
                if (currentHP && parseInt(currentHP.textContent) !== result.character.current_hp) {
                    currentHP.textContent = result.character.current_hp;
                    
                    const percent = (result.character.current_hp / maxHP) * 100;
                    const hpBar = document.getElementById('hpBar');
                    if (hpBar) {
                        hpBar.style.width = percent + '%';
                        hpBar.className = percent > 50 ? 'bg-green-500 h-full transition-all' : 
                                          (percent > 25 ? 'bg-yellow-500 h-full transition-all' : 
                                          'bg-red-500 h-full transition-all');
                    }
                }
                
                // Update AC with flash effect
                const ac = document.getElementById('armorClass');
                if (ac && parseInt(ac.textContent) !== result.character.armor_class) {
                    ac.textContent = result.character.armor_class;
                    ac.classList.add('text-green-400');
                    setTimeout(() => ac.classList.remove('text-green-400'), 1000);
                }
            }
        } catch (error) {
            console.error('Polling error:', error);
        }
    }, 3000);
}
</script>
