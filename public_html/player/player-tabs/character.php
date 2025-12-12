<?php
// Player Character Tab - Interactive Version
// Players can: use/recover spell slots, adjust HP, toggle equipment

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
if (isset($selected_char_id) && $selected_char_id) {
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
    if ($chars->num_rows > 0) {
        $chars->data_seek(0);
        $char = $chars->fetch_assoc();
    } else {
        $char = null;
    }
}

if (!$char) {
    echo '<div class="text-center py-20">
            <svg class="w-16 h-16 text-gray-700 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <h3 class="text-xl font-bold text-white mb-2">No Character Assigned</h3>
            <p class="text-gray-400">Ask your DM to assign a character to your account.</p>
          </div>';
    return;
}

$characterId = $char['id'];

// Fetch feats for this character
$feats = $conn->query("SELECT * FROM character_feats WHERE character_id = {$char['id']} ORDER BY name");
?>

<div class="max-w-5xl mx-auto">

<!-- Character Header -->
<div class="bg-gradient-to-r from-gray-900 to-gray-800 rounded-xl p-6 mb-6 border border-gray-700">
    <div class="flex flex-col md:flex-row items-start md:items-center gap-4">
        <!-- Portrait -->
        <div class="w-24 h-24 bg-gray-800 rounded-xl border-2 border-primary flex items-center justify-center overflow-hidden flex-shrink-0">
            <?php if (!empty($char['image_url'])): ?>
                <img src="<?php echo htmlspecialchars($char['image_url']); ?>" alt="<?php echo htmlspecialchars($char['name']); ?>" class="w-full h-full object-cover">
            <?php else: ?>
                <svg class="w-12 h-12 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            <?php endif; ?>
        </div>
        
        <!-- Basic Info -->
        <div class="flex-grow">
            <h2 class="text-2xl font-bold text-white"><?php echo htmlspecialchars($char['name']); ?></h2>
            <p class="text-primary text-lg">
                Level <?php echo $char['level']; ?> 
                <?php echo htmlspecialchars($char['race']); ?> 
                <?php echo htmlspecialchars($char['class']); ?>
            </p>
            <?php if (!empty($char['background'])): ?>
                <p class="text-gray-400"><?php echo htmlspecialchars($char['background']); ?></p>
            <?php endif; ?>
            
            <!-- Feats as clickable buttons -->
            <?php if ($feats && $feats->num_rows > 0): ?>
            <div class="flex flex-wrap gap-2 mt-3">
                <?php while ($feat = $feats->fetch_assoc()): ?>
                <button onclick="showFeatModal(<?php echo htmlspecialchars(json_encode($feat), ENT_QUOTES, 'UTF-8'); ?>)" 
                        class="inline-flex items-center px-3 py-1 bg-primary/20 hover:bg-primary/30 border border-primary/50 rounded-lg text-primary text-sm font-medium transition">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                    </svg>
                    <?php echo htmlspecialchars($feat['name']); ?>
                </button>
                <?php endwhile; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Quick Stats -->
        <div class="flex gap-4">
            <div class="text-center">
                <p class="text-2xl font-bold text-white"><?php echo $char['armor_class'] ?? 10; ?></p>
                <p class="text-xs text-gray-400">AC</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold text-white"><?php echo $char['speed'] ?? 30; ?></p>
                <p class="text-xs text-gray-400">Speed</p>
            </div>
        </div>
    </div>
</div>

<!-- HP Section - Interactive -->
<div class="bg-gray-900/50 border border-gray-800 rounded-xl p-6 mb-6">
    <h3 class="text-lg font-bold text-white mb-4 flex items-center">
        <svg class="w-5 h-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
        </svg>
        Hit Points
    </h3>
    
    <div class="flex items-center justify-center gap-4 mb-4">
        <!-- Quick damage buttons -->
        <div class="flex gap-1">
            <button onclick="adjustHP(-5)" class="bg-red-500/20 hover:bg-red-500/30 text-red-400 px-3 py-2 rounded-lg text-sm font-bold transition">-5</button>
            <button onclick="adjustHP(-1)" class="bg-red-500/20 hover:bg-red-500/30 text-red-400 px-3 py-2 rounded-lg text-sm font-bold transition">-1</button>
        </div>
        
        <!-- HP Display -->
        <div class="text-center">
            <div class="flex items-baseline justify-center gap-1">
                <span id="currentHP" class="text-4xl font-bold text-white"><?php echo $char['current_hp'] ?? 0; ?></span>
                <span class="text-gray-400 text-xl">/</span>
                <span id="maxHP" class="text-xl text-gray-400"><?php echo $char['max_hp'] ?? 0; ?></span>
            </div>
            <?php if (($char['temp_hp'] ?? 0) > 0): ?>
            <p class="text-blue-400 text-sm">+<span id="tempHP"><?php echo $char['temp_hp']; ?></span> temp</p>
            <?php endif; ?>
        </div>
        
        <!-- Quick heal buttons -->
        <div class="flex gap-1">
            <button onclick="adjustHP(1)" class="bg-green-500/20 hover:bg-green-500/30 text-green-400 px-3 py-2 rounded-lg text-sm font-bold transition">+1</button>
            <button onclick="adjustHP(5)" class="bg-green-500/20 hover:bg-green-500/30 text-green-400 px-3 py-2 rounded-lg text-sm font-bold transition">+5</button>
        </div>
    </div>
    
    <!-- HP Bar -->
    <div class="w-full bg-gray-800 rounded-full h-4 overflow-hidden">
        <?php 
        $hp_percent = ($char['max_hp'] > 0) ? ($char['current_hp'] / $char['max_hp']) * 100 : 0;
        $hp_color = $hp_percent > 50 ? 'bg-green-500' : ($hp_percent > 25 ? 'bg-yellow-500' : 'bg-red-500');
        ?>
        <div id="hpBar" class="<?php echo $hp_color; ?> h-full transition-all duration-300" style="width: <?php echo $hp_percent; ?>%"></div>
    </div>
