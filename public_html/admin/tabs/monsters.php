<?php
// Get all monsters
$monsters = $conn->query("SELECT * FROM monsters ORDER BY name");
?>

<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-white flex items-center">
                <svg class="w-8 h-8 text-primary mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Monsters
            </h2>
            <p class="text-gray-400 ml-11">Create and manage monsters for your campaign</p>
        </div>
        <button onclick="showCreateModal()" class="bg-primary hover:bg-primary-dark text-white font-bold py-3 px-6 rounded-lg transition flex items-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Create Monster</span>
        </button>
    </div>

    <!-- Filters -->
    <div class="bg-gray-900/50 border border-gray-800 rounded-lg p-4 mb-6">
        <div class="flex flex-wrap gap-4 items-center">
            <div class="flex items-center space-x-2">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                <span class="text-gray-400 text-sm font-medium">Sort:</span>
            </div>
            <button onclick="sortMonsters('name')" class="sort-btn active" data-sort="name">
                A-Z
            </button>
            <button onclick="sortMonsters('cr')" class="sort-btn" data-sort="cr">
                Challenge Rating
            </button>
            <div class="flex-1"></div>
            <div class="relative">
                <input 
                    type="text" 
                    id="searchInput" 
                    onkeyup="filterMonsters()" 
                    placeholder="Filter by letter..." 
                    class="px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-primary w-48"
                >
                <span class="absolute right-3 top-2.5 text-gray-500 text-sm">All Letters</span>
            </div>
        </div>
    </div>

    <!-- Monster Grid -->
    <div id="monsterGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <?php while ($monster = $monsters->fetch_assoc()): ?>
        <div class="monster-card bg-gray-900/50 border border-gray-800 rounded-lg p-5 hover:border-primary/50 transition" data-name="<?php echo strtolower($monster['name']); ?>" data-cr="<?php echo $monster['challenge_rating'] ?? '0'; ?>">
            <!-- Monster Header -->
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-start space-x-3 flex-1">
                    <!-- Portrait Thumbnail -->
                    <?php if (!empty($monster['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($monster['image_url']); ?>" 
                            alt="<?php echo htmlspecialchars($monster['name']); ?>" 
                            class="w-12 h-12 object-cover rounded-lg border-2 border-gray-700 flex-shrink-0">
                    <?php else: ?>
                        <div class="w-12 h-12 bg-gray-800 rounded-lg border-2 border-gray-700 flex-shrink-0 flex items-center justify-center">
                            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                    <?php endif; ?>
                    
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-white mb-1"><?php echo htmlspecialchars($monster['name']); ?></h3>
                        <?php if ($monster['type']): ?>
                        <p class="text-sm text-gray-400"><?php echo htmlspecialchars($monster['type']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($monster['challenge_rating']): ?>
                <div class="bg-primary/20 border border-primary rounded-lg px-3 py-1 ml-2">
                    <div class="text-xs text-gray-400">CR</div>
                    <div class="text-lg font-bold text-primary"><?php echo htmlspecialchars($monster['challenge_rating']); ?></div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Stats -->
            <div class="space-y-3 mb-4">
                <!-- HP -->
                <div>
                    <div class="flex justify-between text-xs text-gray-400 mb-1">
                        <span>HP</span>
                        <span><?php echo $monster['current_hp']; ?> / <?php echo $monster['max_hp']; ?></span>
                    </div>
                    <div class="w-full bg-gray-800 rounded-full h-2 overflow-hidden">
                        <?php 
                        $hp_percent = ($monster['max_hp'] > 0) ? ($monster['current_hp'] / $monster['max_hp']) * 100 : 0;
                        $hp_color = $hp_percent > 50 ? 'bg-green-500' : ($hp_percent > 25 ? 'bg-yellow-500' : 'bg-red-500');
                        ?>
                        <div class="<?php echo $hp_color; ?> h-full transition-all" style="width: <?php echo $hp_percent; ?>%"></div>
                    </div>
                </div>

                <!-- AC -->
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-400">Armor Class</span>
                    <span class="text-white font-bold"><?php echo $monster['armor_class'] ?? 10; ?></span>
                </div>

                <!-- Abilities (compact) -->
                <div class="grid grid-cols-6 gap-1">
                    <?php 
                    $abilities = [
                        'STR' => $monster['strength'] ?? 10,
                        'DEX' => $monster['dexterity'] ?? 10,
                        'CON' => $monster['constitution'] ?? 10,
                        'INT' => $monster['intelligence'] ?? 10,
                        'WIS' => $monster['wisdom'] ?? 10,
                        'CHA' => $monster['charisma'] ?? 10
                    ];
                    foreach ($abilities as $abbr => $score):
                        $modifier = floor(($score - 10) / 2);
                    ?>
                    <div class="text-center bg-gray-800/50 rounded p-1">
                        <div class="text-xs text-gray-500"><?php echo $abbr; ?></div>
                        <div class="text-xs font-bold text-white"><?php echo $score; ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-2 pt-3 border-t border-gray-800">
                <button onclick="editMonster(<?php echo $monster['id']; ?>)" class="flex-1 flex items-center justify-center space-x-1 px-3 py-2 bg-gray-800 hover:bg-gray-700 text-white rounded-lg transition text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <span>Edit</span>
                </button>
                <button onclick="deleteMonster(<?php echo $monster['id']; ?>, '<?php echo htmlspecialchars($monster['name'], ENT_QUOTES); ?>')" class="px-3 py-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 rounded-lg transition text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </div>
        </div>
        <?php endwhile; ?>
    </div>

    <?php if ($monsters->num_rows === 0): ?>
    <div class="text-center py-20">
        <svg class="w-20 h-20 text-gray-700 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <h3 class="text-xl font-bold text-gray-600 mb-2">No Monsters Yet</h3>
        <p class="text-gray-500 mb-4">Create your first monster to get started</p>
        <button onclick="showCreateModal()" class="bg-primary hover:bg-primary-dark text-white font-bold py-2 px-6 rounded-lg transition">
            Create Monster
        </button>
    </div>
    <?php endif; ?>
</div>

<!-- Create/Edit Monster Modal -->
<div id="monsterModal" class="hidden fixed inset-0 bg-black/75 backdrop-blur-sm flex items-center justify-center p-4 z-50 overflow-y-auto">
    <div class="bg-gray-900 border border-gray-800 rounded-xl shadow-2xl w-full max-w-4xl my-8">
        <div class="sticky top-0 bg-gray-900 p-6 border-b border-gray-800 rounded-t-xl">
            <h3 id="modalTitle" class="text-2xl font-bold text-white">Create Monster</h3>
        </div>
        <form id="monsterForm" onsubmit="saveMonster(event)" class="p-6">
            <input type="hidden" id="monsterId" name="id">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Left Column -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-300 mb-2 text-sm font-medium">Monster Name *</label>
                        <input type="text" id="monsterName" name="name" required class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 mb-2 text-sm font-medium">Type</label>
                        <input type="text" id="monsterType" name="type" placeholder="e.g., Dragon, Undead, Beast" class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                    </div>
                    
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-gray-300 mb-2 text-sm font-medium">Challenge Rating</label>
                            <input type="text" id="monsterCR" name="challenge_rating" placeholder="1/4, 1, 5..." class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-gray-300 mb-2 text-sm font-medium">AC *</label>
                            <input type="number" id="monsterAC" name="armor_class" value="10" required class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-gray-300 mb-2 text-sm font-medium">Max HP *</label>
                            <input type="number" id="monsterHP" name="max_hp" value="10" required class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                        </div>
                    </div>

                    <div>
                        <label class="block text-gray-300 mb-2 text-sm font-medium">Description</label>
                        <textarea id="monsterDesc" name="description" rows="3" class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary resize-none" placeholder="Monster description, lore..."></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 mb-2 text-sm font-medium">Attacks</label>
                        <textarea id="monsterAttacks" name="attacks" rows="3" class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary resize-none" placeholder="e.g., Bite: +4 to hit, 1d8+2 piercing damage"></textarea>
                    </div>
                </div>

                <!-- Right Column - Ability Scores -->
                <div class="space-y-4">
                    <div class="bg-gray-800/50 rounded-lg p-4">
                        <h4 class="text-white font-bold mb-4 flex items-center">
                            <svg class="w-5 h-5 text-primary mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M13 7H7v6h6V7z"/>
                                <path fill-rule="evenodd" d="M7 2a1 1 0 012 0v1h2V2a1 1 0 112 0v1h2a2 2 0 012 2v2h1a1 1 0 110 2h-1v2h1a1 1 0 110 2h-1v2a2 2 0 01-2 2h-2v1a1 1 0 11-2 0v-1H9v1a1 1 0 11-2 0v-1H5a2 2 0 01-2-2v-2H2a1 1 0 110-2h1V9H2a1 1 0 010-2h1V5a2 2 0 012-2h2V2zM5 5h10v10H5V5z" clip-rule="evenodd"/>
                            </svg>
                            Ability Scores
                        </h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-400 mb-1 text-xs">STR</label>
                                <input type="number" id="monsterSTR" name="strength" value="10" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white text-center focus:outline-none focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-gray-400 mb-1 text-xs">DEX</label>
                                <input type="number" id="monsterDEX" name="dexterity" value="10" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white text-center focus:outline-none focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-gray-400 mb-1 text-xs">CON</label>
                                <input type="number" id="monsterCON" name="constitution" value="10" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white text-center focus:outline-none focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-gray-400 mb-1 text-xs">INT</label>
                                <input type="number" id="monsterINT" name="intelligence" value="10" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white text-center focus:outline-none focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-gray-400 mb-1 text-xs">WIS</label>
                                <input type="number" id="monsterWIS" name="wisdom" value="10" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white text-center focus:outline-none focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-gray-400 mb-1 text-xs">CHA</label>
                                <input type="number" id="monsterCHA" name="charisma" value="10" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white text-center focus:outline-none focus:border-primary">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Monster Portrait -->
                    <div id="monsterPortraitSection" class="bg-gray-800/50 rounded-lg p-4">
                        <h4 class="text-white font-bold mb-3">Monster Portrait</h4>
                        <div class="flex flex-col items-center space-y-3">
                            <div id="monsterPortraitDisplay" class="w-24 h-24 bg-gray-700 rounded-lg flex items-center justify-center">
                                <svg class="w-12 h-12 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <input type="file" id="monsterPortraitInput" accept="image/*" class="hidden">
                            <button type="button" onclick="document.getElementById('monsterPortraitInput').click()" 
                                class="w-full px-3 py-2 bg-primary/20 hover:bg-primary/30 text-primary text-sm rounded-lg transition">
                                Choose Image
                            </button>
                            <button type="button" onclick="uploadMonsterPortrait()" 
                                class="w-full px-3 py-2 bg-primary hover:bg-primary/80 text-white text-sm rounded-lg transition">
                                Upload
                            </button>
                            <p id="monsterPortraitStatus" class="text-xs text-center"></p>
                        </div>
                    </div>
                    
                    <!-- Status Effects (edit mode only) -->
                    <div id="monsterStatusSection" class="bg-gray-800/50 rounded-lg p-4 hidden">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-white font-bold flex items-center">
                                <svg class="w-5 h-5 text-primary mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                Status Effects
                            </h4>
                            <button type="button" onclick="showAddMonsterStatusInModal()" class="px-3 py-1 bg-primary/20 hover:bg-primary/30 text-primary text-sm rounded transition">
                                Add Status
                            </button>
                        </div>
                        <div id="monsterStatusList" class="space-y-2">
                            <p class="text-gray-500 text-sm text-center py-4">No status effects</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex gap-3 mt-6 pt-6 border-t border-gray-800">
                <button type="submit" class="flex-1 bg-primary hover:bg-primary-dark text-white font-bold py-3 px-6 rounded-lg transition">
                    Save Monster
                </button>
                <button type="button" onclick="hideMonsterModal()" class="px-6 bg-gray-800 hover:bg-gray-700 text-white font-bold py-3 rounded-lg transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.sort-btn {
    padding: 0.5rem 1rem;
    background: transparent;
    border: 1px solid #374151;
    color: #9ca3af;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    transition: all 0.2s;
}

.sort-btn:hover {
    border-color: #f97316;
    color: #f97316;
}

.sort-btn.active {
    background: #f97316;
    border-color: #f97316;
    color: white;
}
</style>

<script>
let currentSort = 'name';

function showCreateModal() {
    document.getElementById('modalTitle').textContent = 'Create Monster';
    document.getElementById('monsterForm').reset();
    document.getElementById('monsterId').value = '';
    document.getElementById('monsterStatusSection').classList.add('hidden');
    
    // Reset portrait display
    document.getElementById('monsterPortraitDisplay').innerHTML = `
        <svg class="w-12 h-12 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
        </svg>`;
    document.getElementById('monsterPortraitStatus').textContent = '';
    
    document.getElementById('monsterModal').classList.remove('hidden');
}

function hideMonsterModal() {
    document.getElementById('monsterModal').classList.add('hidden');
}

async function saveMonster(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const monsterId = document.getElementById('monsterId').value;
    
    formData.append('action', monsterId ? 'update_monster' : 'create_monster');
    if (monsterId) {
        formData.append('id', monsterId);
    }
    
    // Set current_hp to max_hp for new monsters
    if (!monsterId) {
        formData.append('current_hp', formData.get('max_hp'));
    }
    
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
            location.reload();
        } else {
            alert('Failed to save monster: ' + (result.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to save monster');
    }
}

async function editMonster(id) {
    try {
        const response = await fetch(`/admin/api.php?action=get_monster&id=${id}`);
        const result = await response.json();
        
        if (result.success && result.monster) {
            const m = result.monster;
            
            document.getElementById('modalTitle').textContent = 'Edit Monster';
            document.getElementById('monsterId').value = m.id;
            document.getElementById('monsterName').value = m.name;
            document.getElementById('monsterType').value = m.type || '';
            document.getElementById('monsterCR').value = m.challenge_rating || '';
            document.getElementById('monsterAC').value = m.armor_class;
            document.getElementById('monsterHP').value = m.max_hp;
            document.getElementById('monsterDesc').value = m.description || '';
            document.getElementById('monsterAttacks').value = m.attacks || '';
            document.getElementById('monsterSTR').value = m.strength;
            document.getElementById('monsterDEX').value = m.dexterity;
            document.getElementById('monsterCON').value = m.constitution;
            document.getElementById('monsterINT').value = m.intelligence;
            document.getElementById('monsterWIS').value = m.wisdom;
            document.getElementById('monsterCHA').value = m.charisma;
            
            // Show status section for edit mode
            document.getElementById('monsterStatusSection').classList.remove('hidden');
            
            // Load and display portrait if exists
            const portraitDisplay = document.getElementById('monsterPortraitDisplay');
            if (m.image_url) {
                portraitDisplay.innerHTML = `<img src="${m.image_url}" alt="Monster" class="w-24 h-24 object-cover rounded-lg">`;
            } else {
                portraitDisplay.innerHTML = `
                    <svg class="w-12 h-12 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>`;
            }
            
            // Load status effects
            loadMonsterStatusEffects(m.id);
            
            document.getElementById('monsterModal').classList.remove('hidden');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to load monster');
    }
}

async function deleteMonster(id, name) {
    if (!confirm(`Delete "${name}"? This cannot be undone.`)) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_monster');
    formData.append('id', id);
    
    try {
        const response = await fetch('/admin/api.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            location.reload();
        } else {
            alert('Failed to delete monster');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to delete monster');
    }
}

function sortMonsters(type) {
    currentSort = type;
    
    // Update button states
    document.querySelectorAll('.sort-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelector(`[data-sort="${type}"]`).classList.add('active');
    
    const grid = document.getElementById('monsterGrid');
    const cards = Array.from(grid.querySelectorAll('.monster-card'));
    
    cards.sort((a, b) => {
        if (type === 'name') {
            return a.dataset.name.localeCompare(b.dataset.name);
        } else if (type === 'cr') {
            const crA = parseCR(a.dataset.cr);
            const crB = parseCR(b.dataset.cr);
            return crB - crA; // Descending
        }
        return 0;
    });
    
    cards.forEach(card => grid.appendChild(card));
}

function parseCR(cr) {
    if (cr.includes('/')) {
        const parts = cr.split('/');
        return parseFloat(parts[0]) / parseFloat(parts[1]);
    }
    return parseFloat(cr) || 0;
}

function filterMonsters() {
    const input = document.getElementById('searchInput').value.toLowerCase();
    const cards = document.querySelectorAll('.monster-card');
    
    cards.forEach(card => {
        const name = card.dataset.name;
        if (input === '' || name.startsWith(input)) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}

// Close modal on outside click
document.getElementById('monsterModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        hideMonsterModal();
    }
});

// Monster Status Effects Management
async function loadMonsterStatusEffects(monsterId) {
    try {
        const response = await fetch(`/admin/api.php?action=get_monster_status_effects&monster_id=${monsterId}`);
        
        // Check if response is ok
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
        
        const container = document.getElementById('monsterStatusList');
        if (!container) return;
        
        if (result.success && result.statuses && result.statuses.length > 0) {
            container.innerHTML = result.statuses.map(status => `
                <div class="flex items-start space-x-4 p-3 bg-gray-700/50 rounded-lg border-l-4 border-yellow-500">
                    <div class="flex-1">
                        <h5 class="text-white font-bold text-sm">${escapeHtml(status.status_name)}</h5>
                        ${status.description ? `<p class="text-xs text-gray-400 mt-1">${escapeHtml(status.description)}</p>` : ''}
                    </div>
                    <button onclick="deleteMonsterStatus(${status.id})" class="text-red-400 hover:text-red-300">
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

function showAddMonsterStatusInModal() {
    const monsterId = document.getElementById('monsterId').value;
    if (!monsterId) {
        alert('Please save the monster first before adding statuses');
        return;
    }
    
    const statusName = prompt('Status name (e.g., Poisoned, Frightened):');
    if (!statusName) return;
    
    const description = prompt('Description/notes (optional):');
    
    addMonsterStatusInModal(monsterId, statusName, description);
}

async function addMonsterStatusInModal(monsterId, statusName, description) {
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
            loadMonsterStatusEffects(monsterId);
        } else {
            alert('Failed to add status');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to add status');
    }
}

async function deleteMonsterStatus(statusId) {
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
            const monsterId = document.getElementById('monsterId').value;
            loadMonsterStatusEffects(monsterId);
        } else {
            alert('Failed to delete status');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to delete status');
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Monster portrait upload
async function uploadMonsterPortrait() {
    const fileInput = document.getElementById('monsterPortraitInput');
    const statusEl = document.getElementById('monsterPortraitStatus');
    const monsterId = document.getElementById('monsterId').value;
    
    if (!monsterId) {
        statusEl.textContent = 'Please save the monster first';
        statusEl.className = 'text-xs text-center text-red-400';
        return;
    }
    
    if (!fileInput.files || !fileInput.files[0]) {
        statusEl.textContent = 'Please choose an image first';
        statusEl.className = 'text-xs text-center text-red-400';
        return;
    }
    
    const file = fileInput.files[0];
    
    if (file.size > 5 * 1024 * 1024) {
        statusEl.textContent = 'File too large (max 5MB)';
        statusEl.className = 'text-xs text-center text-red-400';
        return;
    }
    
    statusEl.textContent = 'Uploading...';
    statusEl.className = 'text-xs text-center text-yellow-400';
    
    const formData = new FormData();
    formData.append('image', file);
    formData.append('entity_type', 'monster');
    formData.append('entity_id', monsterId);
    
    try {
        const response = await fetch('/admin/upload_image.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            statusEl.textContent = 'Uploaded!';
            statusEl.className = 'text-xs text-center text-green-400';
            
            const displayEl = document.getElementById('monsterPortraitDisplay');
            displayEl.innerHTML = `<img src="${result.image_url}" alt="Monster" class="w-24 h-24 object-cover rounded-lg">`;
            
            setTimeout(() => location.reload(), 1000);
        } else {
            statusEl.textContent = result.message;
            statusEl.className = 'text-xs text-center text-red-400';
        }
    } catch (error) {
        console.error('Upload error:', error);
        statusEl.textContent = 'Upload failed';
        statusEl.className = 'text-xs text-center text-red-400';
    }
}
</script>
