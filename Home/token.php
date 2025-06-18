<?php
function getZoomToken() {
    $clientId = '4y5ckqpJQ1WvJAmk3x6PvQ';
    $clientSecret = '8eH7szslJoGeBbyRULvEm6Bx7eE630jB';
    $accountId = '89NOV9jAT-SH7wJmjvsptg';
    $ch = curl_init('https://zoom.us/oauth/token?grant_type=account_credentials&account_id=' . $accountId);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . base64_encode($clientId . ':' . $clientSecret)
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $res['access_token'];
}
?>