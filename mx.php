<?php

if (!isset($_POST) || empty($_POST)){
    echo 'Aucune donnée envoyée !';
    exit();
}
require 'API.php';

include_once '_cgGlobal.php';

$domains = explode(PHP_EOL, $_POST['domains']);
$mail_servers = $_POST['servers'];
$api = new OvhApi(OVH_API_EU, A_KEY, A_SECRET, C_KEY);
foreach($domains as $domain){
	
	/* Suppression des anciens MX */
	$current = $api->get('/domain/zone/' . $domain . '/record', array('fieldType' => 'MX'));
	if (count($current)){
		foreach($current as $id){
			$api->delete('/domain/zone/' . $domain . '/record/' . $id);
		}
	}
	
	/* Ajout des nouveaux MX */
    if ($mail_servers[0]){
	   $mail_servers[0]['server'] = 'mail.' . $domain;
    }
	foreach($mail_servers as $mx){
        if ($mx)
            $api->post('/domain/zone/' . $domain . '/record', array('fieldType' => 'MX', 'ttl' => 0, 'target' => $mx['priority'] . ' ' . $mx['server'] . '.'));
	}
}
