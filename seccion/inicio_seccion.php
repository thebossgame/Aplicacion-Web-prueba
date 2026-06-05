<?php
session_start();
 $mensaje = "";
 $tipo_mensaje = "";

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

if ($_POST && isset($_POST['tipo_login'])) {
    if ($_POST['tipo_login'] == 'usuario') {
        $nombre = trim($_POST['nombre_completo'] ?? '');
        $contrasena = $_POST['contrasena'] ?? '';

        if ($nombre !== '' && $contrasena !== '') {
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE nombre_completo = ?");
            $stmt->execute([$nombre]);
            $usuario = $stmt->fetch();

            if ($usuario && password_verify($contrasena, $usuario['contrasena'])) {
                $_SESSION['usuario'] = $usuario;
                $_SESSION['tipo'] = 'usuario';
                echo "<script>window.location.href = '../usuario/contenedor_usuario.php'; </script>";
                exit;
            } else {
                $mensaje = "Nombre o contraseña incorrectos.";
                $tipo_mensaje = "error";
            }
        } else {
            $mensaje = "Completa todos los campos.";
            $tipo_mensaje = "error";
        }
    } elseif ($_POST['tipo_login'] == 'admin') {
        $usuario_admin = trim($_POST['usuario_admin'] ?? '');
        $contrasena = $_POST['contrasena_admin'] ?? '';

        if ($usuario_admin !== '' && $contrasena !== '') {
            $stmt = $pdo->prepare("SELECT * FROM administradores WHERE usuario = ?");
            $stmt->execute([$usuario_admin]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($contrasena, $admin['contrasena'])) {
                $_SESSION['admin'] = $admin;
                $_SESSION['tipo'] = 'admin';
                echo "<script>window.location.href = '../administrador/administrador.php'; </script>";
                exit;
            } else {
                $mensaje = "Usuario o contraseña incorrectos.";
                $tipo_mensaje = "error";
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
    <title>Inicio de Sesión</title>
    <link rel="stylesheet" href="../css/estilos_formularios.css">
</head>
<body>
    <header>
        <div class="logo">
            <img src="../img/Logo.jpeg" alt="Logo" class="logo-img">
        </div>
        <div class="menu-right">
            <a href="../index.html">← Regresar</a>
        </div>
    </header>

    <div class="container">
        <h1>Inicio de Sesión</h1>

        <?php if ($mensaje): ?>
            <div class="mensaje <?= $tipo_mensaje ?>"><?= $mensaje ?></div>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab-button active" onclick="showTab('usuario')">Usuario</button>
            <button class="tab-button" onclick="showTab('admin')">Administrador</button>
        </div>

        <!-- Formulario Usuario -->
        <form id="formUsuarioLogin" method="POST" class="form-tab active formulario-bordered">
            <input type="hidden" name="tipo_login" value="usuario">
            <input type="text" name="nombre_completo" placeholder="Nombre Completo" required>
            <input type="password" name="contrasena" placeholder="Contraseña" required>
            <button type="submit">Iniciar Sesión</button>
        </form>

        <!-- Formulario Admin -->
        <form id="formAdminLogin" method="POST" class="form-tab formulario-bordered" style="display: none;">
            <input type="hidden" name="tipo_login" value="admin">
            <input type="text" name="usuario_admin" placeholder="Usuario" required>
            <input type="password" name="contrasena_admin" placeholder="Contraseña" required>
            <button type="submit">Login Admin</button>
        </form>
    </div>

    <script>
        function showTab(tabName) {
            document.querySelectorAll('.form-tab').forEach(form => form.style.display = 'none');
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            document.getElementById('form' + (tabName === 'usuario' ? 'UsuarioLogin' : 'AdminLogin')).style.display = 'flex';
            event.target.classList.add('active');
        }
    </script>
</body>
</html>