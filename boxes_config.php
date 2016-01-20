<?php
header('Content-type: application/json');
try{
	include '../cti/database.conf';
	$db = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $user, $password);
}catch(Exception $ex){
	echo json_encode(array('success' => false, 'message' =>'Impossible de se connecter à la base de données'));
	exit();
}

if (isset($_POST['ignoredBoxes'], $_POST['email'], $_POST['startNotify'], $_POST['stopNotify'], $_POST['pingWarn'], $_POST['pingDanger'], $_POST['syncDiff'])){
	if (!is_array($_POST['ignoredBoxes']))
		$_POST['ignoredBoxes'] = array($_POST['ignoredBoxes']);
	$request = $db->prepare('UPDATE xdslConfig SET ignoredBoxes = :ignoredBoxes, email = :email, startNotify = :startNotify, stopNotify = :stopNotify, pingWarn = :pingWarn, pingDanger = :pingDanger, syncDiff = :syncDiff');
	$request->bindValue('ignoredBoxes', json_encode($_POST['ignoredBoxes']));
	$request->bindValue('email', $_POST['email']);
	$request->bindValue('startNotify', $_POST['startNotify']);
	$request->bindValue('stopNotify', $_POST['stopNotify']);
	$request->bindValue('pingWarn', $_POST['pingWarn']);
	$request->bindValue('pingDanger', $_POST['pingDanger']);
	$request->bindValue('syncDiff', $_POST['syncDiff']);
	$return = array('success' => $request->execute());
	$return['message'] = $return['success'] ? "Configuration éditée avec succès !" : "Echec lors de la modification de la configuration";
	echo json_encode($return);
	exit();
} elseif (isset($_GET['action'], $_GET['service']) && $_GET['action'] == 'fixed'){
	$fixReq = $db->prepare('UPDATE xdslErrors SET fixed = 1 WHERE service = :service');
	$fixReq->bindValue('service', $_GET['service']);
	echo json_encode(array('success' => $fixReq->execute()));
	exit();
} else {
	if (isset($_GET['list'])){ // Récupération de la liste des boxes
		$file = 'cache/boxes.cache';
		if (file_exists($file)){ // Si le fichier de cache existe
			$time = filemtime($file);
		    if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $time) {
		        header('Last-Modified: ' . $_SERVER['HTTP_IF_MODIFIED_SINCE'], true, 304);
		        exit();
		    }
		    if ($time>strtotime('-12 hours')){
	    		header("Last-Modified: " . gmdate('r', $time));
		    	echo file_get_contents($file);
		    	exit();
		    }
		}
	    $f = fopen($file, 'w+');

		require 'API.php';

		include_once '_cgGlobal.php';

		$api = new OvhApi(OVH_API_EU, A_KEY, A_SECRET, C_KEY);
		
		$services = $api->get('/xdsl');
		$return = array();
		foreach ($services as $service) {
			$infos = $api->get('/xdsl/' . $service);
			$return[] = array('id' => $service, 'description' => ((isset($infos->description)) ? $infos->description : $service));
		}
		fwrite($f, json_encode($return));
		fclose($f);
		header("Last-Modified: " . gmdate('r', filemtime($file)));
    	echo file_get_contents($file);
		exit();
	} else { // Récupération de la configuration
		echo json_encode($db->query('SELECT ignoredBoxes, email, startNotify, stopNotify, pingWarn, pingDanger, syncDiff FROM xdslConfig')->fetch(PDO::FETCH_ASSOC));
		exit();
	}
}