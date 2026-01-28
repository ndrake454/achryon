# Achryon - D&D Campaign Manager

## Project Overview

Achryon is a web-based Dungeons & Dragons campaign management system designed for collaboration between Dungeon Masters (DMs) and Players. It provides real-time character sheet management, combat tracking, campaign lore distribution, and messaging capabilities.

**Project Type:** Full-stack PHP web application
**Target Users:** DMs (admin role) and Players (player role)
**Campaign Setting:** The world of Achryon (D&D 5E 2024 rules)

---

## Tech Stack

| Component | Technology |
|-----------|------------|
| Backend | PHP 7.4+ |
| Database | MySQL 5.7+ (MySQLi extension) |
| Frontend | HTML5, Vanilla JavaScript, Tailwind CSS v3 (CDN) |
| Real-time | Polling-based system (3-second interval) |
| Authentication | PHP sessions with bcrypt password hashing |
| Architecture | MVC-lite with tab-based component loading |

---

## Directory Structure

```
achryon/
├── CLAUDE.md                         # This file - AI assistant guidelines
├── public_html/                      # Web root directory
│   ├── index.php                     # Landing page (redirects to login/dashboard)
│   ├── login.php                     # Login/registration page
│   ├── logout.php                    # Session termination
│   ├── database.sql                  # Complete database schema
│   ├── README.md                     # User documentation
│   │
│   ├── admin/                        # DM Dashboard (requires dm role)
│   │   ├── index.php                 # Main DM dashboard layout
│   │   ├── api.php                   # DM API endpoints (~1261 lines)
│   │   ├── character.php             # Character editor modal
│   │   ├── upload_image.php          # Profile image upload handler
│   │   ├── upload_content_image.php  # Content image upload handler
│   │   └── tabs/                     # DM interface tab components
│   │       ├── players.php           # Player account management
│   │       ├── characters.php        # Character sheet management
│   │       ├── monsters.php          # Monster library
│   │       ├── items.php             # Item database
│   │       ├── spells.php            # Spell library
│   │       ├── lore.php              # Campaign lore editor
│   │       ├── rules.php             # House rules editor
│   │       ├── messages.php          # Messaging interface
│   │       └── gamestate.php         # Combat tracker
│   │
│   ├── player/                       # Player Interface (requires player role)
│   │   ├── index.php                 # Player dashboard
│   │   ├── api.php                   # Player API endpoints (~478 lines)
│   │   └── player-tabs/              # Player interface components
│   │       ├── character.php         # Character sheet view (read-only)
│   │       ├── battle.php            # Live combat tracker
│   │       ├── lore.php              # Visible campaign lore
│   │       ├── rules.php             # House rules reference
│   │       └── messages.php          # Messaging interface
│   │
│   ├── includes/                     # Shared PHP components
│   │   ├── config.php                # Database configuration
│   │   └── auth.php                  # Authentication & authorization
│   │
│   └── js/                           # Frontend JavaScript
│       └── polling.js                # Real-time polling system
```

---

## Database Schema

### Core Tables

| Table | Purpose |
|-------|---------|
| `users` | Authentication (username, email, password, role: dm/player) |
| `players` | Player profiles linked to users |
| `characters` | Character sheets (name, class, race, level, etc.) |
| `character_stats` | Ability scores, HP, AC, spell slots |
| `character_skills` | Skill proficiencies (18 D&D 5E skills) |
| `character_equipment` | Inventory items |
| `character_spells` | Known spells per character |
| `monsters` | Monster templates for combat |
| `items` | Item library |
| `spells` | Spell library |
| `lore` | Campaign lore entries (with visibility control) |
| `rules` | House rules |
| `messages` | User-to-user messages |
| `combat_sessions` | Active combat encounters |
| `combat_participants` | Combatants in sessions |

### Key Relationships
- `users` -> `players` (1:1)
- `players` -> `characters` (1:many)
- `characters` -> `character_stats`, `character_skills`, `character_equipment`, `character_spells` (1:1 or 1:many)

### Important Indexes
- `idx_messages_to_user` - Message inbox queries
- `idx_combat_participants_session` - Combat lookups
- `idx_lore_visible` - Player-visible lore filtering
- `idx_character_spells_char` - Spell lookup by character

