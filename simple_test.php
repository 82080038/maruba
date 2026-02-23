<?php
// Simple test for development
echo "Development Mode Test - PHP Working!";
echo "<br>";
echo "Environment: " . (getenv('APP_ENV') ?: 'not set');
echo "<br>";
echo "Debug Mode: " . (getenv('APP_DEBUG') ?: 'not set');
echo "<br>";
echo "Current Time: " . date('Y-m-d H:i:s');
echo "<br>";
phpinfo();
?>
