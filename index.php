<?php

// Jesse Campbell
// jcamp@gmx.com
// Written for "Schwab Trader API - Individual" https://developer.schwab.com/products
// Files that get generated in your project folder: responses.html and accessTokens.json

$client_id = '********************************'; // Schwab Developer Portal -> Dashboard -> View Details -> App Key
$client_secret = '****************'; //Schwab Developer Portal ->  Dashboard -> View Details -> Secret
$redirect_uri = 'https://domain.ext/schwab/'; // Your web app URL (must be https and exactly match "Callback URL" from "App Details"

require_once('functions.php');

if(isset($_REQUEST['auth']) || empty($_REQUEST)){
    authorizationCode($client_id, $redirect_uri);
}
else if(isset($_REQUEST['code'])){
    accessTokenCreation($_REQUEST['code'], $client_id, $client_secret, $redirect_uri);
}
else if(isset($_REQUEST['quote'])){
    $tokens = json_decode(file_get_contents('accessTokens.json'),$assoc=true);
    if(isset($tokens['error'])){
        echo '<pre>'.print_r($tokens,true).'</pre>';
    }
    else if(isset($tokens['access_token'])){
        getQuote($_REQUEST['symbol'], $tokens['access_token']);
    }
}
else if(isset($_REQUEST['refresh'])){
    $tokens = json_decode(file_get_contents('accessTokens.json'),$assoc=true);
    print_r($tokens);
    if(!isset($tokens['refresh_token'])){
        die('no saved refresh token in accessTokens.json');
    }
    refreshAccessToken($client_id, $client_secret, $tokens['refresh_token']);
}