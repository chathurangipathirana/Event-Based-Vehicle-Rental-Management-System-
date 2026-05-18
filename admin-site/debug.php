<?php
echo "<h2>Debug Information</h2>";
echo "Current file: " . __FILE__ . "<br>";
echo "Current directory: " . __DIR__ . "<br><br>";

// Check if config folder exists
$config_folder = __DIR__ . "/config";
if (is_dir($config_folder)) {
    echo "✅ config folder EXISTS at: " . $config_folder . "<br>";
} else {
    echo "❌ config folder DOES NOT EXIST at: " . $config_folder . "<br>";
    echo "Please create this folder manually.<br><br>";
}

// Check if database.php exists
$db_file = __DIR__ . "/config/database.php";
if (file_exists($db_file)) {
    echo "✅ database.php EXISTS at: " . $db_file . "<br>";
    echo "File size: " . filesize($db_file) . " bytes<br>";
} else {
    echo "❌ database.php DOES NOT EXIST at: " . $db_file . "<br>";
}

// List all folders in current directory
echo "<br><strong>📁 Folders in current directory:</strong><br>";
$items = scandir(__DIR__);
foreach ($items as $item) {
    if ($item != '.' && $item != '..' && is_dir(__DIR__ . '/' . $item)) {
        echo "- " . $item . "/<br>";
    }
}

// List all files in config folder if it exists
if (is_dir($config_folder)) {
    echo "<br><strong>📄 Files in config folder:</strong><br>";
    $config_files = scandir($config_folder);
    foreach ($config_files as $file) {
        if ($file != '.' && $file != '..') {
            echo "- " . $file . "<br>";
        }
    }
}
?>