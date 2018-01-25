<?php

namespace Scanner;

class RocketMap_Sloppy extends RocketMap
{
    // This is based on assumption from the last version I saw
    public function get_active($eids, $minIv, $minLevel, $exMinIv, $swLat, $swLng, $neLat, $neLng, $tstamp = 0, $oSwLat = 0, $oSwLng = 0, $oNeLat = 0, $oNeLng = 0)
    {
        global $db;
        $conds = array();
        $params = array();

        $select = "pokemon_id, Unix_timestamp(Convert_tz(disappear_time, '+00:00', @@global.time_zone)) AS disappear_time, encounter_id, latitude, longitude, gender, form, weight, height, weather_boosted_condition";
        global $noHighLevelData;
        if (!$noHighLevelData) {
            $select .= ", individual_attack, individual_defense, individual_stamina, move_1, move_2, cp, cp_multiplier";
        }

        $conds[] = "latitude > :swLat AND longitude > :swLng AND latitude < :neLat AND longitude < :neLng AND disappear_time > :time";
        $params[':swLat'] = $swLat;
        $params[':swLng'] = $swLng;
        $params[':neLat'] = $neLat;
        $params[':neLng'] = $neLng;
        $date = new \DateTime();
        $date->setTimezone(new \DateTimeZone('UTC'));
        $date->setTimestamp(time());
        $params[':time'] = date_format($date, 'Y-m-d H:i:s');

        if ($oSwLat != 0) {
            $conds[] = "NOT (latitude > :oswLat AND longitude > :oswLng AND latitude < :oneLat AND longitude < :oneLng)";
            $params[':oswLat'] = $oSwLat;
            $params[':oswLng'] = $oSwLng;
            $params[':oneLat'] = $oNeLat;
            $params[':oneLng'] = $oNeLng;
        }
        if ($tstamp > 0) {
            $date->setTimestamp($tstamp);
            $conds[] = "last_modified > :lastUpdated";
            $params[':lastUpdated'] = date_format($date, 'Y-m-d H:i:s');
        }
        if (count($eids)) {
            $pkmn_in = '';
            $i = 1;
            foreach ($eids as $id) {
                $params[':qry_' . $i . "_"] = $id;
                $pkmn_in .= ':qry_' . $i . "_,";
                $i++;
            }
            $pkmn_in = substr($pkmn_in, 0, -1);
            $conds[] = "pokemon_id NOT IN ( $pkmn_in )";
        }
        $float = $db->info()['driver'] == 'pgsql' ? "::float" : "";
        if (!empty($minIv) && !is_nan((float)$minIv) && $minIv != 0) {
            if (empty($exMinIv)) {
                $conds[] = '((individual_attack' . $float . ' + individual_defense' . $float . ' + individual_stamina' . $float . ')' . $float . ' / 45.00) * 100.00 >= ' . $minIv;
            } else {
                $conds[] = '(((individual_attack' . $float . ' + individual_defense' . $float . ' + individual_stamina' . $float . ')' . $float . ' / 45.00) * 100.00 >= ' . $minIv . ' OR pokemon_id IN(' . $exMinIv . ') )';
            }
        }
        if (!empty($minLevel) && !is_nan((float)$minLevel) && $minLevel != 0) {
            if (empty($exMinIv)) {
                $conds[] = 'cp_multiplier >= ' . $this->cp_multiplier[$minLevel];
            } else {
                $conds[] = '(cp_multiplier >= ' . $this->cp_multiplier[$minLevel] . ' OR pokemon_id IN(' . $exMinIv . ') )';
            }
        }
        return $this->query_active($select, $conds, $params);
    }
}
