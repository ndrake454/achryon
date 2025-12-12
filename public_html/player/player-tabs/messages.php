<?php
// Get current user
$current_user = getCurrentUser();

// Get all users for messaging (including DM)
$users = $conn->query("SELECT id, username, role FROM users WHERE id != {$current_user['id']} ORDER BY role DESC, username");

// Get conversations with last message info
$conversations_query = "
    SELECT 
        u.id, 
        u.username, 
        u.role,
        (SELECT COUNT(*) FROM messages WHERE from_user_id = u.id AND to_user_id = {$current_user['id']} AND is_read = 0) as unread_count,
        (SELECT message FROM messages WHERE (from_user_id = u.id AND to_user_id = {$current_user['id']}) OR (from_user_id = {$current_user['id']} AND to_user_id = u.id) ORDER BY created_at DESC LIMIT 1) as last_message,
        (SELECT created_at FROM messages WHERE (from_user_id = u.id AND to_user_id = {$current_user['id']}) OR (from_user_id = {$current_user['id']} AND to_user_id = u.id) ORDER BY created_at DESC LIMIT 1) as last_message_time
    FROM users u
    WHERE u.id != {$current_user['id']}
    ORDER BY CASE WHEN last_message_time IS NULL THEN 1 ELSE 0 END, last_message_time DESC, u.username
";
$conversations = $conn->query($conversations_query);
?>

<style>
/* Mobile message alignment */
@media (max-width: 640px) {
    .message-wrapper {
        flex-direction: column !important;
        align-items: flex-start !important;
    }
    .message-wrapper.sent {
        align-items: flex-end !important;
    }
}
</style>

<div class="flex h-[calc(100vh-200px)] gap-4">
    <!-- Conversations List -->
    <div class="w-80 flex-shrink-0 bg-gray-900/50 border border-gray-800 rounded-lg flex flex-col">
        <div class="p-4 border-b border-gray-800">
            <h3 class="text-lg font-bold text-white flex items-center">
                <svg class="w-5 h-5 text-primary mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                Messages
            </h3>
        </div>
        
        <div class="flex-1 overflow-y-auto">
            <?php if ($conversations->num_rows === 0): ?>
            <div class="p-8 text-center">
                <svg class="w-12 h-12 text-gray-700 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <p class="text-gray-500 text-sm">No conversations yet</p>
                <p class="text-gray-600 text-xs mt-1">Select a user to start messaging</p>
            </div>
            <?php else: ?>
                <?php while ($conv = $conversations->fetch_assoc()): ?>
                <button 
                    onclick="loadConversation(<?php echo $conv['id']; ?>, '<?php echo htmlspecialchars($conv['username'], ENT_QUOTES); ?>')"
                    class="conversation-item w-full p-4 hover:bg-gray-800/50 transition border-b border-gray-800 text-left flex items-start space-x-3"
                    data-user-id="<?php echo $conv['id']; ?>"
                >
                    <!-- Avatar -->
                    <div class="flex-shrink-0 w-10 h-10 bg-primary/20 rounded-full border-2 border-primary flex items-center justify-center">
                        <span class="text-primary font-bold text-sm">
                            <?php echo strtoupper(substr($conv['username'], 0, 2)); ?>
                        </span>
                    </div>
                    
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between mb-1">
                            <div class="flex items-center space-x-2">
                                <span class="text-white font-medium truncate"><?php echo htmlspecialchars($conv['username']); ?></span>
                                <?php if ($conv['role'] === 'dm'): ?>
                                <span class="px-2 py-0.5 bg-primary/20 text-primary text-xs rounded">DM</span>
                                <?php endif; ?>
                            </div>
                            <?php if ($conv['unread_count'] > 0): ?>
                            <span class="flex-shrink-0 bg-primary text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center">
                                <?php echo $conv['unread_count']; ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($conv['last_message']): ?>
                        <p class="text-sm text-gray-400 truncate"><?php echo htmlspecialchars($conv['last_message']); ?></p>
                        <p class="text-xs text-gray-600 mt-1">
                            <?php 
                            $time = strtotime($conv['last_message_time']);
                            $now = time();
                            $diff = $now - $time;
                            
                            if ($diff < 60) echo 'Just now';
                            elseif ($diff < 3600) echo floor($diff / 60) . 'm ago';
                            elseif ($diff < 86400) echo floor($diff / 3600) . 'h ago';
                            elseif ($diff < 604800) echo floor($diff / 86400) . 'd ago';
                            else echo date('M j', $time);
                            ?>
                        </p>
                        <?php else: ?>
                        <p class="text-sm text-gray-600 italic">No messages yet</p>
                        <?php endif; ?>
                    </div>
                </button>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Message Area -->
    <div class="flex-1 bg-gray-900/50 border border-gray-800 rounded-lg flex flex-col">
        <!-- No conversation selected -->
        <div id="emptyState" class="flex-1 flex items-center justify-center">
            <div class="text-center">
                <svg class="w-20 h-20 text-gray-700 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <h3 class="text-xl font-bold text-gray-600 mb-2">Select a Conversation</h3>
                <p class="text-gray-500">Choose someone from the list to start messaging</p>
            </div>
        </div>

        <!-- Active conversation -->
        <div id="conversationArea" class="hidden flex-1 flex flex-col">
            <!-- Header -->
            <div class="p-4 border-b border-gray-800 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div id="recipientAvatar" class="w-10 h-10 bg-primary/20 rounded-full border-2 border-primary flex items-center justify-center">
                        <span class="text-primary font-bold text-sm"></span>
                    </div>
                    <div>
                        <h3 id="recipientName" class="text-lg font-bold text-white"></h3>
                        <p class="text-xs text-gray-500">Active conversation</p>
                    </div>
                </div>
                
                <button onclick="closeConversation()" class="text-gray-400 hover:text-white transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Messages -->
            <div id="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-4">
                <!-- Messages will be loaded here -->
            </div>

            <!-- Message Input -->
            <div class="p-4 border-t border-gray-800">
                <form id="messageForm" onsubmit="sendMessage(event)" class="flex space-x-3">
                    <input type="hidden" id="recipientId">
                    <input 
                        type="text" 
                        id="messageInput" 
                        placeholder="Type a message..." 
                        class="flex-1 px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary"
                        autocomplete="off"
                    >
                    <button 
                        type="submit" 
                        class="bg-primary hover:bg-primary-dark text-white font-bold px-6 py-3 rounded-lg transition flex items-center space-x-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        <span>Send</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.conversation-item.active {
    background: rgba(249, 115, 22, 0.1);
    border-left: 3px solid #f97316;
}

