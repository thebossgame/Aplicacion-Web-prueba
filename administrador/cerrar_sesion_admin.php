<?php
session_start();
// Solo destruye la sesión admin
unset($_SESSION['admin']);
session_destroy();
header("Location: ../index.html?logout=1");
exit;
?>