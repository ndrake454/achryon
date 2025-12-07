<?php
// Get all lore entries
$lore = $conn->query("SELECT * FROM lore ORDER BY order_index, created_at DESC");
?>

<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-white flex items-center">
                <svg class="w-8 h-8 text-primary mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                Lore Management
            </h2>
            <p class="text-gray-400 ml-11">Manage campaign lore and reveal it to players as they discover it</p>
        </div>
        <button onclick="showCreateModal()" class="bg-primary hover:bg-primary-dark text-white font-bold py-3 px-6 rounded-lg transition flex items-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Create Lore Entry</span>
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
            <button onclick="filterLore('all')" class="filter-btn active" data-filter="all">All</button>
            <button onclick="filterLore('visible')" class="filter-btn" data-filter="visible">Visible to Players</button>
            <button onclick="filterLore('hidden')" class="filter-btn" data-filter="hidden">Hidden from Players</button>
            <div class="flex-1"></div>
            <div class="flex items-center space-x-2">
                <span class="text-gray-400 text-sm">Category:</span>
                <select id="categoryFilter" onchange="filterByCategory()" class="px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm focus:outline-none focus:border-primary">
                    <option value="all">All Categories</option>
                    <option value="History">History</option>
                    <option value="Location">Location</option>
                    <option value="Character">Character</option>
                    <option value="Event">Event</option>
                    <option value="Organization">Organization</option>
                    <option value="Item">Item</option>
                    <option value="Other">Other</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Lore Entries List -->
    <div class="space-y-4" id="loreContainer">
        <?php while ($entry = $lore->fetch_assoc()): ?>
        <div class="lore-entry bg-gray-900/50 border border-gray-800 rounded-lg hover:border-primary/50 transition" 
             data-visible="<?php echo $entry['visible_to_players'] ? 'visible' : 'hidden'; ?>"
             data-category="<?php echo strtolower($entry['category'] ?? 'other'); ?>">
            
            <div class="p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <h3 class="text-xl font-bold text-white"><?php echo htmlspecialchars($entry['title']); ?></h3>
                            <?php if ($entry['category']): ?>
                            <span class="text-xs px-2 py-1 bg-gray-800 text-gray-400 rounded">
                                <?php echo htmlspecialchars($entry['category']); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <p class="text-sm text-gray-500">
                            Created: <?php echo date('M j, Y', strtotime($entry['created_at'])); ?>
                        </p>
                    </div>
                    
                    <!-- Visibility Toggle -->
                    <div class="flex items-center gap-3">
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-400">Players can see:</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input 
                                    type="checkbox" 
                                    <?php echo $entry['visible_to_players'] ? 'checked' : ''; ?>
                                    onchange="toggleVisibility(<?php echo $entry['id']; ?>, this.checked)"
                                    class="sr-only peer"
                                >
                                <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                            </label>
                        </div>
                        
                        <?php if ($entry['visible_to_players']): ?>
                        <span class="px-3 py-1 bg-green-500/20 text-green-400 text-sm font-medium rounded-full flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                            </svg>
                            Visible
                        </span>
                        <?php else: ?>
                        <span class="px-3 py-1 bg-gray-700 text-gray-400 text-sm font-medium rounded-full flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd"/>
                                <path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z"/>
                            </svg>
                            Hidden
                        </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Content Preview -->
                <div class="prose prose-invert prose-sm max-w-none mb-4">
                    <div class="line-clamp-3 text-gray-400">
                        <?php 
                        $preview = strip_tags($entry['content']);
                        echo htmlspecialchars(mb_substr($preview, 0, 200)) . (mb_strlen($preview) > 200 ? '...' : '');
                        ?>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-between pt-4 border-t border-gray-800">
                    <button 
                        onclick="viewLore(<?php echo $entry['id']; ?>)" 
                        class="text-gray-400 hover:text-white transition text-sm flex items-center space-x-1"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <span>View Full Content</span>
                    </button>
                    
                    <div class="flex gap-2">
                        <button onclick="editLore(<?php echo $entry['id']; ?>)" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-white rounded-lg transition text-sm flex items-center space-x-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            <span>Edit</span>
                        </button>
                        <button onclick="deleteLore(<?php echo $entry['id']; ?>, '<?php echo htmlspecialchars($entry['title'], ENT_QUOTES); ?>')" class="px-4 py-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 rounded-lg transition text-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>

    <?php if ($lore->num_rows === 0): ?>
    <div class="text-center py-20">
        <svg class="w-20 h-20 text-gray-700 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
        </svg>
        <h3 class="text-xl font-bold text-gray-600 mb-2">No Lore Entries Yet</h3>
        <p class="text-gray-500 mb-4">Create your first lore entry to start building your campaign world</p>
        <button onclick="showCreateModal()" class="bg-primary hover:bg-primary-dark text-white font-bold py-2 px-6 rounded-lg transition">
            Create Lore Entry
        </button>
    </div>
    <?php endif; ?>
