<?php
$schwabPrivateFolder = 'c:/wamp64/priv/schwab/'; // keep this folder outside your htdocs & www
$httpSubDirPath = '/schwab/'; //http://yourdomain.com/schwab/

$privateFiles = array(
    'appParameters.json', // create this file using your app client_id, client_secret, and redirect_uri.  Save to private folder.
    'accessTokens.json', // generated
    'accountNumbers.json', // generated
    'transactions-log.html', // generated
);
function getFilePath($file){
    global $schwabPrivateFolder;
    return $schwabPrivateFolder.$file;
}
function loadFile($file){
    return json_decode(file_get_contents(getFilePath($file)),$associative=true);
}
function getAppParam($param){
    return loadFile('appParameters.json')[$param];
}
function getHttpFolders(){
    global $httpSubDirPath;
    return $httpSubDirPath;
}
function getDatetimeMillis(){
    $now = DateTime::createFromFormat('U.u', microtime(true));
    return $now->format("F d, Y h:i:s.u A");
}
function saveRequests($ch, $postBody=''){
    $header = curl_getinfo($ch, CURLINFO_HEADER_OUT);
    $output =
    '<u>Header</u>'
    .'<pre>'.htmlentities(print_r($header,true)).'</pre>'
    .'<u>Body</u>'
    .'<pre>'.htmlentities($postBody).'</pre>';

    file_put_contents(getFilePath('transactions-log.html'), '<hr><h3>Request</h3><h4>'.getDatetimeMillis().'</h4>'.$output, FILE_APPEND);
}
function saveResponses($ch, $response){
    $header_len = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $header_len);
    $body = substr($response, $header_len);

    $output =
        '<u>Return code</u><br>'
        .curl_getinfo($ch, CURLINFO_HTTP_CODE).'<br><br>'
        .'<u>Header</u>'
        .'<pre>'.htmlentities(print_r($header,true)).'</pre>'
        .'<u>Body</u>'
        .'<pre>'.htmlentities(json_encode(json_decode($body),JSON_PRETTY_PRINT)).'</pre>';

    global $privateFiles;
    file_put_contents(getFilePath('transactions-log.html'), '<hr><h3>Response</h3><h4>'.getDatetimeMillis().'</h4>'.$output, FILE_APPEND);
}
function curl($url, $headers=[], $method='GET', $postBody=''){
    $ch = curl_init('https://api.schwabapi.com'.$url);
    curl_setopt_array($ch,
        array(
            CURLOPT_HEADER => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_VERBOSE => 1,
            CURLINFO_HEADER_OUT => 1
        )
    );
    if(isset($headers)){
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    if($method == 'POST'){
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postBody);
    }

    $response = curl_exec($ch);
    saveRequests($ch, $postBody);
    saveResponses($ch, $response);

    return $response;
}
function getHttpResponseBody($response){
    return substr($response, stripos($response, "\r\n\r\n")+4);
}
function prettyPrintResponseBody($response){
    echo '<pre>'.print_r(json_encode(json_decode(getHttpResponseBody($response)),JSON_PRETTY_PRINT), true).'</pre>';
}
function getHeadersFromCurlResponse($response){
    $headers = array();
    $headerText = substr($response, 0, strpos($response, "\r\n\r\n"));

    foreach (explode("\r\n", $headerText) as $i => $line){
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
function authorizationCode(){
    $response = curl(
        '/v1/oauth/authorize?'
        .'client_id='.getAppParam('client_id')
        .'&redirect_uri='.urlencode(getAppParam('redirect_uri'))
    );

    $headers = getHeadersFromCurlResponse($response);

    if(isset($headers['location'])) { // log in to your schwab api account and grant authorization
        header('Location: ' . $headers['location']);
        exit();
    }
    else {
        echo '<pre>' . htmlentities($response) . '</pre>';
    }
}
function createAccessToken($code){
    $response = curl(
        $url='/v1/oauth/token',
        $header=array(
            'Authorization: Basic '.base64_encode(getAppParam('client_id').':'.getAppParam('client_secret')),
            'Content-Type: application/x-www-form-urlencoded'
        ),
        $method='POST',
        $postFields='grant_type=authorization_code'
            .'&code='.str_replace('%40','@', $code)
            .'&redirect_uri='.urlencode(getAppParam('redirect_uri'))
    );

    $body = getHttpResponseBody($response);
    file_put_contents(getFilePath('accessTokens.json'), $body);
    prettyPrintResponseBody($response);
    echo '<hr><h3>Access Tokens Created</h3><a href="'.getHttpFolders().'">Return Home</a>';
}
function getToken($name){
    $tokenFile = getFilePath('accessTokens.json');
    if(file_exists($tokenFile)) {
        $tokens = json_decode(file_get_contents($tokenFile),true);
        //print_r($tokens);
        return $tokens[$name];
    }
    else{
        die('tokens file does not exist: '.$tokenFile);
    }
}
function refreshAccessToken(){
    $filePath = getFilePath('accessTokens.json');
    if(time()-filemtime($filePath) < 30*60){
        return;
    }
    $response = curl(
        $url='/v1/oauth/token',
        $headers=array(
            'Authorization: Basic '.base64_encode(getAppParam('client_id').':'.getAppParam('client_secret')),
            'Content-Type: application/x-www-form-urlencoded'
        ),
        $method='POST',
        $postFields='grant_type=refresh_token'
            .'&refresh_token='.str_replace('%40','@', getToken('refresh_token'))
    );
    $body = getHttpResponseBody($response);
    file_put_contents($filePath, $body);
    prettyPrintResponseBody($response);
    return $body;
}
function autoRefreshRenewTokens(){
    $filePath = getFilePath('accessTokens.json');
    if (!file_exists($filePath) || filesize($filePath) == 0) {
        authorizationCode(getAppParam('client_id'), getAppParam('redirect_uri'));  // log into schwab api account and grant access
    }
    else if(file_exists($filePath) && filesize($filePath) > 0){
        $assocArray = json_decode(file_get_contents($filePath),true);
        if(!array_key_exists('refresh_token', $assocArray)){
            unlink($filePath);
        }
    }
    else {
        $secondsAge = time() - filemtime($filePath);
        if ($secondsAge > 7 * 24 * 60 * 60) { // 7 days
            //echo 'renew needed, log into schwab and grant access';
            authorizationCode(); // log into schwab api account and grant access
        } else if ($secondsAge > 30 * 60) { // 30 minutes
            refreshAccessToken();
            //echo '<hr>got new access_token using refresh_token';
        } else if ($secondsAge < 30 * 60) {
            //echo 'access token is still valid for '.(30*60-$secondsAge).' seconds';
        }
    }
}
function saveAccountNumbers(){
    $response = curl(
        $url='/trader/v1/accounts/accountNumbers',
        $headers=array(
            'accept: application/json',
            'Authorization: Bearer '.getToken('access_token')
        )
    );
    $body = getHttpResponseBody($response);
    file_put_contents(getFilePath('accountNumbers.json'), $body);
    //prettyPrintResponseBody($response);
    return $body;
}
function getAccountBalance(){
    $hashAccountNumber = loadFile('accountNumbers.json')[0]['hashValue'];

    $response = curl(
        $url='/trader/v1/accounts/'.$hashAccountNumber.'?'.'fields=positions',
        $headers=array(
            'accept: application/json',
            'Authorization: Bearer '.getToken('access_token')
        )
    );
    prettyPrintResponseBody($response);
    return getHttpResponseBody($response);
}
function getQuote($symbol){
    $response = curl(
        $url='/marketdata/v1/quotes?fields=quote&symbols='.$symbol,
        $headers=array(
            'accept: application/json',
            'Authorization: Bearer '.getToken('access_token')
        )
    );
    prettyPrintResponseBody($response);
    return getHttpResponseBody($response);
}
function makeTrade($jsonData){
    $hashAccountNumber = loadFile('accountNumbers.json')[0]['hashValue'];
    $response = curl(
        $url='/trader/v1/accounts/'.$hashAccountNumber.'/orders',
        $headers=array(
            'Authorization: Bearer '.getToken('access_token'),
            'Content-Type: application/json'
        ),
        $method='POST',
        $postFields=$jsonData
    );

    $body = getHttpResponseBody($response);
    prettyPrintResponseBody($response);
    echo ('<br>http response code 201 with empty body means trade was successful');
    return $body;
}
function makeDate($str){ // examples: 2024-03-29T00:00:00.000Z and 2024-04-28T23:59:59.000Z
    $date = date(DATE_ATOM, strtotime($str));
    $date = substr($date, 0,-6).'.000Z';
    return $date;
}
function getAccountOrders(){
    $hashAccountNumber = loadFile('accountNumbers.json')[0]['hashValue'];
    $response = curl(
        $url = '/trader/v1/accounts/'.$hashAccountNumber.'/orders?'
            .'fromEnteredTime='.urlencode(makeDate('60 days ago'))
            .'&toEnteredTime='.urlencode(makeDate('now')),
            //.'&status=PENDING_ACTIVATION'
            //.'&status=NEW',
        $headers=array(
            'Authorization: Bearer '.getToken('access_token'),
            'Content-Type: application/x-www-form-urlencoded',
            'accept: application/json'
        )
    );
    $body = getHttpResponseBody($response);
    prettyPrintResponseBody($response);
    return $body;
}
