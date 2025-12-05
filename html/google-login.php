<?php
require_once 'vendor/autoload.php';
session_start();

$client = new Google_Client();
$client->setClientId('863391200215-fn8jatj2ivhc1ogma4dgdh0ltuoivohf.apps.googleusercontent.com');
$client->setClientSecret('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');
$client->setRedirectUri('https://hountybunter.click/callback.php');

// ✅ FIX: Profile + Email
$client->addScope(Google_Service_Oauth2::USERINFO_EMAIL);
$client->addScope(Google_Service_Oauth2::USERINFO_PROFILE);

$authUrl = $client->createAuthUrl();
header('Location: ' . $authUrl);
exit();
?>