</div>

<!-- Status Effects -->
<?php
$statuses = $conn->query("SELECT * FROM character_status_effects WHERE character_id = {$char['id']} ORDER BY created_at DESC");
if ($statuses && $statuses->num_rows > 0):
?>
<div class="bg-gray-900/50 border border-gray-800 rounded-xl p-6 mb-6">
    <h3 class="text-lg font-bold text-white mb-4 flex items-center">
        <svg class="w-5 h-5 text-purple-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
        </svg>
        Status Effects
    </h3>
    <div id="statusEffects" class="flex flex-wrap gap-2">
        <?php while ($status = $statuses->fetch_assoc()): ?>
        <div class="bg-purple-500/20 border border-purple-500/50 rounded-lg px-3 py-2">
            <span class="text-purple-300 font-medium"><?php echo htmlspecialchars($status['status_name']); ?></span>
            <?php if (!empty($status['description'])): ?>
            <p class="text-purple-400/70 text-xs mt-1"><?php echo htmlspecialchars($status['description']); ?></p>
            <?php endif; ?>
        </div>
        <?php endwhile; ?>
    </div>
</div>
<?php endif; ?>

<!-- Ability Scores with Info Buttons -->
<div class="bg-gray-900/50 border border-gray-800 rounded-xl p-6 mb-6">
    <h3 class="text-lg font-bold text-white mb-4">Abilities</h3>
    <div class="grid grid-cols-3 md:grid-cols-6 gap-4">
        <?php 
        $abilities = [
            'STR' => ['strength', $char['strength'] ?? 10, 'Strength', 'Natural athleticism, bodily power'],
            'DEX' => ['dexterity', $char['dexterity'] ?? 10, 'Dexterity', 'Physical agility, reflexes, balance, poise'],
            'CON' => ['constitution', $char['constitution'] ?? 10, 'Constitution', 'Health, stamina, vital force'],
            'INT' => ['intelligence', $char['intelligence'] ?? 10, 'Intelligence', 'Mental acuity, information recall, analytical skill'],
            'WIS' => ['wisdom', $char['wisdom'] ?? 10, 'Wisdom', 'Awareness, intuition, insight'],
            'CHA' => ['charisma', $char['charisma'] ?? 10, 'Charisma', 'Confidence, eloquence, leadership']
        ];
        foreach ($abilities as $abbr => $data):
            $modifier = floor(($data[1] - 10) / 2);
            $mod_display = ($modifier >= 0 ? '+' : '') . $modifier;
        ?>
        <div class="bg-gray-800/50 rounded-lg p-3 text-center relative group">
            <button onclick="showAbilityInfo('<?php echo $data[2]; ?>', '<?php echo $data[3]; ?>')" 
                    class="absolute top-1 right-1 w-5 h-5 bg-gray-700/50 hover:bg-gray-600 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </button>
            <p class="text-gray-400 text-xs font-bold mb-1"><?php echo $abbr; ?></p>
            <p class="text-2xl font-bold text-white"><?php echo $data[1]; ?></p>
            <p class="text-sm <?php echo $modifier >= 0 ? 'text-green-400' : 'text-red-400'; ?>"><?php echo $mod_display; ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Skills with Info Buttons -->
<?php
$skills_result = $conn->query("SELECT * FROM character_skills WHERE character_id = {$char['id']}");
$skills = $skills_result ? $skills_result->fetch_assoc() : [];

