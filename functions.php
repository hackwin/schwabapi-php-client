<?php
function saveRequest($ch, $postBody=''){
    $request = curl_getinfo($ch, CURLINFO_HEADER_OUT);
    if($postBody != '') {
        $request .= "\r\n\r\n";
        $request .= $postBody;
    }
    file_put_contents('c:/wamp64/priv/schwab/transactions.html', '<hr><h3>Request</h3><h4>'.date("F d, Y h:i:s A", time()).'</h4><pre>'.htmlentities($request).'</pre>', FILE_APPEND);
}
function saveResponses($response){
    file_put_contents('c:/wamp64/priv/schwab/transactions.html', '<hr><h3>Response</h3></h3><h4>'.date("F d, Y h:i:s A", time()).'</h4><pre>'.htmlentities($response).'</pre>', FILE_APPEND);
}
function authorizationCode($client_id, $redirect_uri){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,
        'https://api.schwabapi.com/v1/oauth/authorize?'
        .'client_id='.$client_id
        .'&redirect_uri='.urlencode($redirect_uri));

    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);

    $response = curl_exec($ch);
    saveRequest($ch,'');
    saveResponses($response);

    $headers = get_headers_from_curl_response($response);

    if(isset($headers['location'])) { // log in to your schwab api account and grant authorization
        header('Location: ' . $headers['location']);
        exit();
    }
    else {
        echo '<pre>' . htmlentities($response) . '</pre>';
    }
}
function accessTokenCreation($code, $client_id, $client_secret, $redirect_uri){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.schwabapi.com/v1/oauth/token');
    curl_setopt($ch, CURLOPT_HTTPHEADER,
        array(
            'Authorization: Basic '.base64_encode($client_id.':'.$client_secret),
            'Content-Type: application/x-www-form-urlencoded'
        )
    );
    curl_setopt($ch, CURLOPT_POST, true);
    $postFields =
        'grant_type=authorization_code'
        .'&code='.str_replace('%40','@', $code)
        .'&redirect_uri='.urlencode($redirect_uri);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_VERBOSE, true);

    $response = curl_exec($ch);
    saveRequest($ch, $postFields);
    saveResponses($response);

    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $body = substr($response, $headerSize);
    file_put_contents('c:/wamp64/priv/schwab/accessTokens.json', $body);
    $body = json_decode($body);
    $body = '<pre>'.print_r($body,true).'</pre>';
    echo $body;
    echo '<hr>';
    echo '<h3>Request a quote</h3>';
    echo '<form action="'.$redirect_uri.'">Symbol: <input name="symbol" type="text"><input type="hidden" name="quote"><input type="submit"></form>';
}
function getQuote($symbol){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,
        'https://api.schwabapi.com/marketdata/v1/quotes?symbols='.$symbol.'&fields=quote&indicative=false'
    );
    $tokens = getTokens();
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'accept: application/json',
            'Authorization: Bearer '.$tokens['access_token'])
    );

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);

    $result = curl_exec($ch);
    saveRequest($ch);
    saveResponses($result);

    $header_len = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($result, 0, $header_len);
    $body = substr($result, $header_len);

    echo '<h2>Return code</h2>';
    echo curl_getinfo($ch, CURLINFO_HTTP_CODE);
    echo '<h2>Header</h2>';
    echo '<pre>'.print_r($header,true).'</pre>';
    echo '<h2>Body</h2>';
    echo '<pre>'.json_encode(json_decode($body),JSON_PRETTY_PRINT).'</pre>';
}
function makeTrade($account, $jsonData){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_URL,
        'https://api.schwabapi.com/trader/v1/accounts/'.$account.'/orders'
    );
    $tokens = getTokens();
    $token = $tokens['access_token'];
    curl_setopt($ch, CURLOPT_HTTPHEADER,
        array(
            'Authorization: Bearer '.$token,
            'Content-Type: application/json'
        )
    );

    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);

    $response = curl_exec($ch);
    saveRequest($ch, $jsonData);
    saveResponses($response);
    echo '<pre>'.htmlentities($response).'</pre>';
    echo ('<br>http response code 201 with empty body means trade was successful');
}
function makeDate($str){ // examples: 2024-03-29T00:00:00.000Z and 2024-04-28T23:59:59.000Z
    $date = date(DATE_ATOM, strtotime($str));
    $date = substr($date, 0, stripos($date, '+')).'.000Z';
    return $date;
}
function getAccountOrders($account){
    //AWAITING_PARENT_ORDER, AWAITING_CONDITION, AWAITING_STOP_CONDITION, AWAITING_MANUAL_REVIEW, ACCEPTED, AWAITING_UR_OUT, PENDING_ACTIVATION, QUEUED, WORKING, REJECTED, PENDING_CANCEL, CANCELED, PENDING_REPLACE, REPLACED, FILLED, EXPIRED, NEW, AWAITING_RELEASE_TIME, PENDING_ACKNOWLEDGEMENT, PENDING_RECALL, UNKNOWN
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,
        'https://api.schwabapi.com/trader/v1/accounts/'.$account.'/orders?'
        .'fromEnteredTime='.urlencode(makeDate('60 days ago'))
        .'&toEnteredTime='.urlencode(makeDate('now'))
    );
    $tokens = getTokens();
    curl_setopt($ch, CURLOPT_HTTPHEADER,
        array(
            'Authorization: Bearer '.$tokens['access_token'],
            'Content-Type: application/x-www-form-urlencoded',
            'accept: application/json'
        )
    );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);

    $response = curl_exec($ch);
    saveRequest($ch, '');
    saveResponses($response);
    $header_len = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    //echo 'header length: '.$header_len;
    $header = substr($response, 0, $header_len);
    $body = substr($response, $header_len);

    echo '<h2>Return code</h2>';
    echo curl_getinfo($ch, CURLINFO_HTTP_CODE);
    echo '<h2>Header</h2>';
    echo '<pre>'.print_r($header,true).'</pre>';
    echo '<h2>Body</h2>';
    echo '<pre>'.json_encode(json_decode($body),JSON_PRETTY_PRINT).'</pre>';
}
function getTokens(){
    $tokenFile = 'c:/wamp64/priv/schwab/accessTokens.json';
    if(file_exists($tokenFile)) {
        return json_decode(file_get_contents($tokenFile), $assoc = true);
    }
    else{
        die('tokens file does not exist: '.$tokenFile);
    }
}
function getAccountNumbers(){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.schwabapi.com/trader/v1/accounts/accountNumbers');
    $tokens = getTokens();
    curl_setopt($ch, CURLOPT_HTTPHEADER,
        array(
            'accept: application/json',
            'Authorization: Bearer '.$tokens['access_token']
        )
    );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
    $response = curl_exec($ch);
    saveRequest($ch, '');
    saveResponses($response);

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if($httpCode == 200) {
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $body = substr($response, $headerSize);
        file_put_contents('c:/wamp64/priv/schwab/accountNumbers.json', $body);
        $body = json_decode($body);
        $body = '<pre>' . print_r($body, true) . '</pre>';
        echo $body;
    }
    else {
        echo '<pre>' . htmlentities($response) . '</pre>';
    }
}

