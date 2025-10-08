<?php
// debug_sheets.php - Fixed version
session_start();
require_once 'includes/db_connection.php';

echo "<h2>Google Sheets Debug Test</h2>";

try {
    $manager = getDataManager();
    
    // Test database connection first
    echo "<h3>📊 Database Connection Test</h3>";
    try {
        $pdo = getPDO();
        echo "<p style='color: green;'>✅ Database connected successfully</p>";
        
        // Test a simple query
        $stmt = $pdo->query("SELECT COUNT(*) as eval_count FROM evaluations");
        $result = $stmt->fetch();
        echo "<p>Total evaluations in database: <strong>" . $result['eval_count'] . "</strong></p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
    }

    // Test if we can access the Google Sheets service
    echo "<h3>🔗 Google Sheets Connection Test</h3>";
    
    $reflection = new ReflectionClass($manager);
    $sheetsProperty = $reflection->getProperty('sheetsService');
    $sheetsProperty->setAccessible(true);
    $sheetsService = $sheetsProperty->getValue($manager);
    
    $sheetIdProperty = $reflection->getProperty('sheetId');
    $sheetIdProperty->setAccessible(true);
    $sheetId = $sheetIdProperty->getValue($manager);
    
    echo "<p><strong>Sheet ID:</strong> " . htmlspecialchars($sheetId ?: 'NOT SET') . "</p>";
    echo "<p><strong>Sheets Service:</strong> " . ($sheetsService ? 'Connected' : 'NOT CONNECTED') . "</p>";
    
    if ($sheetsService && $sheetId) {
        echo "<h3>📖 Reading Students Sheet:</h3>";
        
        try {
            $range = "Students!A:G";
            $response = $sheetsService->spreadsheets_values->get($sheetId, $range);
            $rows = $response->getValues();
            
            if (empty($rows)) {
                echo "<p style='color: red;'>❌ No data found in Students sheet</p>";
            } else {
                echo "<table border='1' style='border-collapse: collapse;'>";
                echo "<tr><th>Row</th><th>A</th><th>B</th><th>C</th><th>D</th><th>E</th><th>F</th><th>G</th></tr>";
                
                foreach ($rows as $index => $row) {
                    echo "<tr>";
                    echo "<td>" . $index . "</td>";
                    for ($i = 0; $i < 7; $i++) {
                        $value = isset($row[$i]) ? htmlspecialchars($row[$i]) : '<em>empty</em>';
                        echo "<td>" . $value . "</td>";
                    }
                    echo "</tr>";
                    
                    if ($index >= 10) { // Show only first 10 rows
                        echo "<tr><td colspan='8'><em>... (showing first 10 rows only)</em></td></tr>";
                        break;
                    }
                }
                echo "</table>";
                
                // Test a specific login
                echo "<h3>🔐 Testing Student Login</h3>";
                if (count($rows) > 2 && isset($rows[2])) {
                    $testRow = $rows[2]; // Row 2 is the first actual student (after headers)
                    $testUsername = $testRow[5] ?? '';
                    $testPassword = $testRow[6] ?? '';
                    
                    echo "<p><strong>Student ID:</strong> " . htmlspecialchars($testRow[0] ?? '') . "</p>";
                    echo "<p><strong>Name:</strong> " . htmlspecialchars(($testRow[2] ?? '') . ' ' . ($testRow[1] ?? '')) . "</p>";
                    echo "<p><strong>Section:</strong> " . htmlspecialchars($testRow[3] ?? '') . "</p>";
                    echo "<p><strong>Program:</strong> " . htmlspecialchars($testRow[4] ?? '') . "</p>";
                    echo "<p><strong>Test Username:</strong> " . htmlspecialchars($testUsername) . "</p>";
                    echo "<p><strong>Test Password:</strong> " . htmlspecialchars($testPassword) . "</p>";
                    
                    // Try to authenticate
                    if (!empty($testUsername) && !empty($testPassword)) {
                        $result = $manager->authenticateUser($testUsername, $testPassword);
                        echo "<p><strong>Authentication Result:</strong> " . ($result ? 'SUCCESS' : 'FAILED') . "</p>";
                        
                        if ($result) {
                            echo "<pre>" . print_r($result, true) . "</pre>";
                        }
                    } else {
                        echo "<p style='color: orange;'>⚠️ No username/password found in row 2</p>";
                    }
                } else {
                    echo "<p style='color: orange;'>⚠️ Not enough rows to test login</p>";
                }
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'><strong>Google Sheets Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Cannot connect to Google Sheets. Check your environment variables:</p>";
        echo "<ul>";
        echo "<li>GOOGLE_SHEETS_ID: " . (getenv('GOOGLE_SHEETS_ID') ? 'SET' : 'NOT SET') . "</li>";
        echo "<li>GOOGLE_CREDENTIALS_JSON: " . (getenv('GOOGLE_CREDENTIALS_JSON') ? 'SET' : 'NOT SET') . "</li>";
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Fatal Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Trace:</strong></p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<br><a href='index.php'>← Back to Login</a>";
?>
