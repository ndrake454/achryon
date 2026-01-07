<?php
require_once 'config.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isDM() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'dm';
}

function isPlayer() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'player';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit();
    }
}

function requireDM() {
    requireLogin();
    if (!isDM()) {
        header('Location: /player/');
        exit();
    }
}

function requirePlayer() {
    requireLogin();
    if (!isPlayer()) {
        header('Location: /admin/');
        exit();
    }
}

function login($username, $password) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT id, password, role, username FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];
            
            // Get player_id if player
            if ($row['role'] === 'player') {
                $stmt = $conn->prepare("SELECT id FROM players WHERE user_id = ?");
                $stmt->bind_param("i", $row['id']);
                $stmt->execute();
                $player = $stmt->get_result()->fetch_assoc();
                if ($player) {
                    $_SESSION['player_id'] = $player['id'];
                }
            }
            
            return true;
        }
    }
    return false;
}

function register($username, $email, $password) {
    global $conn;
    
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'player')");
    $stmt->bind_param("sss", $username, $email, $hashed);
    
    if ($stmt->execute()) {
        $user_id = $conn->insert_id;
        
        // Create player record
        $stmt = $conn->prepare("INSERT INTO players (user_id, display_name) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $username);
        $stmt->execute();
        
        return true;
    }
    return false;
}

function logout() {
    session_destroy();
    header('Location: /login.php');
    exit();
}

function getCurrentUser() {
    global $conn;
    if (!isLoggedIn()) return null;
    
    $stmt = $conn->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}