// Skill definitions with their associated abilities and descriptions
$skill_list = [
    'acrobatics' => ['DEX', 'Acrobatics', 'Your ability to stay on your feet in tricky situations, such as balancing on a tightrope, walking on ice, or tumbling mid-combat.'],
    'animal_handling' => ['WIS', 'Animal Handling', 'Your ability to calm, train, and read the intentions of animals.'],
    'arcana' => ['INT', 'Arcana', 'Your knowledge of magic, spells, magical items, eldritch symbols, and the planes of existence.'],
    'athletics' => ['STR', 'Athletics', 'Your physical prowess in climbing, jumping, swimming, and similar activities.'],
    'deception' => ['CHA', 'Deception', 'Your ability to convincingly hide the truth, either verbally or through your actions.'],
    'history' => ['INT', 'History', 'Your knowledge of historical events, legendary people, ancient kingdoms, past disputes, recent wars, and lost civilizations.'],
    'insight' => ['WIS', 'Insight', 'Your ability to determine the true intentions of a creature, such as detecting lies or predicting someone\'s next move.'],
    'intimidation' => ['CHA', 'Intimidation', 'Your ability to influence someone through overt threats, hostile actions, and physical violence.'],
    'investigation' => ['INT', 'Investigation', 'Your ability to look for clues and make deductions based on those clues, such as determining the location of a hidden object.'],
    'medicine' => ['WIS', 'Medicine', 'Your ability to stabilize dying companions and diagnose illnesses.'],
    'nature' => ['INT', 'Nature', 'Your knowledge of terrain, plants, animals, the weather, and natural cycles.'],
    'perception' => ['WIS', 'Perception', 'Your ability to spot, hear, or otherwise detect the presence of something using your senses.'],
    'performance' => ['CHA', 'Performance', 'Your ability to delight an audience with music, dance, acting, storytelling, or some other form of entertainment.'],
    'persuasion' => ['CHA', 'Persuasion', 'Your ability to influence someone or a group of people with tact, social graces, or good nature.'],
    'religion' => ['INT', 'Religion', 'Your knowledge of deities, rites, prayers, religious hierarchies, holy symbols, and the practices of secret cults.'],
    'sleight_of_hand' => ['DEX', 'Sleight of Hand', 'Your ability to pick pockets, conceal an object on your person, or perform other feats of manual trickery.'],
    'stealth' => ['DEX', 'Stealth', 'Your ability to conceal yourself from enemies, slink past guards, slip away without being noticed, or sneak up on someone.'],
    'survival' => ['WIS', 'Survival', 'Your ability to follow tracks, hunt wild game, guide your group through frozen wastelands, or avoid quicksand and other natural hazards.']
];

// Ability score mapping for modifier calculation
$ability_scores = [
    'STR' => $char['strength'] ?? 10,
    'DEX' => $char['dexterity'] ?? 10,
    'CON' => $char['constitution'] ?? 10,
    'INT' => $char['intelligence'] ?? 10,
    'WIS' => $char['wisdom'] ?? 10,
    'CHA' => $char['charisma'] ?? 10
];

$proficiency_bonus = $char['proficiency_bonus'] ?? 2;

// Separate proficient and non-proficient skills
$proficient_skills = [];
$other_skills = [];