---

## Authentication System

### Functions in `includes/auth.php`

```php
isLoggedIn()      // Check if user has active session
isDM()            // Check if user has dm role
isPlayer()        // Check if user has player role
requireLogin()    // Redirect to login if not authenticated
requireDM()       // Enforce DM-only access
requirePlayer()   // Enforce player-only access
login($user, $pass) // Authenticate user
register($user, $email, $pass) // Create player account
logout()          // Destroy session
getCurrentUser()  // Get current user data
```

### Session Variables
- `$_SESSION['user_id']` - User ID
- `$_SESSION['username']` - Username
- `$_SESSION['role']` - 'dm' or 'player'
- `$_SESSION['player_id']` - Player ID (players only)

### Default Credentials
- **Username:** `admin`
- **Password:** `admin123`
- **Role:** `dm`

---

## API Architecture

### DM API (`/admin/api.php`)

Switch-based action routing. All endpoints require `requireDM()`.

**Character Actions:**
- `get_character` - Fetch character with all related data
- `save_character` - Create/update character
- `delete_character` - Remove character
- `duplicate_character` - Clone character

**Combat Actions:**
- `get_combat_state` - Current combat status
- `create_combat_session` - Start new combat
- `add_combat_participant` - Add combatant
- `update_initiative` - Modify turn order
- `update_participant_hp` - Change HP values
- `end_combat` - Close session

**Library Actions:**
- `get_monsters`, `save_monster`, `delete_monster`
- `get_items`, `save_item`, `delete_item`
- `get_spells`, `save_spell`, `delete_spell`
- `get_lore`, `save_lore`, `delete_lore`, `toggle_lore_visibility`
- `get_rules`, `save_rule`, `delete_rule`

**Messaging:**
- `get_messages`, `send_message`, `mark_read`

### Player API (`/player/api.php`)

All endpoints require `requirePlayer()` and verify character ownership.

**Available Actions:**
- `get_character` - Fetch owned character
- `get_combat_state` - Poll combat status
- `use_spell_slot` - Decrement spell slot
- `recover_spell_slots` - Reset on rest
- `get_messages`, `send_message`, `mark_read`

### API Response Format

```json
{
  "success": true,
  "data": { ... }
}
// or
{
  "success": false,
  "error": "Error message"
}
```

---

## Frontend Conventions

### Design System

```css
/* Color Palette (Tailwind classes) */
--primary: #f97316        /* orange-500 */
--primary-dark: #ea580c   /* orange-600 */
--background: #030712     /* gray-950 */
--card-bg: #111827        /* gray-900 */
--border: #1f2937         /* gray-800 */
--text: white
--text-muted: #9ca3af     /* gray-400 */
```

### Component Patterns

**Tabs:** Tab-based navigation with PHP includes
```php
<?php include "tabs/{$tab}.php"; ?>
```

**Cards:** Dark cards with orange accents
```html
<div class="bg-gray-900 border border-gray-800 rounded-lg p-4">
```

**Buttons:**
```html
<!-- Primary -->
<button class="bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded">

<!-- Secondary -->
<button class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded">
```

**Forms:** Dark inputs with focus states
```html
<input class="w-full p-2 bg-gray-800 border border-gray-700 rounded text-white focus:border-primary">
```

### Polling System (`js/polling.js`)

```javascript
const POLL_INTERVAL = 3000; // 3 seconds

// Start polling for updates
const intervalId = startPolling('/player/api.php?action=get_combat_state', (result) => {
    // Handle update
});

// Stop when leaving page
stopPolling(intervalId);

// Make API calls
const result = await apiCall('action_name', { key: 'value' });
```

---

## Development Guidelines

### Code Style

**PHP:**
- Functions: `snake_case`
- Variables: `$camelCase`
- Constants: `UPPER_CASE`
- Always use prepared statements for SQL
- Include auth.php at the top of protected pages

**JavaScript:**
- Functions: `camelCase`
- Constants: `UPPER_CASE`
- Use async/await for API calls
- Handle errors gracefully

