# D&D Campaign Manager

A web-based campaign management system for Dungeon Masters and Players.

## Features

### DM Dashboard
- **Players** - Manage player accounts
- **Characters** - Full character sheet editor (stats, skills, equipment, spells)
- **Monsters** - Monster library with challenge ratings
- **Items** - Item database with rarity levels
- **Spells** - Spell library organized by level
- **Lore** - Campaign lore with visibility controls
- **Rules** - House rules reference
- **Messages** - Direct messaging with players
- **Game State** - Combat tracker with initiative and HP management

### Player Interface
- **Character** - View character sheet
- **Battle** - Live combat tracker (auto-refreshes)
- **Lore** - Read DM-revealed campaign lore
- **Rules** - Reference house rules
- **Messages** - Communicate with DM and other players

## Installation

### 1. Database Setup
```bash
mysql -u root -p < database.sql
```

Default admin account:
- Username: `admin`
- Password: `admin123`

**Change this password immediately!**

### 2. Configuration
Edit `/includes/config.php` with your database credentials.

### 3. Access
- **DM Dashboard:** `/admin/`
- **Player View:** `/player/`

## Requirements

- PHP 7.4+
- MySQL 5.7+
- Modern web browser

## Quick Start

1. Login as admin
2. Change password
3. Create player accounts
4. Create characters
5. Add monsters/items/spells
6. Start your campaign!

---

**Have fun running your D&D campaign!** 🎲⚔️✨
