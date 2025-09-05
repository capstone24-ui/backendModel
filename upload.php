<?php

    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST');
    header('Access-Control-Allow-Headers: Content-Type');

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
                    echo json_encode([
                        'prediction' => $result['prediction'],
                        'confidence' => $result['confidence'],
                        'symptoms' => isset($result['symptoms']) ? $result['symptoms'] : [],
                        'remedies' => isset($result['remedies']) ? $result['remedies'] : [],
                        'image_url' => 'http://192.168.254.192/backend/' . $uploadPath
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