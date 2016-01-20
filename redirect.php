<?php
if (!isset($_POST) || empty($_POST)){
    echo 'Aucune donnée envoyée !';
    exit();
}
require 'API.php';

include_once '_cgGlobal.php';

$targets = $_POST['datas'];

$api = new OvhApi(OVH_API_EU, A_KEY, A_SECRET, C_KEY);
foreach($targets as $target) {
    $FQDN = $target['domain'];
    $target = $target['target'];
	preg_match_all("#^((([a-zA-Z0-9-_]\-+\.)*[a-zA-Z0-9-\-_]+)\.)?([a-zA-Z0-9\-_]+\.[a-zA-Z]{2,})$#", $FQDN, $matches);
	$domain = $matches[4][0];
	$subDomain = $matches[2][0];
	if (strlen($subDomain)){
        /* Suppression d'une possible redirection existante */
        $current = $api->get('/domain/zone/' . $domain . '/redirection', array('subDomain' => $subDomain));
        if (count($current)){
            foreach($current as $id){
                $api->delete('/domain/zone/' . $domain . '/redirection/' . $id);
            }
        }
        /* Suppressions des champs existant sur le domaine */
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
	/* Ajout de la nouvelle redirection */
	$api->post('/domain/zone/' . $domain . '/redirection', array('subDomain' => $subDomain, 'target' => $target, 'type' => 'visiblePermanent'));
}