<?php
if (!isset($_POST) || empty($_POST)){
    echo 'Aucune donnée envoyée !';
    exit();
}
require 'API.php';

include_once 'c_cgGlobal.php';

$targets = $_POST['datas'];

$api = new OvhApi(OVH_API_EU, A_KEY, A_SECRET, C_KEY);
foreach($targets as $target) {
    $FQDN = $target['fqdn'];
    $ips = $target['ips'];
	preg_match_all("#^((([a-zA-Z0-9-_]+\.)*[a-zA-Z0-9-_]+)\.)?([a-zA-Z0-9-_]+\.[a-zA-Z]{2,})$#", $FQDN, $matches);
	$domain = $matches[4][0];
	$subDomain = $matches[2][0];
	/* Suppression de l'enregistrement du domaine */
	if (strlen($subDomain)){
        $current = $api->get('/domain/zone/' . $domain . '/record', array('fieldType' => 'CNAME', 'subDomain' => $subDomain));
        if (count($current)){
            foreach($current as $id){
                $api->delete('/domain/zone/' . $domain . '/record/' . $id);
            }
        }
    
        $current = $api->get('/domain/zone/' . $domain . '/record', array('fieldType' => 'A', 'subDomain' => $subDomain));
        if (count($current)){
            foreach($current as $id){
                $api->delete('/domain/zone/' . $domain . '/record/' . $id);
            }
        }
    }
	/* Ajout des nouveau sous-domaine */
    foreach(explode(PHP_EOL, $ips) as $ip)
		$api->post('/domain/zone/' . $domain . '/record', array('fieldType' => 'A', 'ttl' => 0, 'subDomain' => $subDomain, 'target' => $ip));
}