<?php
// Get all items
$items = $conn->query("SELECT * FROM items ORDER BY name");
?>

<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-white flex items-center">
                <svg class="w-8 h-8 text-primary mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                Items
            </h2>
            <p class="text-gray-400 ml-11">Create and manage items for your campaign</p>
        </div>
        <button onclick="showCreateModal()" class="bg-primary hover:bg-primary-dark text-white font-bold py-3 px-6 rounded-lg transition flex items-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Create Item</span>
        </button>
    </div>

    <!-- Filters -->
    <div class="bg-gray-900/50 border border-gray-800 rounded-lg p-4 mb-6">
        <div class="flex flex-wrap gap-4 items-center">
            <div class="flex items-center space-x-2">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                <span class="text-gray-400 text-sm font-medium">Filter:</span>
            </div>
            <button onclick="filterItems('all')" class="filter-btn active" data-filter="all">
                All Items
            </button>
            <button onclick="filterItems('weapon')" class="filter-btn" data-filter="weapon">
                Weapons
            </button>
            <button onclick="filterItems('armor')" class="filter-btn" data-filter="armor">
                Armor
            </button>
            <button onclick="filterItems('potion')" class="filter-btn" data-filter="potion">
                Potions
            </button>
            <button onclick="filterItems('magic')" class="filter-btn" data-filter="magic">
                Magic Items
            </button>
            <button onclick="filterItems('misc')" class="filter-btn" data-filter="misc">
                Miscellaneous
            </button>
            <div class="flex-1"></div>
            <input 
                type="text" 
                id="searchInput" 
                onkeyup="searchItems()" 
                placeholder="Search items..." 
                class="px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-primary w-64"
            >
        </div>
    </div>

    <!-- Items Grid -->
    <div id="itemsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php while ($item = $items->fetch_assoc()): ?>
        <div class="item-card bg-gray-900/50 border border-gray-800 rounded-lg p-5 hover:border-primary/50 transition" 
             data-name="<?php echo strtolower($item['name']); ?>" 
             data-type="<?php echo strtolower($item['type'] ?? 'misc'); ?>"
             data-rarity="<?php echo strtolower($item['rarity'] ?? 'common'); ?>">
            
            <!-- Item Header -->
            <div class="flex items-start justify-between mb-3">
                <div class="flex-1">
                    <h3 class="text-lg font-bold text-white mb-1"><?php echo htmlspecialchars($item['name']); ?></h3>
                    <div class="flex items-center gap-2 flex-wrap">
                        <?php if ($item['type']): ?>
                        <span class="text-xs px-2 py-1 bg-gray-800 text-gray-400 rounded"><?php echo htmlspecialchars($item['type']); ?></span>
                        <?php endif; ?>
                        <?php if ($item['rarity']): 
                            $rarityColors = [
                                'common' => 'bg-gray-700 text-gray-300',
                                'uncommon' => 'bg-green-900/50 text-green-400',
                                'rare' => 'bg-blue-900/50 text-blue-400',
                                'very rare' => 'bg-purple-900/50 text-purple-400',
                                'legendary' => 'bg-orange-900/50 text-orange-400',
                                'artifact' => 'bg-red-900/50 text-red-400'
                            ];
                            $rarityColor = $rarityColors[strtolower($item['rarity'])] ?? 'bg-gray-700 text-gray-300';
                        ?>
                        <span class="text-xs px-2 py-1 <?php echo $rarityColor; ?> rounded font-medium">
                            <?php echo htmlspecialchars($item['rarity']); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Item Icon -->
                <div class="ml-3">
                    <?php
                    $type = strtolower($item['type'] ?? '');
                    $icon = 'cube'; // default
                    if (strpos($type, 'weapon') !== false) $icon = 'sword';
                    elseif (strpos($type, 'armor') !== false) $icon = 'shield';
                    elseif (strpos($type, 'potion') !== false) $icon = 'beaker';
                    elseif (strpos($type, 'magic') !== false) $icon = 'sparkles';
                    ?>
                    <div class="w-12 h-12 bg-primary/20 rounded-lg border border-primary/50 flex items-center justify-center">
                        <?php if ($icon === 'sword'): ?>
                        <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"/>
                        </svg>
                        <?php elseif ($icon === 'shield'): ?>
                        <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        <?php elseif ($icon === 'beaker'): ?>
                        <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                        </svg>
                        <?php elseif ($icon === 'sparkles'): ?>
                        <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                        </svg>
                        <?php else: ?>
                        <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Description -->
            <?php if ($item['description']): ?>
            <p class="text-sm text-gray-400 mb-3 line-clamp-2"><?php echo htmlspecialchars($item['description']); ?></p>
            <?php endif; ?>

            <!-- Properties -->
            <?php if ($item['properties']): ?>
            <div class="mb-3 text-xs text-gray-500">
                <span class="font-medium text-gray-400">Properties:</span> <?php echo htmlspecialchars($item['properties']); ?>
            </div>
            <?php endif; ?>

            <!-- Actions -->
            <div class="flex gap-2 pt-3 border-t border-gray-800">
                <button onclick="editItem(<?php echo $item['id']; ?>)" class="flex-1 flex items-center justify-center space-x-1 px-3 py-2 bg-gray-800 hover:bg-gray-700 text-white rounded-lg transition text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <span>Edit</span>
                </button>
                <button onclick="duplicateItem(<?php echo $item['id']; ?>)" class="px-3 py-2 bg-gray-800 hover:bg-gray-700 text-gray-400 hover:text-white rounded-lg transition text-sm" title="Duplicate">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </button>
                <button onclick="deleteItem(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name'], ENT_QUOTES); ?>')" class="px-3 py-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 rounded-lg transition text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </div>
        </div>
        <?php endwhile; ?>
    </div>

    <?php if ($items->num_rows === 0): ?>
    <div class="text-center py-20">
        <svg class="w-20 h-20 text-gray-700 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
        </svg>
        <h3 class="text-xl font-bold text-gray-600 mb-2">No Items Yet</h3>
        <p class="text-gray-500 mb-4">Create your first item to get started</p>
        <button onclick="showCreateModal()" class="bg-primary hover:bg-primary-dark text-white font-bold py-2 px-6 rounded-lg transition">
            Create Item
        </button>
    </div>
    <?php endif; ?>
