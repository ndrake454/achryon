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

// Get skills
$stmt = $conn->prepare("SELECT * FROM character_skills WHERE character_id = ?");
$stmt->bind_param("i", $char['id']);
$stmt->execute();
$skills = $stmt->get_result()->fetch_assoc();
?>

<div class="max-w-5xl mx-auto">
    <!-- Character Header -->
    <div class="bg-gray-900/50 border border-gray-800 rounded-xl p-6 mb-6">
        <div class="flex items-start justify-between mb-4">
            <div class="flex-1">
                <h2 class="text-3xl font-bold text-white mb-1"><?php echo htmlspecialchars($char['name']); ?></h2>
                <p class="text-gray-400">Level <?php echo $char['level']; ?> <?php echo htmlspecialchars($char['race']); ?> <?php echo htmlspecialchars($char['class']); ?></p>
            </div>
            <div class="flex items-center space-x-4">
                <div class="text-center bg-gray-800/50 border-2 border-primary/50 rounded-xl px-6 py-4">
                    <div class="text-sm text-gray-400 mb-1 font-semibold">ARMOR CLASS</div>
                    <div class="text-5xl font-bold text-primary" id="armorClass"><?php echo $char['armor_class'] ?? 10; ?></div>
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
        <div class="w-full bg-gray-800 rounded-full h-3 overflow-hidden">
            <?php 
            $hp_percent = ($char['max_hp'] > 0) ? ($char['current_hp'] / $char['max_hp']) * 100 : 0;
            $hp_color = $hp_percent > 50 ? 'bg-green-500' : ($hp_percent > 25 ? 'bg-yellow-500' : 'bg-red-500');
            ?>
            <div id="hpBar" class="<?php echo $hp_color; ?> h-full transition-all" style="width: <?php echo $hp_percent; ?>%"></div>
        </div>
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
                $slot_col = "spell_slots_$i";
                $slots = $char[$slot_col] ?? 0;
                if ($slots > 0):
            ?>
            <div class="bg-gray-800/50 rounded-lg p-3">
                <p class="text-gray-400 text-xs mb-2 text-center">Level <?php echo $i; ?></p>
                <div class="flex flex-wrap gap-1 justify-center min-h-[24px]">
                    <?php for ($j = 0; $j < $slots; $j++): ?>
                        <div class="w-4 h-4 rounded-full bg-primary"></div>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endif; endfor; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Ability Scores -->
    <div class="bg-gray-900/50 border border-gray-800 rounded-xl p-6 mb-6">
        <h3 class="text-lg font-bold text-white mb-4">Abilities</h3>
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm text-primary">⭐ <?php echo $char['points_available'] ?? 0; ?> points available</span>
        </div>
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

    <!-- Skills (Read-only for players typically) -->
    <div class="bg-gray-900/50 border border-gray-800 rounded-xl p-6">
        <h3 class="text-lg font-bold text-white mb-4">Skills</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            <?php 
            $skill_list = [
                'acrobatics' => ['Acrobatics', 'DEX'],
                'animal_handling' => ['Animal Handling', 'WIS'],
                'arcana' => ['Arcana', 'INT'],
                'athletics' => ['Athletics', 'STR'],
                'deception' => ['Deception', 'CHA'],
                'history' => ['History', 'INT'],
                'insight' => ['Insight', 'WIS'],
                'intimidation' => ['Intimidation', 'CHA'],
                'investigation' => ['Investigation', 'INT'],
                'medicine' => ['Medicine', 'WIS'],
                'nature' => ['Nature', 'INT'],
                'perception' => ['Perception', 'WIS'],
                'performance' => ['Performance', 'CHA'],
                'persuasion' => ['Persuasion', 'CHA'],
                'religion' => ['Religion', 'INT'],
                'sleight_of_hand' => ['Sleight of Hand', 'DEX'],
                'stealth' => ['Stealth', 'DEX'],
                'survival' => ['Survival', 'WIS']
            ];
            foreach ($skill_list as $skill => $info):
                $proficient = $skills[$skill] ?? false;
                $class = $proficient ? 'border-primary bg-primary/10' : 'border-gray-700';
            ?>
            <div class="flex items-center space-x-3 p-3 border <?php echo $class; ?> rounded-lg">
                <div class="<?php echo $proficient ? 'w-3 h-3 bg-primary rounded-full' : 'w-3 h-3 border-2 border-gray-600 rounded-full'; ?>"></div>
                <div class="flex-1">
                    <p class="text-white text-sm"><?php echo $info[0]; ?></p>
                    <p class="text-xs text-gray-500"><?php echo $info[1]; ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Equipment Section -->
    <div class="bg-gray-900/50 border border-gray-800 rounded-xl p-6 mb-6">
        <h3 class="text-xl font-bold text-white mb-4">Equipment & Inventory</h3>
        
        <?php
        $equipment = $conn->query("SELECT * FROM character_equipment WHERE character_id = {$char['id']} ORDER BY is_equipped DESC, item_name");
        $equipped_items = [];
        $unequipped_items = [];
        
        while ($item = $equipment->fetch_assoc()) {
            if ($item['is_equipped']) {
                $equipped_items[] = $item;
            } else {
                $unequipped_items[] = $item;
            }
        }
        ?>
        
        <?php if (count($equipped_items) > 0): ?>
        <div class="mb-4">
            <h4 class="text-sm font-bold text-gray-400 mb-2">Equipped</h4>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($equipped_items as $item): ?>
                <button 
                    onclick="showItemModal(this)" 
                    data-name="<?php echo htmlspecialchars($item['item_name']); ?>"
                    data-quantity="<?php echo $item['quantity']; ?>"
                    data-description="<?php echo htmlspecialchars($item['description']); ?>"
                    data-equipped="1"
                    class="bg-primary/20 border-primary border-2 rounded-lg px-4 py-2 hover:border-primary transition">
                    <span class="text-white font-medium"><?php echo htmlspecialchars($item['item_name']); ?></span>
                    <?php if ($item['quantity'] > 1): ?>
                        <span class="text-gray-400 text-sm ml-1">×<?php echo $item['quantity']; ?></span>
                    <?php endif; ?>
                    <span class="text-xs text-primary ml-2">✓</span>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (count($unequipped_items) > 0): ?>
        <div>
            <h4 class="text-sm font-bold text-gray-400 mb-2">Items</h4>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($unequipped_items as $item): ?>
                <button 
                    onclick="showItemModal(this)" 
                    data-name="<?php echo htmlspecialchars($item['item_name']); ?>"
                    data-quantity="<?php echo $item['quantity']; ?>"
                    data-description="<?php echo htmlspecialchars($item['description']); ?>"
                    data-equipped="0"
                    class="bg-gray-800/50 border-gray-700 border-2 rounded-lg px-4 py-2 hover:border-primary transition">
                    <span class="text-white font-medium"><?php echo htmlspecialchars($item['item_name']); ?></span>
                    <?php if ($item['quantity'] > 1): ?>
                        <span class="text-gray-400 text-sm ml-1">×<?php echo $item['quantity']; ?></span>
                    <?php endif; ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (count($equipped_items) == 0 && count($unequipped_items) == 0): ?>
        <p class="text-gray-500 text-center py-4">No equipment</p>
        <?php endif; ?>
    </div>
    
    <!-- Spells Section -->
    <div class="bg-gray-900/50 border border-gray-800 rounded-xl p-6 mb-6">
        <h3 class="text-xl font-bold text-white mb-4">Spells</h3>
        <?php
        $spells = $conn->query("SELECT * FROM character_spells WHERE character_id = {$char['id']} ORDER BY level, name");
        
        if ($spells->num_rows > 0):
            // Group by level
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
                $level_label = $level == 0 ? 'Cantrips' : 'Level ' . $level;
        ?>
        <div class="mb-4">
            <h4 class="text-sm font-bold text-gray-400 mb-2"><?php echo $level_label; ?></h4>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($level_spells as $spell): ?>
                <button 
                    onclick="showSpellModal(this)"
                    data-name="<?php echo htmlspecialchars($spell['name']); ?>"
                    data-level="<?php echo $spell['level']; ?>"
                    data-school="<?php echo htmlspecialchars($spell['school'] ?? ''); ?>"
                    data-casting-time="<?php echo htmlspecialchars($spell['casting_time'] ?? ''); ?>"
                    data-description="<?php echo htmlspecialchars($spell['description'] ?? ''); ?>"
                    class="bg-gray-800/50 border-2 border-gray-700 rounded-lg px-4 py-2 hover:border-primary transition">
                    <span class="text-white font-medium"><?php echo htmlspecialchars($spell['name']); ?></span>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
        <?php 
            endforeach;
        else:
        ?>
        <p class="text-gray-500 text-center py-4">No spells</p>
        <?php endif; ?>
    </div>
