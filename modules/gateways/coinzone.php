<?php
function coinzone_config() {
    $configarray = array(
        "coinzoneDescription" => array(
            "Type" => "hidden",
            "Description" => 'Add your Client Code and API Key below to configure Coinzone.' .
                'This can be found on the API tab of the Settings page in the <a href="https://merchant.coinzone.com/settings">Coinzone Control Panel</a>.<br/>' .
                'Have questions?  Please visit our <a href="http://support.coinzone.com/">Customer Support Site</a>.<br/>' .
                'Don\'t have a Coinzone account? <a href="https://merchant.coinzone.com/signup">Sign up for free.</a>'
        ),
        "FriendlyName" => array(
            "Type" => "System",
            "Value"=>"Bitcoin - Powered by Coinzone"
        ),
        "clientCode" => array(
            "FriendlyName" => "Client Code",
            "Type" => "text",
            "Size" => "20"
        ),
        "apiKey" => array(
            "FriendlyName" => "API Key",
            "Type" => "text",
            "Size" => "20"
        )
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
