<?php

function authorizationCode($client_id, $redirect_uri){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,
        'https://api.schwabapi.com/v1/oauth/authorize?'
        .'client_id='.$client_id
        .'&redirect_uri='.urlencode($redirect_uri));

    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    saveResponses($response);
    $headers = get_headers_from_curl_response($response);

    if(isset($headers['location'])) { // log in to your schwab account and grant authorization
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
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER,
        array(
            'Authorization: Basic '.base64_encode($client_id.':'.$client_secret),
            'Content-Type: application/x-www-form-urlencoded'
        )
    );

    $postFields =
        'grant_type=authorization_code'
        .'&code='.str_replace('%40','@', $code)
        .'&redirect_uri='.urlencode($redirect_uri);

    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);

    $response = curl_exec($ch);
    saveResponses($response);

    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $body = substr($response, $headerSize);
    file_put_contents('accessTokens.json', $body);
    $body = json_decode($body);
    $body = '<pre>'.print_r($body,true).'</pre>';
    echo $body;
    echo '<hr>';
    echo '<h3>Make a quote</h3>';
    echo '<form action="'.$redirect_uri.'">Symbol: <input name="symbol" type="text"><input type="hidden" name="quote"><input type="submit"></form>';
}

function saveResponses($response){
    file_put_contents('responses.html', '<hr><pre>'.htmlentities($response).'</pre>', FILE_APPEND);
}

function getQuote($symbol, $token){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,
        'https://api.schwabapi.com/marketdata/v1/quotes?symbols='.$symbol.'&fields=quote&indicative=false'
    );
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'accept: application/json',
            'Authorization: Bearer '.$token)
    );

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    $result = curl_exec($ch);
    //echo 'result:'.$result.'<br>';

    $header_len = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    //echo 'header length: '.$header_len;
    $header = substr($result, 0, $header_len);
    $body = substr($result, $header_len);

    echo '<h2>Return code</h2>';
    echo curl_getinfo($ch, CURLINFO_HTTP_CODE);
    echo '<h2>Header</h2>';
    echo '<pre>'.print_r($header,true).'</pre>';
    echo '<h2>Body</h2>';
    echo '<pre>'.json_encode(json_decode($body),JSON_PRETTY_PRINT).'</pre>';
}

function refreshAccessToken($client_id, $client_secret, $refresh_token){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.schwabapi.com/v1/oauth/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER,
        array(
            'Authorization: Basic '.base64_encode($client_id.':'.$client_secret),
            'Content-Type: application/x-www-form-urlencoded'
        )
    );
    $postFields =
        'grant_type=refresh_token'
        .'&refresh_token='.str_replace('%40','@', $refresh_token);

    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);

    $response = curl_exec($ch);
    saveResponses($response);

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if($httpCode == 200) {
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $body = substr($response, $headerSize);
        file_put_contents('accessTokens.json', $body);
        $body = json_decode($body);
        $body = '<pre>' . print_r($body, true) . '</pre>';
        echo $body;
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

?>