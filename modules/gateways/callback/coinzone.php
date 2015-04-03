<?php
include("../../../dbconnect.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");

$headers = getallheaders();
$nHeaders = array();
foreach ($headers as $key => $value) {
    $nHeaders[strtolower($key)] = $value;
}

$schema = isset($_SERVER['HTTPS']) ? "https://" : "http://";
$currentUrl = $schema . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

$gatewaymodule = "coinzone";
$coinzoneConfig = getGatewayVariables($gatewaymodule);
if (!$coinzoneConfig["type"]) {
    header("HTTP/1.0 400 Bad Request");
    exit("Module Not Activated");
}

# Get Returned Variables - Adjust for Post Variable Names from your Gateway's Documentation
if (!empty($_POST)) {
    $input = $_POST;
    $content = http_build_query($input);
} else {
    $content = file_get_contents("php://input");
    $input = json_decode($content, 1);
}

if (empty($input)) {
    header("HTTP/1.0 400 Bad Request");
    exit("No content received");
}

$apiKey = html_entity_decode($coinzoneConfig['apiKey']);
$stringToSign = $content . $currentUrl . $nHeaders['timestamp'];
$signature = hash_hmac('sha256', $stringToSign, $apiKey);
if ($signature !== $nHeaders['signature']) {
    header("HTTP/1.0 400 Bad Request");
    exit("Invalid callback");
}

$status = $input["status"];
$invoiceid = (int)$input["merchantReference"];

$invoiceSQL = mysql_query(
    "
          SELECT
            tblinvoices.total, tblinvoices.status, tblcurrencies.code
          FROM
            tblinvoices, tblclients, tblcurrencies
          WHERE
            1
            AND tblinvoices.userid = tblclients.id
            AND tblclients.currency = tblcurrencies.id
            AND tblinvoices.id = $invoiceid
        "
);

$invoice = mysql_fetch_assoc($invoiceSQL);

$input['amount'] = $invoice['total'];
$input['currency'] = $invoice['code'];

$transid = $input["refNo"];
$amount = $invoice['total'];
$fee = 0;

$invoiceid = checkCbInvoiceID($invoiceid,$coinzoneConfig["name"]);
checkCbTransID($transid); # Checks transaction number isn't already in the database and ends processing if it does

if (in_array($status, array('PAID', 'COMPLETE'))) {
    # Successful
    addInvoicePayment($invoiceid,$transid,$amount,$fee,$gatewaymodule); # Apply Payment to Invoice: invoiceid, transactionid, amount paid, fees, modulename
    logTransaction($coinzoneConfig["name"],$input,"Successful"); # Save to Gateway Log: name, data array, status
    exit('OK');
}
header("HTTP/1.0 400 Bad Request");
exit("No Action");
