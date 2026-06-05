<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../seccion/inicio_seccion.php");
    exit;
}
header("Location: contenedor_usuario.php?seccion=info");
exit;
?>