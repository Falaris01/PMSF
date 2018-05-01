<?php
include(dirname(__FILE__) . '/config/config.php');
global $map, $fork, $db, $nestCoords;

$url = 'https://thesilphroad.com/atlas/getLocalNests.json';

foreach($nestCoords as $c){
    $data = array(
        "data[lat1]"=> $c['lat1'],
        "data[lng1]"=> $c['lng1'],
        "data[lat2]"=> $c['lat2'],
        "data[lng2]"=> $c['lng2'],
        "data[zoom]"=> 1,
        "data[mapFilterValues][mapTypes][]"=> 1,
        "data[mapFilterValues][nestVerificationLevels][]"=> 1,
        "data[mapFilterValues][nestTypes][]"=> -1,
        "data[center_lat]"=> 42.237,
        "data[center_lng]"=> -88.26822);

// use key 'http' even if you send the request to https://...
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
        )
    );
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    if ($result === FALSE) { /* Handle error */ }

    $nests = json_decode($result,true)['localMarkers'];
    foreach($nests as $nest){
        $query = "INSERT INTO nests (nest_id, lat, lon, pokemon_id, updated,type) VALUES (" . $nest['id'] . "," . $nest['lt'] . "," . $nest['ln'] . "," . $nest['pokemon_id'] . "," . time() . ",1) ON DUPLICATE KEY UPDATE pokemon_id=" . $nest['pokemon_id'] . ", updated=" . time() . ", type=1";
        $db->query($query)->fetchAll();
    }
}
echo 'Done Successfully';
