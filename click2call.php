<?php
if (!isset($_POST['lines'], $_POST['user'], $_POST['password'])){
    echo 'Aucune donnée envoyée !';
    exit();
}
try {
    $soap = new SoapClient("https://www.ovh.com/soapi/soapi-re-1.63.wsdl");
    //login
    $session = $soap->login("", "","fr", false);
    foreach(preg_split("/,[\s]?/", $_POST['lines']) as $line)
    $soap->telephonyClick2CallUserAdd($session, $line, "", $_POST['user'], $_POST['password']);
    $soap->logout($session);
} catch(SoapFault $fault) {
    echo $fault->getMessage();
}