<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Supabase configuration
define('SUPABASE_URL', 'https://lrohhxwrxnoibekhhtmz.supabase.co');
define('SUPABASE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Imxyb2hoeHdyeG5vaWJla2hodG16Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDY1MzIxODksImV4cCI6MjA2MjEwODE4OX0.kcbnK6JDtVJKRBHm10Knmd9SLmMAU67ZNFUFZg2_0Cs');

function getFromSupabase($disease) {
    $url = SUPABASE_URL . '/rest/v1/remedies?disease=eq.' . urlencode($disease);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . SUPABASE_KEY,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        return !empty($data) ? $data[0] : null;
    }
    
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['disease'])) {
    $disease = $_GET['disease'];
    $result = getFromSupabase($disease);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'data' => [
                'disease' => $result['disease'],
                'symptoms' => explode("\n", $result['symptoms']),
                'remedies' => explode("\n", $result['remedies'])
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Disease information not found'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request'
    ]);
}
?>