foreach ($skill_list as $skill => $data) {
    $ability = $data[0];
    $skill_name = $data[1];
    $skill_desc = $data[2];
    
    $is_proficient = !empty($skills[$skill]);
    $ability_mod = floor(($ability_scores[$ability] - 10) / 2);
    $total_mod = $is_proficient ? $ability_mod + $proficiency_bonus : $ability_mod;
    
    $skill_data = [
        'name' => $skill_name,
        'ability' => $ability,
        'modifier' => $total_mod,
        'is_proficient' => $is_proficient,
        'description' => $skill_desc
    ];
    
    if ($is_proficient) {
        $proficient_skills[] = $skill_data;
    } else {
        $other_skills[] = $skill_data;
    }
}
?>
<div class="bg-gray-900/50 border border-gray-800 rounded-xl p-6 mb-6">
    <h3 class="text-lg font-bold text-white mb-4 flex items-center">
        <svg class="w-5 h-5 text-primary mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
        </svg>
        Skills
    </h3>
    
    <?php if (!empty($proficient_skills)): ?>
    <!-- Proficient Skills Section -->
    <div class="mb-6">
        <div class="flex items-center gap-2 mb-3">
            <div class="w-2 h-2 bg-primary rounded-full"></div>
            <h4 class="text-primary font-semibold text-sm uppercase tracking-wide">Proficient</h4>
        </div>
        <div class="bg-primary/10 border border-primary/30 rounded-lg p-4">
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                <?php foreach ($proficient_skills as $skill): ?>
                <div class="flex items-center justify-between bg-gray-900/50 rounded-lg px-3 py-2 group relative">
                    <button onclick="showSkillInfo('<?php echo addslashes($skill['name']); ?>', '<?php echo addslashes($skill['description']); ?>')" 
                            class="absolute top-1 right-1 w-5 h-5 bg-gray-700/50 hover:bg-gray-600 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition z-10">
                        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </button>
                    <div>
                        <p class="text-white font-medium text-sm"><?php echo $skill['name']; ?></p>
                        <p class="text-gray-500 text-xs"><?php echo $skill['ability']; ?></p>
                    </div>
                    <span class="text-lg font-bold <?php echo $skill['modifier'] >= 0 ? 'text-green-400' : 'text-red-400'; ?>">
                        <?php echo ($skill['modifier'] >= 0 ? '+' : '') . $skill['modifier']; ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($other_skills)): ?>
    <!-- Other Skills Section -->
    <div>
        <div class="flex items-center gap-2 mb-3">
            <div class="w-2 h-2 bg-gray-500 rounded-full"></div>
            <h4 class="text-gray-400 font-semibold text-sm uppercase tracking-wide">Other Skills</h4>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
            <?php foreach ($other_skills as $skill): ?>
            <div class="flex items-center justify-between bg-gray-800/50 rounded-lg px-3 py-2 group relative">
                <button onclick="showSkillInfo('<?php echo addslashes($skill['name']); ?>', '<?php echo addslashes($skill['description']); ?>')" 
                        class="absolute top-1 right-1 w-5 h-5 bg-gray-700/50 hover:bg-gray-600 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition z-10">
                    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </button>
                <div>
                    <p class="text-gray-300 text-sm"><?php echo $skill['name']; ?></p>
                    <p class="text-gray-600 text-xs"><?php echo $skill['ability']; ?></p>
                </div>
                <span class="text-sm font-medium <?php echo $skill['modifier'] >= 0 ? 'text-gray-400' : 'text-red-400'; ?>">
                    <?php echo ($skill['modifier'] >= 0 ? '+' : '') . $skill['modifier']; ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Equipment - Interactive (toggle equipped) -->
<?php
$equipment = $conn->query("SELECT * FROM character_equipment WHERE character_id = {$char['id']} ORDER BY is_equipped DESC, item_name");
if ($equipment && $equipment->num_rows > 0):
    // Separate equipped and inventory items
    $equipped_items = [];
    $inventory_items = [];
    while ($item = $equipment->fetch_assoc()) {
        if ($item['is_equipped']) {
            $equipped_items[] = $item;
        } else {
            $inventory_items[] = $item;
        }
    }
?>
<div class="bg-gray-900/50 border border-gray-800 rounded-xl p-6 mb-6">
    <h3 class="text-lg font-bold text-white mb-4 flex items-center">
        <svg class="w-5 h-5 text-primary mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
        </svg>
        Equipment
    </h3>
    
    <?php if (!empty($equipped_items)): ?>
    <!-- Equipped Items Section -->
    <div class="mb-6">
        <div class="flex items-center gap-2 mb-3">
            <div class="w-2 h-2 bg-primary rounded-full"></div>
            <h4 class="text-primary font-semibold text-sm uppercase tracking-wide">Currently Equipped</h4>
        </div>
        <div class="bg-primary/10 border border-primary/30 rounded-lg p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <?php foreach ($equipped_items as $item): 
                    $item_json = htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8');
                    $rarity_color = match(strtolower($item['rarity'] ?? '')) {
                        'common' => 'gray-400',
                        'uncommon' => 'green-400',
                        'rare' => 'blue-400',
                        'very rare' => 'purple-400',
                        'legendary' => 'orange-400',
                        'artifact' => 'red-400',
                        default => 'gray-400'
                    };
                ?>
                <div class="flex items-center gap-3 p-3 bg-gray-900/50 rounded-lg hover:bg-gray-900 transition group">
                    <button onclick="event.stopPropagation(); toggleEquipment(<?php echo $item['id']; ?>, this.closest('.group'))"
                            class="w-6 h-6 rounded border-2 flex items-center justify-center transition bg-primary border-primary hover:bg-primary-dark flex-shrink-0"
                            title="Unequip">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                        </svg>
                    </button>
                    <div class="flex-grow cursor-pointer" onclick="showItemDetails(<?php echo $item_json; ?>)">
                        <p class="text-white font-medium"><?php echo htmlspecialchars($item['item_name']); ?></p>
                        <p class="text-gray-400 text-xs">
                            <?php echo htmlspecialchars($item['type'] ?? ''); ?>
                            <?php if (!empty($item['rarity'])): ?>
                            <span class="text-<?php echo $rarity_color; ?>">(<?php echo htmlspecialchars($item['rarity']); ?>)</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <button onclick="showItemDetails(<?php echo $item_json; ?>)"
                            class="text-gray-500 hover:text-white transition p-1" title="View details">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </button>
                    <button onclick="event.stopPropagation(); removeItem(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['item_name'], ENT_QUOTES); ?>')"
                            class="text-gray-500 hover:text-red-400 transition p-1" title="Remove item">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($inventory_items)): ?>
    <!-- Inventory Section -->
    <div>
        <div class="flex items-center gap-2 mb-3">
            <div class="w-2 h-2 bg-gray-500 rounded-full"></div>
            <h4 class="text-gray-400 font-semibold text-sm uppercase tracking-wide">Inventory</h4>
        </div>
        <div class="space-y-2">
            <?php foreach ($inventory_items as $item): 
                $item_json = htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8');
                $rarity_color = match(strtolower($item['rarity'] ?? '')) {
                    'common' => 'gray-400',
                    'uncommon' => 'green-400',
                    'rare' => 'blue-400',
                    'very rare' => 'purple-400',
                    'legendary' => 'orange-400',
                    'artifact' => 'red-400',
                    default => 'gray-400'
                };
            ?>
            <div class="flex items-center gap-3 p-3 bg-gray-800/50 rounded-lg hover:bg-gray-800 transition group">
                <button onclick="event.stopPropagation(); toggleEquipment(<?php echo $item['id']; ?>, this.closest('.group'))"
                        class="w-6 h-6 rounded border-2 flex items-center justify-center transition border-gray-600 hover:border-primary flex-shrink-0"
                        title="Equip">
                </button>
                <div class="flex-grow cursor-pointer" onclick="showItemDetails(<?php echo $item_json; ?>)">
                    <p class="text-white font-medium"><?php echo htmlspecialchars($item['item_name']); ?></p>
                    <p class="text-gray-400 text-xs">
                        <?php echo htmlspecialchars($item['type'] ?? ''); ?>
                        <?php if (!empty($item['rarity'])): ?>
                        <span class="text-<?php echo $rarity_color; ?>">(<?php echo htmlspecialchars($item['rarity']); ?>)</span>
                        <?php endif; ?>
                    </p>
                </div>
                <button onclick="showItemDetails(<?php echo $item_json; ?>)"
                        class="text-gray-500 hover:text-white transition p-1" title="View details">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </button>
                <button onclick="event.stopPropagation(); removeItem(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['item_name'], ENT_QUOTES); ?>')"
                        class="text-gray-500 hover:text-red-400 transition p-1" title="Remove item">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Spells with integrated Spell Slots -->