</div>

<!-- View Lore Modal -->
<div id="viewModal" class="hidden fixed inset-0 bg-black/75 backdrop-blur-sm flex items-center justify-center p-4 z-50 overflow-y-auto">
    <div class="bg-gray-900 border border-gray-800 rounded-xl shadow-2xl w-full max-w-4xl my-8">
        <div class="sticky top-0 bg-gray-900 p-6 border-b border-gray-800 rounded-t-xl flex items-center justify-between">
            <h3 id="viewTitle" class="text-2xl font-bold text-white"></h3>
            <button onclick="hideViewModal()" class="text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="p-6">
            <div id="viewContent" class="prose prose-invert prose-lg max-w-none"></div>
        </div>
    </div>
</div>

<!-- Create/Edit Lore Modal -->
<div id="loreModal" class="hidden fixed inset-0 bg-black/75 backdrop-blur-sm flex items-center justify-center p-4 z-50 overflow-y-auto">
    <div class="bg-gray-900 border border-gray-800 rounded-xl shadow-2xl w-full max-w-5xl my-8">
        <div class="sticky top-0 bg-gray-900 p-6 border-b border-gray-800 rounded-t-xl">
            <h3 id="modalTitle" class="text-2xl font-bold text-white">Create Lore Entry</h3>
        </div>
        <form id="loreForm" onsubmit="saveLore(event)" class="p-6">
            <input type="hidden" id="loreId" name="id">
            
            <div class="space-y-5">
                <!-- Title and Category -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-gray-300 mb-2 text-sm font-medium">Title *</label>
                        <input type="text" id="loreTitle" name="title" required class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary" placeholder="e.g., The Fall of Netheria">
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 mb-2 text-sm font-medium">Category</label>
                        <select id="loreCategory" name="category" class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary">
                            <option value="">Select...</option>
                            <option value="History">History</option>
                            <option value="Location">Location</option>
                            <option value="Character">Character</option>
                            <option value="Event">Event</option>
                            <option value="Organization">Organization</option>
                            <option value="Item">Item</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>

                <!-- Rich Text Editor -->
                <div>
                    <label class="block text-gray-300 mb-2 text-sm font-medium">Content</label>
                    <div id="editor" class="bg-gray-800 border border-gray-700 rounded-lg" style="min-height: 400px;"></div>
                    <input type="hidden" id="loreContent" name="content">
                </div>

                <!-- Visibility Toggle -->
                <div class="bg-gray-800/50 rounded-lg p-4">
                    <label class="flex items-center justify-between cursor-pointer">
                        <div>
                            <span class="text-white font-medium">Visible to Players</span>
                            <p class="text-sm text-gray-400">Toggle this on when players should discover this lore</p>
                        </div>
                        <div class="relative inline-flex items-center cursor-pointer">
                            <input 
                                type="checkbox" 
                                id="loreVisible"
                                name="visible_to_players"
                                class="sr-only peer"
                            >
                            <div class="w-14 h-7 bg-gray-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-primary"></div>
                        </div>
                    </label>
                </div>
            </div>

            <div class="flex gap-3 mt-6 pt-6 border-t border-gray-800">
                <button type="submit" class="flex-1 bg-primary hover:bg-primary-dark text-white font-bold py-3 px-6 rounded-lg transition">
                    Save Lore Entry
                </button>
                <button type="button" onclick="hideLoreModal()" class="px-6 bg-gray-800 hover:bg-gray-700 text-white font-bold py-3 rounded-lg transition">
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

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Quill editor dark theme customization */
.ql-toolbar {
    background: #1f2937 !important;
    border-color: #374151 !important;
    border-radius: 0.5rem 0.5rem 0 0 !important;
}

