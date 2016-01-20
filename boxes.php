<?php
function convertBytes($bytes){
	$units = array('bps', 'Kbps', 'Mbps', 'Gbps');
	$unitCount=0;
	while($bytes/1000>=1){
		$bytes = $bytes/1000;
		$unitCount++;
	}
	return round($bytes, 2) . $units[$unitCount];
}
try{
	include '../cti/database.conf';
	$db = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $user, $password);
}catch(Exception $ex){
	echo 'Impossible de se connecter à la base de données';
	exit();
}
require 'API.php';


include_once '_cgGlobal.php';

$api = new OvhApi(OVH_API_EU, A_KEY, A_SECRET, C_KEY);

$all = $db->query('SELECT * FROM xdslErrors')->fetchAll(PDO::FETCH_ASSOC);
foreach ($all as $datas) {
	$infos = $api->get('/xdsl/' . $datas['service']);
	if (isset($infos->description))
		$description = $infos->description;
	else
		$description = $datas['service'];
	echo '<div class="col-sm-4"><div class="panel panel-primary"><div class="panel-heading">' . $description . ' (' . date('j/n G:i', strtotime($datas['detectedTime'])) . ')<button type="button" class="close" data-dismiss="panel" data-service="' . $datas['service'] . '" aria-label="Close"><span aria-hidden="true">&times;</span></button></div><div class="panel-body" style="min-height: 130px;">';
		foreach (json_decode($datas['errors']) as $error)
			echo $error . '<br/>';
		echo '</div></div></div>';
}

/*		
$all = $db->query('SELECT * FROM xdslStats')->fetchAll(PDO::FETCH_ASSOC);
foreach ($all as $datas) {
	$errors = array();
	if ($datas['ping'] > PING_DANGER)
		$errors[] = '<span class="text-danger">Ping ' . $datas['ping'] . '</span>';
	elseif($datas['ping'] > PING_WARN)
		$errors[] = '<span class="text-warning">Ping ' . $datas['ping'] . '</span>';
	
	if (!$datas['uploadNow'])
		$uploadPercent = 100;
	elseif (!$datas['uploadLast'])
		$uploadPercent = 0;
	else
		$uploadPercent = (($datas['uploadNow']-$datas['uploadLast'])/$datas['uploadLast'])*100;
	if ($uploadPercent<SYNC_DIFF)
		$errors[] = '<span class="text-danger">Debit upload : chute de ' . -round($uploadPercent, 2) . '% (' . convertBytes($datas['uploadLast']) . ' -> ' . convertBytes($datas['uploadNow']) . ')</span>';

	if (!$datas['downloadNow'])
		$downloadPercent = 100;
	elseif (!$datas['downloadLast'])
		$downloadPercent = 0;
	else
		$downloadPercent = (($datas['downloadNow']-$datas['downloadLast'])/$datas['downloadLast'])*100;
	if ($downloadPercent<SYNC_DIFF)
		$errors[] = '<span class="text-danger">Debit download : chute de ' . -round($downloadPercent, 2) . '% (' .convertBytes($datas['downloadLast']) . ' -> ' . convertBytes($datas['downloadNow']) . ')</span>';


	if ($errors){
		$infos = $api->get('/xdsl/' . $datas['service']);
		if (isset($infos->description))
			$description = $infos->description;
		else
			$description = $datas['service'];
   
		echo '<div class="col-sm-4"><div class="panel panel-primary"><div class="panel-heading">' . $description . ' (' . $datas['service'] . ')</div><div class="panel-body">';
		foreach ($errors as $error)
			echo $error . '<br/>';
		echo '</div></div></div>';
	}
}*/