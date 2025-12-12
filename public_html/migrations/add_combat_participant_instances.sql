-- Migration: Add instance-specific fields to combat_participants
-- Date: 2025-12-12
-- Description: Fixes duplicate monster HP issue by storing HP and display names per combat instance

-- Add display_name column for auto-numbered monsters (e.g., "Kobold 1", "Kobold 2")
ALTER TABLE combat_participants
ADD COLUMN IF NOT EXISTS display_name VARCHAR(100) AFTER entity_id;

-- Add current_hp column to store instance-specific HP instead of sharing monster template HP
ALTER TABLE combat_participants
ADD COLUMN IF NOT EXISTS current_hp INT AFTER display_name;

-- Migrate existing combat participants to use instance HP
-- For monsters: copy current_hp from monsters table
UPDATE combat_participants cp
JOIN monsters m ON cp.entity_type = 'monster' AND cp.entity_id = m.id
SET cp.current_hp = m.current_hp
WHERE cp.entity_type = 'monster' AND cp.current_hp IS NULL;

-- For characters: copy current_hp from character_stats table
UPDATE combat_participants cp
JOIN character_stats cs ON cp.entity_type = 'character' AND cp.entity_id = cs.character_id
SET cp.current_hp = cs.current_hp
WHERE cp.entity_type = 'character' AND cp.current_hp IS NULL;
