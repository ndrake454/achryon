<?php
// Get all players and their assigned characters
$players_query = "
    SELECT 
        p.id as player_id,
        p.display_name,
        u.username,
        u.email,
        GROUP_CONCAT(c.id) as character_ids,
        GROUP_CONCAT(c.name SEPARATOR '|') as character_names,
        GROUP_CONCAT(CONCAT(c.race, ' ', c.class) SEPARATOR '|') as character_info
    FROM players p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN characters c ON p.id = c.player_id
    GROUP BY p.id
    ORDER BY p.display_name
";

$players = $conn->query($players_query);

// Get unassigned characters
$unassigned = $conn->query("SELECT id, name, race, class FROM characters WHERE player_id IS NULL");
?>

<div class="max-w-6xl mx-auto">
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-2xl font-bold text-white flex items-center">
                    <svg class="w-8 h-8 text-primary mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    Player Character Assignments
                </h2>
                <p class="text-gray-400 ml-11">Assign characters to players or manage existing assignments</p>
            </div>
        </div>
    </div>

    <!-- Player List -->
    <div class="space-y-4">
        <?php while ($player = $players->fetch_assoc()): 
            $character_ids = $player['character_ids'] ? explode(',', $player['character_ids']) : [];
            $character_names = $player['character_names'] ? explode('|', $player['character_names']) : [];
            $character_info = $player['character_info'] ? explode('|', $player['character_info']) : [];
        ?>
        <div class="bg-gray-900/50 border border-gray-800 rounded-lg p-6 hover:border-primary/50 transition">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex-1">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <h3 class="text-xl font-bold text-white"><?php echo htmlspecialchars($player['display_name'] ?: $player['username']); ?></h3>
                            <p class="text-sm text-gray-500 font-mono"><?php echo htmlspecialchars($player['email']); ?></p>
                        </div>
                        <button 
                            onclick="showAssignModal(<?php echo $player['player_id']; ?>, '<?php echo htmlspecialchars($player['display_name'] ?: $player['username']); ?>')"
                            class="text-primary hover:text-primary-dark transition"
                            title="Assign character"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                            </svg>
                        </button>
                    </div>
                    
                    <?php if (count($character_names) > 0): ?>
                    <div class="space-y-2">
                        <?php for ($i = 0; $i < count($character_names); $i++): ?>
                        <div class="flex items-center justify-between bg-gray-800/50 rounded-lg p-3">
                            <div class="flex items-center space-x-3">
                                <div class="w-2 h-2 bg-primary rounded-full"></div>
                                <div>
                                    <p class="text-white font-medium"><?php echo htmlspecialchars($character_names[$i]); ?></p>
                                    <p class="text-sm text-gray-400"><?php echo htmlspecialchars($character_info[$i]); ?></p>
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <button 
                                    onclick="location.href='../character.php?id=<?php echo $character_ids[$i]; ?>'"
                                    class="text-gray-400 hover:text-white transition p-2"
                                    title="Preview"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                                <button 
                                    onclick="unassignCharacter(<?php echo $character_ids[$i]; ?>)"
                                    class="text-red-400 hover:text-red-300 transition p-2"
                                    title="Unassign"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-gray-500 italic flex items-center py-2">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                        No characters assigned
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<!-- Assign Character Modal -->
<div id="assignModal" class="hidden fixed inset-0 bg-black/75 backdrop-blur-sm flex items-center justify-center p-4 z-50">
    <div class="bg-gray-900 border border-gray-800 rounded-xl shadow-2xl w-full max-w-md">
        <div class="p-6 border-b border-gray-800">
            <h3 class="text-xl font-bold text-white">Assign Character</h3>
            <p class="text-gray-400 text-sm mt-1">Select a character to assign to <span id="playerName" class="text-primary"></span></p>
        </div>
        <div class="p-6">
            <div id="characterList" class="space-y-2 max-h-96 overflow-y-auto">
                <!-- Characters will be loaded here -->
            </div>
        </div>
        <div class="p-6 border-t border-gray-800 flex justify-end space-x-3">
            <button onclick="hideAssignModal()" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-white rounded-lg transition">
                Cancel
            </button>
        </div>
    </div>
</div>

<script>
let currentPlayerId = null;

function showAssignModal(playerId, playerName) {
    currentPlayerId = playerId;
    document.getElementById('playerName').textContent = playerName;
    loadAvailableCharacters();
    document.getElementById('assignModal').classList.remove('hidden');
}

function hideAssignModal() {
    document.getElementById('assignModal').classList.add('hidden');
}

async function loadAvailableCharacters() {
    try {
        const response = await fetch('/admin/api.php?action=get_unassigned_characters');
        const result = await response.json();
        
        const list = document.getElementById('characterList');
        
        if (result.success && result.characters.length > 0) {
            list.innerHTML = result.characters.map(char => `
                <button 
                    onclick="assignCharacter(${char.id})"
                    class="w-full text-left p-4 bg-gray-800 hover:bg-gray-700 border border-gray-700 hover:border-primary rounded-lg transition"
                >
                    <p class="text-white font-medium">${char.name}</p>
                    <p class="text-sm text-gray-400">Level ${char.level} ${char.race} ${char.class}</p>
                </button>
            `).join('');
        } else {
            list.innerHTML = '<p class="text-gray-500 text-center py-8">No unassigned characters available</p>';
        }
    } catch (error) {
        console.error('Error loading characters:', error);
    }
}

async function assignCharacter(characterId) {
    const formData = new FormData();
    formData.append('action', 'assign_character');
    formData.append('character_id', characterId);
    formData.append('player_id', currentPlayerId);
    
    try {
        const response = await fetch('/admin/api.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            location.reload();
        } else {
            alert('Failed to assign character');
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

async function unassignCharacter(characterId) {
    if (!confirm('Unassign this character?')) return;
    
    const formData = new FormData();
    formData.append('action', 'unassign_character');
    formData.append('character_id', characterId);
    
    try {
        const response = await fetch('/admin/api.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            location.reload();
        } else {
            alert('Failed to unassign character');
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Close modal on outside click
document.getElementById('assignModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        hideAssignModal();
    }
});
</script>
