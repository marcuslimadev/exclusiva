<?php
/**
 * Script para disparar sync remoto no Render
 */

echo "🔄 Disparando sync no backend Render...\n\n";

$url = 'https://exclusiva-backend.onrender.com/debug/trigger-sync';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5 minutos
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    print_r($data);
} else {
    echo "❌ Erro HTTP $httpCode\n";
    echo $response . "\n";
}