<?php
$spells = $conn->query("SELECT * FROM character_spells WHERE character_id = {$char['id']} ORDER BY level, name");
$has_spells = $spells && $spells->num_rows > 0;

// Check if character has spell slots
$has_spell_slots = false;
for ($i = 1; $i <= 9; $i++) {
    $slot_col = "spell_slots_$i";
    if (isset($char[$slot_col]) && $char[$slot_col] > 0) {
        $has_spell_slots = true;
        break;
    }
}

if ($has_spells || $has_spell_slots):
    if ($has_spells) {
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
    }
?>
<div class="bg-gray-900/50 border border-gray-800 rounded-xl p-6 mb-6">
    <h3 class="text-lg font-bold text-white flex items-center mb-6">
        <svg class="w-5 h-5 text-primary mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"/>
        </svg>
        Spells
    </h3>
    
    <?php if ($has_spell_slots): ?>
    <!-- Spell Slots Section -->
    <div class="mb-6 pb-6 border-b border-gray-700">
        <h4 class="text-gray-400 font-semibold text-sm uppercase tracking-wide mb-4">Spell Slots</h4>
        <div class="grid grid-cols-3 md:grid-cols-5 lg:grid-cols-9 gap-3">
            <?php for ($i = 1; $i <= 9; $i++): 
                $max_col = "spell_slots_$i";
                $current_col = "current_spell_slots_$i";
                $max_slots = $char[$max_col] ?? 0;
                $current_slots = $char[$current_col] ?? 0;
                if ($max_slots > 0):
            ?>
            <div class="bg-gray-800/50 rounded-lg p-3" data-spell-level="<?php echo $i; ?>">
                <p class="text-gray-400 text-xs mb-2 text-center font-semibold">Level <?php echo $i; ?></p>
                <p class="text-white text-center font-bold mb-2">
                    <span id="currentSlots<?php echo $i; ?>"><?php echo $current_slots; ?></span> / <?php echo $max_slots; ?>
                </p>
                
                <!-- Clickable slot indicators -->
                <div id="spellSlots<?php echo $i; ?>" class="flex flex-wrap gap-1 justify-center min-h-[24px] mb-2">
                    <?php for ($j = 0; $j < $max_slots; $j++): ?>
                    <button onclick="toggleSpellSlot(<?php echo $i; ?>, <?php echo $j; ?>)" 
                            class="w-5 h-5 rounded-full transition-all duration-200 hover:scale-110 <?php echo $j < $current_slots ? 'bg-primary hover:bg-primary-dark' : 'bg-gray-700 hover:bg-gray-600'; ?>"
                            data-slot-index="<?php echo $j; ?>">
                    </button>
                    <?php endfor; ?>
                </div>
                
                <!-- Use/Recover buttons -->
                <div class="flex gap-1 justify-center">
                    <button onclick="useSpellSlot(<?php echo $i; ?>)" 
                            class="bg-red-500/20 hover:bg-red-500/30 text-red-400 px-2 py-1 rounded text-xs font-bold transition"
                            title="Use slot">−</button>
                    <button onclick="recoverSpellSlot(<?php echo $i; ?>)" 
                            class="bg-green-500/20 hover:bg-green-500/30 text-green-400 px-2 py-1 rounded text-xs font-bold transition"
                            title="Recover slot">+</button>
                </div>
            </div>
            <?php endif; endfor; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($has_spells): ?>
    <!-- Spells List -->
    <div>
        <h4 class="text-gray-400 font-semibold text-sm uppercase tracking-wide mb-4">Known Spells</h4>
        <?php foreach ($spells_by_level as $level => $level_spells): 
            $level_label = $level == 0 ? 'Cantrips' : 'Level ' . $level;
        ?>
        <div class="mb-4">
            <h5 class="text-primary font-semibold mb-2"><?php echo $level_label; ?></h5>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                <?php foreach ($level_spells as $spell): ?>
                <div class="bg-gray-800/50 rounded-lg p-3 cursor-pointer hover:bg-gray-800 transition"
                     onclick="showSpellDetails(<?php echo htmlspecialchars(json_encode($spell)); ?>)">
                    <p class="text-white font-medium"><?php echo htmlspecialchars($spell['name']); ?></p>
                    <p class="text-gray-400 text-xs">
                        <?php echo htmlspecialchars($spell['school'] ?? ''); ?> 
                        • <?php echo htmlspecialchars($spell['casting_time'] ?? ''); ?>
                    </p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Feat Modal -->