</div>

<!-- Create/Edit Item Modal -->
<div id="itemModal" class="hidden fixed inset-0 bg-black/75 backdrop-blur-sm flex items-center justify-center p-4 z-50 overflow-y-auto">
    <div class="bg-gray-900 border border-gray-800 rounded-xl shadow-2xl w-full max-w-3xl my-8">
        <div class="sticky top-0 bg-gray-900 p-6 border-b border-gray-800 rounded-t-xl">
            <h3 id="modalTitle" class="text-2xl font-bold text-white">Create Item</h3>
        </div>
        <form id="itemForm" onsubmit="saveItem(event)" class="p-6">
            <input type="hidden" id="itemId" name="id">
            
            <div class="space-y-5">
                <!-- Item Name -->
                <div>
                    <label class="block text-gray-300 mb-2 text-sm font-medium">Item Name *</label>
                    <input type="text" id="itemName" name="name" required class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary" placeholder="e.g., Longsword, Healing Potion">
                </div>

                <!-- Type and Rarity -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-300 mb-2 text-sm font-medium">Type</label>
                        <select id="itemType" name="type" class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                            <option value="">Select type...</option>
                            <option value="Weapon">Weapon</option>
                            <option value="Armor">Armor</option>
                            <option value="Potion">Potion</option>
                            <option value="Magic Item">Magic Item</option>
                            <option value="Adventuring Gear">Adventuring Gear</option>
                            <option value="Tool">Tool</option>
                            <option value="Treasure">Treasure</option>
                            <option value="Miscellaneous">Miscellaneous</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 mb-2 text-sm font-medium">Rarity</label>
                        <select id="itemRarity" name="rarity" class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                            <option value="Common">Common</option>
                            <option value="Uncommon">Uncommon</option>
                            <option value="Rare">Rare</option>
                            <option value="Very Rare">Very Rare</option>
                            <option value="Legendary">Legendary</option>
                            <option value="Artifact">Artifact</option>
                        </select>
                    </div>
                </div>

                <!-- Properties -->
                <div>
                    <label class="block text-gray-300 mb-2 text-sm font-medium">Properties</label>
                    <input type="text" id="itemProperties" name="properties" class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary" placeholder="e.g., Versatile (1d10), Light, Finesse">
                    <p class="text-xs text-gray-500 mt-1">Damage dice, range, weight, etc.</p>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-gray-300 mb-2 text-sm font-medium">Description</label>
                    <textarea id="itemDescription" name="description" rows="5" class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary resize-none" placeholder="Item description, effects, lore..."></textarea>
                </div>
            </div>

            <div class="flex gap-3 mt-6 pt-6 border-t border-gray-800">
                <button type="submit" class="flex-1 bg-primary hover:bg-primary-dark text-white font-bold py-3 px-6 rounded-lg transition">
                    Save Item
                </button>
                <button type="button" onclick="hideItemModal()" class="px-6 bg-gray-800 hover:bg-gray-700 text-white font-bold py-3 rounded-lg transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.filter-btn {
    padding: 0.5rem 1rem;
    background: transparent;
    border: 1px solid #374151;
    color: #9ca3af;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    transition: all 0.2s;
    white-space: nowrap;
}

