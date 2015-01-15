<?php
include("../../../dbconnect.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");

$headers = getallheaders();

$schema = isset($_SERVER['HTTPS']) ? "https://" : "http://";
$currentUrl = $schema . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

$gatewaymodule = "coinzone";
$coinzoneConfig = getGatewayVariables($gatewaymodule);
if (!$coinzoneConfig["type"]) {
    header("HTTP/1.0 400 Bad Request");
    exit("Module Not Activated");
}

# Get Returned Variables - Adjust for Post Variable Names from your Gateway's Documentation
$input = $_POST;
$content = file_get_contents("php://input");

$apiKey = html_entity_decode($coinzoneConfig['apiKey']);
$stringToSign = $content . $currentUrl . $headers['timestamp'];
$signature = hash_hmac('sha256', $stringToSign, $apiKey);
if ($signature !== $headers['signature']) {
    header("HTTP/1.0 400 Bad Request");
    exit("Invalid callback" . $signature);
}

$status = $input["status"];
$invoiceid = $input["merchantReference"];
$transid = $input["refNo"];
$amount = $input["amount"];
$fee = null;

$invoiceid = checkCbInvoiceID($invoiceid,$coinzoneConfig["name"]);
checkCbTransID($transid); # Checks transaction number isn't already in the database and ends processing if it does

if (in_array($status, array('PAID', 'COMPLETE'))) {
    # Successful
    addInvoicePayment($invoiceid,$transid,$amount,$fee,$gatewaymodule); # Apply Payment to Invoice: invoiceid, transactionid, amount paid, fees, modulename
    logTransaction($coinzoneConfig["name"],$_POST,"Successful"); # Save to Gateway Log: name, data array, status
}
