<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../seccion/inicio_seccion.php?error=admin_no_log");
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

// Obtenemos el usuario de forma segura (por si es array o texto)
 $usuario_buscar = '';
if (is_array($_SESSION['admin'])) {
    $usuario_buscar = $_SESSION['admin']['usuario'] ?? '';
    if (is_array($usuario_buscar)) $usuario_buscar = '';
} else {
    $usuario_buscar = $_SESSION['admin'];
}

// Consultamos la base de datos para obtener el nombre de la comunidad
 $admin_datos = null;
if ($usuario_buscar !== '') {
    $stmt = $pdo->prepare("SELECT nombre_completo, usuario FROM administradores WHERE usuario = ?");
    $stmt->execute([$usuario_buscar]);
    $admin_datos = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil</title>
    <link rel="stylesheet" href="../css/estilos.css">
    <style>
        body { display:flex; min-height:100vh; background:#f4f7fa; }
        .sidebar-admin {
            width:240px; background:linear-gradient(180deg,#dc3545 0%,#c82333 100%);
            color:#fff; display:flex; flex-direction:column; align-items:stretch;
            padding:2rem 0; box-shadow:2px 0 10px rgba(220,53,69,0.2); min-height:100vh;
        }
        .sidebar-admin .logo-admin { text-align:center; margin-bottom:2rem; }
        .sidebar-admin .logo-admin img { height:60px; }
        .sidebar-admin nav { flex:1; display:flex; flex-direction:column; gap:2rem; }
        .sidebar-admin a {
            color:#fff; text-decoration:none; padding:1rem 2rem; font-size:1.1rem;
            border-radius:0 20px 20px 0; transition:background 0.3s;
        }
        .sidebar-admin a:hover, .sidebar-admin a.active { background:rgba(255,255,255,0.15); }
        .admin-main-content { flex:1; padding:3rem; }
        .admin-header { font-size:1.6rem; font-weight:bold; margin-bottom:1.5rem; }

        .perfil-box {
            background:#fff; border-radius:10px; box-shadow:0 6px 18px rgba(220,53,69,0.08);
            padding:2rem; max-width:500px;
        }
        .perfil-avatar {
            width:80px; height:80px; border-radius:50%; background:linear-gradient(135deg,#dc3545,#c82333);
            display:flex; align-items:center; justify-content:center; margin:0 auto 1.5rem;
            font-size:2rem; color:#fff;
        }
        .perfil-campo {
            display:flex; justify-content:space-between; align-items:center;
            padding:1rem 0; border-bottom:1px solid #eee;
        }
        .perfil-campo:last-child { border-bottom:none; }
        .perfil-label { color:#888; font-size:0.9rem; }
        .perfil-valor { color:#333; font-weight:bold; font-size:1rem; }

        @media (max-width:700px) {
            body { flex-direction:column; }
            .sidebar-admin { width:100%; flex-direction:row; min-height:auto; padding:0.5rem 0; }
            .sidebar-admin nav { flex-direction:row; gap:0; }
            .sidebar-admin a { border-radius:20px; padding:0.6rem 1rem; font-size:0.9rem; }
            .admin-main-content { padding:1rem; }
        }
    </style>
</head>
<body>
    <aside class="sidebar-admin">
        <div class="logo-admin">
            <img src="../img/logo.jpeg" alt="Logo Admin">
            <div style="margin-top:0.5rem;font-weight:bold;">ADMIN</div>
        </div>
        <nav>
            <a href="administrador.php">🏠 Inicio</a>
            <a href="administrador.php?comuna=info">🏘️ Información de comuna</a>
            <a href="perfil.php" class="active">👤 Perfil</a>
            <a href="cerrar_sesion_admin.php">🚪 Cerrar Sesión</a>
        </nav>
    </aside>

    <main class="admin-main-content">
        <div class="admin-header">Mi Perfil</div>

        <?php if ($admin_datos): ?>
        <div class="perfil-box">
            <div class="perfil-avatar">👤</div>
            <div class="perfil-campo">
                <span class="perfil-label">Nombre de la Comunidad</span>
                <span class="perfil-valor"><?= htmlspecialchars($admin_datos['nombre_completo']) ?></span>
            </div>
            <div class="perfil-campo">
                <span class="perfil-label">Usuario</span>
                <span class="perfil-valor"><?= htmlspecialchars($admin_datos['usuario']) ?></span>
            </div>
        </div>
        <?php else: ?>
            <p style="color:#dc3545; background:#fff; padding:1rem; border-radius:8px; max-width:500px;">No se pudieron cargar los datos del perfil.</p>
        <?php endif; ?>
    </main>
</body>
</html>