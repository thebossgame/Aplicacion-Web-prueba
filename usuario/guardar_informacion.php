<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../seccion/inicio_seccion.php");
    exit;
}

 $usuario_id = $_POST['usuario_id'] ?? '';
 $numero_casa = trim($_POST['numero_casa'] ?? '');
 $comunidad = trim($_POST['comunidad'] ?? '');
 $tipo_gas = $_POST['tipo_gas'] ?? '';
 $bombonas_detalle = trim($_POST['bombonas_detalle'] ?? '');
 $persona_nombres = $_POST['persona_nombre'] ?? [];
 $persona_cedulas = $_POST['persona_cedula'] ?? [];
 $persona_correos = $_POST['persona_correo'] ?? [];
 $persona_telefonos = $_POST['persona_telefono'] ?? [];
 $medicamentos_post = $_POST['medicamentos'] ?? [];
 $otro_med_post = $_POST['otro_medicamento'] ?? [];
 $tiene_problema_post = $_POST['tiene_problema'] ?? [];
 $descripcion_problema_post = $_POST['descripcion_problema'] ?? '';

if ($comunidad === '' || $tipo_gas === '') {
    $_SESSION['ficha_mensaje'] = 'Selecciona la comunidad y el tipo de gas.';
    $_SESSION['ficha_tipo'] = 'error';
    header("Location: contenedor_usuario.php?seccion=integrantes");
    exit;
}
if ($tipo_gas === 'Bombona' && $bombonas_detalle === '') {
    $_SESSION['ficha_mensaje'] = 'Describe qué bombonas tiene.';
    $_SESSION['ficha_tipo'] = 'error';
    header("Location: contenedor_usuario.php?seccion=integrantes");
    exit;
}
if (empty($persona_nombres)) {
    $_SESSION['ficha_mensaje'] = 'Debe agregar al menos una persona.';
    $_SESSION['ficha_tipo'] = 'error';
    header("Location: contenedor_usuario.php?seccion=integrantes");
    exit;
}

foreach ($persona_nombres as $idx => $nombre) {
    if (trim($nombre) === '' || trim($persona_cedulas[$idx] ?? '') === '') {
        $_SESSION['ficha_mensaje'] = 'La Persona ' . ($idx + 1) . ' debe tener nombre y cédula.';
        $_SESSION['ficha_tipo'] = 'error';
        header("Location: contenedor_usuario.php?seccion=integrantes");
        exit;
    }
}

 $servername = "localhost";
 $username = "root";
 $password = "";
 $dbname = "gestion_comunitaria";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    $_SESSION['ficha_mensaje'] = 'Error de conexión a la base de datos.';
    $_SESSION['ficha_tipo'] = 'error';
    header("Location: contenedor_usuario.php?seccion=integrantes");
    exit;
}

 $pdo->beginTransaction();

try {
    // Eliminar ficha anterior si existe (CASCADE elimina personas y medicamentos)
    $stmt = $pdo->prepare("DELETE FROM fichas_hogar WHERE usuario_id = ?");
    $stmt->execute([$usuario_id]);

    // Insertar nueva ficha
    $stmt = $pdo->prepare("INSERT INTO fichas_hogar (usuario_id, numero_casa, comunidad, tipo_gas, bombonas_detalle) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$usuario_id, $numero_casa, $comunidad, $tipo_gas, $bombonas_detalle]);
    $ficha_id = $pdo->lastInsertId();

    // Insertar cada persona con sus medicamentos
    foreach ($persona_nombres as $idx => $nombre) {
        $nombre = trim($nombre);
        $cedula = trim($persona_cedulas[$idx] ?? '');
        $correo = trim($persona_correos[$idx] ?? '') ?: null;
        $telefono = trim($persona_telefonos[$idx] ?? '') ?: null;
        $tiene_problema = isset($tiene_problema_post[$idx]) ? 1 : 0;
        $desc_problema = $tiene_problema ? trim($descripcion_problema_post[$idx] ?? '') : '';

        $stmt = $pdo->prepare("INSERT INTO personas_hogar (ficha_id, nombre_completo, cedula, correo, telefono, tiene_problema_medico, descripcion_problema) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$ficha_id, $nombre, $cedula, $correo, $telefono, $tiene_problema, $desc_problema]);
        $persona_id = $pdo->lastInsertId();

        // Medicamentos de esta persona
        $meds_persona = $medicamentos_post[$idx] ?? [];
        if (!empty($meds_persona)) {
            foreach ($meds_persona as $med) {
                $otro_detalle = '';
                if ($med === 'Otros') {
                    $otro_detalle = trim($otro_med_post[$idx] ?? '');
                }
                $stmt = $pdo->prepare("INSERT INTO medicamentos_persona (persona_id, medicamento, otro_detalle) VALUES (?, ?, ?)");
                $stmt->execute([$persona_id, $med, $otro_detalle]);
            }
        }
    }

    $pdo->commit();
    $_SESSION['ficha_mensaje'] = 'Información guardada correctamente.';
    $_SESSION['ficha_tipo'] = 'exito';
    header("Location: contenedor_usuario.php?seccion=integrantes");
    exit;

} catch(Exception $e) {
    $pdo->rollBack();
    $_SESSION['ficha_mensaje'] = 'Error: ' . $e->getMessage();
    $_SESSION['ficha_tipo'] = 'error';
    header("Location: contenedor_usuario.php?seccion=integrantes");
    exit;
}