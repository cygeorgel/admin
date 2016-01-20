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
/**
  * Permet de calculer la moyenne du tableau envoyé avec un coéficient inversement proportionnel
  */
function avg($array){
	$totalCoef = 0;
	$value = 0;
	for ($i=count($array)-1; $i >= 0 ; $i--) { 
		$curr = $array[$i]->value;
		if ($curr){
			$coef = 1/($i+1);
			$value += ($curr*$coef);
			$totalCoef += $coef;
		}
	} 
	return ($totalCoef) ? $value/$totalCoef : 0;
}

set_time_limit(180);
$start = microtime();

require 'API.php';

include_once '_cgGlobal.php';

$api = new OvhApi(OVH_API_EU, A_KEY, A_SECRET, C_KEY);

try{
	include '../cti/database.conf';
	$db = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $user, $password);
}catch(Exception $ex){
	echo 'Impossible de se connecter à la base de données';
	exit();
}


$request = $db->query('SELECT * FROM xdslConfig');
$config = $request->fetch();
if (!$config){
	echo 'Impossible de se récupérer la config';
	exit();
}
if ($config['ignoredBoxes'])
	$config['ignoredBoxes'] = json_decode($config['ignoredBoxes']);
$services = $api->get('/xdsl');
$all = array();

if (is_array($services)){
	/* Test pour vérifier que la page que l'on veut charger existe bien */
	if ($config['pageSize'] * $config['lastPage'] > count($services)){
		$db->query('UPDATE xdslConfig SET lastPage = 0;');
		$config['lastPage'] = 0;
	}
	/* Récupération de la taille de la page */
	if (($config['pageSize'] * ($config['lastPage'] + 1)) > count($services)){
		$stop = count($services);
		$reinit = true;
	} else {
		$stop = $config['pageSize'] * ($config['lastPage'] + 1);
		$reinit = false;
	}
	/* Déclaration de la requete de suppression des vielles stats */
	$service = '';
	$deleteQuery = $db->prepare('DELETE FROM xdslStats WHERE service = :service');
	$deleteQuery->bindParam('service', $service);

	for ($i = $config['pageSize'] * $config['lastPage']; $i<$stop; $i++) {
		$service = $services[$i];
		if (!$service)
			break;
		$pings = $api->get('/xdsl/' . $service . '/statistics', array('period' => 'daily', 'type' => 'ping'));
		if (!isset($pings->values)){
			$pings->values = array();
		}
		$lines = $api->get("/xdsl/$service/lines");
		if (is_array($lines) && isset($lines[0])){
			$download = $api->get("/xdsl/$service/lines/$lines[0]/statistics", array('period' => 'daily', 'type' => 'synchronization:download'));
			if (!isset($download->values)){
				$download->values = array(0,0);
			}
			$upload = $api->get("/xdsl/$service/lines/$lines[0]/statistics", array('period' => 'daily', 'type' => 'synchronization:upload'));
			if (!isset($upload->values)){
				$upload->values = array(0,0);
			}
			$deleteQuery->execute();
			$deleteQuery->closeCursor();
			$all[$service] = array('ping' => $pings->values, 'download' => $download->values, 'upload' => $upload->values);
		}
	}
}

/* Si on est a la dernière page, on réinitialise */
if ($reinit)
	$db->query('UPDATE xdslConfig SET lastPage = 0;');
else
	$db->query('UPDATE xdslConfig SET lastPage = lastPage + 1;');


$ping = 0;
$uploadNow = 0;
$uploadLast = 0;
$downloadNow = 0;
$downloadLast = 0;
$insertStat = $db->prepare('INSERT INTO xdslStats VALUES (:service, :ping, :uploadLast, :uploadNow, :downloadLast, :downloadNow, CURRENT_TIMESTAMP);');
$insertStat->bindParam('service', $service);
$insertStat->bindParam('ping', $ping);
$insertStat->bindParam('uploadLast', $uploadLast);
$insertStat->bindParam('uploadNow', $uploadNow);
$insertStat->bindParam('downloadLast', $downloadLast);
$insertStat->bindParam('downloadNow', $downloadNow);

/* Requete pour les erreurs */
$errorsJSON = '';
$errorsTest = $db->prepare('SELECT service FROM xdslErrors WHERE service = :service');
$errorsTest->bindParam('service', $service);

$errorsSave = $db->prepare('INSERT INTO xdslErrors VALUES (:service, :errors, CURRENT_TIMESTAMP, 0)');
$errorsSave->bindParam('service', $service);
$errorsSave->bindParam('errors', $errorsJSON);

