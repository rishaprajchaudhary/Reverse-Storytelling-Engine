<?php
require_once 'config.php';

// Handle CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Get POST data
$postData = json_decode(file_get_contents('php://input'), true);

if (!isset($postData['prompt'])) {
    json_response(false, "Please provide a prompt");
}

$prompt = sanitize_input($postData['prompt']);

// Function to call the local Ollama API
function generateTextWithOllama($prompt) {
    // Local Ollama API endpoint (Flask server running on port 5000)
    $apiUrl = "http://localhost:5000/generate";
    
    // Prepare the request data
    $data = json_encode(array(
        'prompt' => $prompt
    ));
    
    // Initialize cURL session
    $ch = curl_init($apiUrl);
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data)
    ));
    
    // Execute cURL session and get the response
    $response = curl_exec($ch);
    
    // Check for cURL errors
    if(curl_errno($ch)) {
        // If Ollama API is not available, fall back to our simple generation
        curl_close($ch);
        return generateTextFallback($prompt);
    }
    
    // Close cURL session
    curl_close($ch);
    
    // Decode the JSON response
    $responseData = json_decode($response, true);
    
    // Check if the response is valid
    if (isset($responseData['success']) && $responseData['success'] === true) {
        return $responseData['data']['text'];
    } else {
        // If Ollama API returns an error, fall back to our simple generation
        return generateTextFallback($prompt);
    }
}

// Fallback function for text generation (the original implementation)
function generateTextFallback($prompt) {
    // For the demo, we'll use a simple text generation function
    $sentences = [
        "The journey began with an unexpected twist.",
        "In the heart of the ancient forest, secrets were waiting to be discovered.",
        "The protagonist faced their greatest fear and emerged transformed.",
        "Legends spoke of a hero who would come when the world needed them most.",
        "Against all odds, they persevered, finding strength they never knew they had.",
        "The truth was more complex than anyone had imagined.",
        "Under the light of a silver moon, destinies intertwined in ways no one anticipated.",
        "What started as a simple quest soon revealed a web of intrigue spanning centuries.",
        "The ancient prophecy began to unfold, revealing its mysteries one by one.",
        "In the darkest hour, hope emerged from an unexpected source."
    ];
    
    // Generate a story based on the prompt
    $story = "# " . ucfirst($prompt) . "\n\n";
    
    // Add an introduction
    $story .= $sentences[array_rand($sentences)] . " ";
    $story .= "This is the tale of " . $prompt . ".\n\n";
    
    // Generate several paragraphs
    for ($i = 0; $i < 5; $i++) {
        // Add 3-5 sentences per paragraph
        $paragraph = "";
        $sentenceCount = rand(3, 5);
        
        for ($j = 0; $j < $sentenceCount; $j++) {
            $paragraph .= $sentences[array_rand($sentences)] . " ";
        }
        
        $story .= $paragraph . "\n\n";
    }
    
    // Add a conclusion
    $story .= "And so, the story of " . $prompt . " became a legend that would be told for generations to come. ";
    $story .= $sentences[array_rand($sentences)];
    
    return $story;
}

// Try to generate the story with Ollama, with fallback to the simple method
$generatedText = generateTextWithOllama($prompt);

// Return the generated text
json_response(true, "Text generated successfully", ['text' => $generatedText]);
?> 