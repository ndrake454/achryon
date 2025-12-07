<?php
// Get all rules
$rules = $conn->query("SELECT * FROM rules ORDER BY order_index, created_at DESC");
?>

<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-white flex items-center">
                <svg class="w-8 h-8 text-primary mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Rules Management
            </h2>
            <p class="text-gray-400 ml-11">Manage game rules and house rules - all visible to players</p>
        </div>
        <button onclick="showCreateModal()" class="bg-primary hover:bg-primary-dark text-white font-bold py-3 px-6 rounded-lg transition flex items-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Create Rule</span>
        </button>
    </div>

    <!-- Info Banner -->
    <div class="bg-blue-500/10 border border-blue-500 rounded-lg p-4 mb-6 flex items-start space-x-3">
        <svg class="w-6 h-6 text-blue-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
        </svg>
        <div class="flex-1">
            <h3 class="text-blue-400 font-bold mb-1">All Rules Are Public</h3>
            <p class="text-blue-300 text-sm">All rules created here are automatically visible to all players. Use this for core rules, house rules, and gameplay mechanics.</p>
        </div>
    </div>

    <!-- Rules List -->
    <div class="space-y-4" id="rulesContainer">
        <?php $index = 0; while ($rule = $rules->fetch_assoc()): $index++; ?>
        <div class="rule-entry bg-gray-900/50 border border-gray-800 rounded-lg hover:border-primary/50 transition">
            
            <div class="p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-start space-x-4 flex-1">
                        <!-- Order Badge -->
                        <div class="flex-shrink-0 w-10 h-10 bg-primary/20 rounded-lg border border-primary flex items-center justify-center">
                            <span class="text-primary font-bold"><?php echo $index; ?></span>
                        </div>
                        
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-white mb-2"><?php echo htmlspecialchars($rule['title']); ?></h3>
                            <p class="text-sm text-gray-500">
                                Created: <?php echo date('M j, Y', strtotime($rule['created_at'])); ?>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Visibility Badge (Always Visible) -->
                    <span class="px-3 py-1 bg-green-500/20 text-green-400 text-sm font-medium rounded-full flex items-center flex-shrink-0">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                        </svg>
                        Visible to All
                    </span>
                </div>

                <!-- Content Preview -->
                <div class="prose prose-invert prose-sm max-w-none mb-4">
                    <div class="line-clamp-3 text-gray-400">
                        <?php 
                        $preview = strip_tags($rule['content']);
                        echo htmlspecialchars(mb_substr($preview, 0, 200)) . (mb_strlen($preview) > 200 ? '...' : '');
                        ?>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-between pt-4 border-t border-gray-800">
                    <button 
                        onclick="viewRule(<?php echo $rule['id']; ?>)" 
                        class="text-gray-400 hover:text-white transition text-sm flex items-center space-x-1"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <span>View Full Rule</span>
                    </button>
                    
                    <div class="flex gap-2">
                        <button onclick="moveRule(<?php echo $rule['id']; ?>, 'up')" class="px-3 py-2 bg-gray-800 hover:bg-gray-700 text-gray-400 hover:text-white rounded-lg transition text-sm" title="Move Up">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                            </svg>
                        </button>
                        <button onclick="moveRule(<?php echo $rule['id']; ?>, 'down')" class="px-3 py-2 bg-gray-800 hover:bg-gray-700 text-gray-400 hover:text-white rounded-lg transition text-sm" title="Move Down">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <button onclick="editRule(<?php echo $rule['id']; ?>)" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-white rounded-lg transition text-sm flex items-center space-x-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            <span>Edit</span>
                        </button>
                        <button onclick="deleteRule(<?php echo $rule['id']; ?>, '<?php echo htmlspecialchars($rule['title'], ENT_QUOTES); ?>')" class="px-4 py-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 rounded-lg transition text-sm">
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

    <?php if ($rules->num_rows === 0): ?>
    <div class="text-center py-20">
        <svg class="w-20 h-20 text-gray-700 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <h3 class="text-xl font-bold text-gray-600 mb-2">No Rules Yet</h3>
        <p class="text-gray-500 mb-4">Create your first rule entry to document your game mechanics</p>
        <button onclick="showCreateModal()" class="bg-primary hover:bg-primary-dark text-white font-bold py-2 px-6 rounded-lg transition">
            Create Rule
        </button>
    </div>
    <?php endif; ?>
</div>

<!-- View Rule Modal -->
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