.ql-container {
    background: #1f2937 !important;
    border-color: #374151 !important;
    color: white !important;
    border-radius: 0 0 0.5rem 0.5rem !important;
    font-size: 16px;
}

.ql-editor {
    min-height: 400px;
    color: white !important;
}

.ql-editor.ql-blank::before {
    color: #6b7280 !important;
}

.ql-snow .ql-stroke {
    stroke: #9ca3af !important;
}

.ql-snow .ql-fill {
    fill: #9ca3af !important;
}

.ql-snow .ql-picker-label {
    color: #9ca3af !important;
}

.ql-snow .ql-picker-options {
    background: #1f2937 !important;
    border-color: #374151 !important;
}

.ql-snow .ql-picker-item:hover {
    color: #f97316 !important;
}

.prose {
    max-width: none;
}

.prose p {
    margin-bottom: 1em;
}

.prose h1, .prose h2, .prose h3 {
    margin-top: 1.5em;
    margin-bottom: 0.5em;
    color: white;
    font-weight: bold;
}

.prose ul, .prose ol {
    margin-left: 1.5em;
    margin-bottom: 1em;
}

.prose strong {
    font-weight: bold;
    color: #f97316;
}

.prose em {
    font-style: italic;
}
</style>

<script>
// Load Quill dynamically if not already loaded
if (typeof Quill === 'undefined') {
    // Load CSS
    const quillCSS = document.createElement('link');
    quillCSS.rel = 'stylesheet';
    quillCSS.href = 'https://cdn.quilljs.com/1.3.6/quill.snow.css';
    document.head.appendChild(quillCSS);
    
    // Load JS
    const quillJS = document.createElement('script');
    quillJS.src = 'https://cdn.quilljs.com/1.3.6/quill.js';
    document.head.appendChild(quillJS);
}

let quill;
let currentFilter = 'all';
let currentCategory = 'all';

function showCreateModal() {
    document.getElementById('modalTitle').textContent = 'Create Lore Entry';
    document.getElementById('loreForm').reset();
    document.getElementById('loreId').value = '';
    document.getElementById('loreVisible').checked = false;
    
    if (quill) {
        quill.setContents([]);
    }
    
    document.getElementById('loreModal').classList.remove('hidden');
    
    // Initialize Quill if not already initialized
    if (!quill) {
        initQuill();
    }
}

function initQuill() {
    quill = new Quill('#editor', {
        theme: 'snow',
        modules: {
            toolbar: {
                container: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    [{ 'indent': '-1'}, { 'indent': '+1' }],
                    [{ 'align': [] }],
                    ['blockquote', 'code-block'],
                    ['link', 'image'],
                    ['clean']
                ],
                handlers: {
                    image: imageHandler
                }
            }
        },
        placeholder: 'Write your lore content here...'
    });
}

