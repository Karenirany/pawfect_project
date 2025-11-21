<?php
// includes/chatbot.php
session_start();

// Google Gemini API Configuration
define('GEMINI_API_KEY', 'AIzaSyANMipxDdPrYI8TTcVrjr3T8_PhfiiLmxo'); // ← REPLACE THIS
define('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContents');

// Bot instructions
$BOT_INSTRUCTIONS = <<<PROMPT
You are Pawsome Assistant, a friendly AI for an educational dog adoption website.

IMPORTANT RULES:
1. This is an EDUCATIONAL DEMONSTRATION only - no real adoptions occur here
2. Always mention the educational purpose when discussing adoption processes
3. Be warm, helpful, and use dog-related emojis occasionally (🐾, 🐶, 🏠, ❤️)
4. Provide accurate adoption information but clarify it's for learning
5. Direct users to website features when relevant
6. Keep responses concise but informative (2-4 sentences)
7. Be encouraging and supportive

WEBSITE FEATURES TO MENTION:
- Browse Dogs page: View demo dog profiles with filters
- Adoption Request: Practice application process
- Registration: Learn account creation flows

RESPONSE STYLE:
- Friendly and educational tone
- Use simple, clear language
- Include relevant emojis occasionally
- Always reinforce educational context naturally

EDUCATIONAL CONTEXT PHRASES:
- "This educational demonstration shows..."
- "On our learning platform..."
- "This helps you practice for real adoption..."
- "Remember, this is for educational purposes..."

Respond to the user's message following these rules:
PROMPT;

function call_gemini_api($user_message) {
    global $BOT_INSTRUCTIONS;
    
    $api_key = GEMINI_API_KEY;
    $url = GEMINI_API_URL . "?key=" . $api_key;
    
    $full_prompt = $BOT_INSTRUCTIONS . "\n\nUser: " . $user_message . "\nAssistant:";
    
    $data = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $full_prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'maxOutputTokens' => 500,
            'temperature' => 0.7
        ]
    ];
    
    $json_data = json_encode($data);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local development
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    // Log for debugging
    error_log("Gemini API Call - HTTP Code: " . $http_code);
    if ($curl_error) {
        error_log("CURL Error: " . $curl_error);
    }
    
    if ($http_code === 200 && $response) {
        $result = json_decode($response, true);
        
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            return trim($result['candidates'][0]['content']['parts'][0]['text']);
        } else {
            error_log("Gemini API Response Error: " . print_r($result, true));
        }
    }
    
    return false;
}

function get_fallback_response($user_message) {
    $lower_message = strtolower($user_message);
    
    $responses = [
        'hello' => "Hello! I'm Pawsome Assistant 🐾 I'm here to help you learn about dog adoption processes on our educational website!",
        'hi' => "Hi there! Welcome to our educational adoption platform. What would you like to know about dog adoption? 🐶",
        'adoption' => "The adoption process typically involves: 1) Research breeds 2) Browse dogs 3) Submit application 4) Meet the dog 5) Home check 6) Finalize adoption. Our educational site demonstrates the digital aspects! 📝",
        'available' => "Check our 'Browse Dogs' page to see demo profiles! You can filter by size, breed, and age to practice searching - just like real adoption sites! 🔍",
        'cost' => "Real adoption fees range $50-$300 to cover vet care, but our educational site has no real costs. We're here to teach you the process! 💰",
        'requirements' => "Real shelters typically require: being 21+, stable housing, references, and ability to provide care. Our demo shows common application requirements! 📋",
        'breed' => "Different breeds have different needs! Browse our demo dogs to learn about various breeds and their characteristics. This helps you understand what to look for in real adoptions! 🐕",
        'process' => "The adoption journey involves: application → screening → meeting → adoption. Our educational platform helps you understand each digital step! 💻",
        'help' => "I can help you learn about: adoption processes, dog breeds, website navigation, and what to expect from real adoption platforms! Ask me anything! 🐾",
        'educational' => "Yes! This is an educational website created to demonstrate full-stack web development and teach adoption processes. No real adoptions occur here - it's all for learning! 👨‍💻",
        'real' => "This is an educational demonstration only. The dogs shown are for learning purposes, and no actual adoptions take place through this platform.",
        'thank' => "You're welcome! I'm glad I could help you learn about dog adoption processes. Feel free to ask more questions! ❤️",
        'default' => "I'd love to help you learn about dog adoption! This educational platform demonstrates adoption processes. Ask me about adoption steps, dog browsing, requirements, or how to use our website features! 🐾"
    ];
    
    // Check for keyword matches
    foreach ($responses as $key => $response) {
        if ($key !== 'default' && strpos($lower_message, $key) !== false) {
            return $response;
        }
    }
    
    return $responses['default'];
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'send_message') {
        $user_message = trim($_POST['message'] ?? '');
        
        if (!empty($user_message)) {
            // Initialize chat history if not exists
            if (!isset($_SESSION['chat_history'])) {
                $_SESSION['chat_history'] = [];
            }
            
            // Add user message to history
            $_SESSION['chat_history'][] = [
                'sender' => 'user',
                'message' => $user_message,
                'time' => date('H:i:s')
            ];
            
            // Try to get AI response from Gemini
            $bot_response = call_gemini_api($user_message);
            
            // If AI fails, use fallback
            if ($bot_response === false) {
                $bot_response = get_fallback_response($user_message);
                error_log("Using fallback response for: " . $user_message);
            }
            
            // Add bot response to history
            $_SESSION['chat_history'][] = [
                'sender' => 'bot',
                'message' => $bot_response,
                'time' => date('H:i:s')
            ];
            
            // Keep history manageable (last 20 messages)
            if (count($_SESSION['chat_history']) > 20) {
                $_SESSION['chat_history'] = array_slice($_SESSION['chat_history'], -20);
            }
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'response' => $bot_response]);
            exit;
        }
    }
    
    elseif ($action === 'clear_chat') {
        $_SESSION['chat_history'] = [];
        echo json_encode(['success' => true]);
        exit;
    }
    
    elseif ($action === 'get_history') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'history' => $_SESSION['chat_history'] ?? []
        ]);
        exit;
    }
    
    elseif ($action === 'test_api') {
        $test_response = call_gemini_api("Hello, are you working?");
        if ($test_response !== false) {
            echo json_encode(['success' => true, 'message' => 'API is working!', 'response' => $test_response]);
        } else {
            echo json_encode(['success' => false, 'message' => 'API connection failed - using fallback mode']);
        }
        exit;
    }
}

// If direct access, show info
if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($_POST)) {
    header('Content-Type: application/json');
    echo json_encode([
        'service' => 'Pawsome Chatbot API',
        'status' => 'running',
        'endpoints' => [
            'POST /action=send_message' => 'Send chat message',
            'POST /action=clear_chat' => 'Clear chat history',
            'POST /action=get_history' => 'Get chat history',
            'POST /action=test_api' => 'Test API connection'
        ]
    ]);
    exit;
}
?>