<div id="featModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4" onclick="hideFeatModal()">
    <div class="bg-gray-900 rounded-xl border border-gray-700 max-w-lg w-full max-h-[80vh] overflow-y-auto" onclick="event.stopPropagation()">
        <div class="p-6">
            <div class="flex justify-between items-start mb-4">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-primary/20 rounded-lg border border-primary/50 flex items-center justify-center mr-3">
                        <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                        </svg>
                    </div>
                    <h3 id="featModalTitle" class="text-xl font-bold text-white"></h3>
                </div>
                <button onclick="hideFeatModal()" class="text-gray-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="bg-gray-800/30 rounded-lg p-4 border border-gray-700">
                <p id="featModalContent" class="text-gray-300 whitespace-pre-line"></p>
            </div>
        </div>
    </div>
</div>

<!-- Ability Info Modal -->
<div id="abilityModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4" onclick="hideAbilityModal()">
    <div class="bg-gray-900 rounded-xl border border-gray-700 max-w-lg w-full" onclick="event.stopPropagation()">
        <div class="p-6">
            <div class="flex justify-between items-start mb-4">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-primary/20 rounded-lg border border-primary/50 flex items-center justify-center mr-3">
                        <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <h3 id="abilityModalTitle" class="text-xl font-bold text-white"></h3>
                </div>
                <button onclick="hideAbilityModal()" class="text-gray-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="bg-gray-800/30 rounded-lg p-4 border border-gray-700">
                <p id="abilityModalContent" class="text-gray-300"></p>
            </div>
        </div>
    </div>
</div>

<!-- Skill Info Modal -->
<div id="skillModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4" onclick="hideSkillModal()">
    <div class="bg-gray-900 rounded-xl border border-gray-700 max-w-lg w-full" onclick="event.stopPropagation()">
        <div class="p-6">
            <div class="flex justify-between items-start mb-4">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-primary/20 rounded-lg border border-primary/50 flex items-center justify-center mr-3">
                        <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                        </svg>
                    </div>
                    <h3 id="skillModalTitle" class="text-xl font-bold text-white"></h3>
                </div>
                <button onclick="hideSkillModal()" class="text-gray-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="bg-gray-800/30 rounded-lg p-4 border border-gray-700">
                <p id="skillModalContent" class="text-gray-300"></p>
            </div>
        </div>
    </div>
</div>

<!-- Spell Details Modal -->
<div id="spellModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4" onclick="hideSpellModal()">
    <div class="bg-gray-900 rounded-xl border border-gray-700 max-w-lg w-full max-h-[80vh] overflow-y-auto" onclick="event.stopPropagation()">
        <div class="p-6">
            <div class="flex justify-between items-start mb-4">
                <h3 id="spellModalTitle" class="text-xl font-bold text-white"></h3>
                <button onclick="hideSpellModal()" class="text-gray-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div id="spellModalContent" class="text-gray-300"></div>
        </div>
    </div>
