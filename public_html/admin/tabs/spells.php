<?php
// Get all spells
$spells = $conn->query("SELECT * FROM spells ORDER BY level, name");
?>

<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-white flex items-center">
                <svg class="w-8 h-8 text-primary mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                </svg>
                Spells
            </h2>
            <p class="text-gray-400 ml-11">Create and manage spells for your campaign</p>
        </div>
        <button onclick="showCreateModal()" class="bg-primary hover:bg-primary-dark text-white font-bold py-3 px-6 rounded-lg transition flex items-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Create Spell</span>
        </button>
    </div>

    <!-- Filters -->
    <div class="bg-gray-900/50 border border-gray-800 rounded-lg p-4 mb-6">
        <div class="flex flex-wrap gap-4 items-center">
            <div class="flex items-center space-x-2">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                <span class="text-gray-400 text-sm font-medium">Level:</span>
            </div>
            <button onclick="filterSpells('all')" class="filter-btn active" data-filter="all">All</button>
            <button onclick="filterSpells('0')" class="filter-btn" data-filter="0">Cantrip</button>
            <button onclick="filterSpells('1')" class="filter-btn" data-filter="1">1st</button>
            <button onclick="filterSpells('2')" class="filter-btn" data-filter="2">2nd</button>
            <button onclick="filterSpells('3')" class="filter-btn" data-filter="3">3rd</button>
            <button onclick="filterSpells('4')" class="filter-btn" data-filter="4">4th</button>
            <button onclick="filterSpells('5')" class="filter-btn" data-filter="5">5th</button>
            <button onclick="filterSpells('6')" class="filter-btn" data-filter="6">6th</button>
            <button onclick="filterSpells('7')" class="filter-btn" data-filter="7">7th</button>
            <button onclick="filterSpells('8')" class="filter-btn" data-filter="8">8th</button>
            <button onclick="filterSpells('9')" class="filter-btn" data-filter="9">9th</button>
        </div>
        <div class="flex flex-wrap gap-4 items-center mt-3 pt-3 border-t border-gray-800">
            <div class="flex items-center space-x-2">
                <span class="text-gray-400 text-sm font-medium">School:</span>
            </div>
            <button onclick="filterBySchool('all')" class="school-btn active" data-school="all">All</button>
            <button onclick="filterBySchool('abjuration')" class="school-btn" data-school="abjuration">Abjuration</button>
            <button onclick="filterBySchool('conjuration')" class="school-btn" data-school="conjuration">Conjuration</button>
            <button onclick="filterBySchool('divination')" class="school-btn" data-school="divination">Divination</button>
            <button onclick="filterBySchool('enchantment')" class="school-btn" data-school="enchantment">Enchantment</button>
            <button onclick="filterBySchool('evocation')" class="school-btn" data-school="evocation">Evocation</button>
            <button onclick="filterBySchool('illusion')" class="school-btn" data-school="illusion">Illusion</button>
            <button onclick="filterBySchool('necromancy')" class="school-btn" data-school="necromancy">Necromancy</button>
            <button onclick="filterBySchool('transmutation')" class="school-btn" data-school="transmutation">Transmutation</button>
            <div class="flex-1"></div>
            <input 
                type="text" 
                id="searchInput" 
                onkeyup="searchSpells()" 
                placeholder="Search spells..." 
                class="px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-primary w-64"
            >
        </div>
    </div>

    <!-- Spells Grid -->
    <div id="spellsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php while ($spell = $spells->fetch_assoc()): ?>
        <div class="spell-card bg-gray-900/50 border border-gray-800 rounded-lg p-5 hover:border-primary/50 transition" 
             data-name="<?php echo strtolower($spell['name']); ?>" 
             data-level="<?php echo $spell['level']; ?>"
             data-school="<?php echo strtolower($spell['school'] ?? ''); ?>">
            
            <!-- Spell Header -->
            <div class="flex items-start justify-between mb-3">
                <div class="flex-1">
                    <h3 class="text-lg font-bold text-white mb-1"><?php echo htmlspecialchars($spell['name']); ?></h3>
                    <div class="flex items-center gap-2 flex-wrap">
                        <?php 
                        $levelColors = [
                            0 => 'bg-gray-700 text-gray-300',
                            1 => 'bg-blue-900/50 text-blue-400',
                            2 => 'bg-blue-800/50 text-blue-300',
                            3 => 'bg-purple-900/50 text-purple-400',
                            4 => 'bg-purple-800/50 text-purple-300',
                            5 => 'bg-pink-900/50 text-pink-400',
                            6 => 'bg-pink-800/50 text-pink-300',
                            7 => 'bg-orange-900/50 text-orange-400',
                            8 => 'bg-orange-800/50 text-orange-300',
                            9 => 'bg-red-900/50 text-red-400'
                        ];
                        $levelColor = $levelColors[$spell['level']] ?? 'bg-gray-700 text-gray-300';
                        $levelText = $spell['level'] == 0 ? 'Cantrip' : ($spell['level'] == 1 ? '1st Level' : ($spell['level'] == 2 ? '2nd Level' : ($spell['level'] == 3 ? '3rd Level' : $spell['level'] . 'th Level')));
                        ?>
                        <span class="text-xs px-2 py-1 <?php echo $levelColor; ?> rounded font-medium">
                            <?php echo $levelText; ?>
                        </span>
                        <?php if ($spell['school']): ?>
                        <span class="text-xs px-2 py-1 bg-gray-800 text-gray-400 rounded">
                            <?php echo htmlspecialchars($spell['school']); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Spell Icon -->
                <div class="ml-3">
                    <div class="w-12 h-12 bg-primary/20 rounded-lg border border-primary/50 flex items-center justify-center">
                        <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Spell Details -->
            <div class="space-y-2 mb-3 text-sm">
                <?php if ($spell['casting_time']): ?>
                <div class="flex items-start">
                    <svg class="w-4 h-4 text-gray-500 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-gray-400"><?php echo htmlspecialchars($spell['casting_time']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($spell['range_area']): ?>
                <div class="flex items-start">
                    <svg class="w-4 h-4 text-gray-500 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span class="text-gray-400"><?php echo htmlspecialchars($spell['range_area']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($spell['components']): ?>
                <div class="flex items-start">
                    <svg class="w-4 h-4 text-gray-500 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                    </svg>
                    <span class="text-gray-400"><?php echo htmlspecialchars($spell['components']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($spell['duration']): ?>
                <div class="flex items-start">
                    <svg class="w-4 h-4 text-gray-500 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <span class="text-gray-400"><?php echo htmlspecialchars($spell['duration']); ?></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Description -->
            <?php if ($spell['description']): ?>
            <p class="text-sm text-gray-400 mb-3 line-clamp-3"><?php echo htmlspecialchars($spell['description']); ?></p>
            <?php endif; ?>

            <!-- Actions -->
            <div class="flex gap-2 pt-3 border-t border-gray-800">
                <button onclick="editSpell(<?php echo $spell['id']; ?>)" class="flex-1 flex items-center justify-center space-x-1 px-3 py-2 bg-gray-800 hover:bg-gray-700 text-white rounded-lg transition text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <span>Edit</span>
                </button>
                <button onclick="duplicateSpell(<?php echo $spell['id']; ?>)" class="px-3 py-2 bg-gray-800 hover:bg-gray-700 text-gray-400 hover:text-white rounded-lg transition text-sm" title="Duplicate">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </button>
                <button onclick="deleteSpell(<?php echo $spell['id']; ?>, '<?php echo htmlspecialchars($spell['name'], ENT_QUOTES); ?>')" class="px-3 py-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 rounded-lg transition text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </div>
        </div>
        <?php endwhile; ?>
    </div>

    <?php if ($spells->num_rows === 0): ?>
    <div class="text-center py-20">
        <svg class="w-20 h-20 text-gray-700 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
        </svg>
        <h3 class="text-xl font-bold text-gray-600 mb-2">No Spells Yet</h3>
        <p class="text-gray-500 mb-4">Create your first spell to get started</p>
        <button onclick="showCreateModal()" class="bg-primary hover:bg-primary-dark text-white font-bold py-2 px-6 rounded-lg transition">
            Create Spell
        </button>
    </div>
    <?php endif; ?>
</div>

<!-- Create/Edit Spell Modal -->
<div id="spellModal" class="hidden fixed inset-0 bg-black/75 backdrop-blur-sm flex items-center justify-center p-4 z-50 overflow-y-auto">
    <div class="bg-gray-900 border border-gray-800 rounded-xl shadow-2xl w-full max-w-3xl my-8">
        <div class="sticky top-0 bg-gray-900 p-6 border-b border-gray-800 rounded-t-xl">
            <h3 id="modalTitle" class="text-2xl font-bold text-white">Create Spell</h3>
        </div>
        <form id="spellForm" onsubmit="saveSpell(event)" class="p-6">
            <input type="hidden" id="spellId" name="id">
            
            <div class="space-y-5">
                <!-- Spell Name -->
                <div>
                    <label class="block text-gray-300 mb-2 text-sm font-medium">Spell Name *</label>
                    <input type="text" id="spellName" name="name" required class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary" placeholder="e.g., Fireball, Cure Wounds">
                </div>

                <!-- Level and School -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-300 mb-2 text-sm font-medium">Level *</label>
                        <select id="spellLevel" name="level" required class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
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
                        <label class="block text-gray-300 mb-2 text-sm font-medium">School</label>
                        <select id="spellSchool" name="school" class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                            <option value="">Select school...</option>
                            <option value="Abjuration">Abjuration</option>
                            <option value="Conjuration">Conjuration</option>
                            <option value="Divination">Divination</option>
                            <option value="Enchantment">Enchantment</option>
                            <option value="Evocation">Evocation</option>
                            <option value="Illusion">Illusion</option>
                            <option value="Necromancy">Necromancy</option>
                            <option value="Transmutation">Transmutation</option>
                        </select>
                    </div>
                </div>

                <!-- Casting Time and Range -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-300 mb-2 text-sm font-medium">Casting Time</label>
                        <input type="text" id="spellCastingTime" name="casting_time" class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary" placeholder="e.g., 1 action, 1 bonus action">
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 mb-2 text-sm font-medium">Range/Area</label>
                        <input type="text" id="spellRange" name="range_area" class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary" placeholder="e.g., 60 feet, Self (30-foot radius)">
                    </div>
                </div>

                <!-- Components and Duration -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-300 mb-2 text-sm font-medium">Components</label>
                        <input type="text" id="spellComponents" name="components" class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary" placeholder="e.g., V, S, M (a bit of bat fur)">
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 mb-2 text-sm font-medium">Duration</label>
                        <input type="text" id="spellDuration" name="duration" class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary" placeholder="e.g., Instantaneous, Concentration, up to 1 minute">
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-gray-300 mb-2 text-sm font-medium">Description</label>
                    <textarea id="spellDescription" name="description" rows="6" class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary resize-none" placeholder="Spell description, effect, saving throw, damage..."></textarea>
                </div>
            </div>

            <div class="flex gap-3 mt-6 pt-6 border-t border-gray-800">
                <button type="submit" class="flex-1 bg-primary hover:bg-primary-dark text-white font-bold py-3 px-6 rounded-lg transition">
                    Save Spell
                </button>
                <button type="button" onclick="hideSpellModal()" class="px-6 bg-gray-800 hover:bg-gray-700 text-white font-bold py-3 rounded-lg transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.filter-btn, .school-btn {
    padding: 0.5rem 0.75rem;
    background: transparent;
    border: 1px solid #374151;
    color: #9ca3af;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    transition: all 0.2s;
    white-space: nowrap;
}

.filter-btn:hover, .school-btn:hover {
    border-color: #f97316;
    color: #f97316;
}

.filter-btn.active, .school-btn.active {
    background: #f97316;
    border-color: #f97316;
    color: white;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<script>
let currentLevelFilter = 'all';
let currentSchoolFilter = 'all';

function showCreateModal() {
    document.getElementById('modalTitle').textContent = 'Create Spell';
    document.getElementById('spellForm').reset();
    document.getElementById('spellId').value = '';
    document.getElementById('spellModal').classList.remove('hidden');
}

function hideSpellModal() {
    document.getElementById('spellModal').classList.add('hidden');
}

async function saveSpell(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const spellId = document.getElementById('spellId').value;
    
    formData.append('action', spellId ? 'update_spell' : 'create_spell');
    if (spellId) {
        formData.append('id', spellId);
    }
    
    try {
        const response = await fetch('/admin/api.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            location.reload();
        } else {
            alert('Failed to save spell: ' + (result.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to save spell');
    }
}

async function editSpell(id) {
    try {
        const response = await fetch(`/admin/api.php?action=get_spell&id=${id}`);
        const result = await response.json();
        
        if (result.success && result.spell) {
            const spell = result.spell;
            
            document.getElementById('modalTitle').textContent = 'Edit Spell';
            document.getElementById('spellId').value = spell.id;
            document.getElementById('spellName').value = spell.name;
            document.getElementById('spellLevel').value = spell.level;
            document.getElementById('spellSchool').value = spell.school || '';
            document.getElementById('spellCastingTime').value = spell.casting_time || '';
            document.getElementById('spellRange').value = spell.range_area || '';
            document.getElementById('spellComponents').value = spell.components || '';
            document.getElementById('spellDuration').value = spell.duration || '';
            document.getElementById('spellDescription').value = spell.description || '';
            
            document.getElementById('spellModal').classList.remove('hidden');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to load spell');
    }
}

async function duplicateSpell(id) {
    try {
        const response = await fetch(`/admin/api.php?action=get_spell&id=${id}`);
        const result = await response.json();
        
        if (result.success && result.spell) {
            const spell = result.spell;
            
            document.getElementById('modalTitle').textContent = 'Create Spell (Copy)';
            document.getElementById('spellId').value = '';
            document.getElementById('spellName').value = spell.name + ' (Copy)';
            document.getElementById('spellLevel').value = spell.level;
            document.getElementById('spellSchool').value = spell.school || '';
            document.getElementById('spellCastingTime').value = spell.casting_time || '';
            document.getElementById('spellRange').value = spell.range_area || '';
            document.getElementById('spellComponents').value = spell.components || '';
            document.getElementById('spellDuration').value = spell.duration || '';
            document.getElementById('spellDescription').value = spell.description || '';
            
            document.getElementById('spellModal').classList.remove('hidden');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to duplicate spell');
    }
}

async function deleteSpell(id, name) {
    if (!confirm(`Delete "${name}"? This cannot be undone.`)) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_spell');
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
            alert('Failed to delete spell');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to delete spell');
    }
}

function filterSpells(level) {
    currentLevelFilter = level;
    
    // Update button states
    document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelector(`[data-filter="${level}"]`).classList.add('active');
    
    applyFilters();
}

function filterBySchool(school) {
    currentSchoolFilter = school;
    
    // Update button states
    document.querySelectorAll('.school-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelector(`[data-school="${school}"]`).classList.add('active');
    
    applyFilters();
}

function applyFilters() {
    const cards = document.querySelectorAll('.spell-card');
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    
    cards.forEach(card => {
        const cardLevel = card.dataset.level;
        const cardSchool = card.dataset.school;
        const cardName = card.dataset.name;
        
        const matchesLevel = currentLevelFilter === 'all' || cardLevel === currentLevelFilter;
        const matchesSchool = currentSchoolFilter === 'all' || cardSchool === currentSchoolFilter;
        const matchesSearch = searchTerm === '' || cardName.includes(searchTerm);
        
        card.style.display = (matchesLevel && matchesSchool && matchesSearch) ? '' : 'none';
    });
}

function searchSpells() {
    applyFilters();
}

// Close modal on outside click
document.getElementById('spellModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        hideSpellModal();
    }
});
</script>
