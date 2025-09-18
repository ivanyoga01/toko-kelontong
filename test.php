<?php
echo "PHP is working! Current time: " . date('Y-m-d H:i:s');
echo "<br>Base URL: " . (defined('BASE_URL') ? BASE_URL : 'BASE_URL not defined');
phpinfo();
?>