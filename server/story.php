<?php
require_once 'config.php';

// Handle CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'generate':
        generateStory();
        break;
    case 'get':
        getStory();
        break;
    case 'list':
        listStories();
        break;
    case 'save':
        saveStory();
        break;
    default:
        json_response(false, "Invalid action");
}

function generateStory() {
    // Get POST data
    $postData = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($postData['title']) || !isset($postData['user_id'])) {
        json_response(false, "Please provide title and user_id");
    }
    
    $title = sanitize_input($postData['title']);
    $user_id = sanitize_input($postData['user_id']);
    
    // In a real application, you would call an AI service like GPT-3/4 here
    // For this example, we'll generate a story using a simple template
    
    $genres = ['fantasy', 'sci-fi', 'mystery', 'romance', 'adventure'];
    $characters = ['wizard', 'detective', 'astronaut', 'time traveler', 'princess', 'dragon'];
    $places = ['ancient castle', 'distant planet', 'underwater city', 'enchanted forest', 'futuristic metropolis'];
    
    $genre = $genres[array_rand($genres)];
    $character = $characters[array_rand($characters)];
    $place = $places[array_rand($places)];
    
    $story = "# $title\n\n";
    $story .= "In a $genre world, a brave $character embarked on an epic journey to a $place. ";
    $story .= "The tale begins with an unexpected discovery that would change everything.\n\n";
    
    // Generate paragraphs using the title words for inspiration
    $words = explode(' ', $title);
    foreach ($words as $word) {
        if (strlen($word) > 3) {
            $story .= "The $word was unlike anything seen before in the realm. ";
            $story .= "It possessed qualities that defied explanation and sparked curiosity in all who beheld it. ";
            $story .= "As our $character investigated further, the mysteries only deepened.\n\n";
        }
    }
    
    $story .= "The journey through the $place revealed many challenges and obstacles. ";
    $story .= "Each test of courage and wit brought our hero closer to the ultimate truth. ";
    $story .= "What started as a simple quest had evolved into a saga that would be told for generations.\n\n";
    
    $story .= "In the end, after facing countless perils and making difficult choices, ";
    $story .= "our $character discovered that the real treasure was the wisdom gained along the way. ";
    $story .= "And so, the story of '$title' became legend in the annals of the $genre world.";
    
    // In a real implementation, this would be a call to an AI API
    // $prompt = "Generate a story based on the title: $title";
    // $story = callAIApi($prompt);
    
    global $conn;
    
    // Log the generation in the database
    $sql = "INSERT INTO stories (user_id, title, content) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iss", $user_id, $title, $story);
    
    if (mysqli_stmt_execute($stmt)) {
        $story_id = mysqli_insert_id($conn);
        
        // Log the generation
        $prompt = "Title: $title";
        $log_sql = "INSERT INTO generation_logs (user_id, story_id, prompt) VALUES (?, ?, ?)";
        $log_stmt = mysqli_prepare($conn, $log_sql);
        mysqli_stmt_bind_param($log_stmt, "iis", $user_id, $story_id, $prompt);
        mysqli_stmt_execute($log_stmt);
        
        json_response(true, "Story generated successfully", [
            'story_id' => $story_id,
            'title' => $title,
            'content' => $story
        ]);
    } else {
        json_response(false, "Error saving story: " . mysqli_error($conn));
    }
}

function getStory() {
    global $conn;
    
    if (!isset($_GET['story_id'])) {
        json_response(false, "Please provide story_id");
    }
    
    $story_id = sanitize_input($_GET['story_id']);
    
    $sql = "SELECT s.*, u.username FROM stories s 
            JOIN users u ON s.user_id = u.user_id 
            WHERE s.story_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $story_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 1) {
        $story = mysqli_fetch_assoc($result);
        
        // Get tags for the story
        $tags_sql = "SELECT t.name FROM tags t 
                    JOIN story_tags st ON t.tag_id = st.tag_id 
                    WHERE st.story_id = ?";
        $tags_stmt = mysqli_prepare($conn, $tags_sql);
        mysqli_stmt_bind_param($tags_stmt, "i", $story_id);
        mysqli_stmt_execute($tags_stmt);
        $tags_result = mysqli_stmt_get_result($tags_stmt);
        
        $tags = [];
        while ($tag = mysqli_fetch_assoc($tags_result)) {
            $tags[] = $tag['name'];
        }
        
        $story['tags'] = $tags;
        
        json_response(true, "Story fetched successfully", $story);
    } else {
        json_response(false, "Story not found");
    }
}

