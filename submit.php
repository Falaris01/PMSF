<?php
$timing['start'] = microtime( true );
include( 'config/config.php' );
global $map, $fork, $db, $raidBosses, $webhookUrl, $sendWebhook, $sendWebhookQuest, $noManualRaids, $noRaids, $noManualPokemon, $noPokemon, $noPokestops, $noManualPokestops, $noGyms, $noManualGyms, $noManualQuests, $noManualNests, $noNests, $hostUrl, $schlurpUrl, $chaneiraUrl, $tangelaUrl, $questUrl, $laprasUrl, $larvitarUrl, $dratiniUrl, $nebulakUrl, $hundusterUrl, $snubbullUrl, $traunfugilUrl, $pandirUrl, $voltilammUrl, $tannzaUrl, $miltankUrl, $porygonUrl, $phanpyUrl, $fukanoUrl, $krabbyUrl, $barschwaUrl, $pinsirUrl, $raupyUrl, $nincadaUrl, $dittoUrl, $aerodactylUrl; $allUrl;
$action = ! empty( $_POST['action'] ) ? $_POST['action'] : '';
$lat    = ! empty( $_POST['lat'] ) ? $_POST['lat'] : '';
$lng    = ! empty( $_POST['lng'] ) ? $_POST['lng'] : '';
// set content type
header( 'Content-Type: application/json' );
$now = new DateTime();
$now->sub( new DateInterval( 'PT20S' ) );
$d           = array();
$d['status'] = "ok";
$d["timestamp"] = $now->getTimestamp();
if ( $action === "raid" ) {
    $raidBosses = json_decode( file_get_contents( "static/dist/data/pokemon.min.json" ), true );
    $pokemonId  = ! empty( $_POST['pokemonId'] ) ? $_POST['pokemonId'] : 0;
    $gymId      = ! empty( $_POST['gymId'] ) ? $_POST['gymId'] : 0;
    $eggTime    = ! empty( $_POST['eggTime'] ) ? $_POST['eggTime'] : 0;
    $monTime    = ! empty( $_POST['monTime'] ) ? $_POST['monTime'] : 0;
    if ( $eggTime > 60 ) {
        $eggTime = 60;
    }
    if ( $monTime > 45 ) {
        $monTime = 45;
    }
    if ( $eggTime < 0 ) {
        $eggTime = 0;
    }
    if ( $monTime < 0 ) {
        $monTime = 45;
    }
// brimful of asha on the:
    $battle_duration = 45 * 60;
    $hour       = 3600;
//$db->debug();
// fetch fort_id
    $gym         = $db->get( "forts", [ 'id', 'name', 'lat', 'lon' ], [ 'external_id' => $gymId ] );
    $gymId       = $gym['id'];
    $add_seconds = ( $monTime * 60 );
    $time_spawn  = time() - $battle_duration;
    $level       = 0;
    if ( strpos( $pokemonId, 'egg_' ) !== false ) {
        $add_seconds = ( $eggTime * 60 );
        $level       = (int) substr( $pokemonId, 4, 1 );
        $time_spawn  = time() + $add_seconds;
    }
    $time_battle = time() + $add_seconds;
    $time_end    = $time_battle + $battle_duration;
    $extId       = rand( 0, 65535 ) . rand( 0, 65535 );
    $cols = [
        'external_id' => $gymId,
        'fort_id'     => $gymId,
        'level'       => $level,
        'time_spawn'  => $time_spawn,
        'time_battle' => $time_battle,
        'time_end'    => $time_end,
        'cp'          => 0,
        'pokemon_id'  => 0,
        'move_1'      => 0, // struggle
        'move_2'      => 0,
        'users'       => $_SESSION['user']->user
    ];
    if ( array_key_exists( $pokemonId, $raidBosses ) ) {
        $time_end = time() + $add_seconds;
        // fake the battle start and spawn times cuz rip hashing :(
        $time_battle         = $time_end - $battle_duration;
        $time_spawn          = $time_battle - $hour;
        $cols['pokemon_id']  = $pokemonId;
        $cols['move_1']      = 133; // struggle :(
        $cols['move_2']      = 133;
        $cols['level']       = array_key_exists('level',$raidBosses[ $pokemonId ]) ? $raidBosses[ $pokemonId ]['level'] : 1; // struggle :(
        $cols['cp']          = array_key_exists('cp',$raidBosses[ $pokemonId ]) ? $raidBosses[ $pokemonId ]['cp'] : 1;
        $cols['time_spawn']  = $time_spawn;
        $cols['time_battle'] = $time_battle;
        $cols['time_end']    = $time_end;
        $cols['users']       = $_SESSION['user']->user;
    } elseif ( $cols['level'] === 0 ) {
        // no boss or egg matched
        http_response_code( 500 );
    }
    $db->query( 'DELETE FROM raids WHERE fort_id = :gymId', [ ':gymId' => $gymId ] );
    $db->insert( "raids", $cols );
// also update fort_sightings so PMSF knows the gym has changed
// todo: put team stuff in here too
    $db->query( "UPDATE fort_sightings SET updated = :updated, last_modified = :updated WHERE fort_id = :gymId", [
        'updated' => time(),
        ':gymId'  => $gymId
    ] );
    if ( $sendWebhook === true ) {
        $webhook = [
            'message' => [
                'gym_id'     => $cols['external_id'],
                'pokemon_id' => $cols['pokemon_id'],
                'cp'         => $cols['cp'],
                'move_1'     => $cols['move_1'],
                'move_2'     => $cols['move_2'],
                'level'      => $cols['level'],
                'latitude'   => $gym['lat'],
                'longitude'  => $gym['lon'],
                'raid_begin' => $time_battle,
                'raid_end'   => $time_end,
                'team'       => 0,
                'name'       => $gym['name']
            ],
            'type'    => 'raid'
        ];
        if ( strpos( $pokemonId, 'egg_' ) !== false ) {
            $webhook['message']['raid_begin'] = $time_spawn;
        }
        foreach ( $webhookUrl as $url ) {
            sendToWebhook( $url, $webhook );
        }
        
        $logWebhook = [
            'content' => 'Arena: **'.$gym['name'].'**
  Pokémon: **'.$cols['pokemon_id'].'** / Ei-Level: **'.$level.'**
  Start: '.$time_battle.', Ende: '.$time_end.'
  gemeldet von: **'.$_SESSION['user']->user.'**
  ['.$hostUrl.']('.$hostUrl.'?lat='.$gym['lat'].'&lon='.$gym['lon'].') | [Google Maps](<https://www.google.com/maps?q='.$gym['lat'].','.$gym['lon'].'>)',
            'username' => "Raid-Meldung",
            'name' => "Raid-Meldung"
            ];
            
        foreach ( $allUrl as $url ) {
            sendToWebhook( $url, $logWebhook );
        }
    }
} elseif ( $action === "pokemon" ) {
    $id = ! empty( $_POST['id'] ) ? $_POST['id'] : 0;
    if ( ! empty( $lat ) && ! empty( $lng ) && ! empty( $id ) ) {
        $spawnID = randomNum();
        $cols    = [
            'spawn_id'                  => $spawnID,
            'encounter_id'              => $spawnID,
            'lon'                       => $lng,
            'lat'                       => $lat,
            'pokemon_id'                => $id,
            'expire_timestamp'          => time() + 1800,
            'updated'                   => time(),
            'weather_boosted_condition' => 0
        ];
        $db->insert( "sightings", $cols );
    }
} elseif ( $action === "gym" ) {
    $gymName = ! empty( $_POST['gymName'] ) ? $_POST['gymName'] : '';
    if ( ! empty( $lat ) && ! empty( $lng ) && ! empty( $gymName ) ) {
        $gymId = randomGymId();
        $cols  = [
            'external_id' => $gymId,
            'lat'         => $lat,
            'lon'         => $lng,
            'name'        => $gymName
        ];
        $db->insert( "forts", $cols );
    }
} elseif ( $action === "quest" ) {
    $pokestopId = ! empty( $_POST['pokestopId'] ) ? $_POST['pokestopId'] : '';
    $questId    = $_POST['questId'] == "NULL" ? 0 : $_POST['questId'];
    $reward     = $_POST['reward'] == "NULL" ? 0 : $_POST['reward'];
    $pokestops  = $db->get( "pokestops", [ 'id', 'name', 'lat', 'lon' ], [ 'external_id' => $pokestopId ] );
    if ( ! empty( $pokestopId ) && ! empty( $questId ) && ! empty( $reward ) ) {
        $cols  = [
            'quest_id' => $questId,
            'reward'   => $reward,
            'users'    => $_SESSION['user']->user
        ];
        $where = [
            'external_id' => $pokestopId
        ];
        $db->update( "pokestops", $cols, $where );
    }
    if ( $sendWebhookQuest === true ) {
        $rewardIcon = $reward;
        if (strpos($reward, 'Sonderbonbon') !== false) {
            $rewardIcon = 'Candy';
        } 
        $avatarIcon = 'https://raw.githubusercontent.com/Falaris01/PMSF/manual_v5/static/forts/discord_icons/QuestIcon_'.$rewardIcon.'.png';
		if (!file_exists($avatarIcon)) {
			$avatarIcon = 'https://raw.githubusercontent.com/Falaris01/PMSF/manual_v5/static/forts/discord_icons/QuestIcon_Discord.png';
		}
        /*
        'content' => 'Belohnung: **'.$reward.'** PokeStop: __**'.$pokestops['name'].'**__ gemeldet von: **'.$_SESSION['user']->user.'** ['.$hostUrl.']('.$hostUrl.'?lat='.$pokestops['lat'].'&lon='.$pokestops['lon'].') | [Google Maps](https://www.google.com/maps?q='.$pokestops['lat'].','.$pokestops['lon'].')',
        */
        $webhook = [
            'content' => 'Belohnung: **'.$reward.'** PokeStop: __**'.$pokestops['name'].'**__ ['.$hostUrl.']('.$hostUrl.'?lat='.$pokestops['lat'].'&lon='.$pokestops['lon'].') | [Google Maps](https://www.google.com/maps?q='.$pokestops['lat'].','.$pokestops['lon'].')',
            'avatar_url' => $avatarIcon,
            'username' => $reward,
            'name' => $reward
            ];
            
        $specialRewards = ["Aerodactyl", "Amonitas", "Anorith", "Kabuto", "Liliep", "Tragosso"];
        $interestingRewards = ["Chaneira", "Dratini", "Lapras", "Larvitar", "Pandir", "Sandan"];
        
        if ($specialQuestUrl and $interestingQuestUrl) {
            if ($specialQuestUrl and in_array($reward, $specialRewards)) {
                $questUrl = $specialQuestUrl;
            } elseif ($interestingQuestUrl and in_array($reward, $interestingRewards)) {
                $questUrl = $interestingQuestUrl;
            }
        } else {
            if (strpos($reward, 'Barschwa') !== false && $barschwaUrl) {
                $questUrl = $barschwaUrl;
            } elseif (strpos($reward, 'Aerodactyl') !== false && $aerodactylUrl) {
                $questUrl = $aerodactylUrl;
            } elseif (strpos($reward, 'Chaneira') !== false && $chaneiraUrl) {
                $questUrl = $chaneiraUrl;
            } elseif (strpos($reward, 'Ditto') !== false && $dittoUrl) {
                $questUrl = $dittoUrl;
            } elseif (strpos($reward, 'Dratini') !== false && $dratiniUrl) {
                $questUrl = $dratiniUrl;
            } elseif (strpos($reward, 'Fukano') !== false && $fukanoUrl) {
                $questUrl = $fukanoUrl;
            } elseif (strpos($reward, 'Hunduster') !== false && $hundusterUrl) {
                $questUrl = $hundusterUrl;
            } elseif (strpos($reward, 'Krabby') !== false && $krabbyUrl) {
                $questUrl = $krabbyUrl;
            } elseif (strpos($reward, 'Lapras') !== false && $laprasUrl) {
                $questUrl = $laprasUrl;
            } elseif (strpos($reward, 'Larvitar') !== false && $larvitarUrl) {
                $questUrl = $larvitarUrl;
            } elseif (strpos($reward, 'Miltank') !== false && $miltankUrl) {
                $questUrl = $miltankUrl;
            } elseif (strpos($reward, 'Nebulak') !== false && $nebulakUrl) {
                $questUrl = $nebulakUrl;
            } elseif (strpos($reward, 'Nincada') !== false && $nincadaUrl) {
                $questUrl = $nincadaUrl;
            } elseif (strpos($reward, 'Pandir') !== false && $pandirUrl) {
                $questUrl = $pandirUrl;
            } elseif (strpos($reward, 'Phanpy') !== false && $phanpyUrl) {
                $questUrl = $phanpyUrl;
            } elseif (strpos($reward, 'Pinsir') !== false && $pinsirUrl) {
                $questUrl = $pinsirUrl;
            } elseif (strpos($reward, 'Porygon') !== false && $porygonUrl) {
                $questUrl = $porygonUrl;
            } elseif (strpos($reward, 'Raupy') !== false && $raupyUrl) {
                $questUrl = $raupyUrl;
            } elseif (strpos($reward, 'Schlurp') !== false && $schlurpUrl) {
                $questUrl = $schlurpUrl;
            } elseif (strpos($reward, 'Snubbull') !== false && $snubbullUrl) {
                $questUrl = $snubbullUrl;
            } elseif (strpos($reward, 'Tangela') !== false && $tangelaUrl) {
                $questUrl = $tangelaUrl;
            } elseif (strpos($reward, 'Tannza') !== false && $tannzaUrl) {
                $questUrl = $tannzaUrl;
            } elseif (strpos($reward, 'Traunfugil') !== false && $traunfugilUrl) {
                $questUrl = $traunfugilUrl;
            } elseif (strpos($reward, 'Voltilamm') !== false && $voltilammUrl) {
                $questUrl = $voltilammUrl;
            }
            /* template
            elseif (strpos($reward, '<pkm/item>') !== false && $<pkm/item>Url) {
                $questUrl = $<pkm/item>Url;
            }
            */
        }
        foreach ( $questUrl as $url ) {
            sendToWebhook( $url, $webhook );
        }
        
        $logWebhook = [
            'content' => 'Aufgabe: **'.$questId.'**, Belohnung: **'.$reward.'**
  Pokéstop: **'.$pokestops['name'].'**
  gemeldet von: **'.$_SESSION['user']->user.'**
  ['.$hostUrl.']('.$hostUrl.'?lat='.$pokestops['lat'].'&lon='.$pokestops['lon'].') | [Google Maps](<https://www.google.com/maps?q='.$pokestops['lat'].','.$pokestops['lon'].'>)',
            'username' => 'Quest-Meldung',
            'name' => 'Quest-Meldung'
            ];
            
        foreach ( $allUrl as $url ) {
            sendToWebhook( $url, $logWebhook );
        }
    }
} elseif ( $action === "delete-quest" ) {
    $id = ! empty( $_POST['id'] ) ? $_POST['id'] : '';
    $cols  = [
        'quest_id' => null,
        'reward'   => null,
        'users'    => null
    ];
    $where = [
        'external_id' => $id
    ];
    $db->update( "pokestops", $cols, $where );
} elseif ( $action === "nest" ) {
    $pokemonId = ! empty( $_POST['pokemonId'] ) ? $_POST['pokemonId'] : '';
    $nestId    = ! empty( $_POST['nestId'] ) ? $_POST['nestId'] : '';
    if ( ! empty( $pokemonId ) && ! empty( $nestId ) ) {
        $cols  = [
            'pokemon_id' => $pokemonId,
        ];
        $where = [
            'nest_id' => $nestId
        ];
        $db->update( "nests", $cols, $where );
    }
} elseif ( $action === "pokestop" ) {
    $pokestopName = ! empty( $_POST['pokestop'] ) ? $_POST['pokestop'] : '';
    if ( ! empty( $lat ) && ! empty( $lng ) && ! empty( $pokestopName ) ) {
        $pokestopId = randomGymId();
        $cols       = [
            'external_id' => $pokestopId,
            'lat'         => $lat,
            'lon'         => $lng,
            'name'        => $pokestopName,
            'updated'     => time()
        ];
        $db->insert( "pokestops", $cols );
    }
} elseif ( $action === "new-nest" ) {
    $id = ! empty( $_POST['id'] ) ? $_POST['id'] : 0;
    if ( ! empty( $lat ) && ! empty( $lng ) && ! empty( $id ) ) {
        $cols = [
            'pokemon_id' => $id,
            'lat'        => $lat,
            'lon'        => $lng,
            'type'       => 0,
            'updated'    => time()
        ];
        $db->insert( "nests", $cols );
    }
} elseif ( $action === "delete-gym" ) {
    $gymId = ! empty( $_POST['id'] ) ? $_POST['id'] : '';
    if ( ! empty( $gymId ) ) {
        $fortid = $db->get( "forts", [ 'id' ], [ 'external_id' => $gymId ] );
        if ( $fortid ) {
            $db->delete( 'fort_sightings', [
                "AND" => [
                    'fort_id' => $fortid['id']
                ]
            ] );
            $db->delete( 'raids', [
                "AND" => [
                    'fort_id' => $fortid['id']
                ]
            ] );
            $db->delete( 'forts', [
                "AND" => [
                    'external_id' => $gymId
                ]
            ] );
        }
    }
} elseif ( $action === "delete-pokestop" ) {
    $pokestopId = ! empty( $_POST['id'] ) ? $_POST['id'] : '';
    if ( ! empty( $pokestopId ) ) {
        $db->delete( 'pokestops', [
            "AND" => [
                'external_id' => $pokestopId
            ]
        ] );
    }
} elseif ( $action === "delete-nest" ) {
    $nestId = ! empty( $_POST['nestId'] ) ? $_POST['nestId'] : '';
    if ( ! empty( $nestId ) ) {
        $db->delete( 'nests', [
            "AND" => [
                'nest_id' => $nestId
            ]
        ] );
    }
}
function randomGymId() {
    $alphabet    = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
    $pass        = array(); //remember to declare $pass as an array
    $alphaLength = strlen( $alphabet ) - 1; //put the length -1 in cache
    for ( $i = 0; $i < 12; $i ++ ) {
        $n      = rand( 0, $alphaLength );
        $pass[] = $alphabet[ $n ];
    }
    return implode( $pass ); //turn the array into a string
}
function randomNum() {
    $alphabet    = '1234567890';
    $pass        = array(); //remember to declare $pass as an array
    $alphaLength = strlen( $alphabet ) - 1; //put the length -1 in cache
    for ( $i = 0; $i < 15; $i ++ ) {
        $n      = rand( 0, $alphaLength );
        $pass[] = $alphabet[ $n ];
    }
    return implode( $pass ); //turn the array into a string
}
$jaysson = json_encode( $d );
echo $jaysson;