</div>

<!-- Item Details Modal -->
<div id="itemModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4" onclick="hideItemModal()">
    <div class="bg-gray-900 rounded-xl border border-gray-700 max-w-lg w-full max-h-[80vh] overflow-y-auto" onclick="event.stopPropagation()">
        <div class="p-6">
            <div class="flex justify-between items-start mb-4">
                <h3 id="itemModalTitle" class="text-xl font-bold text-white"></h3>
                <button onclick="hideItemModal()" class="text-gray-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div id="itemModalContent" class="text-gray-300"></div>
        </div>
    </div>
</div>

</div><!-- End max-w-5xl wrapper -->

<script>
const characterId = <?php echo $characterId; ?>;
const maxHP = <?php echo $char['max_hp'] ?? 0; ?>;

// ===== HP Functions =====
async function adjustHP(change) {
    try {
        const response = await fetch('/player/api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=update_hp&character_id=${characterId}&change=${change}`
        });
        const result = await response.json();
        
        if (result.success) {
            updateHPDisplay(result.new_hp);
        } else {
            alert(result.message || 'Failed to update HP');
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function updateHPDisplay(newHP) {
    const currentHPEl = document.getElementById('currentHP');
    const hpBar = document.getElementById('hpBar');
    
    currentHPEl.textContent = newHP;
    currentHPEl.classList.add('scale-125', 'text-primary');
    setTimeout(() => currentHPEl.classList.remove('scale-125', 'text-primary'), 300);
    
    const percent = (newHP / maxHP) * 100;
    hpBar.style.width = percent + '%';
    hpBar.className = `h-full transition-all duration-300 ${
        percent > 50 ? 'bg-green-500' : (percent > 25 ? 'bg-yellow-500' : 'bg-red-500')
    }`;
}

// ===== Spell Slot Functions =====
async function useSpellSlot(level) {
    try {
        const response = await fetch('/player/api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=use_spell_slot&character_id=${characterId}&level=${level}`
        });
        const result = await response.json();
        
        if (result.success) {
            updateSpellSlotDisplay(level, result.new_value);
        } else {
            const container = document.querySelector(`[data-spell-level="${level}"]`);
            container.classList.add('border', 'border-red-500');
            setTimeout(() => container.classList.remove('border', 'border-red-500'), 500);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

async function recoverSpellSlot(level) {
    try {
        const response = await fetch('/player/api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=recover_spell_slot&character_id=${characterId}&level=${level}`
        });
        const result = await response.json();
        
        if (result.success) {
            updateSpellSlotDisplay(level, result.new_value);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function toggleSpellSlot(level, slotIndex) {
    const currentEl = document.getElementById(`currentSlots${level}`);
    const current = parseInt(currentEl.textContent);
    
    if (slotIndex < current) {
        useSpellSlot(level);
    } else {
        recoverSpellSlot(level);
    }
}

function updateSpellSlotDisplay(level, newValue) {
    const currentEl = document.getElementById(`currentSlots${level}`);
    const slotsContainer = document.getElementById(`spellSlots${level}`);
    
    currentEl.textContent = newValue;
    
    const buttons = slotsContainer.querySelectorAll('button');
    buttons.forEach((btn, index) => {
        if (index < newValue) {
            btn.className = 'w-5 h-5 rounded-full transition-all duration-200 hover:scale-110 bg-primary hover:bg-primary-dark';
        } else {
            btn.className = 'w-5 h-5 rounded-full transition-all duration-200 hover:scale-110 bg-gray-700 hover:bg-gray-600';
        }
    });
}

// ===== Equipment Functions =====
async function toggleEquipment(equipmentId, element) {
    try {
        const response = await fetch('/player/api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=toggle_equipment&character_id=${characterId}&equipment_id=${equipmentId}`
        });
        const result = await response.json();

        if (result.success) {
            location.reload();
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

async function removeItem(equipmentId, itemName) {
    if (!confirm(`Remove "${itemName}" from your inventory? This cannot be undone.`)) {
        return;
    }

    try {
        const response = await fetch('/player/api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=remove_item&character_id=${characterId}&equipment_id=${equipmentId}`
        });
        const result = await response.json();

        if (result.success) {
            location.reload();
        } else {
            alert(result.message || 'Failed to remove item');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred while removing the item');
    }
}

// ===== Modal Functions =====
function showFeatModal(feat) {
    document.getElementById('featModalTitle').textContent = feat.name;
    document.getElementById('featModalContent').textContent = feat.description || 'No description available.';
    document.getElementById('featModal').classList.remove('hidden');
    document.getElementById('featModal').classList.add('flex');
}

function hideFeatModal() {
    document.getElementById('featModal').classList.add('hidden');
    document.getElementById('featModal').classList.remove('flex');
}

function showAbilityInfo(name, description) {
    document.getElementById('abilityModalTitle').textContent = name;
    document.getElementById('abilityModalContent').textContent = description;
    document.getElementById('abilityModal').classList.remove('hidden');
    document.getElementById('abilityModal').classList.add('flex');
}

function hideAbilityModal() {
    document.getElementById('abilityModal').classList.add('hidden');
    document.getElementById('abilityModal').classList.remove('flex');
}

function showSkillInfo(name, description) {
    document.getElementById('skillModalTitle').textContent = name;
    document.getElementById('skillModalContent').textContent = description;
    document.getElementById('skillModal').classList.remove('hidden');
    document.getElementById('skillModal').classList.add('flex');
}

function hideSkillModal() {
    document.getElementById('skillModal').classList.add('hidden');
    document.getElementById('skillModal').classList.remove('flex');
}

function showItemDetails(item) {
    document.getElementById('itemModalTitle').textContent = item.item_name;
    
    const rarityColors = {
        'common': 'text-gray-400',
        'uncommon': 'text-green-400',
        'rare': 'text-blue-400',
        'very rare': 'text-purple-400',
        'legendary': 'text-orange-400',
        'artifact': 'text-red-400'
    };
    const rarityColor = rarityColors[(item.rarity || '').toLowerCase()] || 'text-gray-400';
    
    let content = `
        <div class="space-y-3">
            ${item.type ? `<p><span class="text-gray-500">Type:</span> <span class="text-white">${item.type}</span></p>` : ''}
            ${item.rarity ? `<p><span class="text-gray-500">Rarity:</span> <span class="${rarityColor} font-medium">${item.rarity}</span></p>` : ''}
            ${item.properties ? `<p><span class="text-gray-500">Properties:</span> <span class="text-white">${item.properties}</span></p>` : ''}
        </div>
        ${item.description ? `
            <div class="mt-4 pt-4 border-t border-gray-700">
                <p class="text-gray-500 text-sm mb-2">Description</p>
                <p class="text-gray-300">${item.description}</p>
            </div>
        ` : ''}
    `;
    
    document.getElementById('itemModalContent').innerHTML = content;
    document.getElementById('itemModal').classList.remove('hidden');
    document.getElementById('itemModal').classList.add('flex');
}

function hideItemModal() {
    document.getElementById('itemModal').classList.add('hidden');
    document.getElementById('itemModal').classList.remove('flex');
}

function showSpellDetails(spell) {
    document.getElementById('spellModalTitle').textContent = spell.name;
    
    let content = `
        <div class="space-y-2 text-sm">
            <p><span class="text-gray-500">Level:</span> ${spell.level == 0 ? 'Cantrip' : spell.level}</p>
            ${spell.school ? `<p><span class="text-gray-500">School:</span> ${spell.school}</p>` : ''}
            ${spell.casting_time ? `<p><span class="text-gray-500">Casting Time:</span> ${spell.casting_time}</p>` : ''}
            ${spell.range_area ? `<p><span class="text-gray-500">Range:</span> ${spell.range_area}</p>` : ''}
            ${spell.components ? `<p><span class="text-gray-500">Components:</span> ${spell.components}</p>` : ''}
            ${spell.duration ? `<p><span class="text-gray-500">Duration:</span> ${spell.duration}</p>` : ''}
        </div>
        ${spell.description ? `<div class="mt-4 pt-4 border-t border-gray-700"><p class="text-gray-300">${spell.description}</p></div>` : ''}
    `;
    
    document.getElementById('spellModalContent').innerHTML = content;
    document.getElementById('spellModal').classList.remove('hidden');
    document.getElementById('spellModal').classList.add('flex');
}

function hideSpellModal() {
    document.getElementById('spellModal').classList.add('hidden');
    document.getElementById('spellModal').classList.remove('flex');
}

// ===== Polling for DM updates =====
setInterval(async () => {
    try {
        const response = await fetch(`/player/api.php?action=poll_character&character_id=${characterId}`);
        const result = await response.json();
        
        if (result.success && result.stats) {
            const currentHPEl = document.getElementById('currentHP');
            if (parseInt(currentHPEl.textContent) !== result.stats.current_hp) {
                updateHPDisplay(result.stats.current_hp);
            }
            
            for (let i = 1; i <= 9; i++) {
                const currentEl = document.getElementById(`currentSlots${i}`);
                if (currentEl) {
                    const serverValue = result.stats[`current_spell_slots_${i}`];
                    if (parseInt(currentEl.textContent) !== serverValue) {
                        updateSpellSlotDisplay(i, serverValue);
                    }
                }
            }
        }
    } catch (error) {
        console.error('Polling error:', error);
    }
}, 5000);
</script>