// Custom image handler for Quill
function imageHandler() {
    const input = document.createElement('input');
    input.setAttribute('type', 'file');
    input.setAttribute('accept', 'image/*');
    input.click();

    input.onchange = async () => {
        const file = input.files[0];
        if (!file) return;

        // Validate file size (5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('Image too large. Maximum size is 5MB');
            return;
        }

        // Show loading indicator
        const range = quill.getSelection(true);
        quill.insertText(range.index, 'Uploading image...');
        quill.setSelection(range.index + 18);

        // Upload image
        const formData = new FormData();
        formData.append('image', file);
        formData.append('type', 'content'); // content images (vs portraits)

        try {
            const response = await fetch('/admin/upload_content_image.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                // Remove loading text
                quill.deleteText(range.index, 18);
                
                // Insert image
                quill.insertEmbed(range.index, 'image', result.image_url);
                quill.setSelection(range.index + 1);
            } else {
                // Remove loading text and show error
                quill.deleteText(range.index, 18);
                alert('Upload failed: ' + result.message);
            }
        } catch (error) {
            console.error('Upload error:', error);
            quill.deleteText(range.index, 18);
            alert('Upload failed. Please try again.');
        }
    };
}

function hideLoreModal() {
    document.getElementById('loreModal').classList.add('hidden');
}

function hideViewModal() {
    document.getElementById('viewModal').classList.add('hidden');
}

async function saveLore(event) {
    event.preventDefault();
    
    // Get content from Quill
    const content = quill.root.innerHTML;
    document.getElementById('loreContent').value = content;
    
    const formData = new FormData(event.target);
    const loreId = document.getElementById('loreId').value;
    
    formData.append('action', loreId ? 'update_lore' : 'create_lore');
    formData.append('visible_to_players', document.getElementById('loreVisible').checked ? 1 : 0);
    
    if (loreId) {
        formData.append('id', loreId);
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
            alert('Failed to save lore: ' + (result.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to save lore');
    }
}

async function editLore(id) {
    try {
        const response = await fetch(`/admin/api.php?action=get_lore&id=${id}`);
        const result = await response.json();
        
        if (result.success && result.lore) {
            const lore = result.lore;
            
            document.getElementById('modalTitle').textContent = 'Edit Lore Entry';
            document.getElementById('loreId').value = lore.id;
            document.getElementById('loreTitle').value = lore.title;
            document.getElementById('loreCategory').value = lore.category || '';
            document.getElementById('loreVisible').checked = lore.visible_to_players == 1;
            
            document.getElementById('loreModal').classList.remove('hidden');
            
            // Initialize Quill if needed
            if (!quill) {
                initQuill();
            }
            
            // Set content
            quill.root.innerHTML = lore.content || '';
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to load lore');
    }
}

async function viewLore(id) {
    try {
        const response = await fetch(`/admin/api.php?action=get_lore&id=${id}`);
        const result = await response.json();
        
        if (result.success && result.lore) {
            const lore = result.lore;
            
            document.getElementById('viewTitle').textContent = lore.title;
            document.getElementById('viewContent').innerHTML = lore.content;
            document.getElementById('viewModal').classList.remove('hidden');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to load lore');
    }
}

async function deleteLore(id, title) {
    if (!confirm(`Delete "${title}"? This cannot be undone.`)) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_lore');
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
            alert('Failed to delete lore');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to delete lore');
    }
}

async function toggleVisibility(id, visible) {
    const formData = new FormData();
    formData.append('action', 'toggle_lore_visibility');
    formData.append('id', id);
    formData.append('visible', visible ? 1 : 0);
    
    try {
        const response = await fetch('/admin/api.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            location.reload();
        } else {
            alert('Failed to update visibility');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to update visibility');
    }
}

function filterLore(type) {
    currentFilter = type;
    
    document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelector(`[data-filter="${type}"]`).classList.add('active');
    
    applyFilters();
}

function filterByCategory() {
    currentCategory = document.getElementById('categoryFilter').value;
    applyFilters();
}

function applyFilters() {
    const entries = document.querySelectorAll('.lore-entry');
    
    entries.forEach(entry => {
        const visible = entry.dataset.visible;
        const category = entry.dataset.category;
        
        const matchesFilter = currentFilter === 'all' || visible === currentFilter;
        const matchesCategory = currentCategory === 'all' || category === currentCategory;
        
        entry.style.display = (matchesFilter && matchesCategory) ? '' : 'none';
    });
}

// Close modals on outside click
document.getElementById('loreModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        hideLoreModal();
    }
});

document.getElementById('viewModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        hideViewModal();
    }
});
</script>
