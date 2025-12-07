<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

requireDM();

$char_id = $_GET['id'] ?? 0;

// Fetch character data
$stmt = $conn->prepare("
    SELECT c.*, cs.* 
    FROM characters c 
    LEFT JOIN character_stats cs ON c.id = cs.character_id 
    WHERE c.id = ?
");
$stmt->bind_param("i", $char_id);
$stmt->execute();
$char = $stmt->get_result()->fetch_assoc();

if (!$char) {
    header('Location: /admin/index.php?tab=characters');
    exit;
}

// Fetch related data
$equipment = $conn->query("SELECT * FROM character_equipment WHERE character_id = $char_id ORDER BY is_equipped DESC, item_name");
$spells = $conn->query("SELECT * FROM character_spells WHERE character_id = $char_id ORDER BY level, name");
$feats = $conn->query("SELECT * FROM character_feats WHERE character_id = $char_id ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit <?php echo htmlspecialchars($char['name']); ?> - D&D Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary': '#ff6b35',
                        'primary-dark': '#e85a2a'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-950 text-white min-h-screen">
    <div class="max-w-4xl mx-auto p-4">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <a href="/admin/index.php?tab=characters" class="text-gray-400 hover:text-white transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h1 class="text-3xl font-bold text-primary">Edit Character</h1>
            <div class="w-6"></div>
        </div>

        <!-- Character Portrait -->
        <div class="bg-gray-900/50 border border-gray-800 rounded-xl p-6 mb-6">
            <h3 class="text-lg font-bold text-white mb-4">Character Portrait</h3>
            <div class="flex items-start space-x-6">
                <!-- Current Image -->
                <div class="flex-shrink-0">
                    <?php if (!empty($char['image_url'])): ?>
                        <img id="characterPortrait" src="<?php echo htmlspecialchars($char['image_url']); ?>" 
                            alt="Character Portrait" 
                            class="w-32 h-32 object-cover rounded-lg border-2 border-primary">
                    <?php else: ?>
                        <div id="characterPortrait" class="w-32 h-32 bg-gray-800 rounded-lg border-2 border-gray-700 flex items-center justify-center">
                            <svg class="w-16 h-16 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Upload Controls -->
                <div class="flex-1">
                    <p class="text-gray-400 text-sm mb-3">Upload a portrait image for this character (JPEG, PNG, GIF, or WebP, max 5MB)</p>
                    <div class="flex items-center space-x-3">
                        <input type="file" id="portraitInput" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden">
                        <button type="button" onclick="document.getElementById('portraitInput').click()" 
                            class="px-4 py-2 bg-primary/20 hover:bg-primary/30 text-primary rounded-lg transition">
                            Choose Image
                        </button>
                        <button type="button" onclick="uploadPortrait()" 
                            class="px-4 py-2 bg-primary hover:bg-primary/80 text-white rounded-lg transition">
                            Upload
                        </button>
                        <?php if (!empty($char['image_url'])): ?>
                            <button type="button" onclick="removePortrait()" 
                                class="px-4 py-2 bg-red-900/30 hover:bg-red-900/50 text-red-400 rounded-lg transition">
                                Remove
                            </button>
                        <?php endif; ?>
                    </div>
                    <p id="portraitStatus" class="text-sm mt-2"></p>
                </div>
            </div>
        </div>

        <!-- Character Name & Basic Info -->
        <div class="bg-gray-900/50 border border-gray-800 rounded-xl p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-300 mb-2 text-sm">Character Name</label>
                    <input type="text" id="name" value="<?php echo htmlspecialchars($char['name']); ?>" 
                        onchange="updateField('name', this.value)"
                        class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-gray-300 mb-2 text-sm">Class</label>
                    <input type="text" id="class" value="<?php echo htmlspecialchars($char['class'] ?? ''); ?>" 
                        onchange="updateField('class', this.value)"
                        class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-gray-300 mb-2 text-sm">Level</label>
                    <input type="number" id="level" value="<?php echo $char['level'] ?? 1; ?>" 
                        onchange="updateField('level', this.value)"
                        class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-gray-300 mb-2 text-sm">Race</label>
                    <input type="text" id="race" value="<?php echo htmlspecialchars($char['race'] ?? ''); ?>" 
                        onchange="updateField('race', this.value)"
                        class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                </div>
            </div>
        </div>

        <!-- HP & AC -->
        <div class="grid grid-cols-2 gap-4 mb-6">
            <div class="bg-gray-900/50 border border-gray-800 rounded-xl p-6">
                <h3 class="text-lg font-bold text-white mb-4 flex items-center">
                    <svg class="w-5 h-5 text-primary mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
                    </svg>
                    Hit Points
                </h3>
                <div class="flex items-center justify-center space-x-3 mb-4">
                    <button onclick="adjustHP(-5)" class="bg-red-500/20 hover:bg-red-500/30 text-red-400 font-bold w-12 h-12 rounded-lg transition">-5</button>
                    <button onclick="adjustHP(-1)" class="bg-red-500/20 hover:bg-red-500/30 text-red-400 font-bold w-10 h-10 rounded-lg transition">-1</button>
                    <div class="text-center">
                        <p id="currentHP" class="text-4xl font-bold text-white"><?php echo $char['current_hp'] ?? 0; ?></p>
                        <p class="text-gray-400">/</p>
                        <input type="number" id="maxHP" value="<?php echo $char['max_hp'] ?? 0; ?>" 
                            onchange="updateStat('max_hp', this.value)"
                            class="w-20 px-2 py-1 bg-gray-800 border border-gray-700 rounded text-white text-center focus:outline-none focus:border-primary">
                    </div>
                    <button onclick="adjustHP(1)" class="bg-green-500/20 hover:bg-green-500/30 text-green-400 font-bold w-10 h-10 rounded-lg transition">+1</button>
                    <button onclick="adjustHP(5)" class="bg-green-500/20 hover:bg-green-500/30 text-green-400 font-bold w-12 h-12 rounded-lg transition">+5</button>
                </div>
                <?php 
                $hp_percent = ($char['max_hp'] > 0) ? ($char['current_hp'] / $char['max_hp']) * 100 : 0;
                $hp_color = $hp_percent > 50 ? 'bg-green-500' : ($hp_percent > 25 ? 'bg-yellow-500' : 'bg-red-500');
                ?>
                <div class="w-full bg-gray-800 rounded-full h-3">
                    <div id="hpBar" class="<?php echo $hp_color; ?> h-full transition-all rounded-full" style="width: <?php echo $hp_percent; ?>%"></div>
                </div>
            </div>

            <div class="bg-gray-900/50 border border-gray-800 rounded-xl p-6">
                <h3 class="text-lg font-bold text-white mb-4">Armor Class</h3>
                <div class="text-center">
                    <input type="number" id="armorClass" value="<?php echo $char['armor_class'] ?? 10; ?>" 
                        onchange="updateStat('armor_class', this.value)"
                        class="w-24 px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-center text-4xl font-bold focus:outline-none focus:border-primary">
                </div>
            </div>
        </div>

        <!-- Spell Slots -->
        <div class="bg-gray-900/50 border border-gray-800 rounded-xl p-6 mb-6">
            <h3 class="text-lg font-bold text-white mb-4 flex items-center">
                <svg class="w-5 h-5 text-primary mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"/>
                </svg>
                Spell Slots
            </h3>
            <div class="grid grid-cols-3 md:grid-cols-5 gap-4">
                <?php for ($i = 1; $i <= 9; $i++): 
                    $max_col = "spell_slots_$i";
                    $current_col = "current_spell_slots_$i";
                    $max_slots = $char[$max_col] ?? 0;
                    $current_slots = $char[$current_col] ?? 0;
                ?>
                <div class="bg-gray-800/50 rounded-lg p-4">
                    <p class="text-gray-400 text-xs mb-2 text-center font-semibold">Level <?php echo $i; ?></p>
                    
                    <!-- Max Slots -->
                    <div class="mb-3">
                        <label class="text-xs text-gray-500 block mb-1">Max Slots</label>
                        <input type="number" min="0" max="9" value="<?php echo $max_slots; ?>" 
                            onchange="updateMaxSpellSlots(<?php echo $i; ?>, this.value)"
                            class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white text-center text-sm focus:outline-none focus:border-primary">
                    </div>
                    
                    <!-- Current Available -->
                    <div class="mb-2">
                        <label class="text-xs text-gray-500 block mb-1">Available</label>
                        <div class="flex items-center justify-center space-x-1">
                            <button onclick="updateCurrentSpellSlots(<?php echo $i; ?>, -1)" 
                                class="bg-red-500/20 hover:bg-red-500/30 text-red-400 font-bold w-6 h-6 rounded text-xs transition">-</button>
                            <span id="currentSlots<?php echo $i; ?>" class="text-white font-bold text-lg w-8 text-center"><?php echo $current_slots; ?></span>
                            <button onclick="updateCurrentSpellSlots(<?php echo $i; ?>, 1)" 
                                class="bg-green-500/20 hover:bg-green-500/30 text-green-400 font-bold w-6 h-6 rounded text-xs transition">+</button>
                        </div>
                    </div>
                    
                    <!-- Visual dots -->
                    <div id="spellSlots<?php echo $i; ?>" class="flex flex-wrap gap-1 justify-center min-h-[20px]">
                        <?php for ($j = 0; $j < $max_slots; $j++): ?>
                            <div class="w-3 h-3 rounded-full <?php echo $j < $current_slots ? 'bg-primary' : 'bg-gray-700'; ?>"></div>
                        <?php endfor; ?>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Ability Scores -->
        <div class="bg-gray-900/50 border border-gray-800 rounded-xl p-6 mb-6">
            <h3 class="text-lg font-bold text-white mb-4">Ability Scores</h3>
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
                    <p class="text-xs text-gray-500 uppercase mb-2"><?php echo $abbr; ?></p>
                    <input type="number" id="<?php echo $data[0]; ?>" value="<?php echo $score; ?>" 
                        onchange="updateAbility('<?php echo $data[0]; ?>', this.value, this)"
                        class="w-full px-2 py-1 bg-gray-700 border border-gray-600 rounded text-white text-center text-2xl font-bold focus:outline-none focus:border-primary mb-2">
                    <p id="<?php echo $data[0]; ?>_mod" class="text-lg <?php echo $mod_color; ?> font-bold">
                        <?php echo $modifier >= 0 ? '+' : ''; ?><?php echo $modifier; ?>
                    </p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>


        <!-- Equipment -->
        <div class="bg-gray-900/50 border border-gray-800 rounded-xl p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-white">Equipment & Inventory</h3>
                <button onclick="showAddEquipmentModal()" class="bg-primary hover:bg-primary-dark text-white font-bold py-2 px-4 rounded-lg transition">
                    Add Item
                </button>
            </div>
            
            <?php
            $equipped_items = [];
            $unequipped_items = [];
            $equipment->data_seek(0);
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
                <div class="space-y-2">
                    <?php foreach ($equipped_items as $item): ?>
                    <div class="flex items-center space-x-3 p-3 bg-gray-800/50 rounded-lg">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" checked 
                                onchange="toggleEquipped(<?php echo $item['id']; ?>, this.checked)"
                                class="w-5 h-5 text-primary bg-gray-700 border-gray-600 rounded focus:ring-primary">
                        </label>
                        <div class="flex-1">
                            <h4 class="text-white font-bold"><?php echo htmlspecialchars($item['item_name']); ?></h4>
                            <?php if ($item['description']): ?>
                            <p class="text-sm text-gray-400"><?php echo htmlspecialchars($item['description']); ?></p>
                            <?php endif; ?>
                            <p class="text-xs text-gray-500 mt-1">Quantity: <?php echo $item['quantity']; ?></p>
                        </div>
                        <button onclick="deleteEquipment(<?php echo $item['id']; ?>)" class="text-red-400 hover:text-red-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (count($unequipped_items) > 0): ?>
            <div>
                <h4 class="text-sm font-bold text-gray-400 mb-2">Items</h4>
                <div class="space-y-2">
                    <?php foreach ($unequipped_items as $item): ?>
                    <div class="flex items-center space-x-3 p-3 bg-gray-800/50 rounded-lg">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" 
                                onchange="toggleEquipped(<?php echo $item['id']; ?>, this.checked)"
                                class="w-5 h-5 text-primary bg-gray-700 border-gray-600 rounded focus:ring-primary">
                        </label>
                        <div class="flex-1">
                            <h4 class="text-white font-bold"><?php echo htmlspecialchars($item['item_name']); ?></h4>
                            <?php if ($item['description']): ?>
                            <p class="text-sm text-gray-400"><?php echo htmlspecialchars($item['description']); ?></p>
                            <?php endif; ?>
                            <p class="text-xs text-gray-500 mt-1">Quantity: <?php echo $item['quantity']; ?></p>
                        </div>
                        <button onclick="deleteEquipment(<?php echo $item['id']; ?>)" class="text-red-400 hover:text-red-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (count($equipped_items) == 0 && count($unequipped_items) == 0): ?>
            <p class="text-gray-500 text-center py-8">No equipment yet</p>
            <?php endif; ?>
        </div>

        <!-- Spells -->
        <div class="bg-gray-900/50 border border-gray-800 rounded-xl p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-white">Spells</h3>
                <button onclick="showAddSpellModal()" class="bg-primary hover:bg-primary-dark text-white font-bold py-2 px-4 rounded-lg transition">
                    Add Spell
                </button>
            </div>
            
            <?php
            $spells_by_level = [];
            $spells->data_seek(0);
            while ($spell = $spells->fetch_assoc()) {
                $level = $spell['level'];
                if (!isset($spells_by_level[$level])) {
                    $spells_by_level[$level] = [];
                }
                $spells_by_level[$level][] = $spell;
            }
            ksort($spells_by_level);
            ?>
            
            <?php if (count($spells_by_level) > 0): ?>
                <?php foreach ($spells_by_level as $level => $level_spells): ?>
                <div class="mb-4">
                    <h4 class="text-sm font-bold text-gray-400 mb-2">
                        <?php echo $level == 0 ? 'Cantrips' : 'Level ' . $level; ?>
                    </h4>
                    <div class="space-y-2">
                        <?php foreach ($level_spells as $spell): ?>
                        <div class="flex items-start space-x-4 p-4 bg-gray-800/50 rounded-lg">
                            <div class="flex-1">
                                <h5 class="text-white font-bold"><?php echo htmlspecialchars($spell['name']); ?></h5>
                                <?php if ($spell['school']): ?>
                                <p class="text-xs text-gray-500">
                                    <?php echo htmlspecialchars($spell['school']); ?> 
                                    <?php if ($spell['casting_time']): ?>• <?php echo htmlspecialchars($spell['casting_time']); ?><?php endif; ?>
                                </p>
                                <?php endif; ?>
                                <?php if ($spell['description']): ?>
                                <p class="text-sm text-gray-400 mt-1"><?php echo htmlspecialchars($spell['description']); ?></p>
                                <?php endif; ?>
                            </div>
                            <button onclick="deleteSpell(<?php echo $spell['id']; ?>)" class="text-red-400 hover:text-red-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
            <p class="text-gray-500 text-center py-8">No spells yet</p>
            <?php endif; ?>
        </div>

        <!-- Feats -->
        <div class="bg-gray-900/50 border border-gray-800 rounded-xl p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-white">Feats</h3>
                <button onclick="showAddFeatModal()" class="bg-primary hover:bg-primary-dark text-white font-bold py-2 px-4 rounded-lg transition">
                    Add Feat
                </button>
            </div>
            
            <?php if ($feats->num_rows > 0): ?>
            <div class="space-y-2">
                <?php while ($feat = $feats->fetch_assoc()): ?>
                <div class="flex items-start space-x-4 p-4 bg-gray-800/50 rounded-lg">
                    <div class="flex-1">
                        <h5 class="text-white font-bold"><?php echo htmlspecialchars($feat['name']); ?></h5>
                        <?php if ($feat['description']): ?>
                        <p class="text-sm text-gray-400 mt-1"><?php echo htmlspecialchars($feat['description']); ?></p>
                        <?php endif; ?>
                    </div>
                    <button onclick="deleteFeat(<?php echo $feat['id']; ?>)" class="text-red-400 hover:text-red-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
            <p class="text-gray-500 text-center py-8">No feats yet</p>
            <?php endif; ?>
        </div>
        
        <!-- Status Effects -->
        <div class="bg-gray-900/50 border border-gray-800 rounded-xl p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-white flex items-center">
                    <svg class="w-5 h-5 text-primary mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    Status Effects
                </h3>
                <button onclick="showAddStatusModal()" class="bg-primary hover:bg-primary-dark text-white font-bold py-2 px-4 rounded-lg transition">
                    Add Status
                </button>
            </div>
            
            <div id="statusEffectsList" class="space-y-2">
                <p class="text-gray-500 text-center py-8">Loading status effects...</p>
            </div>
        </div>
    </div>

    <!-- Add Equipment Modal -->
    <div id="addEquipmentModal" class="hidden fixed inset-0 bg-black/75 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <div class="bg-gray-900 border border-gray-800 rounded-xl shadow-2xl w-full max-w-lg">
            <div class="p-6 border-b border-gray-800">
                <h3 class="text-xl font-bold text-white">Add Equipment</h3>
            </div>
            <form onsubmit="addEquipment(event)" class="p-6 space-y-4">
                <div>
                    <label class="block text-gray-300 mb-2 text-sm font-semibold">Select Item *</label>
                    <select id="itemLibrarySelect" required class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                        <option value="">-- Choose an item --</option>
                        <?php
                        $items = $conn->query("SELECT * FROM items ORDER BY name");
                        while ($lib_item = $items->fetch_assoc()):
                        ?>
                        <option value="<?php echo htmlspecialchars(json_encode($lib_item), ENT_QUOTES); ?>"><?php echo htmlspecialchars($lib_item['name']); ?> (<?php echo htmlspecialchars($lib_item['rarity'] ?? 'Common'); ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="flex items-center">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" id="equipmentEquipped" class="w-5 h-5 text-primary bg-gray-700 border-gray-600 rounded focus:ring-primary">
                        <span class="text-gray-300">Equipped</span>
                    </label>
                </div>
                
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-primary hover:bg-primary-dark text-white font-bold py-3 rounded-lg transition">
                        Add Item
                    </button>
                    <button type="button" onclick="hideAddEquipmentModal()" class="px-6 bg-gray-800 hover:bg-gray-700 text-white font-bold py-3 rounded-lg transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Spell Modal -->
    <div id="addSpellModal" class="hidden fixed inset-0 bg-black/75 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <div class="bg-gray-900 border border-gray-800 rounded-xl shadow-2xl w-full max-w-lg">
            <div class="p-6 border-b border-gray-800">
                <h3 class="text-xl font-bold text-white">Add Spell</h3>
            </div>
            <form onsubmit="addSpell(event)" class="p-6 space-y-4">
                <div>
                    <label class="block text-gray-300 mb-2 text-sm font-semibold">Select from Spell Library</label>
                    <select id="spellLibrarySelect" onchange="selectSpellFromLibrary(this.value)" class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                        <option value="">-- Choose a spell or enter custom below --</option>
                        <?php
                        $lib_spells = $conn->query("SELECT * FROM spells ORDER BY level, name");
                        while ($lib_spell = $lib_spells->fetch_assoc()):
                            $level_text = $lib_spell['level'] == 0 ? 'Cantrip' : 'Level ' . $lib_spell['level'];
                        ?>
                        <option value="<?php echo htmlspecialchars(json_encode($lib_spell), ENT_QUOTES); ?>"><?php echo htmlspecialchars($lib_spell['name']); ?> (<?php echo $level_text; ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="border-t border-gray-800 pt-4">
                    <p class="text-sm text-gray-400 mb-3">Or enter custom spell details:</p>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-gray-300 mb-2 text-sm">Spell Name *</label>
                            <input type="text" id="spellName" required class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-gray-300 mb-2 text-sm">Level *</label>
                            <select id="spellLevel" required class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                                <option value="0">Cantrip</option>
                                <option value="1">1st Level</option>
                                <option value="2">2nd Level</option>
                                <option value="3">3rd Level</option>
                                <option value="4">4th Level</option>
                                <option value="5">5th Level</option>
                                <option value="6">6th Level</option>
                                <option value="7">7th Level</option>
                                <option value="8">8th Level</option>
                                <option value="9">9th Level</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-300 mb-2 text-sm">School (optional)</label>
                            <input type="text" id="spellSchool" class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-gray-300 mb-2 text-sm">Casting Time (optional)</label>
                            <input type="text" id="spellCastingTime" class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-gray-300 mb-2 text-sm">Range/Area (optional)</label>
                            <input type="text" id="spellRange" class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-gray-300 mb-2 text-sm">Components (optional)</label>
                            <input type="text" id="spellComponents" class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-gray-300 mb-2 text-sm">Duration (optional)</label>
                            <input type="text" id="spellDuration" class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-gray-300 mb-2 text-sm">Description (optional)</label>
                            <textarea id="spellDesc" rows="2" class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary"></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-primary hover:bg-primary-dark text-white font-bold py-3 rounded-lg transition">
                        Add Spell
                    </button>
                    <button type="button" onclick="hideAddSpellModal()" class="px-6 bg-gray-800 hover:bg-gray-700 text-white font-bold py-3 rounded-lg transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Feat Modal -->
    <div id="addFeatModal" class="hidden fixed inset-0 bg-black/75 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <div class="bg-gray-900 border border-gray-800 rounded-xl shadow-2xl w-full max-w-lg">
            <div class="p-6 border-b border-gray-800">
                <h3 class="text-xl font-bold text-white">Add Feat</h3>
            </div>
            <form onsubmit="addFeat(event)" class="p-6 space-y-4">
                <div>
                    <label class="block text-gray-300 mb-2 text-sm">Feat Name *</label>
                    <input type="text" id="featName" required class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-gray-300 mb-2 text-sm">Description</label>
                    <textarea id="featDesc" rows="3" class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary"></textarea>
                </div>
                
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-primary hover:bg-primary-dark text-white font-bold py-3 rounded-lg transition">
                        Add Feat
                    </button>
                    <button type="button" onclick="hideAddFeatModal()" class="px-6 bg-gray-800 hover:bg-gray-700 text-white font-bold py-3 rounded-lg transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Add Status Effect Modal -->
    <div id="addStatusModal" class="hidden fixed inset-0 bg-black/75 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <div class="bg-gray-900 border border-gray-800 rounded-xl shadow-2xl w-full max-w-lg">
            <div class="p-6 border-b border-gray-800">
                <h3 class="text-xl font-bold text-white">Add Status Effect</h3>
            </div>
            <form onsubmit="addStatus(event)" class="p-6 space-y-4">
                <div>
                    <label class="block text-gray-300 mb-2 text-sm">Status Name *</label>
                    <select id="statusName" required class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
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
                <div id="customStatusNameDiv" class="hidden">
                    <label class="block text-gray-300 mb-2 text-sm">Custom Status Name *</label>
                    <input type="text" id="customStatusName" class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-gray-300 mb-2 text-sm">Description/Notes</label>
                    <textarea id="statusDesc" rows="2" class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary" placeholder="Optional notes (e.g., duration, source)"></textarea>
                </div>
                
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-primary hover:bg-primary-dark text-white font-bold py-3 rounded-lg transition">
                        Add Status
                    </button>
                    <button type="button" onclick="hideAddStatusModal()" class="px-6 bg-gray-800 hover:bg-gray-700 text-white font-bold py-3 rounded-lg transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const charId = <?php echo $char_id; ?>;
        const maxHP = <?php echo $char['max_hp'] ?? 0; ?>;

        async function apiCall(formData) {
            try {
                const response = await fetch('/admin/api.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (!result.success) {
                    alert('Error: ' + (result.error || 'Unknown error'));
                }
                return result.success;
            } catch (error) {
                console.error('API Error:', error);
                alert('Network error occurred');
                return false;
            }
        }

        // Basic field updates
        async function updateField(field, value) {
            const formData = new FormData();
            formData.append('action', 'update_character');
            formData.append('id', charId);
            formData.append('field', field);
            formData.append('value', value);
            await apiCall(formData);
        }

        async function updateStat(field, value) {
            const formData = new FormData();
            formData.append('action', 'update_character_stat');
            formData.append('character_id', charId);
            formData.append('field', field);
            formData.append('value', value);
            await apiCall(formData);
        }

        // HP Management
        async function adjustHP(change) {
            const currentHP = parseInt(document.getElementById('currentHP').textContent);
            const newHP = Math.max(0, Math.min(maxHP, currentHP + change));
            
            const formData = new FormData();
            formData.append('action', 'update_hp');
            formData.append('character_id', charId);
            formData.append('change', change);
            
            if (await apiCall(formData)) {
                document.getElementById('currentHP').textContent = newHP;
                
                const percent = (newHP / maxHP) * 100;
                const hpBar = document.getElementById('hpBar');
                hpBar.style.width = percent + '%';
                hpBar.className = percent > 50 ? 'bg-green-500 h-full transition-all rounded-full' : 
                                  (percent > 25 ? 'bg-yellow-500 h-full transition-all rounded-full' : 
                                  'bg-red-500 h-full transition-all rounded-full');
            }
        }

        // Spell Slots
        async function updateMaxSpellSlots(level, maxSlots) {
            const formData = new FormData();
            formData.append('action', 'update_max_spell_slots');
            formData.append('character_id', charId);
            formData.append('level', level);
            formData.append('max_slots', maxSlots);
            
            if (await apiCall(formData)) {
                updateSpellSlotsDisplay(level, maxSlots, parseInt(document.getElementById(`currentSlots${level}`).textContent));
            }
        }
        
        async function updateCurrentSpellSlots(level, change) {
            const currentEl = document.getElementById(`currentSlots${level}`);
            const current = parseInt(currentEl.textContent);
            const newValue = Math.max(0, current + change);
            
            const formData = new FormData();
            formData.append('action', 'update_current_spell_slots');
            formData.append('character_id', charId);
            formData.append('level', level);
            formData.append('current_slots', newValue);
            
            if (await apiCall(formData)) {
                currentEl.textContent = newValue;
                const maxSlots = parseInt(document.querySelector(`input[onchange*="updateMaxSpellSlots(${level}"]`).value);
                updateSpellSlotsDisplay(level, maxSlots, newValue);
            }
        }
        
        function updateSpellSlotsDisplay(level, maxSlots, currentSlots) {
            const slotEl = document.getElementById(`spellSlots${level}`);
            slotEl.innerHTML = '';
            for (let i = 0; i < maxSlots; i++) {
                const circle = document.createElement('div');
                circle.className = `w-3 h-3 rounded-full ${i < currentSlots ? 'bg-primary' : 'bg-gray-700'}`;
                slotEl.appendChild(circle);
            }
        }

        // Abilities
        async function updateAbility(ability, value, input) {
            const formData = new FormData();
            formData.append('action', 'update_ability');
            formData.append('character_id', charId);
            formData.append('ability', ability);
            formData.append('value', value);
            
            if (await apiCall(formData)) {
                const modifier = Math.floor((parseInt(value) - 10) / 2);
                const modEl = document.getElementById(ability + '_mod');
                modEl.textContent = (modifier >= 0 ? '+' : '') + modifier;
                modEl.className = modifier >= 0 ? 'text-lg text-green-400 font-bold' : 'text-lg text-red-400 font-bold';
            }
        }

        // Equipment
        function showAddEquipmentModal() {
            document.getElementById('addEquipmentModal').classList.remove('hidden');
            document.getElementById('itemLibrarySelect').value = '';
            document.getElementById('equipmentEquipped').checked = false;
        }

        function hideAddEquipmentModal() {
            document.getElementById('addEquipmentModal').classList.add('hidden');
        }

        async function addEquipment(event) {
            event.preventDefault();
            
            const select = document.getElementById('itemLibrarySelect');
            if (!select.value) {
                alert('Please select an item');
                return;
            }
            
            const item = JSON.parse(select.value);
            
            const formData = new FormData();
            formData.append('action', 'add_equipment');
            formData.append('character_id', charId);
            formData.append('item_name', item.name);
            formData.append('type', item.type || '');
            formData.append('rarity', item.rarity || '');
            formData.append('properties', item.properties || '');
            formData.append('description', item.description || '');
            formData.append('is_equipped', document.getElementById('equipmentEquipped').checked ? '1' : '0');
            
            if (await apiCall(formData)) {
                location.reload();
            }
        }

        async function toggleEquipped(equipmentId, equipped) {
            const formData = new FormData();
            formData.append('action', 'toggle_equipped');
            formData.append('id', equipmentId);
            formData.append('equipped', equipped ? '1' : '0');
            
            if (await apiCall(formData)) {
                location.reload();
            }
        }

        async function deleteEquipment(equipmentId) {
            if (!confirm('Delete this item?')) return;
            
            const formData = new FormData();
            formData.append('action', 'delete_equipment');
            formData.append('id', equipmentId);
            
            if (await apiCall(formData)) {
                location.reload();
            }
        }

        // Spells
        function showAddSpellModal() {
            document.getElementById('addSpellModal').classList.remove('hidden');
            document.getElementById('spellLibrarySelect').value = '';
            document.getElementById('spellName').value = '';
            document.getElementById('spellLevel').value = '0';
            document.getElementById('spellSchool').value = '';
            document.getElementById('spellCastingTime').value = '';
            document.getElementById('spellDesc').value = '';
        }

        function hideAddSpellModal() {
            document.getElementById('addSpellModal').classList.add('hidden');
        }

        function selectSpellFromLibrary(jsonData) {
            if (!jsonData) return;
            
            const spell = JSON.parse(jsonData);
            document.getElementById('spellName').value = spell.name;
            document.getElementById('spellLevel').value = spell.level;
            document.getElementById('spellSchool').value = spell.school || '';
            document.getElementById('spellCastingTime').value = spell.casting_time || '';
            document.getElementById('spellRange').value = spell.range_area || '';
            document.getElementById('spellComponents').value = spell.components || '';
            document.getElementById('spellDuration').value = spell.duration || '';
            document.getElementById('spellDesc').value = spell.description || '';
        }

        async function addSpell(event) {
            event.preventDefault();
            
            const formData = new FormData();
            formData.append('action', 'add_spell');
            formData.append('character_id', charId);
            formData.append('spell_name', document.getElementById('spellName').value);
            formData.append('spell_level', document.getElementById('spellLevel').value);
            formData.append('school', document.getElementById('spellSchool').value);
            formData.append('casting_time', document.getElementById('spellCastingTime').value);
            formData.append('range_area', document.getElementById('spellRange').value);
            formData.append('components', document.getElementById('spellComponents').value);
            formData.append('duration', document.getElementById('spellDuration').value);
            formData.append('description', document.getElementById('spellDesc').value);
            
            if (await apiCall(formData)) {
                location.reload();
            }
        }

        async function deleteSpell(spellId) {
            if (!confirm('Delete this spell?')) return;
            
            const formData = new FormData();
            formData.append('action', 'delete_spell');
            formData.append('id', spellId);
            
            if (await apiCall(formData)) {
                location.reload();
            }
        }

        // Feats
        function showAddFeatModal() {
            document.getElementById('addFeatModal').classList.remove('hidden');
            document.getElementById('featName').value = '';
            document.getElementById('featDesc').value = '';
        }

        function hideAddFeatModal() {
            document.getElementById('addFeatModal').classList.add('hidden');
        }

        async function addFeat(event) {
            event.preventDefault();
            
            const formData = new FormData();
            formData.append('action', 'add_feat');
            formData.append('character_id', charId);
            formData.append('name', document.getElementById('featName').value);
            formData.append('description', document.getElementById('featDesc').value);
            
            if (await apiCall(formData)) {
                location.reload();
            }
        }

        async function deleteFeat(featId) {
            if (!confirm('Delete this feat?')) return;
            
            const formData = new FormData();
            formData.append('action', 'delete_feat');
            formData.append('id', featId);
            
            if (await apiCall(formData)) {
                location.reload();
            }
        }
        
        // Status Effects
        function showAddStatusModal() {
            document.getElementById('addStatusModal').classList.remove('hidden');
            document.getElementById('statusName').value = '';
            document.getElementById('customStatusName').value = '';
            document.getElementById('statusDesc').value = '';
            document.getElementById('customStatusNameDiv').classList.add('hidden');
        }
        
        function hideAddStatusModal() {
            document.getElementById('addStatusModal').classList.add('hidden');
        }
        
        document.getElementById('statusName').addEventListener('change', function() {
            const customDiv = document.getElementById('customStatusNameDiv');
            const customInput = document.getElementById('customStatusName');
            if (this.value === 'Custom') {
                customDiv.classList.remove('hidden');
                customInput.required = true;
            } else {
                customDiv.classList.add('hidden');
                customInput.required = false;
            }
        });
        
        async function addStatus(event) {
            event.preventDefault();
            
            let statusName = document.getElementById('statusName').value;
            if (statusName === 'Custom') {
                statusName = document.getElementById('customStatusName').value;
            }
            
            const formData = new FormData();
            formData.append('action', 'add_status');
            formData.append('character_id', charId);
            formData.append('status_name', statusName);
            formData.append('description', document.getElementById('statusDesc').value);
            
            if (await apiCall(formData)) {
                hideAddStatusModal();
                loadStatusEffects();
            }
        }
        
        async function loadStatusEffects() {
            try {
                const response = await fetch(`/admin/api.php?action=get_status_effects&character_id=${charId}`);
                const result = await response.json();
                
                const container = document.getElementById('statusEffectsList');
                
                if (result.success && result.statuses.length > 0) {
                    container.innerHTML = result.statuses.map(status => `
                        <div class="flex items-start space-x-4 p-4 bg-gray-800/50 rounded-lg border-l-4 border-yellow-500">
                            <div class="flex-1">
                                <h5 class="text-white font-bold">${escapeHtml(status.status_name)}</h5>
                                ${status.description ? `<p class="text-sm text-gray-400 mt-1">${escapeHtml(status.description)}</p>` : ''}
                            </div>
                            <button onclick="deleteStatus(${status.id})" class="text-red-400 hover:text-red-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    `).join('');
                } else {
                    container.innerHTML = '<p class="text-gray-500 text-center py-8">No active status effects</p>';
                }
            } catch (error) {
                console.error('Error loading status effects:', error);
            }
        }
        
        async function deleteStatus(statusId) {
            if (!confirm('Remove this status effect?')) return;
            
            const formData = new FormData();
            formData.append('action', 'delete_status');
            formData.append('id', statusId);
            
            if (await apiCall(formData)) {
                loadStatusEffects();
            }
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Portrait upload functions
        async function uploadPortrait() {
            const fileInput = document.getElementById('portraitInput');
            const statusEl = document.getElementById('portraitStatus');
            
            if (!fileInput.files || !fileInput.files[0]) {
                statusEl.textContent = 'Please choose an image first';
                statusEl.className = 'text-sm mt-2 text-red-400';
                return;
            }
            
            const file = fileInput.files[0];
            
            // Validate file size (5MB)
            if (file.size > 5 * 1024 * 1024) {
                statusEl.textContent = 'File too large. Maximum size is 5MB';
                statusEl.className = 'text-sm mt-2 text-red-400';
                return;
            }
            
            // Validate file type
            const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!validTypes.includes(file.type)) {
                statusEl.textContent = 'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed';
                statusEl.className = 'text-sm mt-2 text-red-400';
                return;
            }
            
            statusEl.textContent = 'Uploading...';
            statusEl.className = 'text-sm mt-2 text-yellow-400';
            
            const formData = new FormData();
            formData.append('image', file);
            formData.append('entity_type', 'character');
            formData.append('entity_id', <?php echo $char_id; ?>);
            
            try {
                const response = await fetch('/admin/upload_image.php', {
                    method: 'POST',
                    body: formData
                });
                
                // Get response as text first to see what we got
                const text = await response.text();
                console.log('Upload response:', text);
                
                let result;
                try {
                    result = JSON.parse(text);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Response text:', text);
                    statusEl.textContent = 'Server error. Check console for details.';
                    statusEl.className = 'text-sm mt-2 text-red-400';
                    return;
                }
                
                if (result.success) {
                    statusEl.textContent = 'Image uploaded successfully!';
                    statusEl.className = 'text-sm mt-2 text-green-400';
                    
                    // Update the image display
                    const portraitEl = document.getElementById('characterPortrait');
                    if (portraitEl.tagName === 'IMG') {
                        portraitEl.src = result.image_url + '?' + new Date().getTime();
                    } else {
                        // Replace placeholder with actual image
                        portraitEl.outerHTML = `<img id="characterPortrait" src="${result.image_url}" alt="Character Portrait" class="w-32 h-32 object-cover rounded-lg border-2 border-primary">`;
                    }
                    
                    // Reload page after short delay to show remove button
                    setTimeout(() => location.reload(), 1000);
                } else {
                    statusEl.textContent = 'Upload failed: ' + result.message;
                    statusEl.className = 'text-sm mt-2 text-red-400';
                }
            } catch (error) {
                console.error('Upload error:', error);
                statusEl.textContent = 'Upload failed. Please try again.';
                statusEl.className = 'text-sm mt-2 text-red-400';
            }
        }
        
        async function removePortrait() {
            if (!confirm('Remove character portrait?')) return;
            
            const formData = new FormData();
            formData.append('action', 'update_character');
            formData.append('character_id', <?php echo $char_id; ?>);
            formData.append('field', 'image_url');
            formData.append('value', '');
            
            if (await apiCall(formData)) {
                location.reload();
            }
        }
        
        // Load status effects on page load
        loadStatusEffects();
    </script>
</body>
</html>
