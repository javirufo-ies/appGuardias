<?php
session_start();
require_once 'includes/google_client.php';
require_once 'includes/db.php';

$client = getGoogleClient();

$token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

$accessToken = $token['access_token'];
$refreshToken = $token['refresh_token'] ?? null;
$expiresAt = date('Y-m-d H:i:s', time() + $token['expires_in']);

$db->query("DELETE FROM google_tokens");

$stmt = $db->prepare("
    INSERT INTO google_tokens (access_token, refresh_token, expires_at)
    VALUES (?, ?, ?)
");
$stmt->bind_param("sss", $accessToken, $refreshToken, $expiresAt);
$stmt->execute();

header("Location: calendario.php");
exit;
?>