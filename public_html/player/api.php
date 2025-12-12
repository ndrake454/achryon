<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Fatal error: ' . $error['message']
        ]);
    }
});

require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$current_user = getCurrentUser();
if (!$current_user) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Helper function to verify character ownership
function verifyCharacterOwnership($conn, $character_id, $user_id) {
    $stmt = $conn->prepare("
        SELECT c.id 
        FROM characters c
        JOIN players p ON c.player_id = p.id
        WHERE c.id = ? AND p.user_id = ?
    ");
    $stmt->bind_param("ii", $character_id, $user_id);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

switch ($action) {
    // ===== CHARACTER DATA =====
    case 'get_character':
        $character_id = $_GET['character_id'] ?? 0;
        
        if (!verifyCharacterOwnership($conn, $character_id, $current_user['id'])) {
            echo json_encode(['success' => false, 'message' => 'Not authorized']);
            exit();
        }
        
        $stmt = $conn->prepare("
            SELECT c.*, cs.*, p.user_id
            FROM characters c
            JOIN players p ON c.player_id = p.id
            LEFT JOIN character_stats cs ON c.id = cs.character_id
            WHERE c.id = ?
        ");
        $stmt->bind_param("i", $character_id);
        $stmt->execute();
        $character = $stmt->get_result()->fetch_assoc();
        
        echo json_encode(['success' => true, 'character' => $character]);
        break;
    
    // ===== SPELL SLOTS - Players can use/recover =====
    case 'use_spell_slot':
        $character_id = $_POST['character_id'] ?? 0;
        $level = intval($_POST['level'] ?? 0);
        
        if ($level < 1 || $level > 9) {
            echo json_encode(['success' => false, 'message' => 'Invalid spell level']);
            exit();
        }
        
        if (!verifyCharacterOwnership($conn, $character_id, $current_user['id'])) {
            echo json_encode(['success' => false, 'message' => 'Not authorized']);
            exit();
        }
        
        $current_col = "current_spell_slots_$level";
        
        // Get current value and ensure we don't go below 0
        $stmt = $conn->prepare("SELECT $current_col as current_slots FROM character_stats WHERE character_id = ?");
        $stmt->bind_param("i", $character_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $current = $result['current_slots'] ?? 0;
        
        if ($current <= 0) {
            echo json_encode(['success' => false, 'message' => 'No spell slots available']);
            exit();
        }
        
        $new_value = $current - 1;
        $stmt = $conn->prepare("UPDATE character_stats SET $current_col = ? WHERE character_id = ?");
        $stmt->bind_param("ii", $new_value, $character_id);
        
        echo json_encode(['success' => $stmt->execute(), 'new_value' => $new_value]);
        break;
    
    case 'recover_spell_slot':
        $character_id = $_POST['character_id'] ?? 0;
        $level = intval($_POST['level'] ?? 0);
        
        if ($level < 1 || $level > 9) {
            echo json_encode(['success' => false, 'message' => 'Invalid spell level']);
            exit();
        }
        
        if (!verifyCharacterOwnership($conn, $character_id, $current_user['id'])) {
            echo json_encode(['success' => false, 'message' => 'Not authorized']);
            exit();
        }
        
        $max_col = "spell_slots_$level";
        $current_col = "current_spell_slots_$level";
        
        // Get current and max values
        $stmt = $conn->prepare("SELECT $max_col as max_slots, $current_col as current_slots FROM character_stats WHERE character_id = ?");
        $stmt->bind_param("i", $character_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $max = $result['max_slots'] ?? 0;
        $current = $result['current_slots'] ?? 0;
        
        if ($current >= $max) {
            echo json_encode(['success' => false, 'message' => 'Spell slots already at maximum']);
            exit();
        }
        
        $new_value = $current + 1;
        $stmt = $conn->prepare("UPDATE character_stats SET $current_col = ? WHERE character_id = ?");
        $stmt->bind_param("ii", $new_value, $character_id);
        
        echo json_encode(['success' => $stmt->execute(), 'new_value' => $new_value]);
        break;
    
    case 'rest_recover_slots':
        // Recover all spell slots (long rest)
        $character_id = $_POST['character_id'] ?? 0;
        
        if (!verifyCharacterOwnership($conn, $character_id, $current_user['id'])) {
            echo json_encode(['success' => false, 'message' => 'Not authorized']);
            exit();
        }
        
        $stmt = $conn->prepare("
            UPDATE character_stats SET 
                current_spell_slots_1 = spell_slots_1,
                current_spell_slots_2 = spell_slots_2,
                current_spell_slots_3 = spell_slots_3,
                current_spell_slots_4 = spell_slots_4,
                current_spell_slots_5 = spell_slots_5,
                current_spell_slots_6 = spell_slots_6,
                current_spell_slots_7 = spell_slots_7,
                current_spell_slots_8 = spell_slots_8,
                current_spell_slots_9 = spell_slots_9
            WHERE character_id = ?
        ");
        $stmt->bind_param("i", $character_id);
        
        echo json_encode(['success' => $stmt->execute()]);
        break;
    
    // ===== HP - Players can adjust their own HP =====
    case 'update_hp':
        $character_id = $_POST['character_id'] ?? 0;
        $change = intval($_POST['change'] ?? 0);
        
        if (!verifyCharacterOwnership($conn, $character_id, $current_user['id'])) {
            echo json_encode(['success' => false, 'message' => 'Not authorized']);
            exit();
        }
        
        // Get current HP and max HP
        $stmt = $conn->prepare("SELECT current_hp, max_hp FROM character_stats WHERE character_id = ?");
        $stmt->bind_param("i", $character_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        $current_hp = $result['current_hp'] ?? 0;
        $max_hp = $result['max_hp'] ?? 0;
        
        // Calculate new HP (can't go below 0 or above max)
        $new_hp = max(0, min($max_hp, $current_hp + $change));
        
        $stmt = $conn->prepare("UPDATE character_stats SET current_hp = ? WHERE character_id = ?");
        $stmt->bind_param("ii", $new_hp, $character_id);
        
        echo json_encode(['success' => $stmt->execute(), 'new_hp' => $new_hp]);
        break;
    
    case 'set_hp':
        $character_id = $_POST['character_id'] ?? 0;
        $new_hp = intval($_POST['hp'] ?? 0);
        
        if (!verifyCharacterOwnership($conn, $character_id, $current_user['id'])) {
            echo json_encode(['success' => false, 'message' => 'Not authorized']);
            exit();
        }
        
        // Get max HP to enforce limit
        $stmt = $conn->prepare("SELECT max_hp FROM character_stats WHERE character_id = ?");
        $stmt->bind_param("i", $character_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $max_hp = $result['max_hp'] ?? 0;
        
        // Enforce bounds
        $new_hp = max(0, min($max_hp, $new_hp));
        
        $stmt = $conn->prepare("UPDATE character_stats SET current_hp = ? WHERE character_id = ?");
        $stmt->bind_param("ii", $new_hp, $character_id);
        
        echo json_encode(['success' => $stmt->execute(), 'new_hp' => $new_hp]);
        break;
    
    // ===== TEMP HP =====
    case 'set_temp_hp':
        $character_id = $_POST['character_id'] ?? 0;
        $temp_hp = max(0, intval($_POST['temp_hp'] ?? 0));
        
        if (!verifyCharacterOwnership($conn, $character_id, $current_user['id'])) {
            echo json_encode(['success' => false, 'message' => 'Not authorized']);
            exit();
        }
        
        $stmt = $conn->prepare("UPDATE character_stats SET temp_hp = ? WHERE character_id = ?");
        $stmt->bind_param("ii", $temp_hp, $character_id);
        
        echo json_encode(['success' => $stmt->execute(), 'temp_hp' => $temp_hp]);
        break;
    
    // ===== EQUIPMENT - Toggle equipped status =====
    case 'toggle_equipment':
        $character_id = $_POST['character_id'] ?? 0;
        $equipment_id = $_POST['equipment_id'] ?? 0;

        if (!verifyCharacterOwnership($conn, $character_id, $current_user['id'])) {
            echo json_encode(['success' => false, 'message' => 'Not authorized']);
            exit();
        }

        // Verify the equipment belongs to this character
        $stmt = $conn->prepare("SELECT id, is_equipped FROM character_equipment WHERE id = ? AND character_id = ?");
        $stmt->bind_param("ii", $equipment_id, $character_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if (!$result) {
            echo json_encode(['success' => false, 'message' => 'Equipment not found']);
            exit();
        }

        $new_status = $result['is_equipped'] ? 0 : 1;
        $stmt = $conn->prepare("UPDATE character_equipment SET is_equipped = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_status, $equipment_id);

        echo json_encode(['success' => $stmt->execute(), 'is_equipped' => (bool)$new_status]);
        break;

    // ===== EQUIPMENT - Remove item from inventory =====
    case 'remove_item':
        $character_id = $_POST['character_id'] ?? 0;
        $equipment_id = $_POST['equipment_id'] ?? 0;

        if (!verifyCharacterOwnership($conn, $character_id, $current_user['id'])) {
            echo json_encode(['success' => false, 'message' => 'Not authorized']);
            exit();
        }

        // Verify the equipment belongs to this character
        $stmt = $conn->prepare("SELECT id FROM character_equipment WHERE id = ? AND character_id = ?");
        $stmt->bind_param("ii", $equipment_id, $character_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if (!$result) {
            echo json_encode(['success' => false, 'message' => 'Equipment not found']);
            exit();
        }

        // Delete the item
        $stmt = $conn->prepare("DELETE FROM character_equipment WHERE id = ?");
        $stmt->bind_param("i", $equipment_id);

        echo json_encode(['success' => $stmt->execute()]);
        break;
    
    // ===== POLLING - Get updated character data =====
    case 'poll_character':
        $character_id = $_GET['character_id'] ?? 0;
        
        if (!verifyCharacterOwnership($conn, $character_id, $current_user['id'])) {
            echo json_encode(['success' => false, 'message' => 'Not authorized']);
            exit();
        }
        
        $stmt = $conn->prepare("
            SELECT cs.current_hp, cs.max_hp, cs.temp_hp, cs.armor_class,
                   cs.current_spell_slots_1, cs.current_spell_slots_2, cs.current_spell_slots_3,
                   cs.current_spell_slots_4, cs.current_spell_slots_5, cs.current_spell_slots_6,
                   cs.current_spell_slots_7, cs.current_spell_slots_8, cs.current_spell_slots_9,
                   cs.spell_slots_1, cs.spell_slots_2, cs.spell_slots_3,
                   cs.spell_slots_4, cs.spell_slots_5, cs.spell_slots_6,
                   cs.spell_slots_7, cs.spell_slots_8, cs.spell_slots_9
            FROM character_stats cs 
            WHERE cs.character_id = ?
        ");
        $stmt->bind_param("i", $character_id);
        $stmt->execute();
        $stats = $stmt->get_result()->fetch_assoc();
        
        // Get status effects
        $stmt = $conn->prepare("SELECT * FROM character_status_effects WHERE character_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $character_id);
        $stmt->execute();
        $statuses = [];
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $statuses[] = $row;
        }
        
        echo json_encode([
            'success' => true, 
            'stats' => $stats,
            'status_effects' => $statuses
        ]);
        break;
    
    // ===== MESSAGES =====
    case 'send_message':
        $to_user_id = $_POST['to_user_id'] ?? 0;
        $message = trim($_POST['message'] ?? '');
        
        if (empty($message)) {
            echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
            exit();
        }
        
        $stmt = $conn->prepare("INSERT INTO messages (from_user_id, to_user_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $current_user['id'], $to_user_id, $message);
        echo json_encode(['success' => $stmt->execute()]);
        break;
    
    case 'get_messages':
        $other_user_id = $_GET['user_id'] ?? 0;
        $current_user_id = $current_user['id'];
        
        $stmt = $conn->prepare("
            SELECT m.*, u.username as from_username
            FROM messages m
            JOIN users u ON m.from_user_id = u.id
            WHERE (m.to_user_id = ? AND m.from_user_id = ?)
               OR (m.from_user_id = ? AND m.to_user_id = ?)
            ORDER BY m.created_at ASC
        ");
        $stmt->bind_param("iiii", $current_user_id, $other_user_id, $current_user_id, $other_user_id);
        $stmt->execute();
        
        $messages = [];
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
        
        // Mark as read
        $stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE to_user_id = ? AND from_user_id = ? AND is_read = 0");
        $stmt->bind_param("ii", $current_user_id, $other_user_id);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'messages' => $messages]);
        break;
    
    case 'get_unread_count':
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM messages WHERE to_user_id = ? AND is_read = 0");
        $stmt->bind_param("i", $current_user['id']);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        echo json_encode(['success' => true, 'count' => $result['count']]);
        break;
    
    // ===== LORE & RULES (read-only) =====
    case 'get_lore':
        $lore_id = $_GET['id'] ?? 0;
        $stmt = $conn->prepare("SELECT * FROM lore WHERE id = ? AND visible_to_players = 1");
        $stmt->bind_param("i", $lore_id);
        $stmt->execute();
        $lore = $stmt->get_result()->fetch_assoc();
        
        if ($lore) {
            echo json_encode(['success' => true, 'lore' => $lore]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lore not found']);
        }
        break;
    
    case 'get_rule':
        $rule_id = $_GET['id'] ?? 0;
        $stmt = $conn->prepare("SELECT * FROM rules WHERE id = ? AND visible_to_players = 1");
        $stmt->bind_param("i", $rule_id);
        $stmt->execute();
        $rule = $stmt->get_result()->fetch_assoc();
        
        if ($rule) {
            echo json_encode(['success' => true, 'rule' => $rule]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Rule not found']);
        }
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
        break;
}
