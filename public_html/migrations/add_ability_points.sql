-- Migration: Add ability_points column to character_stats table
-- Date: 2025-12-12
-- Description: Allows DMs to grant ability points that players can spend to increase their attributes

ALTER TABLE character_stats
ADD COLUMN IF NOT EXISTS ability_points INT DEFAULT 0 AFTER charisma;
