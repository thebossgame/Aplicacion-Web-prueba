<?php
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

 $mensaje = "";
 $tipo_mensaje = "";

if ($_POST && isset($_POST['tipo_registro'])) {
    if ($_POST['tipo_registro'] == 'usuario') {
        $nombre = trim($_POST['nombre_completo'] ?? '');
        $cedula = trim($_POST['cedula'] ?? '');
        $casa = trim($_POST['numero_casa'] ?? '');
        $contrasena = $_POST['contrasena'] ?? '';

        if ($nombre !== '' && $cedula !== '' && $casa !== '' && $contrasena !== '') {
            // Verificar que cedula no exista
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE cedula = ?");
            $stmt->execute([$cedula]);
            if ($stmt->fetch()) {
                $mensaje = "Ya existe un usuario con esa cédula.";
                $tipo_mensaje = "error";
            } else {
                $contrasena_encriptada = password_hash($contrasena, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO usuarios (nombre_completo, cedula, numero_casa, contrasena) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nombre, $cedula, $casa, $contrasena_encriptada]);
                $mensaje = "¡Usuario registrado exitosamente!";
                $tipo_mensaje = "exito";
            }
        } else {
            $mensaje = "Completa todos los campos.";
            $tipo_mensaje = "error";
        }
    } elseif ($_POST['tipo_registro'] == 'admin') {
        $nombre = trim($_POST['nombre_admin'] ?? '');
        $usuario = trim($_POST['usuario_admin'] ?? '');
        $contrasena = $_POST['contrasena_admin'] ?? '';

        if ($nombre !== '' && $usuario !== '' && $contrasena !== '') {
            $stmt = $pdo->prepare("SELECT id FROM administradores WHERE usuario = ?");
            $stmt->execute([$usuario]);
            if ($stmt->fetch()) {
                $mensaje = "Ya existe un administrador con ese usuario.";
                $tipo_mensaje = "error";
            } else {
                $contrasena_encriptada = password_hash($contrasena, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO administradores (nombre_completo, usuario, contrasena) VALUES (?, ?, ?)");
                $stmt->execute([$nombre, $usuario, $contrasena_encriptada]);
                $mensaje = "¡Administrador registrado exitosamente!";
                $tipo_mensaje = "exito";
            }
        } else {
            $mensaje = "Completa todos los campos.";
            $tipo_mensaje = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro</title>
    <link rel="stylesheet" href="../css/estilos_formularios.css">
</head>
<body>
    <header>
        <div class="logo">
            <img src="../img/logo.jpeg" alt="Logo" class="logo-img">
        </div>
        <div class="menu-right">
            <a href="../index.html">← Regresar</a>
        </div>
    </header>

    <div class="container">
        <h1>Registro</h1>

        <?php if ($mensaje): ?>
            <div class="mensaje <?= $tipo_mensaje ?>"><?= $mensaje ?></div>
            <?php if ($tipo_mensaje == "exito"): ?>
                <script>setTimeout(() => { window.location.href = '../index.html'; }, 2000);</script>
            <?php endif; ?>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab-button active" onclick="showTab('usuario')">Registro Usuario</button>
            <button class="tab-button" onclick="showTab('admin')">Registro Administrador</button>
        </div>

        <!-- Formulario Usuario -->
        <form id="formUsuario" method="POST" class="form-tab active formulario-bordered">
            <input type="hidden" name="tipo_registro" value="usuario">
            <input type="text" name="nombre_completo" placeholder="Nombre completo" required>
            <input type="text" name="cedula" placeholder="Cédula" required>
            <input type="text" name="numero_casa" placeholder="Número de Casa" required>
            <input type="password" name="contrasena" placeholder="Contraseña" required minlength="4">
            <button type="submit">Registrarse</button>
        </form>

        <!-- Formulario Admin -->
        <form id="formAdmin" method="POST" class="form-tab formulario-bordered" style="display: none;">
            <input type="hidden" name="tipo_registro" value="admin">
            <input type="text" name="nombre_admin" placeholder="Nombre completo de la Comunidad" required>
            <input type="text" name="usuario_admin" placeholder="Usuario" required>
            <input type="password" name="contrasena_admin" placeholder="Contraseña" required minlength="4">
            <button type="submit">Registrar Admin</button>
        </form>
    </div>

    <script>
        function showTab(tabName) {
            document.querySelectorAll('.form-tab').forEach(form => form.style.display = 'none');
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            document.getElementById('form' + (tabName === 'usuario' ? 'Usuario' : 'Admin')).style.display = 'flex';
            event.target.classList.add('active');
        }
    </script>
</body>
</html>