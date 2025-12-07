<?php
// Player API - Proxies certain actions to admin API
// This file exists so players can access messaging without going through /admin/

require_once '../includes/config.php';
require_once '../includes/auth.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$current_user = getCurrentUser();

if (!$current_user || !isset($current_user['id'])) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'send_message':
        $from_user_id = $current_user['id'];
        $to_user_id = $_POST['to_user_id'] ?? 0;
        $message = $_POST['message'] ?? '';
        
        if (empty($message)) {
            echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
            break;
        }
        
        if (empty($to_user_id)) {
            echo json_encode(['success' => false, 'message' => 'Recipient not specified']);
            break;
        }
        
        $stmt = $conn->prepare("INSERT INTO messages (from_user_id, to_user_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $from_user_id, $to_user_id, $message);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'id' => $conn->insert_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
        }
        break;
    
    case 'get_messages':
        $current_user_id = $current_user['id'];
        $other_user_id = $_GET['user_id'] ?? 0;
        
        $stmt = $conn->prepare("
            SELECT 
                m.*,
                CASE WHEN m.from_user_id = ? THEN 1 ELSE 0 END as is_sent
            FROM messages m
            WHERE (m.from_user_id = ? AND m.to_user_id = ?) 
               OR (m.from_user_id = ? AND m.to_user_id = ?)
            ORDER BY m.created_at ASC
        ");
        $stmt->bind_param("iiiii", $current_user_id, $current_user_id, $other_user_id, $other_user_id, $current_user_id);
        $stmt->execute();
        
        $messages = [];
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
        
        // Mark messages as read
        $stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE to_user_id = ? AND from_user_id = ? AND is_read = 0");
        $stmt->bind_param("ii", $current_user_id, $other_user_id);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'messages' => $messages]);
        break;
    
    case 'get_unread_count':
        $user_id = $current_user['id'];
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM messages WHERE to_user_id = ? AND is_read = 0");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        echo json_encode(['success' => true, 'count' => $result['count']]);
        break;
    
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
        $stmt = $conn->prepare("SELECT * FROM rules WHERE id = ?");
        $stmt->bind_param("i", $rule_id);
        $stmt->execute();
        $rule = $stmt->get_result()->fetch_assoc();
        
        if ($rule) {
            echo json_encode(['success' => true, 'rule' => $rule]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Rule not found']);
        }
        break;
    
    case 'poll_character':
        $character_id = $_GET['character_id'] ?? 0;
        
        // Verify this character belongs to the current player
        $stmt = $conn->prepare("
            SELECT c.*, cs.*, p.user_id
            FROM characters c
            JOIN players p ON c.player_id = p.id
            LEFT JOIN character_stats cs ON c.id = cs.character_id
            WHERE c.id = ? AND p.user_id = ?
        ");
        $stmt->bind_param("ii", $character_id, $current_user['id']);
        $stmt->execute();
        $character = $stmt->get_result()->fetch_assoc();
        
        if ($character) {
            echo json_encode(['success' => true, 'character' => $character]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Character not found']);
        }
        break;
    
    case 'update_character_hp':
        $character_id = $_POST['character_id'] ?? 0;
        $current_hp = $_POST['current_hp'] ?? 0;
        
        // Verify this character belongs to the current player
        $stmt = $conn->prepare("
            SELECT c.id 
            FROM characters c
            JOIN players p ON c.player_id = p.id
            WHERE c.id = ? AND p.user_id = ?
        ");
        $stmt->bind_param("ii", $character_id, $current_user['id']);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $stmt = $conn->prepare("UPDATE character_stats SET current_hp = ? WHERE character_id = ?");
            $stmt->bind_param("ii", $current_hp, $character_id);
            echo json_encode(['success' => $stmt->execute()]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Not authorized']);
        }
        break;
    
    case 'poll_character':
        $character_id = $_GET['character_id'] ?? 0;
        
        // Verify the character belongs to this user
        $stmt = $conn->prepare("
            SELECT c.id, cs.current_hp, cs.armor_class
            FROM characters c
            JOIN players p ON c.player_id = p.id
            LEFT JOIN character_stats cs ON c.id = cs.character_id
            WHERE c.id = ? AND p.user_id = ?
        ");
        $stmt->bind_param("ii", $character_id, $current_user['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            echo json_encode([
                'success' => true,
                'character' => [
                    'current_hp' => (int)$row['current_hp'],
                    'armor_class' => (int)$row['armor_class']
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Not authorized']);
        }
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
        break;
}
