<?php
// Set a test cookie with explicit options
setcookie('test', 'test', time() + 3600, '/', 'hountybunter.click', false, true);

echo 'Cookie set';
