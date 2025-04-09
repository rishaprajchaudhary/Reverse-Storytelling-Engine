<?php
// Display all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Ollama Integration Test</h1>";

// Function to call the local Ollama API
function callOllamaAPI($prompt) {
    $apiUrl = "http://localhost:5000/generate";
    
    $data = json_encode(array(
        'prompt' => $prompt
    ));
    
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data)
    ));
    
    echo "<p>Sending request to Ollama API...</p>";
    $response = curl_exec($ch);
    
    if(curl_errno($ch)) {
        echo "<p style='color: red;'>Error: " . curl_error($ch) . "</p>";
        curl_close($ch);
        return null;
    }
    
    curl_close($ch);
    
    return json_decode($response, true);
}

// First, check if the Ollama API server is running
$ch = curl_init("http://localhost:5000/status");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);

if(curl_errno($ch)) {
    echo "<p style='color: red;'>Error: Could not connect to Ollama API server.</p>";
    echo "<p>Make sure the Ollama API server is running by executing <code>server/start_ollama_api.ps1</code>.</p>";
    curl_close($ch);
    exit;
}

curl_close($ch);
$status = json_decode($response, true);

echo "<pre>";
echo "Ollama API Status: ";
print_r($status);
echo "</pre>";

// If the API server is running, test with a simple prompt
if ($status && isset($status['success']) && $status['success']) {
    echo "<h2>Testing Story Generation</h2>";
    echo "<form method='post' action=''>";
    echo "<label for='prompt'>Enter a story title:</label>";
    echo "<input type='text' name='prompt' id='prompt' value='The Magic Forest' style='margin: 10px; padding: 5px;'>";
    echo "<input type='submit' value='Generate Story' style='margin: 10px; padding: 5px;'>";
    echo "</form>";
    
    if (isset($_POST['prompt'])) {
        $prompt = $_POST['prompt'];
        echo "<p>Generating story for: <strong>" . htmlspecialchars($prompt) . "</strong></p>";
        
        $result = callOllamaAPI($prompt);
        
        if ($result && isset($result['success']) && $result['success']) {
            echo "<h3>Generated Story:</h3>";
            echo "<p><strong>Generated at: " . date('Y-m-d H:i:s') . "</strong></p>";
            $text = nl2br(htmlspecialchars($result['data']['text']));
            // Convert Markdown # to actual heading
            $text = preg_replace('/^# (.+)$/m', '<h2>$1</h2>', $text);
            echo "<div style='background: #f5f5f5; padding: 20px; border-radius: 5px;'>";
            echo $text;
            echo "</div>";
        } else {
            echo "<p style='color: red;'>Error generating story:</p>";
            echo "<pre>";
            print_r($result);
            echo "</pre>";
        }
    }
} else {
    echo "<p style='color: red;'>Ollama API server is not running correctly.</p>";
}
?> 