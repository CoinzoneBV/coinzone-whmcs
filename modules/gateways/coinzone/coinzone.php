<?php
include '../../../dbconnect.php';
include '../../../includes/gatewayfunctions.php';

$response = _coinzoneTransaction();
if ($response->status->code !== 201) {
    exit('Error');
}

header('Location:' . $response->response->url);

/** COINZONE New Transaction */
function _coinzoneTransaction() {
    $gatewaymodule = "coinzone";
    $coinzoneConfig = getGatewayVariables($gatewaymodule);

    $invoiceId = (int) $_POST['invoiceid'];
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
            AND tblinvoices.id = $invoiceId
        "
    );

    $invoice = mysql_fetch_assoc($invoiceSQL);
    if (!$invoice) {
        die("Invalid invoice");
    }
    $currency = $invoice['code'];
    $amount = $invoice['total'];

    $convertTo = false;
    $converSQL = mysql_query(
        "
          SELECT
            value
          FROM
            tblpaymentgateways
          WHERE
            1
            AND `gateway` = '$gatewaymodule'
            AND `setting` = 'convertto'
        "
    );
    $convert = mysql_fetch_assoc($converSQL);
    if ($convert) {
        $convertTo = $convert['value'];
    }
    if ($convertTo) {
        $rateSQL = mysql_query(
            "
              SELECT
                rate
              FROM
                tblcurrencies
              WHERE
                1
                AND `code` = '$currency'
            "
        );
        $rate = mysql_fetch_assoc($rateSQL);
        if (!$rate) {
            die("Invalid invoice currency");
        }

        $convertRateSQL = mysql_query(
            "
              SELECT
                code, rate
              FROM
                tblcurrencies
              WHERE
                1
                AND `id` = $convertTo
            "
        );
        $convertRate = mysql_fetch_assoc($convertRateSQL);
        if (!$convertRate) {
            die("Invalid convertTo currency");
        }
        $currency = $convertRate['code'];
        $amount    = $amount / $rate['rate'] * $convertRate['rate'];
    }


    $url = 'https://api.coinzone.local/v2/transaction';

    $payload = json_encode(
        array(
            'amount' => $amount,
            'currency' => $currency,
            'merchantReference' => $_POST['invoiceid'],
            'speed' => $coinzoneConfig['transactionSpeed'],
            'email' => $_POST['email'],
            'description' => $_POST['description'],
            'notificationUrl' => $_POST['notificationUrl']
        )
    );
    var_dump($payload);
    $timestamp = time();
    $stringToSign = $payload . $url . $timestamp;
    $signature = hash_hmac('sha256', $stringToSign, $coinzoneConfig['apiKey']);

    $headers = array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload),
        'clientCode: ' . $coinzoneConfig['clientCode'],
        'timestamp: ' . $timestamp,
        'signature: ' . $signature
    );
    $curlHandler = curl_init($url);
    curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curlHandler, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curlHandler, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curlHandler, CURLOPT_SSL_VERIFYPEER, false);

    if (!empty($payload)) {
        curl_setopt($curlHandler, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curlHandler, CURLOPT_POSTFIELDS, $payload);
    }

    $result = curl_exec($curlHandler);
    var_dump($result);
    if ($result === false) {
        return false;
    }
    $response = json_decode($result);
    curl_close($curlHandler);

    return $response;
}


