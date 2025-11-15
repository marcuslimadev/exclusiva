<?php

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://232fd9e473df.ngrok-free.app//exclusiva/api/imoveis.php',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'GET',
));

$response = curl_exec($curl);

// Check for cURL errors
if (curl_error($curl)) {
    echo 'cURL error: ' . curl_error($curl);
    curl_close($curl);
    exit;
}

curl_close($curl);

// Debug: Show raw response
echo "Raw response:\n";
echo $response . "\n\n";

// Decode the JSON response
$dados = json_decode($response, true);

// Check if JSON decoding was successful
if (json_last_error() !== JSON_ERROR_NONE) {
    echo 'JSON decode error: ' . json_last_error_msg();
    exit;
}

// Debug: Show decoded structure
echo "Decoded structure:\n";
print_r($dados);
echo "\n";

// Check if $dados is actually an array
if (!is_array($dados)) {
    echo "Response is not an array";
    exit;
}

// Iterate through the data
foreach ($dados as $index => $items) {
    echo "Processing item $index:\n";
    
    // Check if $items is an array before iterating
    if (is_array($items)) {
        foreach($items as $key => $detalhes) {
            echo "  Key: $key\n";
            echo "  Value: ";
            var_dump($detalhes);
            echo "\n";
        }
    } else {
        echo "  Direct value: ";
        var_dump($items);
        echo "\n";
    }
    echo "---\n";
}

?>