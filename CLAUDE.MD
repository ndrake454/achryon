# Achryon - D&D Campaign Manager

## Project Overview
A web-based campaign management system for Dungeon Masters and Players. This is a PHP/MySQL application that provides DMs with tools to manage campaigns and players with real-time access to character sheets, combat trackers, and campaign information.

## Tech Stack
- **Backend:** PHP 7.4+
- **Database:** MySQL 5.7+
- **Frontend:** HTML, JavaScript, CSS
- **Real-time Updates:** Polling-based system (see `js/polling.js`)

## Project Structure
```
achryon/
├── public_html/              # Web root
│   ├── admin/               # DM Dashboard
│   │   └── tabs/           # DM interface components
│   │       ├── characters.php
│   │       ├── gamestate.php
│   │       ├── items.php
│   │       ├── lore.php
│   │       ├── messages.php
│   │       ├── monsters.php
│   │       ├── players.php
│   │       ├── rules.php
│   │       └── spells.php
│   ├── player/             # Player Interface
│   │   ├── player-tabs/    # Player interface components
│   │   │   ├── battle.php
│   │   │   ├── character.php
│   │   │   ├── lore.php
│   │   │   ├── messages.php
│   │   │   └── rules.php
│   │   ├── api.php         # Player API endpoints
│   │   └── index.php       # Player dashboard
│   ├── includes/           # Shared PHP components
│   │   ├── config.php      # Database configuration
│   │   └── auth.php        # Authentication logic
│   ├── js/                 # JavaScript files
│   │   └── polling.js      # Real-time update polling
│   ├── index.php           # Landing/login page
│   └── README.md           # User documentation
└── database.sql            # Database schema (if exists)
```

## Key Features

### DM Dashboard (`/admin/`)
- **Players:** Manage player accounts
- **Characters:** Full character sheet editor (stats, skills, equipment, spells)
- **Monsters:** Monster library with challenge ratings
- **Items:** Item database with rarity levels
- **Spells:** Spell library organized by level
- **Lore:** Campaign lore with visibility controls
- **Rules:** House rules reference
- **Messages:** Direct messaging with players
- **Game State:** Combat tracker with initiative and HP management

### Player Interface (`/player/`)
- **Character:** View character sheet
- **Battle:** Live combat tracker (auto-refreshes)
- **Lore:** Read DM-revealed campaign lore
- **Rules:** Reference house rules
- **Messages:** Communicate with DM and other players

## Authentication & Security
- Authentication handled in `includes/auth.php`
- Default admin credentials: `admin` / `admin123` (should be changed)
- Separate DM and Player access levels

## Database
- Configuration: `includes/config.php`
- Setup script: `database.sql` (if exists)
- Must be MySQL 5.7 or higher

## Development Guidelines

### When Making Changes
1. **Authentication:** Always check user permissions before allowing access to DM features
2. **SQL Injection:** Use prepared statements for all database queries
3. **XSS Protection:** Sanitize all user input before displaying
4. **Session Management:** Verify sessions are properly validated in `includes/auth.php`

### Common Tasks

#### Adding a New Tab to DM Dashboard
1. Create new PHP file in `admin/tabs/`
2. Add database queries with proper authentication checks
3. Include the tab in the admin navigation
4. Test with both admin and player accounts to verify permissions

#### Adding a New Tab to Player Interface
1. Create new PHP file in `player/player-tabs/`
2. Ensure it only shows data for the logged-in player
3. Include the tab in the player navigation
4. Consider if real-time updates are needed (use `polling.js`)

#### Modifying Database Schema
1. Always backup existing data first
2. Update `database.sql` with schema changes
3. Create migration script if modifying existing installation
4. Test with sample data

#### Adding Real-time Features
- Check `js/polling.js` for polling implementation
- Battle tab auto-refreshes for live combat tracking
- Consider performance impact of polling frequency

### File Locations Reference

**Configuration:**
- Database config: `public_html/includes/config.php`
- Auth logic: `public_html/includes/auth.php`

**Entry Points:**
- Main login: `public_html/index.php`
- DM Dashboard: `public_html/admin/index.php`
- Player Dashboard: `public_html/player/index.php`

**APIs:**
- Player API: `public_html/player/api.php`

**Frontend:**
- Polling system: `public_html/js/polling.js`

## Security Considerations
- Never commit `config.php` with real credentials
- Always sanitize user input
- Use prepared statements for database queries
- Validate session on every page load
- Implement CSRF protection for forms
- Change default admin password immediately after installation

## Testing Checklist
- [ ] Test as DM (admin role)
- [ ] Test as Player (player role)
- [ ] Verify permissions are enforced
- [ ] Check real-time updates work (battle tab)
- [ ] Test messaging between DM and players
- [ ] Verify character sheet CRUD operations
- [ ] Test combat tracker functionality

## Notes for Claude Code
- This is a legacy PHP application - maintain existing code style
- Focus on security when handling user input
- The app uses session-based authentication
- Real-time features use polling, not WebSockets
- Keep DM and Player interfaces separate and secure
