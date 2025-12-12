<?php
// Get all characters
$characters = $conn->query("
    SELECT 
        c.*,
        cs.current_hp,
        cs.max_hp,
        cs.armor_class,
        cs.strength,
        cs.dexterity,
        cs.constitution,
        cs.intelligence,
        cs.wisdom,
        cs.charisma,
        p.display_name as player_name,
        u.username as player_username
    FROM characters c
    LEFT JOIN character_stats cs ON c.id = cs.character_id
    LEFT JOIN players p ON c.player_id = p.id
    LEFT JOIN users u ON p.user_id = u.id
    ORDER BY c.name
");
?>

<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-white flex items-center">
                <svg class="w-8 h-8 text-primary mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                Character Management
            </h2>
            <p class="text-gray-400 ml-11">Manage all character sheets and stats</p>
        </div>
        <button onclick="showCreateModal()" class="bg-primary hover:bg-primary-dark text-white font-bold py-3 px-6 rounded-lg transition flex items-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Create Character</span>
        </button>
    </div>

    <!-- Character Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php while ($char = $characters->fetch_assoc()): ?>
        <div class="bg-gray-900/50 border border-gray-800 rounded-lg hover:border-primary/50 transition overflow-hidden">
            <!-- Character Header -->
            <div class="p-6 border-b border-gray-800">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex-1">
                        <h3 class="text-xl font-bold text-white"><?php echo htmlspecialchars($char['name']); ?></h3>
                        <p class="text-gray-400 text-sm">Level <?php echo $char['level']; ?> <?php echo htmlspecialchars($char['race']); ?> <?php echo htmlspecialchars($char['class']); ?></p>
                        <?php if ($char['player_name']): ?>
                        <p class="text-xs text-primary mt-1 flex items-center">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                            </svg>
                            Player: <?php echo htmlspecialchars($char['player_name'] ?: $char['player_username']); ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="text-xs text-gray-500 bg-gray-800 px-2 py-1 rounded">AC: <?php echo $char['armor_class'] ?? 10; ?></span>
                    </div>
                </div>
                
                <!-- HP Bar -->
                <div class="space-y-1">
                    <div class="flex justify-between text-xs text-gray-400">
                        <span>HP</span>
                        <span><?php echo $char['current_hp'] ?? 0; ?> / <?php echo $char['max_hp'] ?? 0; ?></span>
                    </div>
                    <div class="w-full bg-gray-800 rounded-full h-2 overflow-hidden">
                        <?php 
                        $hp_percent = ($char['max_hp'] > 0) ? ($char['current_hp'] / $char['max_hp']) * 100 : 0;
                        $hp_color = $hp_percent > 50 ? 'bg-green-500' : ($hp_percent > 25 ? 'bg-yellow-500' : 'bg-red-500');
                        ?>
                        <div class="<?php echo $hp_color; ?> h-full transition-all" style="width: <?php echo $hp_percent; ?>%"></div>
                    </div>
                </div>
            </div>

            <!-- Ability Scores -->
            <div class="p-4 border-b border-gray-800">
                <p class="text-xs text-gray-500 uppercase tracking-wider mb-2">Abilities</p>
                <div class="grid grid-cols-6 gap-2">
                    <?php 
                    $abilities = [
                        'STR' => $char['strength'] ?? 10,
                        'DEX' => $char['dexterity'] ?? 10,
                        'CON' => $char['constitution'] ?? 10,
                        'INT' => $char['intelligence'] ?? 10,
                        'WIS' => $char['wisdom'] ?? 10,
                        'CHA' => $char['charisma'] ?? 10
                    ];
                    foreach ($abilities as $abbr => $score):
                        $modifier = floor(($score - 10) / 2);
                    ?>
                    <div class="text-center">
                        <div class="text-xs text-gray-500"><?php echo $abbr; ?></div>
                        <div class="text-sm font-bold text-white"><?php echo $score; ?></div>
                        <div class="text-xs text-primary"><?php echo $modifier >= 0 ? '+' : ''; ?><?php echo $modifier; ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Equipped Items (sample) -->
            <?php 
            $equipment = $conn->query("SELECT item_name FROM character_equipment WHERE character_id = {$char['id']} AND is_equipped = 1 LIMIT 1");
            $item = $equipment->fetch_assoc();
            ?>
            <?php if ($item): ?>
            <div class="p-4 border-b border-gray-800">
                <p class="text-xs text-gray-500 uppercase tracking-wider mb-2">Equipped Items</p>
                <div class="flex items-center text-sm text-gray-300">
                    <svg class="w-4 h-4 text-primary mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <?php echo htmlspecialchars($item['item_name']); ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Actions -->
            <div class="p-4 flex gap-2">
                <button onclick="location.href='/admin/character.php?id=<?php echo $char['id']; ?>'" class="flex-1 flex items-center justify-center space-x-2 px-4 py-2 bg-gray-800 hover:bg-gray-700 text-white rounded-lg transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <span>Edit</span>
                </button>
                <button onclick="duplicateCharacter(<?php echo $char['id']; ?>)" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-gray-400 hover:text-white rounded-lg transition" title="Duplicate">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </button>
                <button onclick="deleteCharacter(<?php echo $char['id']; ?>)" class="px-4 py-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 rounded-lg transition" title="Delete">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<!-- Create Character Modal -->
<div id="createModal" class="hidden fixed inset-0 bg-black/75 backdrop-blur-sm flex items-center justify-center p-4 z-50">
    <div class="bg-gray-900 border border-gray-800 rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-gray-900 p-6 border-b border-gray-800">
            <h3 class="text-xl font-bold text-white">Create New Character</h3>
        </div>
        <form id="createForm" onsubmit="createCharacter(event)" class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-300 mb-2 text-sm">Character Name</label>
                    <input type="text" name="name" required class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-gray-300 mb-2 text-sm">Race</label>
                    <input type="text" name="race" required class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-gray-300 mb-2 text-sm">Class</label>
                    <input type="text" name="class" required class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-gray-300 mb-2 text-sm">Level</label>
                    <input type="number" name="level" value="1" min="1" max="20" class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                </div>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="submit" class="flex-1 bg-primary hover:bg-primary-dark text-white font-bold py-3 px-4 rounded-lg transition">
                    Create Character
                </button>
                <button type="button" onclick="hideCreateModal()" class="px-6 bg-gray-800 hover:bg-gray-700 text-white rounded-lg transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showCreateModal() {
    document.getElementById('createModal').classList.remove('hidden');
}

function hideCreateModal() {
    document.getElementById('createModal').classList.add('hidden');
    document.getElementById('createForm').reset();
}

async function createCharacter(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append('action', 'create_character');
    
    try {
        const response = await fetch('/admin/api.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) {
            location.reload();
        } else {
            alert('Failed to create character');
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

async function duplicateCharacter(id) {
    const formData = new FormData();
    formData.append('action', 'duplicate_character');
    formData.append('id', id);
    
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

async function deleteCharacter(id) {
    if (!confirm('Delete this character? This cannot be undone.')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_character');
    formData.append('id', id);
    
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

document.getElementById('createModal')?.addEventListener('click', function(e) {
    if (e.target === this) hideCreateModal();
});
</script>
