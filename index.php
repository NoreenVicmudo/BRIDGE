<?php
// Redirect root of the app to the clean URL handled by .htaccess
// The app currently expects to live under /bridge (see core/auth.php)
header("Location: /bridge/home");
exit;


