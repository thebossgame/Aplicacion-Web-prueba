<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../seccion/inicio_seccion.php");
    exit;
}

 $titulo = trim($_POST['titulo'] ?? '');
 $contenido = trim($_POST['contenido'] ?? '');
 $video_url = trim($_POST['video_url'] ?? '');

if ($titulo === '' || $contenido === '') {
    $_SESSION['pub_mensaje'] = 'El título y contenido son obligatorios.';
    $_SESSION['pub_tipo'] = 'error';
    header("Location: administrador.php");
    exit;
}

 $servername = "localhost";
 $username = "root";
 $password = "";
 $dbname = "gestion_comunitaria";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

 $pdo->beginTransaction();

try {
    $stmt = $pdo->prepare("INSERT INTO publicaciones (titulo, contenido) VALUES (?, ?)");
    $stmt->execute([$titulo, $contenido]);
    $pub_id = $pdo->lastInsertId();

    // Procesar imágenes
    if (!empty($_FILES['imagenes']) && $_FILES['imagenes']['error'][0] !== UPLOAD_ERR_NO_FILE) {
        $carpeta = '../uploads/publicaciones/';
        if (!is_dir($carpeta)) mkdir($carpeta, 0755, true);

        $permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $total = count($_FILES['imagenes']['name']);

        for ($i = 0; $i < $total; $i++) {
            if ($_FILES['imagenes']['error'][$i] !== UPLOAD_ERR_OK) continue;
            if (!in_array($_FILES['imagenes']['type'][$i], $permitidos)) continue;

            $ext = pathinfo($_FILES['imagenes']['name'][$i], PATHINFO_EXTENSION);
            $nuevo_nombre = 'pub_' . $pub_id . '_' . $i . '_' . time() . '.' . $ext;
            $ruta_guardar = $carpeta . $nuevo_nombre;
            $ruta_bd = 'uploads/publicaciones/' . $nuevo_nombre;

            if (move_uploaded_file($_FILES['imagenes']['tmp_name'][$i], $ruta_guardar)) {
                $stmt = $pdo->prepare("INSERT INTO imagenes_publicacion (publicacion_id, ruta_imagen) VALUES (?, ?)");
                $stmt->execute([$pub_id, $ruta_bd]);
            }
        }
    }

    // Procesar video de YouTube
    if ($video_url !== '' && preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/', $video_url)) {
        $stmt = $pdo->prepare("INSERT INTO videos_publicacion (publicacion_id, url_video) VALUES (?, ?)");
        $stmt->execute([$pub_id, $video_url]);
    }

    $pdo->commit();
    $_SESSION['pub_mensaje'] = 'Publicación creada correctamente.';
    $_SESSION['pub_tipo'] = 'exito';
    header("Location: administrador.php");
    exit;

} catch(Exception $e) {
    $pdo->rollBack();
    $_SESSION['pub_mensaje'] = 'Error al guardar la publicación.';
    $_SESSION['pub_tipo'] = 'error';
    header("Location: administrador.php");
    exit;
}