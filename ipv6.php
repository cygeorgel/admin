<?php
if (!isset($_POST) || empty($_POST)){
    echo 'Aucune donnée envoyée !';
    exit();
}
require 'API.php';

include_once '_cgGlobal.php';

$status = isset($_POST['on']);

$api = new OvhApi(OVH_API_EU, A_KEY, A_SECRET, C_KEY);
$services = $api->get('/xdsl');
if (is_array($services)){
	foreach($services as $service){
		 $api->post('/xdsl/' . $service . '/ipv6', array('enabled' => !$status));
		 $api->post('/xdsl/' . $service . '/ipv6', array('enabled' => $status));
	}
}