function listStories() {
    global $conn;
    
    $user_id = isset($_GET['user_id']) ? sanitize_input($_GET['user_id']) : null;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    
    // Base query
    $sql_base = "SELECT s.*, u.username FROM stories s 
                JOIN users u ON s.user_id = u.user_id";
    
    // Add conditions based on parameters
    $conditions = [];
    $params = [];
    $types = "";
    
    if ($user_id !== null) {
        $conditions[] = "s.user_id = ?";
        $params[] = $user_id;
        $types .= "i";
    }
    
    // Combine conditions if any
    if (!empty($conditions)) {
        $sql_base .= " WHERE " . implode(" AND ", $conditions);
    }
    
    // Add order and limit
    $sql = $sql_base . " ORDER BY s.created_at DESC LIMIT ?, ?";
    $params[] = $offset;
    $params[] = $limit;
    $types .= "ii";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    // Bind parameters dynamically
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $stories = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Truncate content for list view
        $row['content'] = substr($row['content'], 0, 150) . '...';
        $stories[] = $row;
    }
    
    json_response(true, "Stories fetched successfully", $stories);
}

function saveStory() {
    global $conn;
    
    // Get POST data
    $postData = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($postData['user_id']) || !isset($postData['title']) || !isset($postData['content'])) {
        json_response(false, "Please provide user_id, title and content");
    }
    
    $user_id = sanitize_input($postData['user_id']);
    $title = sanitize_input($postData['title']);
    $content = $postData['content']; // Don't sanitize to preserve formatting
    $story_id = isset($postData['story_id']) ? sanitize_input($postData['story_id']) : null;
    $tags = isset($postData['tags']) ? $postData['tags'] : [];
    
    if ($story_id) {
        // Update existing story
        $sql = "UPDATE stories SET title = ?, content = ?, updated_at = CURRENT_TIMESTAMP WHERE story_id = ? AND user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssii", $title, $content, $story_id, $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Update tags if provided
            if (!empty($tags)) {
                // First delete existing tags
                $delete_tags_sql = "DELETE FROM story_tags WHERE story_id = ?";
                $delete_stmt = mysqli_prepare($conn, $delete_tags_sql);
                mysqli_stmt_bind_param($delete_stmt, "i", $story_id);
                mysqli_stmt_execute($delete_stmt);
                
                // Then add new tags
                foreach ($tags as $tag_name) {
                    addTagToStory($story_id, $tag_name);
                }
            }
            
            json_response(true, "Story updated successfully", ['story_id' => $story_id]);
        } else {
            json_response(false, "Error updating story: " . mysqli_error($conn));
        }
    } else {
        // Insert new story
        $sql = "INSERT INTO stories (user_id, title, content) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iss", $user_id, $title, $content);
        
        if (mysqli_stmt_execute($stmt)) {
            $story_id = mysqli_insert_id($conn);
            
            // Add tags if provided
            if (!empty($tags)) {
                foreach ($tags as $tag_name) {
                    addTagToStory($story_id, $tag_name);
                }
            }
            
            json_response(true, "Story saved successfully", ['story_id' => $story_id]);
        } else {
            json_response(false, "Error saving story: " . mysqli_error($conn));
        }
    }
}

function addTagToStory($story_id, $tag_name) {
    global $conn;
    
    // First check if tag exists
    $tag_sql = "SELECT tag_id FROM tags WHERE name = ?";
    $tag_stmt = mysqli_prepare($conn, $tag_sql);
    mysqli_stmt_bind_param($tag_stmt, "s", $tag_name);
    mysqli_stmt_execute($tag_stmt);
    $tag_result = mysqli_stmt_get_result($tag_stmt);
    
    if (mysqli_num_rows($tag_result) === 0) {
        // Tag doesn't exist, create it
        $create_tag_sql = "INSERT INTO tags (name) VALUES (?)";
        $create_stmt = mysqli_prepare($conn, $create_tag_sql);
        mysqli_stmt_bind_param($create_stmt, "s", $tag_name);
        mysqli_stmt_execute($create_stmt);
        $tag_id = mysqli_insert_id($conn);
    } else {
        $tag = mysqli_fetch_assoc($tag_result);
        $tag_id = $tag['tag_id'];
    }
    
    // Link tag to story
    $link_sql = "INSERT INTO story_tags (story_id, tag_id) VALUES (?, ?)";
    $link_stmt = mysqli_prepare($conn, $link_sql);
    mysqli_stmt_bind_param($link_stmt, "ii", $story_id, $tag_id);
    mysqli_stmt_execute($link_stmt);
}
?> 