</div>

<!-- Item Detail Modal -->
<div id="itemModal" class="hidden fixed inset-0 bg-black/75 backdrop-blur-sm flex items-center justify-center p-4 z-50" onclick="if(event.target === this) hideItemModal()">
    <div class="bg-gray-900 border border-gray-800 rounded-xl shadow-2xl w-full max-w-lg">
        <div class="p-6">
            <!-- Item Header -->
            <div class="flex items-start justify-between mb-4">
                <div class="flex-1">
                    <h3 id="itemModalName" class="text-2xl font-bold text-white mb-2"></h3>
                    <div id="itemModalEquipped"></div>
                </div>
                
                <!-- Item Icon -->
                <div class="ml-4">
                    <div class="w-12 h-12 bg-primary/20 rounded-lg border border-primary/50 flex items-center justify-center">
                        <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                </div>
                
                <button onclick="hideItemModal()" class="ml-2 text-gray-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Item Details -->
            <div class="space-y-2 mb-4 text-sm">
                <div class="flex items-start" id="itemModalQtyContainer">
                    <svg class="w-4 h-4 text-gray-500 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                    <span class="text-gray-400" id="itemModalQty"></span>
                </div>
            </div>
            
            <!-- Item Description -->
            <div class="bg-gray-800/30 rounded-lg p-4 border border-gray-700">
                <p id="itemModalDesc" class="text-gray-300 leading-relaxed"></p>
            </div>
        </div>
    </div>
