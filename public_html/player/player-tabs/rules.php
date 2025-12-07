<?php
// Get all rules (all are visible to players)
$rules = $conn->query("
    SELECT * FROM rules 
    WHERE visible_to_players = 1 
    ORDER BY order_index
");
?>

<div class="max-w-6xl mx-auto">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-white flex items-center">
            <svg class="w-8 h-8 text-primary mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Game Rules & Reference
        </h2>
        <p class="text-gray-400 ml-11">Game rules and mechanics</p>
    </div>

    <?php if ($rules->num_rows === 0): ?>
        <div class="text-center py-20">
            <svg class="w-20 h-20 text-gray-700 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <h3 class="text-xl font-bold text-gray-600 mb-2">No Rules Yet</h3>
            <p class="text-gray-500">Your DM hasn't added any house rules or reference material yet</p>
        </div>
    <?php else: ?>


        <!-- Rules List -->
        <div class="space-y-4">
            <?php $index = 1; while ($rule = $rules->fetch_assoc()): ?>
            <div class="bg-gray-900/50 border border-gray-800 rounded-lg overflow-hidden hover:border-primary/50 transition">
                <div class="p-6">
                    <div class="flex items-start gap-4">
                        <!-- Rule Number -->
                        <div class="flex-shrink-0 w-10 h-10 bg-primary/20 rounded-lg border-2 border-primary flex items-center justify-center">
                            <span class="text-primary font-bold"><?php echo $index++; ?></span>
                        </div>
                        
                        <div class="flex-1">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <h3 class="text-xl font-bold text-white mb-1"><?php echo htmlspecialchars($rule['title']); ?></h3>
                                </div>
                            </div>
                            
                            <button onclick="viewRule(<?php echo $rule['id']; ?>)" class="mt-4 text-primary hover:text-primary-dark font-medium text-sm flex items-center">
                                <span>Read Full Rule</span>
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>

                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<!-- View Full Rule Modal -->
<div id="viewRuleModal" class="hidden fixed inset-0 bg-black/75 backdrop-blur-sm flex items-center justify-center p-4 z-50">
    <div class="bg-gray-900 border border-gray-800 rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col">
        <div class="sticky top-0 bg-gray-900 p-6 border-b border-gray-800 flex items-center justify-between">
            <div>
                <h3 id="modalTitle" class="text-2xl font-bold text-white"></h3>
                <p id="modalDate" class="text-sm text-gray-400 mt-1"></p>
            </div>
            <button onclick="closeRuleModal()" class="text-gray-400 hover:text-white transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="p-6 overflow-y-auto flex-1">
            <div id="modalContent" class="prose prose-invert max-w-none rule-content"></div>
        </div>
    </div>
</div>

<style>
.rule-content {
    line-height: 1.75;
}

.rule-content h1,
.rule-content h2,
.rule-content h3 {
    color: white;
    font-weight: bold;
    margin-top: 1.5em;
    margin-bottom: 0.5em;
}

.rule-content h1 { font-size: 1.875rem; }
.rule-content h2 { font-size: 1.5rem; }
.rule-content h3 { font-size: 1.25rem; }

.rule-content p {
    margin-bottom: 1em;
    color: #d1d5db;
}

.rule-content ul,
.rule-content ol {
    margin-left: 1.5em;
    margin-bottom: 1em;
    color: #d1d5db;
}

.rule-content li {
    margin-bottom: 0.5em;
}

.rule-content strong {
    color: #f97316;
    font-weight: bold;
}

.rule-content em {
    font-style: italic;
}

.rule-content blockquote {
    border-left: 4px solid #f97316;
    padding-left: 1em;
    margin-left: 0;
    color: #9ca3af;
    font-style: italic;
}

.rule-content a {
    color: #f97316;
    text-decoration: underline;
}

.rule-content code {
    background: #1f2937;
    padding: 0.2em 0.4em;
    border-radius: 0.25rem;
    font-family: monospace;
    font-size: 0.9em;
}

.rule-content pre {
    background: #1f2937;
    padding: 1em;
    border-radius: 0.5rem;
    overflow-x: auto;
    margin-bottom: 1em;
}

.rule-content img {
    max-width: 100%;
    height: auto;
    border-radius: 0.5rem;
    margin: 1.5em 0;
    border: 2px solid #374151;
}
</style>

<script>
async function viewRule(ruleId) {
    try {
        const response = await fetch(`/player/api.php?action=get_rule&id=${ruleId}`);
        const result = await response.json();
        
        if (result.success && result.rule) {
            document.getElementById('modalTitle').textContent = result.rule.title;
            document.getElementById('modalDate').textContent = 'Added on ' + new Date(result.rule.created_at).toLocaleDateString();
            document.getElementById('modalContent').innerHTML = result.rule.content;
            document.getElementById('viewRuleModal').classList.remove('hidden');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to load rule');
    }
}

function closeRuleModal() {
    document.getElementById('viewRuleModal').classList.add('hidden');
}

// Close modal on outside click
document.getElementById('viewRuleModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeRuleModal();
    }
});
</script>