.filter-btn:hover {
    border-color: #f97316;
    color: #f97316;
}

.filter-btn.active {
    background: #f97316;
    border-color: #f97316;
    color: white;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<script>
let currentFilter = 'all';

function showCreateModal() {
    document.getElementById('modalTitle').textContent = 'Create Item';
    document.getElementById('itemForm').reset();
    document.getElementById('itemId').value = '';
    document.getElementById('itemModal').classList.remove('hidden');
}

function hideItemModal() {
    document.getElementById('itemModal').classList.add('hidden');
}

async function saveItem(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const itemId = document.getElementById('itemId').value;
    
    formData.append('action', itemId ? 'update_item' : 'create_item');
    if (itemId) {
        formData.append('id', itemId);
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
            alert('Failed to save item: ' + (result.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to save item');
    }
}

async function editItem(id) {
    try {
        const response = await fetch(`/admin/api.php?action=get_item&id=${id}`);
        const result = await response.json();
        
        if (result.success && result.item) {
            const item = result.item;
            
            document.getElementById('modalTitle').textContent = 'Edit Item';
            document.getElementById('itemId').value = item.id;
            document.getElementById('itemName').value = item.name;
            document.getElementById('itemType').value = item.type || '';
            document.getElementById('itemRarity').value = item.rarity || 'Common';
            document.getElementById('itemProperties').value = item.properties || '';
            document.getElementById('itemDescription').value = item.description || '';
            
            document.getElementById('itemModal').classList.remove('hidden');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to load item');
    }
}

async function duplicateItem(id) {
    try {
        const response = await fetch(`/admin/api.php?action=get_item&id=${id}`);
        const result = await response.json();
        
        if (result.success && result.item) {
            const item = result.item;
            
            document.getElementById('modalTitle').textContent = 'Create Item (Copy)';
            document.getElementById('itemId').value = '';
            document.getElementById('itemName').value = item.name + ' (Copy)';
            document.getElementById('itemType').value = item.type || '';
            document.getElementById('itemRarity').value = item.rarity || 'Common';
            document.getElementById('itemProperties').value = item.properties || '';
            document.getElementById('itemDescription').value = item.description || '';
            
            document.getElementById('itemModal').classList.remove('hidden');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to duplicate item');
    }
}

async function deleteItem(id, name) {
    if (!confirm(`Delete "${name}"? This cannot be undone.`)) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_item');
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
            alert('Failed to delete item');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to delete item');
    }
}

function filterItems(type) {
    currentFilter = type;
    
    // Update button states
    document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelector(`[data-filter="${type}"]`).classList.add('active');
    
    const cards = document.querySelectorAll('.item-card');
    
    cards.forEach(card => {
        const cardType = card.dataset.type;
        
        if (type === 'all') {
            card.style.display = '';
        } else {
            card.style.display = cardType.includes(type) ? '' : 'none';
        }
    });
}

function searchItems() {
    const input = document.getElementById('searchInput').value.toLowerCase();
    const cards = document.querySelectorAll('.item-card');
    
    cards.forEach(card => {
        const name = card.dataset.name;
        const matchesSearch = name.includes(input);
        const matchesFilter = currentFilter === 'all' || card.dataset.type.includes(currentFilter);
        
        card.style.display = (matchesSearch && matchesFilter) ? '' : 'none';
    });
}

// Close modal on outside click
document.getElementById('itemModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        hideItemModal();
    }
});
</script>