function refreshAccessToken($client_id, $client_secret){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.schwabapi.com/v1/oauth/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER,
        array(
            'Authorization: Basic '.base64_encode($client_id.':'.$client_secret),
            'Content-Type: application/x-www-form-urlencoded'
        )
    );
    $tokens = getTokens();
    $postFields =
        'grant_type=refresh_token'
        .'&refresh_token='.str_replace('%40','@', $tokens['refresh_token']);

    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);

    $response = curl_exec($ch);
    saveRequest($ch, $postFields);
    saveResponses($response);

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if($httpCode == 200) {
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $body = substr($response, $headerSize);
        file_put_contents('c:/wamp64/priv/schwab/accessTokens.json', $body);
        $body = json_decode($body);
        $body = '<pre>' . print_r($body, true) . '</pre>';
        echo $body;
    }
}
function autoRefreshOrRenewTokensAsNeeded($client_id, $client_secret, $redirect_uri){
    $filename = 'c:/wamp64/priv/schwab/accessTokens.json';
    if(file_exists($filename) && filesize($filename) > 0) {
        $secondsAge = time() - filemtime($filename);

        if ($secondsAge > 7 * 24 * 60 * 60) {
            //echo 'renew needed';
            authorizationCode($client_id, $redirect_uri); // log into schwab api account and grant access
        } else if ($secondsAge > 30 * 60) {
            //echo 'get new access_token using refresh_token';
            refreshAccessToken($client_id, $client_secret);
        } else if ($secondsAge < 30 * 60) {
            //echo 'access token is still valid for '.(30*60-$secondsAge).' seconds';
        }
    }
    else{
        authorizationCode($client_id, $redirect_uri);  // log into schwab api account and grant access
    }
}

function get_headers_from_curl_response($response){
    $headers = array();
    $header_text = substr($response, 0, strpos($response, "\r\n\r\n"));

    foreach (explode("\r\n", $header_text) as $i => $line){
        if ($i === 0) {
            $headers['http_code'] = $line;
        }
        else{
            list ($key, $value) = explode(': ', $line);
            $headers[$key] = $value;
        }
    }
    return $headers;
}