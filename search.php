<?php
include( 'config/config.php' );
global $map, $fork, $db, $noSearch, $noGyms, $noPokestops, $noRaids, $defaultUnit;
if ( $noSearch === true || ( $noGyms && $noRaids && $noPokestops ) ) {
    http_response_code( 401 );
    die();
}
$term = ! empty( $_POST['term'] ) ? $_POST['term'] : '';
$action = ! empty( $_POST['action'] ) ? $_POST['action'] : '';
$lat = ! empty( $_POST['lat'] ) ? $_POST['lat'] : '';
$lon = ! empty( $_POST['lon'] ) ? $_POST['lon'] : '';
$dbname = '';
if ( $action === "pokestops" ) {
    $dbname = "pokestops";
} elseif ( $action === "forts" ) {
    $dbname = "forts";
} elseif ( $action === "reward" ) {
    $dbname = "pokestops";
} elseif ( $action === "nests" ) {
    $dbname = "nests";
}

if ( $dbname !== '' ) {
    if ( $action === "reward" ) {
        if ( $db->info()['driver'] === 'pgsql' ) {
            $data = $db->query( "SELECT id,external_id,name,lat,lon,url,quest_id,reward FROM " . $dbname . " WHERE LOWER(reward) LIKE :name LIMIT 10", [ ':name' => "%" . strtolower( $term ) . "%" ] )->fetchAll();
        } else {
            $data = $db->select( "pokestops", [
                'id',
                'external_id',
                'name',
                'lat',
                'lon',
                'url',
                'quest_id',
                'reward'
            ], [ 'reward[~]' => $term, 'LIMIT' => 10 ] );
        }
    } elseif ( $action === "nests" ) {

        $json = file_get_contents( 'static/dist/data/pokemon.min.json' );
        $mons = json_decode( $json, true );
        $resids = [];
        foreach($mons as $k => $mon){
            if( $k > 493){
                break;
            }
            if(strpos($mon['name'], $term) !== false){
                $resids[] = $k;
            } else{
                foreach($mon['types'] as $t){
                    if(strpos($t['type'], $term) !== false){
                        $resids[] = $k;
                        break;
                    }
                }
            }
        }
        if ( $db->info()['driver'] === 'pgsql' ) {
            $query = "SELECT nest_id,pokemon_id,lat,lon, ROUND(cast( 3959 * acos( cos( radians(:lat) ) * cos( radians( lat ) ) * cos( radians( lon ) - radians(:lon) ) + sin( radians(:lat) ) * sin( radians( lat ) ) ) as numeric),2) AS distance FROM nests WHERE pokemon_id IN (" . implode(',',$resids) . ") ORDER BY distance LIMIT 10";
        } else{
            $query = "SELECT nest_id,pokemon_id,lat,lon, ROUND(( 3959 * acos( cos( radians(:lat) ) * cos( radians( lat ) ) * cos( radians( lon ) - radians(:lon) ) + sin( radians(:lat) ) * sin( radians( lat ) ) ) ),2) AS distance FROM nests WHERE pokemon_id IN (" . implode(',',$resids) . ") ORDER BY distance LIMIT 10";
        }
        $data = $db->query($query,[ ':lat' => $lat, ':lon' => $lon])->fetchAll();
        foreach($data as $k => $p){
            $data[$k]['name'] = $mons[$p['pokemon_id']]['name'];
            if($defaultUnit === "km"){
                $data[$k]['distance'] = round($data[$k]['distance'] * 1.60934,2);
            }
        }
    } else {
        if ( $db->info()['driver'] === 'pgsql' ) {
            $data = $db->query( "SELECT id,external_id,name,lat,lon,url FROM " . $dbname . " WHERE LOWER(name) LIKE :name LIMIT 10", [ ':name' => "%" . strtolower( $term ) . "%" ] )->fetchAll();
        } else {
            $data = $db->select( $action, [ 'id', 'external_id', 'name', 'lat', 'lon', 'url' ], [
                'name[~]' => $term,
                'LIMIT'   => 10
            ] );
        }
    }
    //var_dump($db->last());

    // set content type
    header( 'Content-Type: application/json' );

    $jaysson = json_encode( $data );
    echo $jaysson;
}
