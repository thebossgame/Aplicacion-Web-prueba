<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../seccion/inicio_seccion.php");
    exit;
}

 $id = $_GET['id'] ?? 0;

if ($id > 0) {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "gestion_comunitaria";

    try {
        $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Obtener imágenes para borrar archivos del servidor
        $stmt = $pdo->prepare("SELECT ruta_imagen FROM imagenes_publicacion WHERE publicacion_id = ?");
        $stmt->execute([$id]);
        while ($img = $stmt->fetch()) {
            $ruta = '../' . $img['ruta_imagen'];
            if (file_exists($ruta)) unlink($ruta);
        }

        // Eliminar publicación (CASCADE elimina imágenes y videos de la BD)
        $stmt = $pdo->prepare("DELETE FROM publicaciones WHERE id = ?");
        $stmt->execute([$id]);
    } catch(PDOException $e) {}
}

 $_SESSION['pub_mensaje'] = 'Publicación eliminada.';
 $_SESSION['pub_tipo'] = 'exito';
header("Location: administrador.php");
exit;