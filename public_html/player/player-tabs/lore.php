<?php
// Get visible lore entries
$lore_entries = $conn->query("
    SELECT * FROM lore 
    WHERE visible_to_players = 1 
    ORDER BY order_index, created_at DESC
");

// Get categories for filtering
$categories = $conn->query("
    SELECT DISTINCT category FROM lore 
    WHERE visible_to_players = 1 
    ORDER BY category
");

$selected_category = $_GET['category'] ?? 'all';
?>

<div class="max-w-6xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-white flex items-center">
                <svg class="w-8 h-8 text-primary mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                Campaign Lore
            </h2>
            <p class="text-gray-400 ml-11">Revealed knowledge and discoveries</p>
        </div>
    </div>

    <!-- Category Filter -->
    <?php if ($categories->num_rows > 0): ?>
    <div class="mb-6 flex flex-wrap gap-2">
        <a href="?tab=lore<?php echo $selected_char_id ? '&char='.$selected_char_id : ''; ?>&category=all" 
           class="filter-btn <?php echo $selected_category === 'all' ? 'active' : ''; ?>">
            All
        </a>
        <?php while ($cat = $categories->fetch_assoc()): ?>
        <a href="?tab=lore<?php echo $selected_char_id ? '&char='.$selected_char_id : ''; ?>&category=<?php echo urlencode($cat['category']); ?>" 
           class="filter-btn <?php echo $selected_category === $cat['category'] ? 'active' : ''; ?>">
            <?php echo htmlspecialchars($cat['category']); ?>
        </a>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>

    <!-- Lore Entries -->
    <?php 
    // Reset result pointer and filter
    $lore_entries->data_seek(0);
    $filtered_entries = [];
    while ($entry = $lore_entries->fetch_assoc()) {
        if ($selected_category === 'all' || $entry['category'] === $selected_category) {
            $filtered_entries[] = $entry;
        }
    }
    ?>

    <?php if (count($filtered_entries) === 0): ?>
        <div class="text-center py-20">
            <svg class="w-20 h-20 text-gray-700 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
            <h3 class="text-xl font-bold text-gray-600 mb-2">No Lore Yet</h3>
            <p class="text-gray-500">Your DM hasn't revealed any lore entries yet</p>
            <p class="text-gray-600 text-sm mt-2">Check back as you explore and discover more about the world!</p>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($filtered_entries as $entry): ?>
            <div class="bg-gray-900/50 border border-gray-800 rounded-lg overflow-hidden hover:border-primary/50 transition">
                <div class="p-6">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="text-xl font-bold text-white"><?php echo htmlspecialchars($entry['title']); ?></h3>
                                <span class="px-2 py-1 bg-primary/20 text-primary text-xs rounded">
                                    <?php echo htmlspecialchars($entry['category']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Content Preview -->
                    <div class="prose prose-invert max-w-none">
                        <div class="text-gray-300 leading-relaxed lore-content">
                            <?php 
                            // Show first 50 characters
                            $content = $entry['content'];
                            $preview = strip_tags($content);
                            if (strlen($preview) > 50) {
                                echo htmlspecialchars(substr($preview, 0, 50)) . '...';
                            } else {
                                echo $content;
                            }
                            ?>
                        </div>
                    </div>
                    
                    <?php if (strlen(strip_tags($entry['content'])) > 50): ?>
                    <button onclick="viewLore(<?php echo $entry['id']; ?>)" class="mt-4 text-primary hover:text-primary-dark font-medium text-sm flex items-center">
                        <span>Read Full Entry</span>
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- View Full Lore Modal -->
<div id="viewLoreModal" class="hidden fixed inset-0 bg-black/75 backdrop-blur-sm flex items-center justify-center p-4 z-50">
    <div class="bg-gray-900 border border-gray-800 rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col">
        <div class="sticky top-0 bg-gray-900 p-6 border-b border-gray-800 flex items-center justify-between">
            <div>
                <h3 id="modalTitle" class="text-2xl font-bold text-white"></h3>
                <p id="modalCategory" class="text-sm text-gray-400 mt-1"></p>
            </div>
            <button onclick="closeLoreModal()" class="text-gray-400 hover:text-white transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="p-6 overflow-y-auto flex-1">
            <div id="modalContent" class="prose prose-invert max-w-none lore-content"></div>
        </div>
    </div>
</div>

<style>
.filter-btn {
    padding: 0.5rem 1rem;
    background: #1f2937;
    border: 1px solid #374151;
    color: #9ca3af;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s;
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

.lore-content {
    line-height: 1.75;
}

.lore-content h1,
.lore-content h2,
.lore-content h3 {
    color: white;
    font-weight: bold;
    margin-top: 1.5em;
    margin-bottom: 0.5em;
}

.lore-content h1 { font-size: 1.875rem; }
.lore-content h2 { font-size: 1.5rem; }
.lore-content h3 { font-size: 1.25rem; }

.lore-content p {
    margin-bottom: 1em;
    color: #d1d5db;
}

.lore-content ul,
.lore-content ol {
    margin-left: 1.5em;
    margin-bottom: 1em;
}

.lore-content strong {
    color: #f97316;
    font-weight: bold;
}

.lore-content em {
    font-style: italic;
}

.lore-content blockquote {
    border-left: 4px solid #f97316;
    padding-left: 1em;
    margin-left: 0;
    color: #9ca3af;
    font-style: italic;
}

.lore-content a {
    color: #f97316;
    text-decoration: underline;
}

.lore-content img {
    max-width: 100%;
    height: auto;
    border-radius: 0.5rem;
    margin: 1.5em 0;
    border: 2px solid #374151;
}

.lore-content code {
    background: #1f2937;
    padding: 0.2em 0.4em;
    border-radius: 0.25rem;
    font-size: 0.875em;
}

.lore-content pre {
    background: #1f2937;
    padding: 1em;
    border-radius: 0.5rem;
    overflow-x: auto;
    border: 1px solid #374151;
}
</style>

<script>
async function viewLore(loreId) {
    try {
        const response = await fetch(`/player/api.php?action=get_lore&id=${loreId}`);
        const result = await response.json();
        
        if (result.success && result.lore) {
            document.getElementById('modalTitle').textContent = result.lore.title;
            document.getElementById('modalCategory').textContent = result.lore.category + ' • ' + new Date(result.lore.created_at).toLocaleDateString();
            document.getElementById('modalContent').innerHTML = result.lore.content;
            document.getElementById('viewLoreModal').classList.remove('hidden');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to load lore entry');
    }
}

function closeLoreModal() {
    document.getElementById('viewLoreModal').classList.add('hidden');
}

// Close modal on outside click
document.getElementById('viewLoreModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeLoreModal();
    }
});
</script>