</div>

<!-- Spell Detail Modal -->
<div id="spellModal" class="hidden fixed inset-0 bg-black/75 backdrop-blur-sm flex items-center justify-center p-4 z-50" onclick="if(event.target === this) hideSpellModal()">
    <div class="bg-gray-900 border border-gray-800 rounded-xl shadow-2xl w-full max-w-lg">
        <div class="p-6">
            <!-- Spell Header -->
            <div class="flex items-start justify-between mb-4">
                <div class="flex-1">
                    <h3 id="spellModalName" class="text-2xl font-bold text-white mb-2"></h3>
                    <div class="flex items-center gap-2 flex-wrap" id="spellModalBadges">
                        <!-- Badges added by JS -->
                    </div>
                </div>
                
                <!-- Spell Icon -->
                <div class="ml-4">
                    <div class="w-12 h-12 bg-primary/20 rounded-lg border border-primary/50 flex items-center justify-center">
                        <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                        </svg>
                    </div>
                </div>
                
                <button onclick="hideSpellModal()" class="ml-2 text-gray-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Spell Details -->
            <div class="space-y-2 mb-4 text-sm" id="spellModalMeta">
                <!-- Meta info added by JS -->
            </div>
            
            <!-- Spell Description -->
            <div class="bg-gray-800/30 rounded-lg p-4 border border-gray-700">
                <p id="spellModalDesc" class="text-gray-300 leading-relaxed"></p>
            </div>
        </div>
    </div>
</div>

<script>
const characterId = <?php echo $char['id']; ?>;
const maxHP = <?php echo $char['max_hp'] ?? 0; ?>;

