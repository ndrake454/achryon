<?php
// Get active combat session
$active_session = $conn->query("SELECT * FROM combat_sessions WHERE is_active = 1 LIMIT 1")->fetch_assoc();

if ($active_session) {
    // Get combat participants with visibility settings
    $participants = $conn->query("
        SELECT 
            cp.*,
            c.name as character_name,
            c.image_url as char_image_url,
            cs.current_hp as char_current_hp,
            cs.max_hp as char_max_hp,
            cs.armor_class as char_ac,
            cs.strength as char_str,
            cs.dexterity as char_dex,
            cs.constitution as char_con,
            cs.intelligence as char_int,
            cs.wisdom as char_wis,
            cs.charisma as char_cha,
            m.name as monster_name,
            m.image_url as mon_image_url,
            m.current_hp as mon_current_hp,
            m.max_hp as mon_max_hp,
            m.armor_class as mon_ac,
            m.strength as mon_str,
            m.dexterity as mon_dex,
            m.constitution as mon_con,
            m.intelligence as mon_int,
            m.wisdom as mon_wis,
            m.charisma as mon_cha
        FROM combat_participants cp
        LEFT JOIN characters c ON cp.entity_type = 'character' AND cp.entity_id = c.id
        LEFT JOIN character_stats cs ON c.id = cs.character_id
        LEFT JOIN monsters m ON cp.entity_type = 'monster' AND cp.entity_id = m.id
        WHERE cp.session_id = {$active_session['id']}
        ORDER BY cp.initiative DESC, cp.turn_order
    ");
}
?>

<div class="max-w-6xl mx-auto">
    <?php if (!$active_session): ?>
        <!-- No Active Combat -->
        <div class="text-center py-20">
            <svg class="w-20 h-20 text-gray-700 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
            </svg>
            <h3 class="text-xl font-bold text-gray-600 mb-2">No Active Combat</h3>
            <p class="text-gray-500">Your DM hasn't started a combat encounter yet</p>
        </div>
    <?php else: ?>
        <!-- Active Combat -->
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-white flex items-center">
                        <svg class="w-8 h-8 text-primary mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                        </svg>
                        Initiative Order
                    </h2>
                    <p class="text-gray-400 ml-11">Current combat encounter</p>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 bg-primary rounded-full animate-pulse"></div>
                    <span class="text-primary font-bold">Combat Active</span>
                </div>
            </div>
        </div>

        <!-- Initiative Order -->
        <div class="space-y-3">
            <?php $turn = 1; while ($participant = $participants->fetch_assoc()): 
                $is_character = $participant['entity_type'] === 'character';
                $name = $is_character ? $participant['character_name'] : $participant['monster_name'];
                $image_url = $is_character ? $participant['char_image_url'] : $participant['mon_image_url'];
                $current_hp = $is_character ? $participant['char_current_hp'] : $participant['mon_current_hp'];
                $max_hp = $is_character ? $participant['char_max_hp'] : $participant['mon_max_hp'];
                $ac = $is_character ? $participant['char_ac'] : $participant['mon_ac'];
                
                // Check visibility
                $show_hp = $participant['hp_visible'];
                $show_stats = $participant['stats_visible'];
                
                $hp_percent = ($max_hp > 0 && $show_hp) ? ($current_hp / $max_hp) * 100 : 0;
                $hp_color = $hp_percent > 50 ? 'bg-green-500' : ($hp_percent > 25 ? 'bg-yellow-500' : 'bg-red-500');
            ?>
            <div class="bg-gray-900/50 border-2 border-gray-800 rounded-lg p-5 hover:border-primary/30 transition">
                <div class="flex items-center gap-4">
                    <!-- Turn Order -->
                    <div class="flex-shrink-0 w-12 h-12 bg-primary/20 rounded-lg border-2 border-primary flex items-center justify-center">
                        <span class="text-primary font-bold text-lg"><?php echo $turn++; ?></span>
                    </div>
                    
                    <!-- Portrait -->
                    <?php if (!empty($image_url)): ?>
                        <img src="<?php echo htmlspecialchars($image_url); ?>" 
                            alt="<?php echo htmlspecialchars($name); ?>" 
                            class="w-16 h-16 object-cover rounded-lg border-2 <?php echo $is_character ? 'border-blue-500' : 'border-red-500'; ?> flex-shrink-0">
                    <?php else: ?>
                        <div class="w-16 h-16 rounded-lg border-2 <?php echo $is_character ? 'border-blue-500 bg-blue-900/20' : 'border-red-500 bg-red-900/20'; ?> flex-shrink-0 flex items-center justify-center">
                            <svg class="w-8 h-8 <?php echo $is_character ? 'text-blue-500' : 'text-red-500'; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Entity Info -->
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2 flex-wrap">
                            <h3 class="text-xl font-bold text-white"><?php echo htmlspecialchars($name); ?></h3>
                            <span class="px-2 py-1 text-xs rounded <?php echo $is_character ? 'bg-blue-500/20 text-blue-400' : 'bg-red-500/20 text-red-400'; ?>">
                                <?php echo $is_character ? 'Player' : 'Enemy'; ?>
                            </span>
                            <span class="px-3 py-1 bg-gray-800 text-gray-300 text-sm rounded font-mono">
                                Init: <?php echo $participant['initiative']; ?>
                            </span>
                            <?php 
                            // Show status effects
                            if ($is_character) {
                                $status_query = $conn->query("SELECT * FROM character_status_effects WHERE character_id = {$participant['entity_id']}");
                            } else {
                                $status_query = $conn->query("SELECT * FROM monster_status_effects WHERE monster_id = {$participant['entity_id']}");
                            }
                            while ($status = $status_query->fetch_assoc()):
                            ?>
                            <span class="px-2 py-1 text-xs bg-red-900/30 text-red-400 border border-red-500/50 rounded flex items-center gap-1" title="<?php echo htmlspecialchars($status['description'] ?? ''); ?>">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <?php echo htmlspecialchars($status['status_name']); ?>
                            </span>
                            <?php endwhile; ?>
                        </div>
                        
                        <!-- HP Bar (if visible) -->
                        <?php if ($show_hp): ?>
                        <div class="flex items-center gap-4 mb-2">
                            <div class="flex-1">
                                <div class="flex justify-between text-xs text-gray-400 mb-1">
                                    <span>HP</span>
                                    <span><?php echo $current_hp; ?> / <?php echo $max_hp; ?></span>
                                </div>
                                <div class="w-full bg-gray-800 rounded-full h-3 overflow-hidden">
                                    <div class="<?php echo $hp_color; ?> h-full transition-all" style="width: <?php echo $hp_percent; ?>%"></div>
                                </div>
                            </div>
                            
                            <?php if ($show_stats): ?>
                            <div class="px-3 py-2 bg-gray-800 rounded text-center">
                                <div class="text-xs text-gray-400">AC</div>
                                <div class="text-lg font-bold text-white"><?php echo $ac; ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <div class="flex items-center gap-2">
                            <?php if ($show_stats): ?>
                            <div class="px-3 py-2 bg-gray-800 rounded text-center">
                                <div class="text-xs text-gray-400">AC</div>
                                <div class="text-lg font-bold text-white"><?php echo $ac; ?></div>
                            </div>
                            <?php endif; ?>
                            <span class="text-sm text-gray-500 italic">HP hidden by DM</span>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Stats (if visible) -->
                        <?php if ($show_stats): ?>
                        <div class="grid grid-cols-6 gap-2 mt-2">
                            <?php 
                            $stats = [
                                'STR' => $is_character ? $participant['char_str'] : $participant['mon_str'],
                                'DEX' => $is_character ? $participant['char_dex'] : $participant['mon_dex'],
                                'CON' => $is_character ? $participant['char_con'] : $participant['mon_con'],
                                'INT' => $is_character ? $participant['char_int'] : $participant['mon_int'],
                                'WIS' => $is_character ? $participant['char_wis'] : $participant['mon_wis'],
                                'CHA' => $is_character ? $participant['char_cha'] : $participant['mon_cha']
                            ];
                            foreach ($stats as $abbr => $value):
                                $modifier = floor(($value - 10) / 2);
                            ?>
                            <div class="text-center bg-gray-800/50 rounded p-2">
                                <div class="text-xs text-gray-500"><?php echo $abbr; ?></div>
                                <div class="text-white font-bold"><?php echo $value; ?></div>
                                <div class="text-xs text-gray-400"><?php echo $modifier >= 0 ? '+' : ''; ?><?php echo $modifier; ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>

        <!-- Auto-refresh notice -->
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-500">
                <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Auto-refreshing every 5 seconds
            </p>
        </div>
    <?php endif; ?>
</div>

<?php if ($active_session): ?>
<script>
// Auto-refresh combat tracker every 5 seconds
setInterval(() => {
    location.reload();
}, 5000);
</script>
<?php endif; ?>
