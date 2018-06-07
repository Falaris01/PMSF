<?php
include(dirname(__FILE__) . '/config/config.php');
global $map, $fork, $db, $noManualQuests;

$db->update('pokestops',['quest_id' => null, 'reward' => null, 'users' => null]);
echo 'updated pokestops';