$errorsDelete = $db->prepare('DELETE FROM xdslErrors WHERE service = :service');
$errorsDelete->bindParam('service', $service);
foreach ($all as $service => $datas) {
	/* Ping */
	$ping = avg(array_reverse(array_splice($datas['ping'], 0, 12)));
	
	/* Upload */
	$i=0;
	do {
		$uploadNow = $datas['upload'][(count($datas['upload'])-1-$i)]->value;
		$i++;
	} while ($uploadNow == null);
	$uploadNow = avg(array_reverse(array_slice($datas['upload'], -12-$i, 12)));
	$uploadLast = avg(array_reverse(array_splice($datas['upload'], 0, 12)));
	
	/* Download */
	$i=0;
	do {
		$downloadNow = $datas['download'][(count($datas['download'])-1-$i)]->value;
		$i++;
	} while ($downloadNow == null);
	$downloadNow = avg(array_reverse(array_slice($datas['download'], -12-$i, 12)));
	$downloadLast = avg(array_splice($datas['download'], 0, 12));;
	if ($ping && $uploadLast && $uploadNow && $downloadNow && $downloadLast)
	$insertStat->execute();
	echo $service . " : $ping : $uploadNow : $uploadLast : $downloadNow : $downloadLast<br/>";
	
	/* Détection des erreurs */
	$errors = array();

	/* Ping */
	if ($ping > $config['pingDanger'])
		$errors[] = '<span class="text-danger">Ping ' . round($ping, 2) . '</span>';
	elseif($ping > $config['pingWarn'])
		$errors[] = '<span class="text-warning">Ping ' . round($ping, 2) . '</span>';
	
	/* Upload */
	if (!$uploadNow)
		$uploadPercent = 100;
	elseif (!$uploadLast)
		$uploadPercent = 0;
	else
		$uploadPercent = (($uploadNow-$uploadLast)/$uploadLast)*100;
	if ($uploadPercent<$config['syncDiff'])
		$errors[] = '<span class="text-danger">Debit upload : chute de ' . -round($uploadPercent, 2) . '% (' . convertBytes($uploadLast) . ' -> ' . convertBytes($uploadNow) . ')</span>';

	/* Download */
	if (!$downloadNow)
		$downloadPercent = 100;
	elseif (!$downloadLast)
		$downloadPercent = 0;
	else
		$downloadPercent = (($downloadNow-$downloadLast)/$downloadLast)*100;
	if ($downloadPercent<$config['syncDiff'])
		$errors[] = '<span class="text-danger">Debit download : chute de ' . -round($downloadPercent, 2) . '% (' .convertBytes($downloadLast) . ' -> ' . convertBytes($downloadNow) . ')</span>';


	if ($errors){
		$errorsTest->execute();
		if (!$errorsTest->rowCount()){
			$errorsJSON = json_encode($errors);
			$errorsSave->execute();
		}
	} else {
		$errorsDelete->execute();
	}
}

/* Envoi du mail */
if ($reinit){
	$config['startNotify'] = strtotime($config['startNotify']);
	$config['stopNotify'] = strtotime($config['stopNotify']);
	$time = time();
	if (isset($config['email']) && !empty($config['email']) && $time>$config['startNotify'] && $time<$config['stopNotify']){
		$getErrors = $db->query('SELECT * FROM xdslErrors WHERE (notified = 0 AND fixed = 0) OR TIMESTAMPDIFF(minute, detectedTime, CURRENT_TIMESTAMP) > 1440');
		if ($getErrors->rowCount()){
			$content = '';
			$errorsBox = $getErrors->fetchAll();
			if ($errorsBox){
				foreach ($errorsBox as $box) {
					if (!in_array($box['service'], $config['ignoredBoxes'])){
						$infos = $api->get('/xdsl/' . $box['service'] . '/');
						if (isset($infos->description))
							$description = $infos->description;
						else
							$description = $service;
						$content .= "<u>" . $description . '</u><br />';
						foreach (json_decode($box['errors']) as $error)
							$content .= $error . '<br />';
					}
				}
				if ($content){
					$content = '<html><head><style>.text-warning{color:#c09853} .text-danger{color:#b94a48} .text-info{color:#3a87ad} .text-success{color:#468847}</style></head><body>' . $content . '</body></html>';
					$headersmail = "From: CRON Box<cron@transacom.fr>\r\n"; // Expéditeur du message
			        $headersmail .= "Content-Type: text/html; charset=utf-8 \r\n"; // Type de mail (html)
			        $headersmail .= "MIME-Version: 1.0 ";
			        mail($config['email'], 'Raport de scan des boxs', $content, $headersmail); // Envoi du mail
				}
		        $db->query('UPDATE xdslErrors SET notified = 1;');
		    }
		}
	}
}