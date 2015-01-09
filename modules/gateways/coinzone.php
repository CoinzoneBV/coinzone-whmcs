<?php
function coinzone_config() {
    $configarray = array(
        "FriendlyName" => array("Type" => "System", "Value"=>"Coinzone"),
        "clientCode" => array("FriendlyName" => "Client Code", "Type" => "text", "Size" => "20"),
        "apiKey" => array("FriendlyName" => "API Key", "Type" => "text", "Size" => "20"),
        "transactionSpeed" => array("FriendlyName" => "Transaction Speed", "Type" => "dropdown", "Options" => "NONE,LOW,MEDIUM,HIGH"),
    );
    return $configarray;
}

function coinzone_link($params) {
    $post = array(
        'invoiceid' => $params['invoiceid'],
        'notificationUrl' => $params['systemurl'] . '/modules/gateways/callback/coinzone.php',
        'email' => $params['clientdetails']['email'],
        'description' => $params['description']
    );

    $code = '<form action="' . $params['systemurl'] . '/modules/gateways/coinzone/coinzone.php" method="post" >';

    foreach ($post as $key => $value) {
        $code.= '<input type="hidden" name="'.$key.'" value = "'.$value.'" />';
    }
    $code .= '<input type="submit" value="Pay Now" /></form>';

    return $code;
}
