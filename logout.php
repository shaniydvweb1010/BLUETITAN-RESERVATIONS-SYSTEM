<?php
// 1. Initialize the session
session_start();

// 2. Unset all of the session variables to clear the data array
$_SESSION = array();

// 3. Destroy the session cookie on the user's browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Finally, completely destroy the active session on the server
session_destroy();

// 5. Redirect the user back to the homepage (or login page)
header("Location: index.php");
exit();
?>