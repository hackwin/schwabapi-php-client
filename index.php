<?php

$client_id = '********************************'; // 32-char Schwab Developer Portal -> Dashboard -> View Details -> App Key
$client_secret = '****************'; // 16-char Schwab Developer Portal ->  Dashboard -> View Details -> Secret
$redirect_uri = 'https://domain.ext/schwab/'; // Your web app URL (must be https)
$accountNumber = '99999999'; // 8-digits Schwab account number
$hashedAccountNumber = '****************************************************************'; // 64-char from getAccountNumbers()

require_once('functions.php');

// open your web browser and trigger functions like this: 
// https://domain.ext/schwab/?auth // allows you to grant authorization to run the functions below
// https://domain.ext/schwab/?quote&symbol=AAPL // gets the price of AAPL and various details
// https://domain.ext/schwab/?accountNumbers // gives your 64-character encoded account number
// https://domain.ext/schwab/?trade // edit JSON to buy or sell
// https://domain.ext/schwab/?getAccountOrders // there is a slight delay to this, check schwab.com to verify

// timings:
// 30 seconds after auth to use the URL variable 'code' to create an access_token
// 30 minutes to use an access_token before refresh_token needed to new access_token
// 7 days to use the refresh_token before you need to re-authorize

if((empty($_REQUEST))){
    echo 'Installer code could go here...';
}
else{
	// tokens in private JSON file are checked against the file modification datetime to decide whether to auto-refresh access_token
    autoRefreshOrRenewTokensAsNeeded($client_id, $client_secret, $redirect_uri);
}

if(isset($_REQUEST['auth'])){
    authorizationCode($client_id, $redirect_uri);
}
else if(isset($_REQUEST['code'])){
    accessTokenCreation($_REQUEST['code'], $client_id, $client_secret, $redirect_uri);
}
else if(isset($_REQUEST['quote'])){
    getQuote($_REQUEST['symbol']);
}
else if(isset($_REQUEST['refresh'])){
    refreshAccessToken($client_id, $client_secret);
}
else if(isset($_REQUEST['renew'])){
    autoRefreshOrRenewTokensAsNeeded($client_id, $client_secret, $redirect_uri);
}
else if(isset($_REQUEST['accountNumbers'])){
    getAccountNumbers();
}
else if(isset($_REQUEST['trade'])){
    $jsonData = <<<json
{
  "orderType": "MARKET", 
  "session": "NORMAL", 
  "duration": "DAY", 
  "orderStrategyType": "SINGLE", 
  "orderLegCollection": [ 
   { 
    "instruction": "SELL", 
    "quantity": 1, 
    "instrument": { 
     "symbol": "INTC", 
     "assetType": "EQUITY" 
    } 
   } 
  ] 
}
json;
    makeTrade($hashedAccountNumber, $jsonData);
    getAccountOrders($hashedAccountNumber);
}
else if(isset($_REQUEST['getAccountOrders'])){
    getAccountOrders($hashedAccountNumber);
}