<!-- Create/Edit Rule Modal -->
<div id="ruleModal" class="hidden fixed inset-0 bg-black/75 backdrop-blur-sm flex items-center justify-center p-4 z-50 overflow-y-auto">
    <div class="bg-gray-900 border border-gray-800 rounded-xl shadow-2xl w-full max-w-5xl my-8">
        <div class="sticky top-0 bg-gray-900 p-6 border-b border-gray-800 rounded-t-xl">
            <h3 id="modalTitle" class="text-2xl font-bold text-white">Create Rule</h3>
        </div>
        <form id="ruleForm" onsubmit="saveRule(event)" class="p-6">
            <input type="hidden" id="ruleId" name="id">
            
            <div class="space-y-5">
                <!-- Title -->
                <div>
                    <label class="block text-gray-300 mb-2 text-sm font-medium">Rule Title *</label>
                    <input type="text" id="ruleTitle" name="title" required class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary" placeholder="e.g., Combat Actions, House Rules, Critical Hits">
                </div>

                <!-- Rich Text Editor -->
                <div>
                    <label class="block text-gray-300 mb-2 text-sm font-medium">Content</label>
                    <div id="editor" class="bg-gray-800 border border-gray-700 rounded-lg" style="min-height: 400px;"></div>
                    <input type="hidden" id="ruleContent" name="content">
                </div>

                <!-- Info Box -->
                <div class="bg-blue-500/10 border border-blue-500 rounded-lg p-4">
                    <div class="flex items-start space-x-3">
                        <svg class="w-5 h-5 text-blue-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <p class="text-blue-300 text-sm"><strong class="text-blue-400">Note:</strong> This rule will be immediately visible to all players once saved.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex gap-3 mt-6 pt-6 border-t border-gray-800">
                <button type="submit" class="flex-1 bg-primary hover:bg-primary-dark text-white font-bold py-3 px-6 rounded-lg transition">
                    Save Rule
                </button>
                <button type="button" onclick="hideRuleModal()" class="px-6 bg-gray-800 hover:bg-gray-700 text-white font-bold py-3 rounded-lg transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<style>
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

function showCreateModal() {
    document.getElementById('modalTitle').textContent = 'Create Rule';
    document.getElementById('ruleForm').reset();
    document.getElementById('ruleId').value = '';
    
    if (quill) {
        quill.setContents([]);
    }
    
    document.getElementById('ruleModal').classList.remove('hidden');
    
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
        placeholder: 'Write your rule content here... Use headers, lists, and formatting to make it clear and easy to read.'
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
        formData.append('type', 'content');

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

function hideRuleModal() {
    document.getElementById('ruleModal').classList.add('hidden');
}

function hideViewModal() {
    document.getElementById('viewModal').classList.add('hidden');
}

async function saveRule(event) {
    event.preventDefault();
    
    // Get content from Quill
    const content = quill.root.innerHTML;
    document.getElementById('ruleContent').value = content;
    
    const formData = new FormData(event.target);
    const ruleId = document.getElementById('ruleId').value;
    
    formData.append('action', ruleId ? 'update_rule' : 'create_rule');
    
    if (ruleId) {
        formData.append('id', ruleId);
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
            alert('Failed to save rule: ' + (result.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to save rule');
    }
}

async function editRule(id) {
    try {
        const response = await fetch(`/admin/api.php?action=get_rule&id=${id}`);
        const result = await response.json();
        
        if (result.success && result.rule) {
            const rule = result.rule;
            
            document.getElementById('modalTitle').textContent = 'Edit Rule';
            document.getElementById('ruleId').value = rule.id;
            document.getElementById('ruleTitle').value = rule.title;
            
            document.getElementById('ruleModal').classList.remove('hidden');
            
            // Initialize Quill if needed
            if (!quill) {
                initQuill();
            }
            
            // Set content
            quill.root.innerHTML = rule.content || '';
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to load rule');
    }
}

async function viewRule(id) {
    try {
        const response = await fetch(`/admin/api.php?action=get_rule&id=${id}`);
        const result = await response.json();
        
        if (result.success && result.rule) {
            const rule = result.rule;
            
            document.getElementById('viewTitle').textContent = rule.title;
            document.getElementById('viewContent').innerHTML = rule.content;
            document.getElementById('viewModal').classList.remove('hidden');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to load rule');
    }
}

async function deleteRule(id, title) {
    if (!confirm(`Delete "${title}"? This cannot be undone.`)) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_rule');
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
            alert('Failed to delete rule');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to delete rule');
    }
}

async function moveRule(id, direction) {
    const formData = new FormData();
    formData.append('action', 'reorder_rule');
    formData.append('id', id);
    formData.append('direction', direction);
    
    try {
        const response = await fetch('/admin/api.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            location.reload();
        } else {
            alert('Failed to reorder rule');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to reorder rule');
    }
}

// Close modals on outside click
document.getElementById('ruleModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        hideRuleModal();
    }
});

document.getElementById('viewModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        hideViewModal();
    }
});
</script>