.message-bubble {
    max-width: 70%;
    padding: 0.75rem 1rem;
    border-radius: 1rem;
    word-wrap: break-word;
}

.message-sent {
    background: #f97316;
    color: white;
    margin-left: auto;
    border-bottom-right-radius: 0.25rem;
}

.message-received {
    background: #374151;
    color: white;
    margin-right: auto;
    border-bottom-left-radius: 0.25rem;
}

#messagesContainer {
    scroll-behavior: smooth;
    max-height: calc(100vh - 400px);
    min-height: 300px;
}
</style>

<script>
let currentRecipientId = null;
let messagePollingInterval = null;

async function loadConversation(userId, username) {
    currentRecipientId = userId;
    
    // Update UI
    document.getElementById('emptyState').classList.add('hidden');
    document.getElementById('conversationArea').classList.remove('hidden');
    
    document.getElementById('recipientName').textContent = username;
    document.getElementById('recipientAvatar').querySelector('span').textContent = username.substring(0, 2).toUpperCase();
    document.getElementById('recipientId').value = userId;
    
    // Highlight active conversation
    document.querySelectorAll('.conversation-item').forEach(item => {
        item.classList.remove('active');
    });
    document.querySelector(`[data-user-id="${userId}"]`)?.classList.add('active');
    
    // Load messages
    await loadMessages();
    
    // Start polling for new messages
    if (messagePollingInterval) {
        clearInterval(messagePollingInterval);
    }
    messagePollingInterval = setInterval(loadMessages, 3000);
}

function closeConversation() {
    currentRecipientId = null;
    
    document.getElementById('emptyState').classList.remove('hidden');
    document.getElementById('conversationArea').classList.add('hidden');
    
    document.querySelectorAll('.conversation-item').forEach(item => {
        item.classList.remove('active');
    });
    
    if (messagePollingInterval) {
        clearInterval(messagePollingInterval);
    }
}

async function loadMessages() {
    if (!currentRecipientId) return;
    
    try {
        const response = await fetch(`/player/api.php?action=get_messages&user_id=${currentRecipientId}`);
        const result = await response.json();
        
        if (result.success) {
            const container = document.getElementById('messagesContainer');
            const wasAtBottom = container.scrollHeight - container.scrollTop <= container.clientHeight + 100;
            
            container.innerHTML = '';
            
            if (result.messages.length === 0) {
                container.innerHTML = '<div class="text-center text-gray-500 py-8">No messages yet. Send the first message!</div>';
            } else {
                result.messages.forEach(msg => {
                    const messageDiv = document.createElement('div');
                    messageDiv.className = 'message-wrapper flex ' + (msg.is_sent ? 'justify-end sent' : 'justify-start');
                    
                    const bubble = document.createElement('div');
                    bubble.className = 'message-bubble ' + (msg.is_sent ? 'message-sent' : 'message-received');
                    
                    const text = document.createElement('p');
                    text.textContent = msg.message;
                    bubble.appendChild(text);
                    
                    const time = document.createElement('p');
                    time.className = 'text-xs mt-1 ' + (msg.is_sent ? 'text-orange-200' : 'text-gray-400');
                    time.textContent = formatTime(msg.created_at);
                    bubble.appendChild(time);
                    
                    messageDiv.appendChild(bubble);
                    container.appendChild(messageDiv);
                });
                
                // Scroll to bottom if user was already at bottom
                if (wasAtBottom) {
                    container.scrollTop = container.scrollHeight;
                }
            }
        }
    } catch (error) {
        console.error('Error loading messages:', error);
    }
}

async function sendMessage(event) {
    event.preventDefault();
    
    const messageInput = document.getElementById('messageInput');
    const message = messageInput.value.trim();
    
    if (!message || !currentRecipientId) return;
    
    const formData = new FormData();
    formData.append('action', 'send_message');
    formData.append('to_user_id', currentRecipientId);
    formData.append('message', message);
    
    try {
        const response = await fetch('/player/api.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            messageInput.value = '';
            await loadMessages();
            
            // Scroll to bottom
            const container = document.getElementById('messagesContainer');
            container.scrollTop = container.scrollHeight;
        } else {
            alert('Failed to send message: ' + (result.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to send message');
    }
}

function formatTime(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const diff = now - date;
    
    // Less than 1 minute
    if (diff < 60000) return 'Just now';
    
    // Less than 1 hour
    if (diff < 3600000) {
        const mins = Math.floor(diff / 60000);
        return mins + 'm ago';
    }
    
    // Today
    if (date.toDateString() === now.toDateString()) {
        return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
    }
    
    // This week
    if (diff < 604800000) {
        const days = Math.floor(diff / 86400000);
        return days + 'd ago';
    }
    
    // Older
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (messagePollingInterval) {
        clearInterval(messagePollingInterval);
    }
});
</script>
