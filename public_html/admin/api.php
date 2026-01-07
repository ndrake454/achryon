<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_CORE_ERROR || $error['type'] === E_COMPILE_ERROR)) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Fatal error',
            'error' => $error['message'],
            'file' => $error['file'],
            'line' => $error['line']
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

if (!$current_user || !isset($current_user['id']) || empty($current_user['id'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'User not found',
        'debug' => [
            'current_user' => $current_user,
            'session_user_id' => $_SESSION['user_id'] ?? 'not set'
        ]
    ]);
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    // ==================== CHARACTER OPERATIONS ====================
    case 'create_character':
        requireDM();
        $name = $_POST['name'] ?? '';
        $race = $_POST['race'] ?? '';
        $class = $_POST['class'] ?? '';
        $level = $_POST['level'] ?? 1;
        
        $stmt = $conn->prepare("INSERT INTO characters (name, race, class, level) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $name, $race, $class, $level);
        
        if ($stmt->execute()) {
            $char_id = $conn->insert_id;
            $conn->query("INSERT INTO character_stats (character_id) VALUES ($char_id)");
            $conn->query("INSERT INTO character_skills (character_id) VALUES ($char_id)");
            echo json_encode(['success' => true, 'character_id' => $char_id]);
        } else {
            echo json_encode(['success' => false]);
        }
        break;
        
    case 'delete_character':
        requireDM();
        $id = $_POST['id'] ?? 0;
        $stmt = $conn->prepare("DELETE FROM characters WHERE id = ?");
        $stmt->bind_param("i", $id);
        echo json_encode(['success' => $stmt->execute()]);
        break;
        
    case 'duplicate_character':
        requireDM();
        $id = $_POST['id'] ?? 0;
        $stmt = $conn->prepare("SELECT * FROM characters WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $char = $stmt->get_result()->fetch_assoc();
        
        if ($char) {
            $name = $char['name'] . ' (Copy)';
            $stmt = $conn->prepare("INSERT INTO characters (name, race, class, level, background, alignment) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssiss", $name, $char['race'], $char['class'], $char['level'], $char['background'], $char['alignment']);
            
            if ($stmt->execute()) {
                $new_id = $conn->insert_id;
                $conn->query("INSERT INTO character_stats (character_id, strength, dexterity, constitution, intelligence, wisdom, charisma, armor_class, max_hp, current_hp) SELECT $new_id, strength, dexterity, constitution, intelligence, wisdom, charisma, armor_class, max_hp, max_hp FROM character_stats WHERE character_id = $id");
                $conn->query("INSERT INTO character_skills (character_id) VALUES ($new_id)");
                echo json_encode(['success' => true, 'new_id' => $new_id]);
            } else {
                echo json_encode(['success' => false]);
            }
        } else {
            echo json_encode(['success' => false]);
        }
        break;
        
    case 'assign_character':
        requireDM();
        $char_id = $_POST['character_id'] ?? 0;
        $player_id = $_POST['player_id'] ?? 0;
        
        $stmt = $conn->prepare("UPDATE characters SET player_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $player_id, $char_id);
        echo json_encode(['success' => $stmt->execute()]);
        break;
        
    case 'unassign_character':
        requireDM();
        $char_id = $_POST['character_id'] ?? 0;
        
        $stmt = $conn->prepare("UPDATE characters SET player_id = NULL WHERE id = ?");
        $stmt->bind_param("i", $char_id);
        echo json_encode(['success' => $stmt->execute()]);
        break;
        
    case 'get_unassigned_characters':
        requireDM();
        $result = $conn->query("SELECT id, name, race, class, level FROM characters WHERE player_id IS NULL ORDER BY name");
        $characters = [];
        while ($row = $result->fetch_assoc()) {
            $characters[] = $row;
        }
        echo json_encode(['success' => true, 'characters' => $characters]);
        break;

    case 'update_character':
        $char_id = $_POST['id'] ?? 0;
        $field = $_POST['field'] ?? '';
        $value = $_POST['value'] ?? '';
        
        $allowed_fields = ['name', 'race', 'class', 'level', 'background', 'alignment'];
        if (!in_array($field, $allowed_fields)) {
            echo json_encode(['success' => false, 'error' => 'Invalid field']);
            break;
        }
        
        $stmt = $conn->prepare("UPDATE characters SET $field = ? WHERE id = ?");
        $stmt->bind_param("si", $value, $char_id);
        echo json_encode(['success' => $stmt->execute()]);
        break;

    case 'update_character_basic':
        requireDM();
        
        $char_id = $_POST['character_id'] ?? 0;
        $name = $_POST['name'] ?? '';
        $race = $_POST['race'] ?? '';
        $class = $_POST['class'] ?? '';
        $level = $_POST['level'] ?? 1;
        $exp = $_POST['experience'] ?? 0;
        $background = $_POST['background'] ?? '';
        $alignment = $_POST['alignment'] ?? '';
        
        $stmt = $conn->prepare("UPDATE characters SET name = ?, race = ?, class = ?, level = ?, experience = ?, background = ?, alignment = ? WHERE id = ?");
        $stmt->bind_param("sssiissi", $name, $race, $class, $level, $exp, $background, $alignment, $char_id);
        echo json_encode(['success' => $stmt->execute()]);
        break;

    // ==================== CHARACTER STATS ====================
    case 'update_character_stat':
        $char_id = $_POST['character_id'] ?? 0;
        $field = $_POST['field'] ?? '';
        $value = $_POST['value'] ?? '';
        
        $allowed_fields = ['max_hp', 'current_hp', 'armor_class', 'proficiency_bonus', 'initiative', 'speed', 'hit_dice'];
        if (!in_array($field, $allowed_fields)) {
            echo json_encode(['success' => false, 'error' => 'Invalid field']);
            break;
        }
        
        if ($field === 'hit_dice') {
            $stmt = $conn->prepare("UPDATE character_stats SET $field = ? WHERE character_id = ?");
            $stmt->bind_param("si", $value, $char_id);
        } else {
            $stmt = $conn->prepare("UPDATE character_stats SET $field = ? WHERE character_id = ?");
            $stmt->bind_param("ii", $value, $char_id);
        }
        echo json_encode(['success' => $stmt->execute()]);
        break;

    case 'update_character_combat':
        requireDM();
        
        $char_id = $_POST['character_id'] ?? 0;
        $current_hp = $_POST['current_hp'] ?? 0;
        $max_hp = $_POST['max_hp'] ?? 0;
        $ac = $_POST['armor_class'] ?? 10;
        $initiative = $_POST['initiative'] ?? 0;
        $speed = $_POST['speed'] ?? 30;
        $hit_dice = $_POST['hit_dice'] ?? '1d8';
        $prof = $_POST['proficiency_bonus'] ?? 2;
        
        $stmt = $conn->prepare("UPDATE character_stats SET current_hp = ?, max_hp = ?, armor_class = ?, initiative = ?, speed = ?, hit_dice = ?, proficiency_bonus = ? WHERE character_id = ?");
        $stmt->bind_param("iiiiisii", $current_hp, $max_hp, $ac, $initiative, $speed, $hit_dice, $prof, $char_id);
        echo json_encode(['success' => $stmt->execute()]);
        break;
    
    case 'update_character_abilities':
        requireDM();
        
        $char_id = $_POST['character_id'] ?? 0;
        $str = $_POST['strength'] ?? 10;
        $dex = $_POST['dexterity'] ?? 10;
        $con = $_POST['constitution'] ?? 10;
        $int = $_POST['intelligence'] ?? 10;
        $wis = $_POST['wisdom'] ?? 10;
        $cha = $_POST['charisma'] ?? 10;
        
        $stmt = $conn->prepare("UPDATE character_stats SET strength = ?, dexterity = ?, constitution = ?, intelligence = ?, wisdom = ?, charisma = ? WHERE character_id = ?");
        $stmt->bind_param("iiiiiii", $str, $dex, $con, $int, $wis, $cha, $char_id);
        echo json_encode(['success' => $stmt->execute()]);
        break;
        
case 'update_stat':
    requireDM();
    $char_id = $_POST['character_id'] ?? 0;
    $stat = $_POST['stat'] ?? '';
    $value = $_POST['value'] ?? '';
    
    $allowed = [
        'strength', 'dexterity', 'constitution', 'intelligence', 'wisdom', 'charisma', 
        'armor_class', 'max_hp', 'initiative', 'speed', 
        'spellcasting_ability', 'proficiency_bonus', 'hit_dice'
    ];
    
    if (in_array($stat, $allowed)) {
        // Sanitize column name (prevent SQL injection)
        $safe_stat = preg_replace('/[^a-z_]/', '', $stat);
        
        // Handle string values for hit_dice and spellcasting_ability
        if ($stat === 'hit_dice' || $stat === 'spellcasting_ability') {
            $stmt = $conn->prepare("UPDATE character_stats SET `$safe_stat` = ? WHERE character_id = ?");
            $stmt->bind_param("si", $value, $char_id);
        } else {
            $value_int = intval($value);
            $stmt = $conn->prepare("UPDATE character_stats SET `$safe_stat` = ? WHERE character_id = ?");
            $stmt->bind_param("ii", $value_int, $char_id);
        }
        
        $success = $stmt->execute();
        echo json_encode([
            'success' => $success,
            'debug' => [
                'stat' => $stat,
                'value' => $value,
                'char_id' => $char_id,
                'error' => $stmt->error
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid stat: ' . $stat]);
    }
    break;

    case 'update_hp':
        $char_id = $_POST['character_id'] ?? 0;
        $change = $_POST['change'] ?? null;
        $current_hp = $_POST['current_hp'] ?? null;
        $max_hp = $_POST['max_hp'] ?? null;
        
        if (!isDM()) {
            $stmt = $conn->prepare("SELECT c.id FROM characters c JOIN players p ON c.player_id = p.id WHERE c.id = ? AND p.user_id = ?");
            $stmt->bind_param("ii", $char_id, $_SESSION['user_id']);
            $stmt->execute();
            if (!$stmt->get_result()->fetch_assoc()) {
                echo json_encode(['success' => false, 'message' => 'Permission denied']);
                exit();
            }
        }
        
        if ($change !== null) {
            $stmt = $conn->prepare("UPDATE character_stats SET current_hp = GREATEST(0, LEAST(max_hp, current_hp + ?)) WHERE character_id = ?");
            $stmt->bind_param("ii", $change, $char_id);
            
            if ($stmt->execute()) {
                $stmt = $conn->prepare("SELECT current_hp, max_hp FROM character_stats WHERE character_id = ?");
                $stmt->bind_param("i", $char_id);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                echo json_encode(['success' => true, 'current_hp' => $result['current_hp'], 'max_hp' => $result['max_hp']]);
            } else {
                echo json_encode(['success' => false]);
            }
        } elseif ($current_hp !== null) {
            $stmt = $conn->prepare("UPDATE character_stats SET current_hp = ? WHERE character_id = ?");
            $stmt->bind_param("ii", $current_hp, $char_id);
            echo json_encode(['success' => $stmt->execute()]);
        } elseif ($max_hp !== null) {
            $stmt = $conn->prepare("UPDATE character_stats SET max_hp = ? WHERE character_id = ?");
            $stmt->bind_param("ii", $max_hp, $char_id);
            echo json_encode(['success' => $stmt->execute()]);
        } else {
            echo json_encode(['success' => false, 'error' => 'No HP value provided']);
        }
        break;

    case 'update_ac':
        $char_id = $_POST['character_id'] ?? 0;
        $ac = $_POST['ac'] ?? 10;
        
        $stmt = $conn->prepare("UPDATE character_stats SET armor_class = ? WHERE character_id = ?");
        $stmt->bind_param("ii", $ac, $char_id);
        echo json_encode(['success' => $stmt->execute()]);
        break;

    case 'update_ability':
        $char_id = $_POST['character_id'] ?? 0;
        $ability = $_POST['ability'] ?? '';
        $value = $_POST['value'] ?? 10;
        
        $allowed_abilities = ['strength', 'dexterity', 'constitution', 'intelligence', 'wisdom', 'charisma'];
        if (!in_array($ability, $allowed_abilities)) {
            echo json_encode(['success' => false, 'error' => 'Invalid ability']);
            break;
        }
        
        $stmt = $conn->prepare("UPDATE character_stats SET $ability = ? WHERE character_id = ?");
        $stmt->bind_param("ii", $value, $char_id);
        echo json_encode(['success' => $stmt->execute()]);
        break;

    // ==================== SKILLS - FIXED ====================
    case 'toggle_skill':
        $char_id = $_POST['character_id'] ?? 0;
        $skill = $_POST['skill'] ?? '';
        // Accept both 'value' and 'proficient' parameters
        $proficient = isset($_POST['value']) ? intval($_POST['value']) : (isset($_POST['proficient']) ? intval($_POST['proficient']) : 0);
        
        // Check permissions
        if (!isDM()) {
            $stmt = $conn->prepare("SELECT c.id FROM characters c JOIN players p ON c.player_id = p.id WHERE c.id = ? AND p.user_id = ?");
            $stmt->bind_param("ii", $char_id, $_SESSION['user_id']);
            $stmt->execute();
            if (!$stmt->get_result()->fetch_assoc()) {
                echo json_encode(['success' => false, 'message' => 'Permission denied']);
                exit();
            }
        }
        
        $allowed = ['acrobatics', 'animal_handling', 'arcana', 'athletics', 'deception', 'history', 'insight', 'intimidation', 'investigation', 'medicine', 'nature', 'perception', 'performance', 'persuasion', 'religion', 'sleight_of_hand', 'stealth', 'survival'];
        
        if (in_array($skill, $allowed)) {
            // Update the character_skills table (NOT character_stats!)
            $stmt = $conn->prepare("UPDATE character_skills SET $skill = ? WHERE character_id = ?");
            $stmt->bind_param("ii", $proficient, $char_id);
            $success = $stmt->execute();
            echo json_encode(['success' => $success, 'skill' => $skill, 'value' => $proficient]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid skill: ' . $skill]);
        }
        break;

    // ==================== SPELL SLOTS ====================
    case 'update_max_spell_slots':
        $char_id = $_POST['character_id'] ?? 0;
        $level = $_POST['level'] ?? 1;
        $max_slots = $_POST['max_slots'] ?? 0;
        
        $column = "spell_slots_$level";
        $stmt = $conn->prepare("UPDATE character_stats SET $column = ? WHERE character_id = ?");
        $stmt->bind_param("ii", $max_slots, $char_id);
        echo json_encode(['success' => $stmt->execute()]);
        break;
    
    case 'update_current_spell_slots':
        $char_id = $_POST['character_id'] ?? 0;
        $level = $_POST['level'] ?? 1;
        $current_slots = $_POST['current_slots'] ?? 0;
        
        $column = "current_spell_slots_$level";
        $stmt = $conn->prepare("UPDATE character_stats SET $column = ? WHERE character_id = ?");
        $stmt->bind_param("ii", $current_slots, $char_id);
        echo json_encode(['success' => $stmt->execute()]);
        break;

    // ==================== EQUIPMENT ====================
    case 'add_equipment':
        $char_id = $_POST['character_id'] ?? 0;
        $item_name = $_POST['item_name'] ?? '';
        $type = $_POST['type'] ?? '';
        $rarity = $_POST['rarity'] ?? '';
        $properties = $_POST['properties'] ?? '';
        $description = $_POST['description'] ?? '';
        $is_equipped = $_POST['is_equipped'] ?? 0;
        
        $stmt = $conn->prepare("INSERT INTO character_equipment (character_id, item_name, type, rarity, properties, description, is_equipped) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssi", $char_id, $item_name, $type, $rarity, $properties, $description, $is_equipped);
        echo json_encode(['success' => $stmt->execute(), 'id' => $conn->insert_id]);
        break;
        
    case 'delete_equipment':
        $id = $_POST['id'] ?? 0;
        $stmt = $conn->prepare("DELETE FROM character_equipment WHERE id = ?");
        $stmt->bind_param("i", $id);
        echo json_encode(['success' => $stmt->execute()]);
        break;

    case 'toggle_equipped':
    case 'toggle_equipment':
        $equipment_id = $_POST['id'] ?? $_POST['equipment_id'] ?? 0;
        $equipped = $_POST['equipped'] ?? $_POST['is_equipped'] ?? 0;
        
        $stmt = $conn->prepare("UPDATE character_equipment SET is_equipped = ? WHERE id = ?");
        $stmt->bind_param("ii", $equipped, $equipment_id);
        echo json_encode(['success' => $stmt->execute()]);
        break;

    // ==================== SPELLS ====================
    case 'add_spell':
        $char_id = $_POST['character_id'] ?? 0;
        $spell_name = $_POST['spell_name'] ?? $_POST['name'] ?? '';
        $spell_level = $_POST['spell_level'] ?? $_POST['level'] ?? 0;
        $school = $_POST['school'] ?? '';
        $casting_time = $_POST['casting_time'] ?? '';
        $range_area = $_POST['range_area'] ?? '';
        $components = $_POST['components'] ?? '';
        $duration = $_POST['duration'] ?? '';
        $description = $_POST['description'] ?? '';
        
        $stmt = $conn->prepare("INSERT INTO character_spells (character_id, name, level, school, casting_time, range_area, components, duration, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isissssss", $char_id, $spell_name, $spell_level, $school, $casting_time, $range_area, $components, $duration, $description);
        echo json_encode(['success' => $stmt->execute(), 'id' => $conn->insert_id]);
        break;
        
    case 'delete_character_spell':
        $id = $_POST['id'] ?? 0;
        $stmt = $conn->prepare("DELETE FROM character_spells WHERE id = ?");
        $stmt->bind_param("i", $id);
        echo json_encode(['success' => $stmt->execute()]);
        break;

    // ==================== FEATS ====================
    case 'add_feat':
        $char_id = $_POST['character_id'] ?? 0;
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        
        $stmt = $conn->prepare("INSERT INTO character_feats (character_id, name, description) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $char_id, $name, $description);
        echo json_encode(['success' => $stmt->execute(), 'id' => $conn->insert_id]);
        break;
    
    case 'delete_feat':
        $id = $_POST['id'] ?? 0;
        $stmt = $conn->prepare("DELETE FROM character_feats WHERE id = ?");
        $stmt->bind_param("i", $id);
        echo json_encode(['success' => $stmt->execute()]);
        break;

    // ==================== STATUS EFFECTS ====================
    case 'add_status':
        requireDM();
        $char_id = $_POST['character_id'] ?? 0;
        $status_name = $_POST['status_name'] ?? '';
        $description = $_POST['description'] ?? '';
        
        $stmt = $conn->prepare("INSERT INTO character_status_effects (character_id, status_name, description) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $char_id, $status_name, $description);
        echo json_encode(['success' => $stmt->execute(), 'id' => $conn->insert_id]);
        break;
    
    case 'get_status_effects':
        $char_id = $_GET['character_id'] ?? 0;
        
        $stmt = $conn->prepare("SELECT * FROM character_status_effects WHERE character_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $char_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $statuses = [];
        while ($row = $result->fetch_assoc()) {
            $statuses[] = $row;
        }
        
        echo json_encode(['success' => true, 'statuses' => $statuses]);
        break;
    
    case 'delete_status':
        requireDM();
        $id = $_POST['id'] ?? 0;
        
        $stmt = $conn->prepare("DELETE FROM character_status_effects WHERE id = ?");
        $stmt->bind_param("i", $id);
        echo json_encode(['success' => $stmt->execute()]);
        break;

    // ==================== MONSTER STATUS EFFECTS ====================
    case 'add_monster_status':
        requireDM();
        $monster_id = $_POST['monster_id'] ?? 0;
        $status_name = $_POST['status_name'] ?? '';
        $description = $_POST['description'] ?? '';
        
        $stmt = $conn->prepare("INSERT INTO monster_status_effects (monster_id, status_name, description) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $monster_id, $status_name, $description);
        echo json_encode(['success' => $stmt->execute(), 'id' => $conn->insert_id]);
        break;
    
    case 'get_monster_status_effects':
        $monster_id = $_GET['monster_id'] ?? 0;
        
        $stmt = $conn->prepare("SELECT * FROM monster_status_effects WHERE monster_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $monster_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $statuses = [];
        while ($row = $result->fetch_assoc()) {
            $statuses[] = $row;
        }
        
        echo json_encode(['success' => true, 'statuses' => $statuses]);
        break;
    
    case 'delete_monster_status':
        requireDM();
        $id = $_POST['id'] ?? 0;
        
        $stmt = $conn->prepare("DELETE FROM monster_status_effects WHERE id = ?");
        $stmt->bind_param("i", $id);
        echo json_encode(['success' => $stmt->execute()]);
        break;

    // ==================== MONSTERS ====================
    case 'create_monster':
        requireDM();
        $name = $_POST['name'] ?? '';
        $type = $_POST['type'] ?? '';
        $cr = $_POST['challenge_rating'] ?? '';
        $ac = intval($_POST['armor_class'] ?? 10);
        $hp = intval($_POST['max_hp'] ?? 10);
        $current_hp = intval($_POST['current_hp'] ?? $hp);
        $str = intval($_POST['strength'] ?? 10);
        $dex = intval($_POST['dexterity'] ?? 10);
        $con = intval($_POST['constitution'] ?? 10);
        $intel = intval($_POST['intelligence'] ?? 10);
        $wis = intval($_POST['wisdom'] ?? 10);
        $cha = intval($_POST['charisma'] ?? 10);
        $attacks = $_POST['attacks'] ?? '';
        $desc = $_POST['description'] ?? '';
        
        // Check if attacks column exists
        $cols_check = $conn->query("SHOW COLUMNS FROM monsters LIKE 'attacks'");
        $has_attacks = $cols_check && $cols_check->num_rows > 0;
        
        if ($has_attacks) {
            $stmt = $conn->prepare("INSERT INTO monsters (name, type, challenge_rating, armor_class, max_hp, current_hp, strength, dexterity, constitution, intelligence, wisdom, charisma, attacks, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
                break;
            }
            $stmt->bind_param("sssiiiiiiiiiss", $name, $type, $cr, $ac, $hp, $current_hp, $str, $dex, $con, $intel, $wis, $cha, $attacks, $desc);
        } else {
            $stmt = $conn->prepare("INSERT INTO monsters (name, type, challenge_rating, armor_class, max_hp, current_hp, strength, dexterity, constitution, intelligence, wisdom, charisma, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
                break;
            }
            $stmt->bind_param("sssiiiiiiiiis", $name, $type, $cr, $ac, $hp, $current_hp, $str, $dex, $con, $intel, $wis, $cha, $desc);
        }
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'id' => $conn->insert_id]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Execute failed: ' . $stmt->error]);
        }
        break;
        
    case 'delete_monster':
        requireDM();
        $id = $_POST['id'] ?? 0;
        $stmt = $conn->prepare("DELETE FROM monsters WHERE id = ?");
        $stmt->bind_param("i", $id);
        echo json_encode(['success' => $stmt->execute()]);
        break;

    case 'update_monster':
        requireDM();
        $id = $_POST['id'] ?? 0;
        $name = $_POST['name'] ?? '';
        $type = $_POST['type'] ?? '';
        $cr = $_POST['challenge_rating'] ?? '';
        $ac = intval($_POST['armor_class'] ?? 10);
        $hp = intval($_POST['max_hp'] ?? 10);
        $str = intval($_POST['strength'] ?? 10);
        $dex = intval($_POST['dexterity'] ?? 10);
        $con = intval($_POST['constitution'] ?? 10);
        $intel = intval($_POST['intelligence'] ?? 10);
        $wis = intval($_POST['wisdom'] ?? 10);
        $cha = intval($_POST['charisma'] ?? 10);
        $attacks = $_POST['attacks'] ?? '';
        $desc = $_POST['description'] ?? '';
        
        $cols_check = $conn->query("SHOW COLUMNS FROM monsters LIKE 'attacks'");
        $has_attacks = $cols_check && $cols_check->num_rows > 0;
        
        if ($has_attacks) {
            $stmt = $conn->prepare("UPDATE monsters SET name = ?, type = ?, challenge_rating = ?, armor_class = ?, max_hp = ?, strength = ?, dexterity = ?, constitution = ?, intelligence = ?, wisdom = ?, charisma = ?, attacks = ?, description = ? WHERE id = ?");
            if (!$stmt) {
                echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
                break;
            }
            $stmt->bind_param("sssiiiiiiisssi", $name, $type, $cr, $ac, $hp, $str, $dex, $con, $intel, $wis, $cha, $attacks, $desc, $id);
        } else {
            $stmt = $conn->prepare("UPDATE monsters SET name = ?, type = ?, challenge_rating = ?, armor_class = ?, max_hp = ?, strength = ?, dexterity = ?, constitution = ?, intelligence = ?, wisdom = ?, charisma = ?, description = ? WHERE id = ?");
            if (!$stmt) {
                echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
                break;
            }
            $stmt->bind_param("sssiiiiiiiisi", $name, $type, $cr, $ac, $hp, $str, $dex, $con, $intel, $wis, $cha, $desc, $id);
        }
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Execute failed: ' . $stmt->error]);
        }
        break;

    case 'get_monster':
        $id = $_GET['id'] ?? 0;
        
        $stmt = $conn->prepare("SELECT * FROM monsters WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $monster = $stmt->get_result()->fetch_assoc();
        
        echo json_encode(['success' => $monster !== null, 'monster' => $monster]);
        break;

    // ==================== ITEMS ====================
    case 'create_item':
        requireDM();
        $name = $_POST['name'] ?? '';
        $type = $_POST['type'] ?? '';
        $rarity = $_POST['rarity'] ?? '';
        $properties = $_POST['properties'] ?? '';
        $description = $_POST['description'] ?? '';
        
        $stmt = $conn->prepare("INSERT INTO items (name, type, rarity, properties, description) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $type, $rarity, $properties, $description);
        echo json_encode(['success' => $stmt->execute(), 'id' => $conn->insert_id]);
        break;
        
    case 'delete_item':
        requireDM();
        $id = $_POST['id'] ?? 0;
        $stmt = $conn->prepare("DELETE FROM items WHERE id = ?");
        $stmt->bind_param("i", $id);
        echo json_encode(['success' => $stmt->execute()]);
        break;

    case 'update_item':
        requireDM();
        $id = $_POST['id'] ?? 0;
        $name = $_POST['name'] ?? '';
        $type = $_POST['type'] ?? '';
        $rarity = $_POST['rarity'] ?? '';
        $properties = $_POST['properties'] ?? '';
        $description = $_POST['description'] ?? '';
        
        $stmt = $conn->prepare("UPDATE items SET name = ?, type = ?, rarity = ?, properties = ?, description = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $name, $type, $rarity, $properties, $description, $id);
        echo json_encode(['success' => $stmt->execute()]);
        break;

    case 'get_item':
        $id = $_GET['id'] ?? 0;
        
        $stmt = $conn->prepare("SELECT * FROM items WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $item = $stmt->get_result()->fetch_assoc();
        
        echo json_encode(['success' => $item !== null, 'item' => $item]);
        break;

    // ==================== SPELLS LIBRARY ====================
    case 'create_spell':
        requireDM();
        $name = $_POST['name'] ?? '';
        $level = $_POST['level'] ?? 0;
        $school = $_POST['school'] ?? '';
        $casting_time = $_POST['casting_time'] ?? '';
        $range_area = $_POST['range_area'] ?? '';
        $components = $_POST['components'] ?? '';
        $duration = $_POST['duration'] ?? '';
        $description = $_POST['description'] ?? '';
        
        $stmt = $conn->prepare("INSERT INTO spells (name, level, school, casting_time, range_area, components, duration, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sissssss", $name, $level, $school, $casting_time, $range_area, $components, $duration, $description);
        echo json_encode(['success' => $stmt->execute(), 'id' => $conn->insert_id]);
        break;

    case 'update_spell':
        requireDM();
        $id = $_POST['id'] ?? 0;
        $name = $_POST['name'] ?? '';
        $level = $_POST['level'] ?? 0;
        $school = $_POST['school'] ?? '';
        $casting_time = $_POST['casting_time'] ?? '';
        $range_area = $_POST['range_area'] ?? '';
        $components = $_POST['components'] ?? '';
        $duration = $_POST['duration'] ?? '';
        $description = $_POST['description'] ?? '';
        
        $stmt = $conn->prepare("UPDATE spells SET name = ?, level = ?, school = ?, casting_time = ?, range_area = ?, components = ?, duration = ?, description = ? WHERE id = ?");
        $stmt->bind_param("sissssssi", $name, $level, $school, $casting_time, $range_area, $components, $duration, $description, $id);
        echo json_encode(['success' => $stmt->execute()]);
        break;

    case 'get_spell':
        $id = $_GET['id'] ?? 0;
        
        $stmt = $conn->prepare("SELECT * FROM spells WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $spell = $stmt->get_result()->fetch_assoc();
        
        echo json_encode(['success' => $spell !== null, 'spell' => $spell]);
        break;

    case 'delete_spell':
        requireDM();
        $id = $_POST['id'] ?? 0;
        $stmt = $conn->prepare("DELETE FROM spells WHERE id = ?");
        $stmt->bind_param("i", $id);
        echo json_encode(['success' => $stmt->execute()]);
        break;

    // ==================== LORE ====================
    case 'create_lore':
        requireDM();
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        $category = $_POST['category'] ?? 'Other';
        $visible = $_POST['visible_to_players'] ?? 0;
        
        $stmt = $conn->prepare("INSERT INTO lore (title, content, category, visible_to_players) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $title, $content, $category, $visible);
        echo json_encode(['success' => $stmt->execute(), 'id' => $conn->insert_id]);
        break;
        
    case 'update_lore':
        requireDM();
        $id = $_POST['id'] ?? 0;
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        $category = $_POST['category'] ?? 'Other';
        $visible = $_POST['visible_to_players'] ?? 0;
        
        $stmt = $conn->prepare("UPDATE lore SET title = ?, content = ?, category = ?, visible_to_players = ? WHERE id = ?");
        $stmt->bind_param("sssii", $title, $content, $category, $visible, $id);
        echo json_encode(['success' => $stmt->execute()]);
        break;
        
    case 'delete_lore':
        requireDM();
        $id = $_POST['id'] ?? 0;
        $stmt = $conn->prepare("DELETE FROM lore WHERE id = ?");
        $stmt->bind_param("i", $id);
        echo json_encode(['success' => $stmt->execute()]);
        break;

    case 'toggle_lore_visibility':
        requireDM();
        $id = $_POST['id'] ?? 0;
        $visible = $_POST['visible'] ?? 0;
        
        $stmt = $conn->prepare("UPDATE lore SET visible_to_players = ? WHERE id = ?");
        $stmt->bind_param("ii", $visible, $id);
        echo json_encode(['success' => $stmt->execute()]);
        break;

    case 'get_lore':
        $id = $_GET['id'] ?? 0;

        $stmt = $conn->prepare("SELECT * FROM lore WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $lore = $stmt->get_result()->fetch_assoc();

        // Players can only see visible lore
        if (!isDM() && $lore && !$lore['visible_to_players']) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            break;
        }

        echo json_encode(['success' => $lore !== null, 'lore' => $lore]);
        break;

    case 'reorder_lore':
        requireDM();
        $id = $_POST['id'] ?? 0;
        $direction = $_POST['direction'] ?? 'up';

        $stmt = $conn->prepare("SELECT order_index FROM lore WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $current = $stmt->get_result()->fetch_assoc();

        if (!$current) {
            echo json_encode(['success' => false]);
            break;
        }

        $current_order = $current['order_index'];

        if ($direction === 'up') {
            $stmt = $conn->prepare("SELECT id FROM lore WHERE order_index < ? ORDER BY order_index DESC LIMIT 1");
            $stmt->bind_param("i", $current_order);
            $stmt->execute();
            $swap = $stmt->get_result()->fetch_assoc();

            if ($swap) {
                $stmt = $conn->prepare("UPDATE lore SET order_index = ? WHERE id = ?");
                $stmt->bind_param("ii", $current_order, $swap['id']);
                $stmt->execute();

                $new_order = $current_order - 1;
                $stmt = $conn->prepare("UPDATE lore SET order_index = ? WHERE id = ?");
                $stmt->bind_param("ii", $new_order, $id);
                $stmt->execute();
            }
        } else {
            $stmt = $conn->prepare("SELECT id FROM lore WHERE order_index > ? ORDER BY order_index ASC LIMIT 1");
            $stmt->bind_param("i", $current_order);
            $stmt->execute();
            $swap = $stmt->get_result()->fetch_assoc();

            if ($swap) {
                $stmt = $conn->prepare("UPDATE lore SET order_index = ? WHERE id = ?");
                $stmt->bind_param("ii", $current_order, $swap['id']);
                $stmt->execute();

                $new_order = $current_order + 1;
                $stmt = $conn->prepare("UPDATE lore SET order_index = ? WHERE id = ?");
                $stmt->bind_param("ii", $new_order, $id);
                $stmt->execute();
            }
        }

        echo json_encode(['success' => true]);
        break;

    // ==================== RULES ====================
    case 'create_rule':
        requireDM();
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        $visible = $_POST['visible_to_players'] ?? 1;
        
        $stmt = $conn->prepare("INSERT INTO rules (title, content, visible_to_players) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $title, $content, $visible);
        echo json_encode(['success' => $stmt->execute(), 'id' => $conn->insert_id]);
        break;
        
    case 'update_rule':
        requireDM();
        $id = $_POST['id'] ?? 0;
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        $visible = $_POST['visible_to_players'] ?? 1;
        
        $stmt = $conn->prepare("UPDATE rules SET title = ?, content = ?, visible_to_players = ? WHERE id = ?");
        $stmt->bind_param("ssii", $title, $content, $visible, $id);
        echo json_encode(['success' => $stmt->execute()]);
        break;

    case 'delete_rule':
        requireDM();
        $id = $_POST['id'] ?? 0;
        $stmt = $conn->prepare("DELETE FROM rules WHERE id = ?");
        $stmt->bind_param("i", $id);
        echo json_encode(['success' => $stmt->execute()]);
        break;

    case 'get_rule':
        $id = $_GET['id'] ?? 0;
        
        $stmt = $conn->prepare("SELECT * FROM rules WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $rule = $stmt->get_result()->fetch_assoc();
        
        echo json_encode(['success' => $rule !== null, 'rule' => $rule]);
        break;

    case 'reorder_rule':
        requireDM();
        $id = $_POST['id'] ?? 0;
        $direction = $_POST['direction'] ?? 'up';
        
        $stmt = $conn->prepare("SELECT order_index FROM rules WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $current = $stmt->get_result()->fetch_assoc();
        
        if (!$current) {
            echo json_encode(['success' => false]);
            break;
        }
        
        $current_order = $current['order_index'];
        
        if ($direction === 'up') {
            $stmt = $conn->prepare("SELECT id FROM rules WHERE order_index < ? ORDER BY order_index DESC LIMIT 1");
            $stmt->bind_param("i", $current_order);
            $stmt->execute();
            $swap = $stmt->get_result()->fetch_assoc();
            
            if ($swap) {
                $stmt = $conn->prepare("UPDATE rules SET order_index = ? WHERE id = ?");
                $stmt->bind_param("ii", $current_order, $swap['id']);
                $stmt->execute();
                
                $new_order = $current_order - 1;
                $stmt = $conn->prepare("UPDATE rules SET order_index = ? WHERE id = ?");
                $stmt->bind_param("ii", $new_order, $id);
                $stmt->execute();
            }
        } else {
            $stmt = $conn->prepare("SELECT id FROM rules WHERE order_index > ? ORDER BY order_index ASC LIMIT 1");
            $stmt->bind_param("i", $current_order);
            $stmt->execute();
            $swap = $stmt->get_result()->fetch_assoc();
            
            if ($swap) {
                $stmt = $conn->prepare("UPDATE rules SET order_index = ? WHERE id = ?");
                $stmt->bind_param("ii", $current_order, $swap['id']);
                $stmt->execute();
                
                $new_order = $current_order + 1;
                $stmt = $conn->prepare("UPDATE rules SET order_index = ? WHERE id = ?");
                $stmt->bind_param("ii", $new_order, $id);
                $stmt->execute();
            }
        }
        
        echo json_encode(['success' => true]);
        break;

    // ==================== MESSAGES ====================
    case 'send_message':
        if (!isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Not logged in']);
            break;
        }
        
        if (!$current_user || !isset($current_user['id'])) {
            echo json_encode(['success' => false, 'message' => 'Current user not found']);
            break;
        }
        
        $from_user_id = $current_user['id'];
        $to_user_id = $_POST['to_user_id'] ?? 0;
        $message = $_POST['message'] ?? '';
        
        if (empty($message)) {
            echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
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
        
        $stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE to_user_id = ? AND from_user_id = ?");
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

    // ==================== PLAYERS ====================
    case 'create_player':
        requireDM();
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $display_name = $_POST['display_name'] ?? '';
        
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $role = 'player';
        
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $email, $hashed, $role);
        
        if ($stmt->execute()) {
            $user_id = $conn->insert_id;
            $stmt = $conn->prepare("INSERT INTO players (user_id, display_name) VALUES (?, ?)");
            $stmt->bind_param("is", $user_id, $display_name);
            $stmt->execute();
            echo json_encode(['success' => true, 'user_id' => $user_id]);
        } else {
            echo json_encode(['success' => false, 'message' => $conn->error]);
        }
        break;

    case 'delete_player':
        requireDM();
        $user_id = $_POST['user_id'] ?? 0;
        
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'player'");
        $stmt->bind_param("i", $user_id);
        echo json_encode(['success' => $stmt->execute()]);
        break;

    case 'update_player':
        requireDM();
        $user_id = $_POST['user_id'] ?? 0;
        $display_name = $_POST['display_name'] ?? '';
        $email = $_POST['email'] ?? '';
        
        $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        
        $stmt = $conn->prepare("UPDATE players SET display_name = ? WHERE user_id = ?");
        $stmt->bind_param("si", $display_name, $user_id);
        echo json_encode(['success' => $stmt->execute()]);
        break;

    case 'reset_player_password':
        requireDM();
        $user_id = $_POST['user_id'] ?? 0;
        $password = $_POST['password'] ?? '';
        
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed, $user_id);
        echo json_encode(['success' => $stmt->execute()]);
        break;

    // ==================== COMBAT ====================
    case 'create_combat_session':
        requireDM();
        $conn->query("UPDATE combat_sessions SET is_active = 0");
        $conn->query("INSERT INTO combat_sessions (is_active) VALUES (1)");
        echo json_encode(['success' => true, 'session_id' => $conn->insert_id]);
        break;

    case 'end_combat_session':
        requireDM();
        $session_id = $_POST['session_id'] ?? 0;
        
        $stmt = $conn->prepare("UPDATE combat_sessions SET is_active = 0 WHERE id = ?");
        $stmt->bind_param("i", $session_id);
        echo json_encode(['success' => $stmt->execute()]);
        break;

    case 'add_to_combat':
        requireDM();
        $entity_type = $_POST['entity_type'] ?? '';
        $entity_id = $_POST['entity_id'] ?? 0;
        $initiative = $_POST['initiative'] ?? 10;
        
        $result = $conn->query("SELECT id FROM combat_sessions WHERE is_active = 1 LIMIT 1");
        $session = $result->fetch_assoc();
        
        if (!$session) {
            echo json_encode(['success' => false, 'message' => 'No active combat session']);
            break;
        }
        
        $max_result = $conn->query("SELECT MAX(turn_order) as max_order FROM combat_participants WHERE session_id = " . $session['id']);
        $max_row = $max_result->fetch_assoc();
        $turn_order = ($max_row['max_order'] ?? 0) + 1;
        
        $stmt = $conn->prepare("INSERT INTO combat_participants (session_id, entity_type, entity_id, initiative, turn_order) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isiii", $session['id'], $entity_type, $entity_id, $initiative, $turn_order);
        echo json_encode(['success' => $stmt->execute()]);
        break;

    case 'remove_from_combat':
        requireDM();
        $participant_id = $_POST['participant_id'] ?? 0;
        
        $stmt = $conn->prepare("DELETE FROM combat_participants WHERE id = ?");
        $stmt->bind_param("i", $participant_id);
        echo json_encode(['success' => $stmt->execute()]);
        break;

    case 'adjust_combat_hp':
        requireDM();
        $entity_type = $_POST['entity_type'] ?? '';
        $entity_id = $_POST['entity_id'] ?? 0;
        $change = $_POST['change'] ?? 0;
        
        if ($entity_type === 'character') {
            $stmt = $conn->prepare("UPDATE character_stats SET current_hp = GREATEST(0, LEAST(max_hp, current_hp + ?)) WHERE character_id = ?");
        } else {
            $stmt = $conn->prepare("UPDATE monsters SET current_hp = GREATEST(0, LEAST(max_hp, current_hp + ?)) WHERE id = ?");
        }
        $stmt->bind_param("ii", $change, $entity_id);
        echo json_encode(['success' => $stmt->execute()]);
        break;

    case 'update_initiative':
        requireDM();
        $participant_id = $_POST['participant_id'] ?? 0;
        $initiative = $_POST['initiative'] ?? 10;
        
        $stmt = $conn->prepare("UPDATE combat_participants SET initiative = ? WHERE id = ?");
        $stmt->bind_param("ii", $initiative, $participant_id);
        echo json_encode(['success' => $stmt->execute()]);
        break;

    case 'toggle_participant_visibility':
        requireDM();
        $participant_id = $_POST['participant_id'] ?? 0;
        $visibility_type = $_POST['visibility_type'] ?? 'hp';
        $visible = $_POST['visible'] ?? 0;
        
        if ($visibility_type === 'hp') {
            $stmt = $conn->prepare("UPDATE combat_participants SET hp_visible = ? WHERE id = ?");
        } else {
            $stmt = $conn->prepare("UPDATE combat_participants SET stats_visible = ? WHERE id = ?");
        }
        
        $stmt->bind_param("ii", $visible, $participant_id);
        echo json_encode(['success' => $stmt->execute()]);
        break;

    case 'toggle_global_visibility':
        requireDM();
        $visibility_type = $_POST['visibility_type'] ?? 'hp';
        $visible = $_POST['visible'] ?? 0;
        
        $result = $conn->query("SELECT id FROM combat_sessions WHERE is_active = 1 LIMIT 1");
        $session = $result->fetch_assoc();
        
        if (!$session) {
            echo json_encode(['success' => false]);
            break;
        }
        
        if ($visibility_type === 'hp') {
            $stmt = $conn->prepare("UPDATE combat_participants SET hp_visible = ? WHERE session_id = ?");
        } else {
            $stmt = $conn->prepare("UPDATE combat_participants SET stats_visible = ? WHERE session_id = ?");
        }
        
        $stmt->bind_param("ii", $visible, $session['id']);
        echo json_encode(['success' => $stmt->execute()]);
        break;

    case 'get_combat_state':
        $result = $conn->query("SELECT id FROM combat_sessions WHERE is_active = 1 LIMIT 1");
        $session = $result->fetch_assoc();
        
        if (!$session) {
            echo json_encode(['success' => true, 'active' => false, 'participants' => []]);
            break;
        }
        
        $participants = $conn->query("
            SELECT cp.*, 
                CASE 
                    WHEN cp.entity_type = 'character' THEN c.name
                    WHEN cp.entity_type = 'monster' THEN m.name
                END as name,
                CASE 
                    WHEN cp.entity_type = 'character' THEN cs.current_hp
                    WHEN cp.entity_type = 'monster' THEN m.current_hp
                END as current_hp,
                CASE 
                    WHEN cp.entity_type = 'character' THEN cs.max_hp
                    WHEN cp.entity_type = 'monster' THEN m.max_hp
                END as max_hp,
                CASE 
                    WHEN cp.entity_type = 'character' THEN cs.armor_class
                    WHEN cp.entity_type = 'monster' THEN m.armor_class
                END as armor_class
            FROM combat_participants cp
            LEFT JOIN characters c ON cp.entity_type = 'character' AND cp.entity_id = c.id
            LEFT JOIN character_stats cs ON cp.entity_type = 'character' AND cp.entity_id = cs.character_id
            LEFT JOIN monsters m ON cp.entity_type = 'monster' AND cp.entity_id = m.id
            WHERE cp.session_id = {$session['id']}
            ORDER BY cp.initiative DESC, cp.turn_order ASC
        ");
        
        $data = [];
        while ($row = $participants->fetch_assoc()) {
            $data[] = $row;
        }
        
        echo json_encode(['success' => true, 'active' => true, 'session_id' => $session['id'], 'participants' => $data]);
        break;

    // ==================== IMAGE REMOVAL ====================
    case 'remove_image':
        requireDM();
        $entity_type = $_POST['entity_type'] ?? '';
        $entity_id = $_POST['entity_id'] ?? 0;
        
        $table_map = [
            'character' => 'characters',
            'monster' => 'monsters',
            'item' => 'items',
            'spell' => 'spells'
        ];
        
        if (!isset($table_map[$entity_type])) {
            echo json_encode(['success' => false, 'message' => 'Invalid entity type']);
            break;
        }
        
        $table = $table_map[$entity_type];
        $stmt = $conn->prepare("UPDATE $table SET image_url = NULL WHERE id = ?");
        $stmt->bind_param("i", $entity_id);
        echo json_encode(['success' => $stmt->execute()]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
}
?>