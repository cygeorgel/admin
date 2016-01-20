<?php
if (!isset($_POST['ips']) || empty($_POST['ips'])){
    echo 'Aucune donnée envoyée !';
    exit();
}
require 'API.php';

include_once '_cgGlobal.php';

if (!strlen($_POST['ips'])){
	echo "Aucune IP entrée !\n";
	exit;
}
$_POST['ips'] = str_replace("\r\n", "\n", $_POST['ips']);
foreach(explode("\n", $_POST['ips']) as $ip){
    if (preg_match("#^((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$#", $ip)){
		$ips[] = $ip;
	} else {
		echo $ip . " n'est pas une IP valide !\n";
	}
}
if (!isset($ips)){
	echo "Aucune IP valide n'a été entrée\n";
	exit;
}

$domain = "transacom.fr";
$subDomains = array();
$api = new OvhApi(OVH_API_EU, A_KEY, A_SECRET, C_KEY);
foreach($ips as $key => $ip) {
	$subDomains[$key] = preg_replace('#^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$#', '$4-$3-$2-$1.dsl', $ip);
	if (strlen($subDomains[$key])){
        $current = $api->get('/domain/zone/' . $domain . '/record', array('fieldType' => 'A', 'subDomain' => $subDomains[$key]));
        $deleted=false;
        if (count($current)){
            foreach($current as $id){
                $details = $api->get('/domain/zone/' . $domain . '/record/' . $id);
                if (!(isset($details->target) && $details->target == $ip)){
                    $api->delete('/domain/zone/' . $domain . '/record/' . $id);
                    $deleted=true;
                }
            }
        } else
            $deleted=true;
        if ($deleted){
           $api->post('/domain/zone/' . $domain . '/record', array('fieldType' => 'A', 'subDomain' => $subDomains[$key], 'target' => $ip, 'ttl' => 0));
        }
    }
}
$api->post('/domain/zone/' . $domain . '/refresh');
sleep(10);
foreach($ips as $key => $ip){
    if (strlen($subDomains[$key])){
        $response = $api->post('/ip/' . $ip . '/reverse', array('ipReverse' => $ip, 'reverse' => $subDomains[$key] . '.' . $domain));
        if (isset($response->message))
            echo $response->message . '<br />';
    }
}