function showItemModal(button) {
    document.getElementById('itemModalName').textContent = button.dataset.name;
    
    // Equipped badge
    document.getElementById('itemModalEquipped').innerHTML = button.dataset.equipped == '1' ? 
        '<span class="inline-block px-3 py-1 bg-primary/20 text-primary rounded-full text-sm font-semibold">Equipped</span>' : '';
    
    // Quantity
    const qty = button.dataset.quantity;
    if (qty > 1) {
        document.getElementById('itemModalQty').textContent = `Quantity: ${qty}`;
        document.getElementById('itemModalQtyContainer').style.display = 'flex';
    } else {
        document.getElementById('itemModalQtyContainer').style.display = 'none';
    }
    
    document.getElementById('itemModalDesc').textContent = button.dataset.description || 'No description available.';
    document.getElementById('itemModal').classList.remove('hidden');
}

function hideItemModal() {
    document.getElementById('itemModal').classList.add('hidden');
}

function showSpellModal(button) {
    const level = button.dataset.level;
    const levelColors = {
        '0': 'bg-gray-700 text-gray-300',
        '1': 'bg-blue-900/50 text-blue-400',
        '2': 'bg-blue-800/50 text-blue-300',
        '3': 'bg-purple-900/50 text-purple-400',
        '4': 'bg-purple-800/50 text-purple-300',
        '5': 'bg-pink-900/50 text-pink-400',
        '6': 'bg-pink-800/50 text-pink-300',
        '7': 'bg-orange-900/50 text-orange-400',
        '8': 'bg-orange-800/50 text-orange-300',
        '9': 'bg-red-900/50 text-red-400'
    };
    
    const levelColor = levelColors[level] || 'bg-gray-700 text-gray-300';
    const levelTexts = ['Cantrip', '1st Level', '2nd Level', '3rd Level', '4th Level', '5th Level', '6th Level', '7th Level', '8th Level', '9th Level'];
    const levelText = levelTexts[parseInt(level)] || `${level}th Level`;
    
    document.getElementById('spellModalName').textContent = button.dataset.name;
    
    // Level and school badges
    let badgesHTML = `<span class="text-xs px-2 py-1 ${levelColor} rounded font-medium">${levelText}</span>`;
    if (button.dataset.school) {
        badgesHTML += `<span class="text-xs px-2 py-1 bg-gray-800 text-gray-400 rounded">${button.dataset.school}</span>`;
    }
    document.getElementById('spellModalBadges').innerHTML = badgesHTML;
    
    // Casting time meta
    let metaHTML = '';
    if (button.dataset.castingTime) {
        metaHTML = `
            <div class="flex items-start">
                <svg class="w-4 h-4 text-gray-500 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-gray-400">${button.dataset.castingTime}</span>
            </div>
        `;
    }
    document.getElementById('spellModalMeta').innerHTML = metaHTML;
    
    document.getElementById('spellModalDesc').textContent = button.dataset.description || 'No description available.';
    document.getElementById('spellModal').classList.remove('hidden');
}

function hideSpellModal() {
    document.getElementById('spellModal').classList.add('hidden');
}

// Poll for updates every 3 seconds
setInterval(async () => {
    try {
        const response = await fetch(`/player/api.php?action=poll_character&character_id=${characterId}`);
        const result = await response.json();
        
        if (result.success && result.character) {
            const currentHP = document.getElementById('currentHP');
            if (currentHP && parseInt(currentHP.textContent) !== result.character.current_hp) {
                currentHP.textContent = result.character.current_hp;
                currentHP.classList.add('text-primary');
                setTimeout(() => currentHP.classList.remove('text-primary'), 500);
                
                const percent = (result.character.current_hp / maxHP) * 100;
                const hpBar = document.getElementById('hpBar');
                if (hpBar) {
                    hpBar.style.width = percent + '%';
                    hpBar.className = percent > 50 ? 'bg-green-500 h-full transition-all' : 
                                      (percent > 25 ? 'bg-yellow-500 h-full transition-all' : 
                                      'bg-red-500 h-full transition-all');
                }
            }
        }
    } catch (error) {
        console.error('Polling error:', error);
    }
}, 3000);
</script>
