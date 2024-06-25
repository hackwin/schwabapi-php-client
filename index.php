<?php

require_once('functions.php');

if(!file_exists($schwabPrivateFolder)){
    echo 'Error: Private folder does not exist, create it and update functions.php, and try again.';
    exit();
}
else if(file_exists($schwabPrivateFolder) && !file_exists(getFilePath('appParameters.json'))){
    echo 'Error: Private folder does not contain your appParameters.json file.  Create it and try again. ';
    exit();
}

if(!isset($_REQUEST['code'])) {
    autoRefreshRenewTokens(); // if access tokens don't exist, or expired
}
else{
    createAccessToken($_REQUEST['code']);  // response from granting access in schwab api
    saveAccountNumbers(); // need to get hashValue of accountNumber
}

if((empty($_REQUEST))){
    echo <<<HTML
    <h3>AutoTrader Schwab API App</h3>
    <hr>
    <form style='margin: 0; padding: 0; display: inline;' target="resultFrame"><span>Get Quote: <input type="hidden" name="quote"><input type="text" name="symbol" value="aapl" style="display: inline;"><input type="submit" style="display: inline;"></span></form>
    <span style="display: inline;"> | <a href="?getAccountBalance" target="resultFrame">Get Balance</a></span> 
    <span style="display: inline;"> | <a href="?getAccountOrders" target="resultFrame">Get Orders</a></span>
    <span style="display: inline;"> | <a href="?closeSellAll" target="resultFrame">Sell & close trades</a></span>
<hr>
<iframe name="resultFrame" style="width: 100%; height: 75%;"></iframe>
<hr>
Indicators: (Risk On or Risk Off) | Gold Daily Price Change: []| 
HTML;
}

if(isset($_REQUEST['auth'])){
    authorizationCode();
}
else if(isset($_REQUEST['quote'])){
    getQuote($_REQUEST['symbol']);
}
else if(isset($_REQUEST['refresh'])){
    refreshAccessToken();
}
else if(isset($_REQUEST['renew'])){
    autoRefreshRenewTokens();
}
else if(isset($_REQUEST['trade'])){
    $jsonData = <<<json
{
  "orderType": "MARKET",
  "session": "NORMAL",
  "duration": "DAY",
  "orderStrategyType": "TRIGGER",
  "orderLegCollection": [
   {
    "instruction": "BUY",
    "quantity": 1,
    "instrument": {
     "symbol": "VZ",
     "assetType": "EQUITY"
    }
   }
  ],
  "childOrderStrategies": [
    {
    "orderType": "LIMIT",
    "session": "NORMAL",
    "price": "41.50",
    "duration": "DAY",
    "orderStrategyType": "SINGLE",
    "orderLegCollection": [
      {
        "instruction" : "SELL",
        "quantity": 1,
        "instrument": {
          "symbol": "VZ",
          "assetType": "EQUITY"
        }
      }
    ]
    }
  ]
}
json;
    makeTrade($jsonData);
}
else if(isset($_REQUEST['getAccountOrders'])){
    getAccountOrders();
}
else if(isset($_REQUEST['getAccountBalance'])){
    getAccountBalance();
}