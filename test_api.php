<?php
// test_api.php - UPDATED
session_start();

// Your Gemini API Key
$api_key = "AIzaSyDsVVyC0cqb6ONLQDhiZ75eibrsn1GTLiM"; // Your actual key
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=" . $api_key; // ← Note: generateContent (singular)

$data = [
    'contents' => [
        [
            'parts' => [
                ['text' => "Say 'Hello World' in a fun way!"]
            ]
        ]
    ],
    'generationConfig' => [
        'maxOutputTokens' => 100
    ]
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
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

echo "<h2>API Key Test Results</h2>";
echo "<p><strong>API Key:</strong> " . substr($api_key, 0, 10) . "..." . "</p>";
echo "<p><strong>URL Used:</strong> " . $url . "</p>";
echo "<p><strong>HTTP Status Code:</strong> " . $http_code . "</p>";

if ($http_code === 200) {
    $result = json_decode($response, true);
    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        $ai_response = $result['candidates'][0]['content']['parts'][0]['text'];
        echo "<p style='color: green;'><strong>✅ SUCCESS! API Key is working!</strong></p>";
        echo "<p><strong>AI Response:</strong> " . htmlspecialchars($ai_response) . "</p>";
    } else {
        echo "<p style='color: red;'><strong>❌ ERROR: No response text found</strong></p>";
        echo "<pre>" . print_r($result, true) . "</pre>";
    }
} else {
    echo "<p style='color: red;'><strong>❌ ERROR: API call failed with code $http_code</strong></p>";
    echo "<p><strong>Response:</strong> " . htmlspecialchars($response) . "</p>";
    echo "<p><strong>CURL Error:</strong> " . $curl_error . "</p>";
    
    // More detailed error analysis
    if ($http_code === 404) {
        echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px;'>";
        echo "<h3>🔧 404 Fix Required:</h3>";
        echo "<p><strong>Possible causes:</strong></p>";
        echo "<ul>";
        echo "<li>❌ Gemini API not enabled in Google Cloud</li>";
        echo "<li>❌ Incorrect API endpoint URL</li>";
        echo "<li>❌ Project not properly set up</li>";
        echo "</ul>";
        echo "<p><strong>Solution:</strong> Follow the steps below to enable the API</p>";
        echo "</div>";
    }
}
?>