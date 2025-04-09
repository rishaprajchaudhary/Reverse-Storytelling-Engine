<?php
echo 'Checking MySQL connection...\n';

// MySQLi
$mysqli = @new mysqli('localhost', 'root', '', 'reverse_storytelling');
if ($mysqli->connect_error) {
    echo 'MySQLi Connection error: ' . $mysqli->connect_error . "\n";
} else {
    echo 'MySQLi Connection successful!\n';
    $mysqli->close();
}

echo 'Done.';

