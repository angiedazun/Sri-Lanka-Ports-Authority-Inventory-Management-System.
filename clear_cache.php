<?php
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache cleared successfully!";
} else {
    echo "OPcache is not enabled.";
}

// Also clear any session data that might be causing issues
echo "<br>Clearing any error caches...";
echo "<br><a href='pages/papers_return.php'>Go to Papers Return</a>";
?>