**HTML/CSS:**
- Use Tailwind utility classes
- Semantic HTML elements
- Mobile-first responsive design (md:, lg: breakpoints)

### Security Requirements

1. **SQL Injection Prevention:** Always use prepared statements
   ```php
   $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
   $stmt->bind_param("i", $id);
   ```

2. **XSS Prevention:** Escape output with `htmlspecialchars()`
   ```php
   echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8');
   ```

3. **Authentication:** Call `requireDM()` or `requirePlayer()` at page start

4. **Authorization:** Verify ownership before allowing access
   ```php
   function verifyCharacterOwnership($characterId, $playerId) {
       // Check character belongs to player
   }
   ```

5. **Password Security:** Use `password_hash()` and `password_verify()`

### Common Tasks

#### Adding a New DM Tab

1. Create `public_html/admin/tabs/newtab.php`
2. Add tab button to `admin/index.php` navigation
3. Add case to tab loading switch
4. Include `requireDM()` check if accessing API

#### Adding a New Player Tab

1. Create `public_html/player/player-tabs/newtab.php`
2. Add tab button to `player/index.php` navigation
3. Add case to tab loading switch
4. Use polling if real-time updates needed

#### Adding a New API Endpoint

1. Add case to switch in `api.php`:
   ```php
   case 'new_action':
       // Validate input
       // Execute query with prepared statement
       // Return JSON response
       echo json_encode(['success' => true, 'data' => $result]);
       break;
   ```

#### Modifying Database Schema

1. Backup existing data
2. Update `database.sql`
3. Create migration script for existing installations
4. Update related PHP code
5. Test with sample data

---

## File Locations Quick Reference

| Purpose | File Path |
|---------|-----------|
| Database config | `public_html/includes/config.php` |
| Authentication | `public_html/includes/auth.php` |
| Database schema | `public_html/database.sql` |
| DM Dashboard | `public_html/admin/index.php` |
| DM API | `public_html/admin/api.php` |
| Player Dashboard | `public_html/player/index.php` |
| Player API | `public_html/player/api.php` |
| Polling JS | `public_html/js/polling.js` |
| Login page | `public_html/login.php` |

---

## Testing Checklist

- [ ] Login as DM (admin/admin123)
- [ ] Login as Player (create test account)
- [ ] Verify DM cannot access /player/ routes
- [ ] Verify Player cannot access /admin/ routes
- [ ] Create/edit/delete character as DM
- [ ] View character sheet as Player
- [ ] Test combat tracker (create session, add participants)
- [ ] Test real-time polling on battle tab
- [ ] Send messages between DM and Player
- [ ] Test lore visibility toggle
- [ ] Test spell slot usage/recovery
- [ ] Verify prepared statements in all queries
- [ ] Check for XSS in user-generated content

---

## Important Notes for AI Assistants

1. **Maintain Code Style:** This is a vanilla PHP application. Keep existing patterns and conventions.

2. **Security First:** Always use prepared statements. Never trust user input.

3. **Role Separation:** DM and Player interfaces are intentionally separate. Don't mix access levels.

4. **No Framework Changes:** Don't introduce Composer dependencies or frameworks unless explicitly requested.

5. **Polling, Not WebSockets:** Real-time features use HTTP polling. Keep this architecture.

6. **Dark Theme:** All UI should follow the existing dark theme with orange accents.

7. **Tab Architecture:** New features should fit into the existing tab-based navigation.

8. **Database Changes:** Always update `database.sql` when modifying schema.

9. **Test Both Roles:** Changes should be tested as both DM and Player.

10. **Session Handling:** Auth checks happen via `includes/auth.php`. Always include it.

---

## Campaign Context (Achryon World)

The application is themed for a specific D&D campaign:

- **World:** Achryon (mysterious changes occurring - "the sky is dulling")
- **Starting Location:** Wayrest (peaceful mountain hamlet)
- **Nearby City:** Bastionford (sea-side port)
- **Faction:** The Lantern Bearers (antagonistic religious movement)
- **Rules:** D&D 5E 2024 rules, starting at Level 2
- **Tone:** Character-driven, relaxed gameplay
