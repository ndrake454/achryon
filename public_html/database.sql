-- D&D Game Manager Database Schema - Complete Version
CREATE DATABASE IF NOT EXISTS dnd_manager;
USE dnd_manager;

-- Users table (both DMs and Players)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('dm', 'player') DEFAULT 'player',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Players table (for player accounts)
CREATE TABLE IF NOT EXISTS players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    display_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Characters table
CREATE TABLE IF NOT EXISTS characters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    player_id INT,
    name VARCHAR(100) NOT NULL,
    class VARCHAR(50) NOT NULL,
    level INT DEFAULT 1,
    race VARCHAR(50) NOT NULL,
    background VARCHAR(100),
    alignment VARCHAR(30),
    experience INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE SET NULL
);

-- Character stats
CREATE TABLE IF NOT EXISTS character_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    character_id INT NOT NULL,
    strength INT DEFAULT 10,
    dexterity INT DEFAULT 10,
    constitution INT DEFAULT 10,
    intelligence INT DEFAULT 10,
    wisdom INT DEFAULT 10,
    charisma INT DEFAULT 10,
    armor_class INT DEFAULT 10,
    initiative INT DEFAULT 0,
    speed INT DEFAULT 30,
    max_hp INT DEFAULT 10,
    current_hp INT DEFAULT 10,
    temp_hp INT DEFAULT 0,
    hit_dice VARCHAR(20) DEFAULT '1d8',
    proficiency_bonus INT DEFAULT 2,
    spell_slots_1 INT DEFAULT 0,
    spell_slots_2 INT DEFAULT 0,
    spell_slots_3 INT DEFAULT 0,
    spell_slots_4 INT DEFAULT 0,
    spell_slots_5 INT DEFAULT 0,
    spell_slots_6 INT DEFAULT 0,
    spell_slots_7 INT DEFAULT 0,
    spell_slots_8 INT DEFAULT 0,
    spell_slots_9 INT DEFAULT 0,
    current_spell_slots_1 INT DEFAULT 0,
    current_spell_slots_2 INT DEFAULT 0,
    current_spell_slots_3 INT DEFAULT 0,
    current_spell_slots_4 INT DEFAULT 0,
    current_spell_slots_5 INT DEFAULT 0,
    current_spell_slots_6 INT DEFAULT 0,
    current_spell_slots_7 INT DEFAULT 0,
    current_spell_slots_8 INT DEFAULT 0,
    current_spell_slots_9 INT DEFAULT 0,
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE
);

-- Skills
CREATE TABLE IF NOT EXISTS character_skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    character_id INT NOT NULL,
    acrobatics BOOLEAN DEFAULT FALSE,
    animal_handling BOOLEAN DEFAULT FALSE,
    arcana BOOLEAN DEFAULT FALSE,
    athletics BOOLEAN DEFAULT FALSE,
    deception BOOLEAN DEFAULT FALSE,
    history BOOLEAN DEFAULT FALSE,
    insight BOOLEAN DEFAULT FALSE,
    intimidation BOOLEAN DEFAULT FALSE,
    investigation BOOLEAN DEFAULT FALSE,
    medicine BOOLEAN DEFAULT FALSE,
    nature BOOLEAN DEFAULT FALSE,
    perception BOOLEAN DEFAULT FALSE,
    performance BOOLEAN DEFAULT FALSE,
    persuasion BOOLEAN DEFAULT FALSE,
    religion BOOLEAN DEFAULT FALSE,
    sleight_of_hand BOOLEAN DEFAULT FALSE,
    stealth BOOLEAN DEFAULT FALSE,
    survival BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE
);

-- Equipment
CREATE TABLE IF NOT EXISTS character_equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    character_id INT NOT NULL,
    item_name VARCHAR(100) NOT NULL,
    type VARCHAR(50),
    rarity VARCHAR(50),
    properties TEXT,
    description TEXT,
    is_equipped BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE
);

-- Character Spells
CREATE TABLE IF NOT EXISTS character_spells (
    id INT AUTO_INCREMENT PRIMARY KEY,
    character_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    level INT NOT NULL,
    school VARCHAR(50),
    casting_time VARCHAR(50),
    range_area VARCHAR(50),
    components VARCHAR(100),
    duration VARCHAR(50),
    description TEXT,
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE
);

-- Monsters
CREATE TABLE IF NOT EXISTS monsters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(50),
    challenge_rating VARCHAR(10),
    armor_class INT DEFAULT 10,
    max_hp INT DEFAULT 10,
    current_hp INT DEFAULT 10,
    strength INT DEFAULT 10,
    dexterity INT DEFAULT 10,
    constitution INT DEFAULT 10,
    intelligence INT DEFAULT 10,
    wisdom INT DEFAULT 10,
    charisma INT DEFAULT 10,
    attacks TEXT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Items
CREATE TABLE IF NOT EXISTS items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(50),
    rarity VARCHAR(30),
    description TEXT,
    properties TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Spells library
CREATE TABLE IF NOT EXISTS spells (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    level INT NOT NULL,
    school VARCHAR(50),
    casting_time VARCHAR(50),
    range_area VARCHAR(50),
    components VARCHAR(100),
    duration VARCHAR(50),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Lore entries
CREATE TABLE IF NOT EXISTS lore (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    category VARCHAR(50) DEFAULT 'Other',
    visible_to_players BOOLEAN DEFAULT FALSE,
    order_index INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Rules
CREATE TABLE IF NOT EXISTS rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    order_index INT DEFAULT 0,
    visible_to_players BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Messages
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    from_user_id INT NOT NULL,
    to_user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (to_user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Combat sessions
CREATE TABLE IF NOT EXISTS combat_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Combat participants
CREATE TABLE IF NOT EXISTS combat_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    entity_type ENUM('character', 'monster') NOT NULL,
    entity_id INT NOT NULL,
    initiative INT DEFAULT 0,
    turn_order INT DEFAULT 0,
    hp_visible BOOLEAN DEFAULT FALSE,
    stats_visible BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (session_id) REFERENCES combat_sessions(id) ON DELETE CASCADE
);

-- Performance indexes
CREATE INDEX idx_messages_to_user ON messages(to_user_id, is_read);
CREATE INDEX idx_messages_from_user ON messages(from_user_id);
CREATE INDEX idx_combat_participants_session ON combat_participants(session_id);
CREATE INDEX idx_lore_visible ON lore(visible_to_players, order_index);
CREATE INDEX idx_rules_order ON rules(order_index);
CREATE INDEX idx_character_spells_char ON character_spells(character_id, level);

-- Insert default DM user (username: admin, password: admin123)
INSERT INTO users (username, email, password, role) VALUES 
('admin', 'admin@dndmanager.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'dm');
