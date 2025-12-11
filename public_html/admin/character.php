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
$skills_result = $conn->query("SELECT * FROM character_skills WHERE character_id = $char_id");
$skills = $skills_result ? $skills_result->fetch_assoc() : [];
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
                        'primary': '#f97316',
                        'primary-dark': '#ea580c'
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
            <a href="/admin/index.php?tab=characters" class="text-gray-400 hover:text-white transition flex items-center gap-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                <span>Back</span>
            </a>
            <h1 class="text-2xl font-bold text-primary"><?php echo htmlspecialchars($char['name']); ?></h1>
            <div class="w-16"></div>
        </div>

        <!-- Portrait Section -->
        <div class="bg-gray-900/50 border border-gray-800 rounded-xl p-6 mb-6">
            <div class="flex items-start gap-6">
                <div id="portraitDisplay" class="w-32 h-32 bg-gray-800 rounded-xl border-2 border-gray-700 flex items-center justify-center overflow-hidden flex-shrink-0">
                    <?php if (!empty($char['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($char['image_url']); ?>" alt="Portrait" class="w-full h-full object-cover">
                    <?php else: ?>
                        <svg class="w-16 h-16 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    <?php endif; ?>
                </div>
                <div class="flex-grow">
                    <h3 class="text-lg font-bold text-white mb-2">Character Portrait</h3>
                    <p class="text-gray-400 text-sm mb-4">Upload a portrait image (max 5MB, JPG/PNG/GIF/WebP)</p>
                    <input type="file" id="portraitInput" accept="image/*" class="hidden" onchange="document.getElementById('portraitStatus').textContent = this.files[0]?.name || ''">
                    <div class="flex gap-2 flex-wrap">
                        <button type="button" onclick="document.getElementById('portraitInput').click()" 
                            class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition text-sm">
                            Choose Image
                        </button>
                        <button type="button" onclick="uploadPortrait()" 
                            class="px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg transition text-sm">
                            Upload
                        </button>
                        <?php if (!empty($char['image_url'])): ?>
                        <button type="button" onclick="removePortrait()" 
                            class="px-4 py-2 bg-red-900/30 hover:bg-red-900/50 text-red-400 rounded-lg transition text-sm">
                            Remove
                        </button>
                        <?php endif; ?>
                    </div>
                    <p id="portraitStatus" class="text-sm mt-2 text-gray-400"></p>
                </div>
            </div>
        </div>

        <!-- Basic Info -->
        <div class="bg-gray-900/50 border border-gray-800 rounded-xl p-6 mb-6">
            <h3 class="text-xl font-bold text-white mb-4">Basic Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-400 mb-2 text-sm">Name</label>
                    <input type="text" id="charName" value="<?php echo htmlspecialchars($char['name']); ?>" 
                        onchange="updateField('name', this.value)"
                        class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-gray-400 mb-2 text-sm">Race</label>
                    <input type="text" id="charRace" value="<?php echo htmlspecialchars($char['race'] ?? ''); ?>" 
                        onchange="updateField('race', this.value)"
                        class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-gray-400 mb-2 text-sm">Class</label>
                    <input type="text" id="charClass" value="<?php echo htmlspecialchars($char['class'] ?? ''); ?>" 
                        onchange="updateField('class', this.value)"
                        class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-gray-400 mb-2 text-sm">Level</label>
                    <input type="number" id="charLevel" value="<?php echo $char['level'] ?? 1; ?>" min="1" max="20"
                        onchange="updateField('level', this.value)"
                        class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-gray-400 mb-2 text-sm">Background</label>
                    <input type="text" id="charBackground" value="<?php echo htmlspecialchars($char['background'] ?? ''); ?>" 
                        onchange="updateField('background', this.value)"
                        class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-gray-400 mb-2 text-sm">Alignment</label>
                    <input type="text" id="charAlignment" value="<?php echo htmlspecialchars($char['alignment'] ?? ''); ?>" 
                        onchange="updateField('alignment', this.value)"
                        class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                </div>
            </div>
        </div>

        <!-- Combat Stats -->
        <div class="bg-gray-900/50 border border-gray-800 rounded-xl p-6 mb-6">
            <h3 class="text-xl font-bold text-white mb-4">Combat Stats</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center">
                    <label class="block text-gray-400 mb-2 text-sm">Current HP</label>
                    <input type="number" id="currentHP" value="<?php echo $char['current_hp'] ?? 0; ?>" 
                        onchange="updateStat('current_hp', this.value)"
                        class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-center text-xl font-bold focus:outline-none focus:border-primary">
                </div>
                <div class="text-center">
                    <label class="block text-gray-400 mb-2 text-sm">Max HP</label>
                    <input type="number" id="maxHP" value="<?php echo $char['max_hp'] ?? 0; ?>" 
                        onchange="updateStat('max_hp', this.value)"
                        class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-center text-xl font-bold focus:outline-none focus:border-primary">
                </div>
                <div class="text-center">
                    <label class="block text-gray-400 mb-2 text-sm">Armor Class</label>
                    <input type="number" id="armorClass" value="<?php echo $char['armor_class'] ?? 10; ?>" 
                        onchange="updateStat('armor_class', this.value)"
                        class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-center text-xl font-bold focus:outline-none focus:border-primary">
                </div>
                <div class="text-center">
                    <label class="block text-gray-400 mb-2 text-sm">Speed</label>
                    <input type="number" id="speed" value="<?php echo $char['speed'] ?? 30; ?>" 
                        onchange="updateStat('speed', this.value)"
                        class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-center text-xl font-bold focus:outline-none focus:border-primary">
                </div>
                <div class="text-center">
                    <label class="block text-gray-400 mb-2 text-sm">Initiative</label>
                    <input type="number" id="initiative" value="<?php echo $char['initiative'] ?? 0; ?>" 
                        onchange="updateStat('initiative', this.value)"
                        class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-center text-xl font-bold focus:outline-none focus:border-primary">
                </div>
                <div class="text-center">
                    <label class="block text-gray-400 mb-2 text-sm">Proficiency</label>
                    <input type="number" id="profBonus" value="<?php echo $char['proficiency_bonus'] ?? 2; ?>" min="2" max="6"
                        onchange="updateStat('proficiency_bonus', this.value)"
                        class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-center text-xl font-bold focus:outline-none focus:border-primary">
                </div>
                <div class="text-center col-span-2">
                    <label class="block text-gray-400 mb-2 text-sm">Hit Dice</label>
                    <input type="text" id="hitDice" value="<?php echo htmlspecialchars($char['hit_dice'] ?? '1d8'); ?>" 
                        onchange="updateStat('hit_dice', this.value)"
                        class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-center focus:outline-none focus:border-primary">
                </div>
            </div>
        </div>

        <!-- Spell Slots -->
        <div class="bg-gray-900/50 border border-gray-800 rounded-xl p-6 mb-6">
            <h3 class="text-xl font-bold text-white mb-4">Spell Slots</h3>
            <div class="grid grid-cols-3 md:grid-cols-5 lg:grid-cols-9 gap-3">
                <?php for ($i = 1; $i <= 9; $i++): 
                    $max_col = "spell_slots_$i";
                    $current_col = "current_spell_slots_$i";
                    $max_slots = $char[$max_col] ?? 0;
                    $current_slots = $char[$current_col] ?? 0;
                ?>
                <div class="bg-gray-800/50 rounded-lg p-3">
                    <p class="text-gray-400 text-xs mb-2 text-center font-semibold">Level <?php echo $i; ?></p>
                    <div class="mb-2">
                        <label class="text-xs text-gray-500 block mb-1">Max</label>
                        <input type="number" min="0" max="9" value="<?php echo $max_slots; ?>" 
                            onchange="updateMaxSpellSlots(<?php echo $i; ?>, this.value)"
                            class="w-full px-2 py-1 bg-gray-900 border border-gray-700 rounded text-white text-center text-sm focus:outline-none focus:border-primary">
                    </div>
                    <div class="mb-2">
                        <label class="text-xs text-gray-500 block mb-1">Current</label>
                        <div class="flex items-center justify-center gap-1">
                            <button onclick="updateCurrentSpellSlots(<?php echo $i; ?>, -1)" 
                                class="bg-red-500/20 hover:bg-red-500/30 text-red-400 w-6 h-6 rounded text-xs font-bold transition">-</button>
                            <span id="currentSlots<?php echo $i; ?>" class="text-white font-bold w-6 text-center"><?php echo $current_slots; ?></span>
                            <button onclick="updateCurrentSpellSlots(<?php echo $i; ?>, 1)" 
                                class="bg-green-500/20 hover:bg-green-500/30 text-green-400 w-6 h-6 rounded text-xs font-bold transition">+</button>
                        </div>
                    </div>
                    <div id="spellSlots<?php echo $i; ?>" class="flex flex-wrap gap-1 justify-center min-h-[16px]">
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
            <h3 class="text-xl font-bold text-white mb-4">Ability Scores</h3>
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
                        onchange="updateAbility('<?php echo $data[0]; ?>', this.value)"
                        class="w-full px-2 py-1 bg-gray-700 border border-gray-600 rounded text-white text-center text-2xl font-bold focus:outline-none focus:border-primary mb-2">
                    <p id="<?php echo $data[0]; ?>_mod" class="text-lg <?php echo $mod_color; ?> font-bold">
                        <?php echo $modifier >= 0 ? '+' : ''; ?><?php echo $modifier; ?>
                    </p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Skills -->
        <div class="bg-gray-900/50 border border-gray-800 rounded-xl p-6 mb-6">
            <h3 class="text-xl font-bold text-white mb-4 flex items-center">
                <svg class="w-5 h-5 text-primary mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                </svg>
                Skills
            </h3>
            
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
            
            $ability_scores = [
                'STR' => $char['strength'] ?? 10,
                'DEX' => $char['dexterity'] ?? 10,
                'CON' => $char['constitution'] ?? 10,
                'INT' => $char['intelligence'] ?? 10,
                'WIS' => $char['wisdom'] ?? 10,
                'CHA' => $char['charisma'] ?? 10
            ];
            
            $proficiency_bonus = $char['proficiency_bonus'] ?? 2;
            
            $proficient_skills = [];
            $other_skills = [];
            
            foreach ($skill_list as $skill_key => $skill_info) {
                $is_proficient = !empty($skills[$skill_key]);
                $ability = $skill_info[1];
                $ability_mod = floor(($ability_scores[$ability] - 10) / 2);
                $total_mod = $is_proficient ? $ability_mod + $proficiency_bonus : $ability_mod;
                
                $skill_data = [
                    'key' => $skill_key,
                    'name' => $skill_info[0],
                    'ability' => $ability,
                    'modifier' => $total_mod,
                    'is_proficient' => $is_proficient
                ];
                
                if ($is_proficient) {
                    $proficient_skills[] = $skill_data;
                } else {
                    $other_skills[] = $skill_data;
                }
            }
            ?>
            
            <?php if (!empty($proficient_skills)): ?>
            <div class="mb-6">
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-2 h-2 bg-primary rounded-full"></div>
                    <h4 class="text-primary font-semibold text-sm uppercase tracking-wide">Proficient</h4>
                </div>
                <div class="bg-primary/10 border border-primary/30 rounded-lg p-4">
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        <?php foreach ($proficient_skills as $skill): ?>
                        <div class="flex items-center justify-between bg-gray-900/50 rounded-lg px-3 py-2">
                            <label class="flex items-center gap-3 cursor-pointer flex-grow">
                                <input type="checkbox" checked
                                       onchange="toggleSkill('<?php echo $skill['key']; ?>', this.checked)"
                                       class="w-5 h-5 rounded border-2 border-primary bg-primary text-white focus:ring-primary cursor-pointer">
                                <div>
                                    <p class="text-white font-medium text-sm"><?php echo $skill['name']; ?></p>
                                    <p class="text-gray-500 text-xs"><?php echo $skill['ability']; ?></p>
                                </div>
                            </label>
                            <span class="text-lg font-bold <?php echo $skill['modifier'] >= 0 ? 'text-green-400' : 'text-red-400'; ?>">
                                <?php echo ($skill['modifier'] >= 0 ? '+' : '') . $skill['modifier']; ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-2 h-2 bg-gray-500 rounded-full"></div>
                    <h4 class="text-gray-400 font-semibold text-sm uppercase tracking-wide">Other Skills</h4>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                    <?php foreach ($other_skills as $skill): ?>
                    <div class="flex items-center justify-between bg-gray-800/50 rounded-lg px-3 py-2 hover:bg-gray-800 transition">
                        <label class="flex items-center gap-3 cursor-pointer flex-grow">
                            <input type="checkbox"
                                   onchange="toggleSkill('<?php echo $skill['key']; ?>', this.checked)"
                                   class="w-5 h-5 rounded border-2 border-gray-600 bg-gray-700 text-primary focus:ring-primary cursor-pointer">
                            <div>
                                <p class="text-gray-300 text-sm"><?php echo $skill['name']; ?></p>
                                <p class="text-gray-600 text-xs"><?php echo $skill['ability']; ?></p>
                            </div>
                        </label>
                        <span class="text-sm font-medium <?php echo $skill['modifier'] >= 0 ? 'text-gray-400' : 'text-red-400'; ?>">
                            <?php echo ($skill['modifier'] >= 0 ? '+' : '') . $skill['modifier']; ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Equipment -->
        <div class="bg-gray-900/50 border border-gray-800 rounded-xl p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-white">Equipment & Inventory</h3>
                <button onclick="showAddEquipmentModal()" class="bg-primary hover:bg-primary-dark text-white font-bold py-2 px-4 rounded-lg transition text-sm">
                    Add Item
                </button>
            </div>
            
            <?php
            $equipped_items = [];
            $unequipped_items = [];
            if ($equipment) {
                $equipment->data_seek(0);
                while ($item = $equipment->fetch_assoc()) {
                    if ($item['is_equipped']) {
                        $equipped_items[] = $item;
                    } else {
                        $unequipped_items[] = $item;
                    }
                }
            }
            ?>
            
            <?php if (count($equipped_items) > 0): ?>
            <div class="mb-4">
                <h4 class="text-sm font-bold text-primary mb-2">Equipped</h4>
                <div class="space-y-2">
                    <?php foreach ($equipped_items as $item): ?>
                    <div class="flex items-center justify-between p-3 bg-primary/10 border border-primary/30 rounded-lg">
                        <div class="flex items-center gap-3">
                            <input type="checkbox" checked onchange="toggleEquipped(<?php echo $item['id']; ?>, false)"
                                class="w-5 h-5 rounded border-primary bg-primary text-white cursor-pointer">
                            <div>
                                <p class="text-white font-medium"><?php echo htmlspecialchars($item['item_name']); ?></p>
                                <p class="text-gray-400 text-xs"><?php echo htmlspecialchars($item['type'] ?? ''); ?> <?php echo $item['rarity'] ? '• ' . htmlspecialchars($item['rarity']) : ''; ?></p>
                            </div>
                        </div>
                        <button onclick="deleteEquipment(<?php echo $item['id']; ?>)" class="text-red-400 hover:text-red-300 p-1">
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
                <h4 class="text-sm font-bold text-gray-400 mb-2">Inventory</h4>
                <div class="space-y-2">
                    <?php foreach ($unequipped_items as $item): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-800/50 rounded-lg">
                        <div class="flex items-center gap-3">
                            <input type="checkbox" onchange="toggleEquipped(<?php echo $item['id']; ?>, true)"
                                class="w-5 h-5 rounded border-gray-600 bg-gray-700 text-primary cursor-pointer">
                            <div>
                                <p class="text-white font-medium"><?php echo htmlspecialchars($item['item_name']); ?></p>
                                <p class="text-gray-400 text-xs"><?php echo htmlspecialchars($item['type'] ?? ''); ?> <?php echo $item['rarity'] ? '• ' . htmlspecialchars($item['rarity']) : ''; ?></p>
                            </div>
                        </div>
                        <button onclick="deleteEquipment(<?php echo $item['id']; ?>)" class="text-red-400 hover:text-red-300 p-1">
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
                <button onclick="showAddSpellModal()" class="bg-primary hover:bg-primary-dark text-white font-bold py-2 px-4 rounded-lg transition text-sm">
                    Add Spell
                </button>
            </div>
            
            <?php
            $spells_by_level = [];
            if ($spells) {
                $spells->data_seek(0);
                while ($spell = $spells->fetch_assoc()) {
                    $level = $spell['level'];
                    if (!isset($spells_by_level[$level])) {
                        $spells_by_level[$level] = [];
                    }
                    $spells_by_level[$level][] = $spell;
                }
            }
            ksort($spells_by_level);
            ?>
            
            <?php if (count($spells_by_level) > 0): ?>
                <?php foreach ($spells_by_level as $level => $level_spells): ?>
                <div class="mb-4">
                    <h4 class="text-sm font-bold text-gray-400 mb-2">
                        <?php echo $level == 0 ? 'Cantrips' : 'Level ' . $level; ?>
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <?php foreach ($level_spells as $spell): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-800/50 rounded-lg">
                            <div>
                                <p class="text-white font-medium"><?php echo htmlspecialchars($spell['name']); ?></p>
                                <p class="text-gray-400 text-xs"><?php echo htmlspecialchars($spell['school'] ?? ''); ?> • <?php echo htmlspecialchars($spell['casting_time'] ?? ''); ?></p>
                            </div>
                            <button onclick="deleteSpell(<?php echo $spell['id']; ?>)" class="text-red-400 hover:text-red-300 p-1">
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
                <h3 class="text-xl font-bold text-white">Feats & Features</h3>
                <button onclick="showAddFeatModal()" class="bg-primary hover:bg-primary-dark text-white font-bold py-2 px-4 rounded-lg transition text-sm">
                    Add Feat
                </button>
            </div>
            
            <?php if ($feats && $feats->num_rows > 0): ?>
            <div class="space-y-3">
                <?php $feats->data_seek(0); while ($feat = $feats->fetch_assoc()): ?>
                <div class="flex items-start justify-between p-4 bg-gray-800/50 rounded-lg">
                    <div>
                        <p class="text-white font-bold"><?php echo htmlspecialchars($feat['name']); ?></p>
                        <?php if (!empty($feat['description'])): ?>
                        <p class="text-gray-400 text-sm mt-1"><?php echo nl2br(htmlspecialchars($feat['description'])); ?></p>
                        <?php endif; ?>
                    </div>
                    <button onclick="deleteFeat(<?php echo $feat['id']; ?>)" class="text-red-400 hover:text-red-300 p-1 flex-shrink-0">
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
                <h3 class="text-xl font-bold text-white">Status Effects</h3>
                <button onclick="showAddStatusModal()" class="bg-primary hover:bg-primary-dark text-white font-bold py-2 px-4 rounded-lg transition text-sm">
                    Add Status
                </button>
            </div>
            <div id="statusEffectsContainer">
                <p class="text-gray-500 text-center py-8">Loading...</p>
            </div>
        </div>

    </div>

    <!-- Add Equipment Modal -->
    <?php
    // Fetch items from library for dropdown
    $items_library = $conn->query("SELECT * FROM items ORDER BY name");
    ?>
    <div id="addEquipmentModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
        <div class="bg-gray-900 rounded-xl border border-gray-700 max-w-md w-full p-6 max-h-[90vh] overflow-y-auto">
            <h3 class="text-xl font-bold text-white mb-4">Add Equipment</h3>
            <form onsubmit="addEquipment(event)">
                <div class="space-y-4">
                    <!-- Library Selection -->
                    <div>
                        <label class="block text-gray-400 mb-1 text-sm">Select from Library</label>
                        <select id="itemLibrarySelect" onchange="selectItemFromLibrary(this.value)" class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                            <option value="">-- Choose an item or enter manually --</option>
                            <?php if ($items_library): while ($item = $items_library->fetch_assoc()): ?>
                            <option value='<?php echo htmlspecialchars(json_encode($item), ENT_QUOTES); ?>'>
                                <?php echo htmlspecialchars($item['name']); ?>
                                <?php if ($item['type']): ?> (<?php echo htmlspecialchars($item['type']); ?>)<?php endif; ?>
                                <?php if ($item['rarity']): ?> - <?php echo htmlspecialchars($item['rarity']); ?><?php endif; ?>
                            </option>
                            <?php endwhile; endif; ?>
                        </select>
                    </div>
                    
                    <div class="border-t border-gray-700 pt-4">
                        <p class="text-gray-500 text-xs mb-3">Or enter details manually:</p>
                    </div>
                    
                    <div>
                        <label class="block text-gray-400 mb-1 text-sm">Item Name *</label>
                        <input type="text" id="equipName" required class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-400 mb-1 text-sm">Type</label>
                            <input type="text" id="equipType" class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-gray-400 mb-1 text-sm">Rarity</label>
                            <select id="equipRarity" class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                                <option value="">None</option>
                                <option value="Common">Common</option>
                                <option value="Uncommon">Uncommon</option>
                                <option value="Rare">Rare</option>
                                <option value="Very Rare">Very Rare</option>
                                <option value="Legendary">Legendary</option>
                                <option value="Artifact">Artifact</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-gray-400 mb-1 text-sm">Properties</label>
                        <input type="text" id="equipProperties" class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-gray-400 mb-1 text-sm">Description</label>
                        <textarea id="equipDescription" rows="3" class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary"></textarea>
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="equipEquipped" class="w-4 h-4 rounded border-gray-600 bg-gray-700 text-primary">
                        <span class="text-gray-300">Equipped</span>
                    </label>
                </div>
                <div class="flex gap-3 mt-6">
                    <button type="submit" class="flex-1 bg-primary hover:bg-primary-dark text-white font-bold py-2 px-4 rounded-lg transition">Add Item</button>
                    <button type="button" onclick="hideAddEquipmentModal()" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Spell Modal -->
    <?php
    // Fetch spells from library for dropdown
    $spells_library = $conn->query("SELECT * FROM spells ORDER BY level, name");
    ?>
    <div id="addSpellModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
        <div class="bg-gray-900 rounded-xl border border-gray-700 max-w-md w-full p-6 max-h-[90vh] overflow-y-auto">
            <h3 class="text-xl font-bold text-white mb-4">Add Spell</h3>
            <form onsubmit="addSpell(event)">
                <div class="space-y-4">
                    <!-- Library Selection -->
                    <div>
                        <label class="block text-gray-400 mb-1 text-sm">Select from Library</label>
                        <select id="spellLibrarySelect" onchange="selectSpellFromLibrary(this.value)" class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                            <option value="">-- Choose a spell or enter manually --</option>
                            <?php if ($spells_library): while ($spell = $spells_library->fetch_assoc()): ?>
                            <option value='<?php echo htmlspecialchars(json_encode($spell), ENT_QUOTES); ?>'>
                                <?php echo $spell['level'] == 0 ? '(Cantrip)' : '(Lvl ' . $spell['level'] . ')'; ?>
                                <?php echo htmlspecialchars($spell['name']); ?>
                                <?php if ($spell['school']): ?> - <?php echo htmlspecialchars($spell['school']); ?><?php endif; ?>
                            </option>
                            <?php endwhile; endif; ?>
                        </select>
                    </div>
                    
                    <div class="border-t border-gray-700 pt-4">
                        <p class="text-gray-500 text-xs mb-3">Or enter details manually:</p>
                    </div>
                    
                    <div>
                        <label class="block text-gray-400 mb-1 text-sm">Spell Name *</label>
                        <input type="text" id="spellName" required class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-400 mb-1 text-sm">Level *</label>
                            <select id="spellLevel" required class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                                <option value="0">Cantrip</option>
                                <?php for ($i = 1; $i <= 9; $i++): ?>
                                <option value="<?php echo $i; ?>">Level <?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-400 mb-1 text-sm">School</label>
                            <input type="text" id="spellSchool" class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-400 mb-1 text-sm">Casting Time</label>
                            <input type="text" id="spellCastingTime" class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-gray-400 mb-1 text-sm">Range</label>
                            <input type="text" id="spellRange" class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-400 mb-1 text-sm">Components</label>
                            <input type="text" id="spellComponents" class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-gray-400 mb-1 text-sm">Duration</label>
                            <input type="text" id="spellDuration" class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                        </div>
                    </div>
                    <div>
                        <label class="block text-gray-400 mb-1 text-sm">Description</label>
                        <textarea id="spellDescription" rows="3" class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary"></textarea>
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button type="submit" class="flex-1 bg-primary hover:bg-primary-dark text-white font-bold py-2 px-4 rounded-lg transition">Add Spell</button>
                    <button type="button" onclick="hideAddSpellModal()" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Feat Modal -->
    <div id="addFeatModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
        <div class="bg-gray-900 rounded-xl border border-gray-700 max-w-md w-full p-6">
            <h3 class="text-xl font-bold text-white mb-4">Add Feat</h3>
            <form onsubmit="addFeat(event)">
                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-400 mb-1 text-sm">Feat Name *</label>
                        <input type="text" id="featName" required class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-gray-400 mb-1 text-sm">Description</label>
                        <textarea id="featDesc" rows="4" class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary"></textarea>
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button type="submit" class="flex-1 bg-primary hover:bg-primary-dark text-white font-bold py-2 px-4 rounded-lg transition">Add Feat</button>
                    <button type="button" onclick="hideAddFeatModal()" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Status Modal -->
    <div id="addStatusModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
        <div class="bg-gray-900 rounded-xl border border-gray-700 max-w-md w-full p-6">
            <h3 class="text-xl font-bold text-white mb-4">Add Status Effect</h3>
            <form onsubmit="addStatus(event)">
                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-400 mb-1 text-sm">Status *</label>
                        <select id="statusName" class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
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
                            <option value="custom">Custom...</option>
                        </select>
                    </div>
                    <div id="customStatusDiv" class="hidden">
                        <label class="block text-gray-400 mb-1 text-sm">Custom Status Name</label>
                        <input type="text" id="customStatusName" class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-gray-400 mb-1 text-sm">Description (optional)</label>
                        <textarea id="statusDesc" rows="2" class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary"></textarea>
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button type="submit" class="flex-1 bg-primary hover:bg-primary-dark text-white font-bold py-2 px-4 rounded-lg transition">Add Status</button>
                    <button type="button" onclick="hideAddStatusModal()" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const charId = <?php echo $char_id; ?>;
        
        // Load status effects on page load
        document.addEventListener('DOMContentLoaded', loadStatusEffects);
        
        // Status name change handler
        document.getElementById('statusName').addEventListener('change', function() {
            document.getElementById('customStatusDiv').classList.toggle('hidden', this.value !== 'custom');
        });

        // API helper
        async function apiCall(formData) {
            try {
                const response = await fetch('/admin/api.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (!result.success) {
                    console.error('API Error:', result);
                }
                return result.success;
            } catch (error) {
                console.error('Error:', error);
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

        async function updateStat(stat, value) {
            const formData = new FormData();
            formData.append('action', 'update_character_stat');
            formData.append('character_id', charId);
            formData.append('field', stat);
            formData.append('value', value);
            await apiCall(formData);
        }

        // Ability scores
        async function updateAbility(ability, value) {
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

        // Skills
        async function toggleSkill(skill, proficient) {
            const formData = new FormData();
            formData.append('action', 'toggle_skill');
            formData.append('character_id', charId);
            formData.append('skill', skill);
            formData.append('value', proficient ? 1 : 0);
            
            if (await apiCall(formData)) {
                location.reload();
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
                const maxInput = document.querySelector(`input[onchange*="updateMaxSpellSlots(${level}"]`);
                const maxSlots = maxInput ? parseInt(maxInput.value) : 0;
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

        // Equipment
        function showAddEquipmentModal() {
            document.getElementById('addEquipmentModal').classList.remove('hidden');
            document.getElementById('addEquipmentModal').classList.add('flex');
            // Reset form
            document.getElementById('itemLibrarySelect').value = '';
            document.getElementById('equipName').value = '';
            document.getElementById('equipType').value = '';
            document.getElementById('equipRarity').value = '';
            document.getElementById('equipProperties').value = '';
            document.getElementById('equipDescription').value = '';
            document.getElementById('equipEquipped').checked = false;
        }
        function hideAddEquipmentModal() {
            document.getElementById('addEquipmentModal').classList.add('hidden');
            document.getElementById('addEquipmentModal').classList.remove('flex');
        }
        
        function selectItemFromLibrary(jsonData) {
            if (!jsonData) return;
            
            try {
                const item = JSON.parse(jsonData);
                document.getElementById('equipName').value = item.name || '';
                document.getElementById('equipType').value = item.type || '';
                document.getElementById('equipRarity').value = item.rarity || '';
                document.getElementById('equipProperties').value = item.properties || '';
                document.getElementById('equipDescription').value = item.description || '';
            } catch (e) {
                console.error('Error parsing item data:', e);
            }
        }
        
        async function addEquipment(event) {
            event.preventDefault();
            const formData = new FormData();
            formData.append('action', 'add_equipment');
            formData.append('character_id', charId);
            formData.append('item_name', document.getElementById('equipName').value);
            formData.append('type', document.getElementById('equipType').value);
            formData.append('rarity', document.getElementById('equipRarity').value);
            formData.append('properties', document.getElementById('equipProperties').value);
            formData.append('description', document.getElementById('equipDescription').value);
            formData.append('is_equipped', document.getElementById('equipEquipped').checked ? 1 : 0);
            
            if (await apiCall(formData)) {
                location.reload();
            }
        }

        async function toggleEquipped(equipId, equipped) {
            const formData = new FormData();
            formData.append('action', 'toggle_equipment');
            formData.append('id', equipId);
            formData.append('is_equipped', equipped ? 1 : 0);
            
            if (await apiCall(formData)) {
                location.reload();
            }
        }

        async function deleteEquipment(equipId) {
            if (!confirm('Delete this item?')) return;
            const formData = new FormData();
            formData.append('action', 'delete_equipment');
            formData.append('id', equipId);
            
            if (await apiCall(formData)) {
                location.reload();
            }
        }

        // Spells
        function showAddSpellModal() {
            document.getElementById('addSpellModal').classList.remove('hidden');
            document.getElementById('addSpellModal').classList.add('flex');
            // Reset form
            document.getElementById('spellLibrarySelect').value = '';
            document.getElementById('spellName').value = '';
            document.getElementById('spellLevel').value = '0';
            document.getElementById('spellSchool').value = '';
            document.getElementById('spellCastingTime').value = '';
            document.getElementById('spellRange').value = '';
            document.getElementById('spellComponents').value = '';
            document.getElementById('spellDuration').value = '';
            document.getElementById('spellDescription').value = '';
        }
        function hideAddSpellModal() {
            document.getElementById('addSpellModal').classList.add('hidden');
            document.getElementById('addSpellModal').classList.remove('flex');
        }

        function selectSpellFromLibrary(jsonData) {
            if (!jsonData) return;
            
            try {
                const spell = JSON.parse(jsonData);
                document.getElementById('spellName').value = spell.name || '';
                document.getElementById('spellLevel').value = spell.level || '0';
                document.getElementById('spellSchool').value = spell.school || '';
                document.getElementById('spellCastingTime').value = spell.casting_time || '';
                document.getElementById('spellRange').value = spell.range_area || '';
                document.getElementById('spellComponents').value = spell.components || '';
                document.getElementById('spellDuration').value = spell.duration || '';
                document.getElementById('spellDescription').value = spell.description || '';
            } catch (e) {
                console.error('Error parsing spell data:', e);
            }
        }

        async function addSpell(event) {
            event.preventDefault();
            const formData = new FormData();
            formData.append('action', 'add_spell');
            formData.append('character_id', charId);
            formData.append('name', document.getElementById('spellName').value);
            formData.append('level', document.getElementById('spellLevel').value);
            formData.append('school', document.getElementById('spellSchool').value);
            formData.append('casting_time', document.getElementById('spellCastingTime').value);
            formData.append('range_area', document.getElementById('spellRange').value);
            formData.append('components', document.getElementById('spellComponents').value);
            formData.append('duration', document.getElementById('spellDuration').value);
            formData.append('description', document.getElementById('spellDescription').value);
            
            if (await apiCall(formData)) {
                location.reload();
            }
        }

        async function deleteSpell(spellId) {
            if (!confirm('Delete this spell?')) return;
            const formData = new FormData();
            formData.append('action', 'delete_character_spell');
            formData.append('id', spellId);
            
            if (await apiCall(formData)) {
                location.reload();
            }
        }

        // Feats
        function showAddFeatModal() {
            document.getElementById('addFeatModal').classList.remove('hidden');
            document.getElementById('addFeatModal').classList.add('flex');
        }
        function hideAddFeatModal() {
            document.getElementById('addFeatModal').classList.add('hidden');
            document.getElementById('addFeatModal').classList.remove('flex');
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
            document.getElementById('addStatusModal').classList.add('flex');
            document.getElementById('statusName').value = '';
            document.getElementById('customStatusName').value = '';
            document.getElementById('statusDesc').value = '';
            document.getElementById('customStatusDiv').classList.add('hidden');
        }
        function hideAddStatusModal() {
            document.getElementById('addStatusModal').classList.add('hidden');
            document.getElementById('addStatusModal').classList.remove('flex');
        }

        async function addStatus(event) {
            event.preventDefault();
            let statusName = document.getElementById('statusName').value;
            if (statusName === 'custom') {
                statusName = document.getElementById('customStatusName').value;
            }
            if (!statusName) {
                alert('Please select or enter a status name');
                return;
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
                const container = document.getElementById('statusEffectsContainer');
                
                if (result.success && result.statuses && result.statuses.length > 0) {
                    container.innerHTML = result.statuses.map(status => `
                        <div class="flex items-center justify-between p-3 bg-red-900/20 border border-red-900/30 rounded-lg mb-2">
                            <div>
                                <p class="text-red-400 font-bold">${escapeHtml(status.status_name)}</p>
                                ${status.description ? `<p class="text-gray-400 text-sm">${escapeHtml(status.description)}</p>` : ''}
                            </div>
                            <button onclick="deleteStatus(${status.id})" class="text-red-400 hover:text-red-300 p-1">
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
                console.error('Error:', error);
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

        // Portrait
        async function uploadPortrait() {
            const fileInput = document.getElementById('portraitInput');
            const statusEl = document.getElementById('portraitStatus');
            
            if (!fileInput.files || !fileInput.files[0]) {
                statusEl.textContent = 'Please choose an image first';
                statusEl.className = 'text-sm mt-2 text-red-400';
                return;
            }
            
            statusEl.textContent = 'Uploading...';
            statusEl.className = 'text-sm mt-2 text-yellow-400';
            
            const formData = new FormData();
            formData.append('image', fileInput.files[0]);
            formData.append('entity_type', 'character');
            formData.append('entity_id', charId);
            
            try {
                const response = await fetch('/admin/upload_image.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    statusEl.textContent = 'Uploaded!';
                    statusEl.className = 'text-sm mt-2 text-green-400';
                    setTimeout(() => location.reload(), 500);
                } else {
                    statusEl.textContent = result.message || 'Upload failed';
                    statusEl.className = 'text-sm mt-2 text-red-400';
                }
            } catch (error) {
                statusEl.textContent = 'Upload failed';
                statusEl.className = 'text-sm mt-2 text-red-400';
            }
        }

        async function removePortrait() {
            if (!confirm('Remove character portrait?')) return;
            
            const formData = new FormData();
            formData.append('action', 'remove_image');
            formData.append('entity_type', 'character');
            formData.append('entity_id', charId);
            
            if (await apiCall(formData)) {
                location.reload();
            }
        }
    </script>
</body>
</html>