<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../seccion/inicio_seccion.php");
    exit;
}

 $mapa_archivos = [
    'armando' => 'Armandomolero1.docx',
    'ernesto' => 'ErnestocheguevarasigloXXI.docx',
    'exito'   => 'Exitorevolucionario.pdf',
    'fuerza'  => 'fuerzapatriota.docx',
    'general' => 'generalenjeferafaelurdaneta.pdf',
    'rosa'    => 'rosainesdechavez.pdf',
    'sitio'   => 'Sitiorevolucionado.pdf'
];

 $clave = $_GET['archivo'] ?? '';

if (!isset($mapa_archivos[$clave])) {
    die("Error: Enlace no válido.");
}

 $nombre_real = $mapa_archivos[$clave];
 $ruta_carpeta = 'documentos/carta_residencia/';
 $ruta_completa = $ruta_carpeta . $nombre_real;

if (!file_exists($ruta_completa)) {
    die("Error: El archivo físico no existe en la carpeta.");
}

// Limpia cualquier cosa que PHP haya mostrado en pantalla antes (espacios, errores, etc)
if (ob_get_level()) {
    ob_end_clean();
}

// Forzar la descarga limpia
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($nombre_real) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($ruta_completa));
flush();
readfile($ruta_completa);
exit;
?>