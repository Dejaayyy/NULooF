<?php
session_start();
session_unset();
session_destroy();

// Redirect to login or index page
header("Location: ../users/index.php");
exit();
?>
