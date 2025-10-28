<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Supabase configuration
define('SUPABASE_URL', 'https://lrohhxwrxnoibekhhtmz.supabase.co');
define('SUPABASE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Imxyb2hoeHdyeG5vaWJla2hodG16Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDY1MzIxODksImV4cCI6MjA2MjEwODE4OX0.kcbnK6JDtVJKRBHm10Knmd9SLmMAU67ZNFUFZg2_0Cs');

function getRemediesFromSupabase($disease) {
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
        if (!empty($data)) {
            return [
                'symptoms' => array_filter(explode("\n", trim($data[0]['symptoms']))),
                'remedies' => array_filter(explode("\n", trim($data[0]['remedies'])))
            ];
        }
    }
    
    return [
        'symptoms' => ['No information available'],
        'remedies' => ['Please consult an agronomist']
    ];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Only POST method allowed']);
    exit;
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'No image uploaded or upload error']);
    exit;
}

$uploadDir = 'uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$file = $_FILES['image'];
$fileName = time() . '_' . basename($file['name']);
$uploadPath = $uploadDir . $fileName;

if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
    
    $fullImagePath = __DIR__ . '/' . $uploadPath;
    $pythonScript = __DIR__ . '/predict.py';
    $command = "python \"$pythonScript\" \"$fullImagePath\" 2>&1";
    
    $output = shell_exec($command);
    
    if ($output) {
        $lines = explode("\n", trim($output));
        $jsonResult = null;

        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            if (!empty($trimmedLine) && (substr($trimmedLine, 0, 1) === '{')) {
                $jsonResult = $trimmedLine;
                break;
            }
        }
        
        if ($jsonResult) {
            $result = json_decode($jsonResult, true);
            
            if ($result && isset($result['prediction'])) {
                // Get remedies from Supabase
                $remediesData = getRemediesFromSupabase($result['prediction']);
                
                echo json_encode([
                    'prediction' => $result['prediction'],
                    'confidence' => $result['confidence'],
                    'symptoms' => $remediesData['symptoms'],
                    'remedies' => $remediesData['remedies'],
                    'image_url' => 'http://192.168.1.54/backend/' . $uploadPath
                ]);
            } else {
                echo json_encode([
                    'error' => isset($result['error']) ? $result['error'] : 'Unknown prediction error',
                    'debug' => $output
                ]);
            }
        } else {
            echo json_encode([
                'error' => 'No valid prediction result',
                'debug' => $output
            ]);
        }
    } else {
        echo json_encode(['error' => 'Failed to execute ML prediction']);
    }
    
} else {
    echo json_encode(['error' => 'Failed to save image']);
}
?>
