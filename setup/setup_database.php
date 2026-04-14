<?php
// Database setup script
$host = "localhost";
$username = "root";
$password = "";
$dbname = "dilan_airlines";

try {
    // Connect to MySQL without database
    $conn = new PDO("mysql:host=$host", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if not exists
    $conn->exec("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database '$dbname' created successfully or already exists.<br>";
    
    // Select the database
    $conn->exec("USE $dbname");
    
    // Read and execute SQL file
    require_once __DIR__ . '/config/database.php';
    $sqlFile = __DIR__ . '/database.sql';
    if (file_exists($sqlFile)) {
        $sql = file_get_contents($sqlFile);
        
        // Remove comments and split into statements
        $sql = preg_replace("/--.*\n/", "", $sql);
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $conn->exec($statement);
            }
        }
        
        echo "Database schema imported successfully.<br>";
    } else {
        echo "Error: database.sql file not found.<br>";
    }
    
    echo "<h3>Setup completed successfully!</h3>";
    echo "<p>You can now access the application at: <a href='index.php'>index.php</a></p>";
    echo "<p><strong>Default Login:</strong></p>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> admin / admin123</li>";
    echo "<li><strong>User:</strong> user / user123</li>";
    echo "</ul>";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "<br>";
    echo "<p>Please check your MySQL credentials and make sure MySQL is running.</p>";
}
?>
