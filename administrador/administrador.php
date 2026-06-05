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

 $mensaje = $_SESSION['pub_mensaje'] ?? '';
 $tipo_mensaje = $_SESSION['pub_tipo'] ?? '';
unset($_SESSION['pub_mensaje'], $_SESSION['pub_tipo']);

 $mostrar_comuna = isset($_GET['comuna']) && $_GET['comuna'] === 'info';

 $publicaciones = [];
if (!$mostrar_comuna) {
    $stmt = $pdo->query("SELECT * FROM publicaciones ORDER BY fecha_publicacion DESC");
    while ($pub = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $pub_id = $pub['id'];
        $pub['imagenes'] = [];
        $s = $pdo->prepare("SELECT ruta_imagen FROM imagenes_publicacion WHERE publicacion_id = ?");
        $s->execute([$pub_id]);
        while ($img = $s->fetch()) $pub['imagenes'][] = $img['ruta_imagen'];
        $pub['videos'] = [];
        $s2 = $pdo->prepare("SELECT url_video FROM videos_publicacion WHERE publicacion_id = ?");
        $s2->execute([$pub_id]);
        while ($vid = $s2->fetch()) $pub['videos'][] = $vid['url_video'];
        $publicaciones[] = $pub;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administrador</title>
    <link rel="stylesheet" href="../css/estilos.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
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

        .pub-form-box {
            background:#fff; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.06);
            padding:1.5rem; max-width:500px;
        }
        .pub-form-box label { display:block; margin-bottom:0.4rem; font-weight:bold; color:#333; }
        .pub-form-box input, .pub-form-box textarea, .pub-form-box select {
            width:100%; padding:0.7rem; border:1px solid #ddd; border-radius:5px;
            box-sizing:border-box; margin-bottom:1rem; font-family:Arial,sans-serif;
        }
        .pub-form-box button {
            background:#dc3545; color:#fff; border:none; padding:0.8rem; border-radius:5px;
            cursor:pointer; font-weight:bold; width:100%;
        }
        .pub-form-box button:hover { background:#c82333; }

        .pub-list { max-width:500px; }
        .pub-item {
            background:#fff; border-radius:8px; padding:1rem; margin-bottom:1rem;
            box-shadow:0 2px 8px rgba(0,0,0,0.05);
        }
        .pub-item h4 { color:#333; margin-bottom:0.3rem; }
        .pub-item .pub-fecha { color:#999; font-size:0.8rem; }
        .pub-item a { color:#dc3545; text-decoration:none; font-size:0.85rem; }
        .pub-item a:hover { text-decoration:underline; }

        .admin-info-box {
            background:#fff; border-radius:10px; box-shadow:0 6px 18px rgba(220,53,69,0.08);
            padding:2rem; max-width:900px;
        }
        .admin-info-box select {
            width:250px; padding:0.6rem; border-radius:6px; border:1px solid #ddd;
        }

        .btn-descargas { display:flex; gap:1rem; margin-bottom:1.5rem; }
        .btn-descargar {
            padding:0.7rem 1.5rem; border:none; border-radius:5px; cursor:pointer;
            font-weight:bold; color:#fff; font-size:0.9rem; display:flex; align-items:center; gap:0.5rem;
        }
        .btn-excel { background:#28a745; }
        .btn-excel:hover { background:#218838; }
        .btn-pdf { background:#dc3545; }
        .btn-pdf:hover { background:#c82333; }

        .lista-personas {
            display:grid; grid-template-columns:1fr 1fr; gap:0.8rem; margin-top:1.5rem;
        }
        .persona-card {
            background:#f8f9fa; border-radius:6px; padding:0.8rem; border-left:4px solid #667eea;
        }
        .persona-card.problema { border-left-color:#dc3545; background:#fff5f5; }
        .persona-card .pc-nombre { font-weight:bold; color:#333; font-size:0.9rem; }
        .persona-card .pc-casa { color:#888; font-size:0.8rem; }
        .persona-card .pc-detalle { font-size:0.82rem; margin-top:0.3rem; }
        .persona-card .pc-detalle.meds { color:#d35400; }
        .persona-card .pc-detalle.prob { color:#c0392b; }

        .mensaje { margin-bottom:1.5rem; padding:1rem; border-radius:5px; }
        .exito { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
        .error { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }

        @media (max-width:900px) { .lista-personas { grid-template-columns:1fr; } }
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
            <a href="administrador.php" class="<?= !$mostrar_comuna ? 'active' : '' ?>">🏠 Inicio</a>
            <a href="administrador.php?comuna=info" class="<?= $mostrar_comuna ? 'active' : '' ?>">🏘️ Información de comuna</a>
            <a href="perfil.php">👤 Perfil</a>
            <a href="cerrar_sesion_admin.php">🚪 Cerrar Sesión</a>
        </nav>
    </aside>

    <main class="admin-main-content">
        <?php if ($mensaje): ?>
            <div class="mensaje <?= $tipo_mensaje ?>"><?= $mensaje ?></div>
        <?php endif; ?>

        <?php if (!$mostrar_comuna): ?>
        <div class="admin-header">Panel Administrador</div>
        <div style="display:flex; gap:2rem; flex-wrap:wrap;">
            <div class="pub-form-box">
                <h3 style="margin-bottom:1rem; color:#333;">Nueva Publicación</h3>
                <form method="POST" action="guardar_publicacion.php" enctype="multipart/form-data">
                    <label>Título</label>
                    <input type="text" name="titulo" placeholder="Título de la publicación" required>
                    <label>Contenido</label>
                    <textarea name="contenido" rows="5" placeholder="Escribe el contenido aquí..." required></textarea>
                    <label>Imágenes</label>
                    <input type="file" name="imagenes[]" multiple accept="image/*">
                    <label>Video (URL de YouTube)</label>
                    <input type="url" name="video_url" placeholder="https://www.youtube.com/watch?v=...">
                    <button type="submit">Publicar</button>
                </form>
            </div>
            <div class="pub-list">
                <h3 style="margin-bottom:1rem; color:#333;">Publicaciones Anteriores</h3>
                <?php if (empty($publicaciones)): ?>
                    <p style="color:#888;">No hay publicaciones aún.</p>
                <?php else: ?>
                    <?php foreach ($publicaciones as $pub): ?>
                    <div class="pub-item">
                        <div style="display:flex; justify-content:space-between; align-items:start;">
                            <h4><?= htmlspecialchars($pub['titulo']) ?></h4>
                            <a href="eliminar_publicacion.php?id=<?= $pub['id'] ?>" onclick="return confirm('¿Eliminar esta publicación?')">🗑️</a>
                        </div>
                        <div class="pub-fecha"><?= date('d/m/Y H:i', strtotime($pub['fecha_publicacion'])) ?></div>
                        <p style="color:#666; font-size:0.85rem; margin-top:0.3rem;"><?= htmlspecialchars(mb_substr($pub['contenido'], 0, 100)) ?>...</p>
                        <?php if (!empty($pub['imagenes'])): ?>
                            <div style="display:flex; gap:4px; margin-top:0.5rem;">
                                <?php foreach (array_slice($pub['imagenes'], 0, 3) as $img): ?>
                                    <img src="<?= htmlspecialchars($img) ?>" style="width:50px; height:50px; object-fit:cover; border-radius:4px;">
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php else: ?>
        <div class="admin-header">Información de Comunidad</div>
        <div class="admin-info-box">
            <h2 style="margin-bottom:0.5rem;">Estadísticas por Comunidad</h2>
            <p style="color:#888; margin-bottom:1.5rem;">Selecciona una comunidad para ver sus datos.</p>
            <select id="select-comuna">
                <option value="">Seleccione una...</option>
                <option>Rosa Ines Chavez</option>
                <option>Exito Revolucionario</option>
                <option>Fuerza Patriotica</option>
                <option>El Sitio Revolucionario</option>
                <option>Che Guevara Siglo XXI</option>
                <option>General En Jefe Rafael Urdaneta</option>
                <option>Armando Molero 1</option>
            </select>
            <div id="contenedor-datos" style="margin-top:2rem;"></div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
        let chartUsuarios = null, chartGas = null;
        let datosActuales = null;
        let nombreComunaActual = '';

        document.getElementById('select-comuna').addEventListener('change', function() {
            const comuna = this.value;
            if (!comuna) {
                document.getElementById('contenedor-datos').innerHTML = '';
                if (chartUsuarios) { chartUsuarios.destroy(); chartUsuarios = null; }
                if (chartGas) { chartGas.destroy(); chartGas = null; }
                datosActuales = null;
                return;
            }

            document.getElementById('contenedor-datos').innerHTML = '<p style="color:#888;">Cargando...</p>';
            nombreComunaActual = comuna;

            fetch('obtener_estadisticas_comuna.php?comuna=' + encodeURIComponent(comuna))
                .then(resp => resp.json())
                .then(data => {
                    if (chartUsuarios) { chartUsuarios.destroy(); chartUsuarios = null; }
                    if (chartGas) { chartGas.destroy(); chartGas = null; }
                    
                    datosActuales = data; 

                    let html = '';
                    html += '<div class="btn-descargas">';
                    html += '<button class="btn-descargar btn-excel" onclick="descargarExcel()">📥 Descargar Excel</button>';
                    html += '<button class="btn-descargar btn-pdf" onclick="descargarPDF()">📄 Descargar PDF</button>';
                    html += '</div>';

                    html += '<div style="display:flex; gap:1rem; flex-wrap:wrap; margin-bottom:2rem;">';
                    html += '<div style="background:#667eea; color:#fff; padding:1rem 1.5rem; border-radius:8px; text-align:center;"><div style="font-size:1.8rem;font-weight:bold;">'+data.total_hogares+'</div><div style="font-size:0.8rem;opacity:0.8;">Hogares</div></div>';
                    html += '<div style="background:#28a745; color:#fff; padding:1rem 1.5rem; border-radius:8px; text-align:center;"><div style="font-size:1.8rem;font-weight:bold;">'+data.total_personas+'</div><div style="font-size:0.8rem;opacity:0.8;">Personas</div></div>';
                    html += '<div style="background:#17a2b8; color:#fff; padding:1rem 1.5rem; border-radius:8px; text-align:center;"><div style="font-size:1.8rem;font-weight:bold;">'+data.gas_tuberia+'</div><div style="font-size:0.8rem;opacity:0.8;">Gas Tubería</div></div>';
                    html += '<div style="background:#ffc107; color:#333; padding:1rem 1.5rem; border-radius:8px; text-align:center;"><div style="font-size:1.8rem;font-weight:bold;">'+data.bombona+'</div><div style="font-size:0.8rem;opacity:0.8;">Bombona</div></div>';
                    html += '</div>';

                    html += '<div style="display:flex; gap:2rem; flex-wrap:wrap; margin-bottom:2rem;">';
                    html += '<div style="flex:1; min-width:280px; background:#fff; padding:1rem; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.05);"><canvas id="grafUsuarios" height="120"></canvas></div>';
                    html += '<div style="flex:1; min-width:280px; background:#fff; padding:1rem; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.05);"><canvas id="grafGas" height="120"></canvas></div>';
                    html += '</div>';

                    html += '<h3 style="color:#333; margin-bottom:0.8rem;">Personas que consumen medicamentos ('+data.personas_medicamentos.length+')</h3>';
                    if (data.personas_medicamentos.length === 0) { html += '<p style="color:#888;">No hay datos.</p>'; } 
                    else {
                        html += '<div class="lista-personas">';
                        data.personas_medicamentos.forEach(function(p) { html += '<div class="persona-card"><div class="pc-nombre">'+p.nombre+'</div><div class="pc-casa">Casa '+p.casa+'</div><div class="pc-detalle meds">'+p.medicamentos+'</div></div>'; });
                        html += '</div>';
                    }

                    html += '<h3 style="color:#333; margin:2rem 0 0.8rem;">Personas con problemas médicos ('+data.personas_problemas.length+')</h3>';
                    if (data.personas_problemas.length === 0) { html += '<p style="color:#888;">No hay datos.</p>'; } 
                    else {
                        html += '<div class="lista-personas">';
                        data.personas_problemas.forEach(function(p) { html += '<div class="persona-card problema"><div class="pc-nombre">'+p.nombre+'</div><div class="pc-casa">Casa '+p.casa+'</div><div class="pc-detalle prob">'+p.problema+'</div></div>'; });
                        html += '</div>';
                    }

                    document.getElementById('contenedor-datos').innerHTML = html;

                    const ctx1 = document.getElementById('grafUsuarios').getContext('2d');
                    chartUsuarios = new Chart(ctx1, { type: 'bar', data: { labels: [comuna], datasets: [{ label: 'Hogares', data: [data.total_hogares], backgroundColor: '#667eea', borderRadius: 4 }] }, options: { plugins: { title: { display: true, text: 'Hogares' } }, scales: { y: { beginAtZero: true, precision: 0 } } } });
                    const ctx2 = document.getElementById('grafGas').getContext('2d');
                    chartGas = new Chart(ctx2, { type: 'pie', data: { labels: ['Gas de tubería', 'Bombona'], datasets: [{ data: [data.gas_tuberia, data.bombona], backgroundColor: ['#17a2b8', '#ffc107'] }] }, options: { plugins: { title: { display: true, text: 'Tipo de gas' } } } });
                })
                .catch(function() {
                    document.getElementById('contenedor-datos').innerHTML = '<p style="color:#dc3545;">Error al cargar los datos.</p>';
                });
        });

        // ==========================================
        // FUNCIES AUXILIARES PARA AGRUPAR POR CASA
        // ==========================================
        function agruparHabitantesPorCasa() {
            if (!datosActuales) return {};

            const habitantesPorCasa = {};

            // Función para ir guardando sin duplicar
            function agregarPersona(persona, tipoDetalle, detalleTexto) {
                let casa = persona.casa || persona.numero_casa || persona.nro_casa || 'Sin asignar';
                let nombre = persona.nombre || persona.nombre_completo || 'Sin nombre';

                if (!habitantesPorCasa[casa]) habitantesPorCasa[casa] = {};
                if (!habitantesPorCasa[casa][nombre]) habitantesPorCasa[casa][nombre] = { medicamentos: [], problemas: [] };

                if (tipoDetalle === 'med') habitantesPorCasa[casa][nombre].medicamentos.push(detalleTexto);
                if (tipoDetalle === 'prob') habitantesPorCasa[casa][nombre].problemas.push(detalleTexto);
            }

            // Procesar personas con medicamentos
            if (datosActuales.personas_medicamentos) {
                datosActuales.personas_medicamentos.forEach(function(p) {
                    // Si por casualidad ya vienen agrupadas dentro de un array 'personas'
                    if (Array.isArray(p.personas)) {
                        p.personas.forEach(function(subp) {
                            agregarPersona({...subp, casa: p.casa || subp.casa}, 'med', subp.medicamentos || '');
                        });
                    } else {
                        agregarPersona(p, 'med', p.medicamentos || p.medicamento || '');
                    }
                });
            }

            // Procesar personas con problemas médicos
            if (datosActuales.personas_problemas) {
                datosActuales.personas_problemas.forEach(function(p) {
                    if (Array.isArray(p.personas)) {
                        p.personas.forEach(function(subp) {
                            agregarPersona({...subp, casa: p.casa || subp.casa}, 'prob', subp.problema || subp.descripcion_problema || '');
                        });
                    } else {
                        agregarPersona(p, 'prob', p.problema || p.descripcion_problema || '');
                    }
                });
            }

            return habitantesPorCasa;
        }

        // ==========================================
        // FUNCIÓN PARA DESCARGAR EXCEL (AGRUPADO)
        // ==========================================
        function descargarExcel() {
            if (!datosActuales) return alert("No hay datos para exportar.");
            const habitantesPorCasa = agruparHabitantesPorCasa();
            
            let tablaHTML = `<table border="1">
                <tr style="background:#dc3545; color:#fff;"><th colspan="3">Informe de: ${nombreComunaActual}</th></tr>
                <tr style="background:#f2f2f2;"><th>Hogares</th><th>Personas</th><th>Gas Tubería</th><th>Bombona</th></tr>
                <tr><td>${datosActuales.total_hogares}</td><td>${datosActuales.total_personas}</td><td>${datosActuales.gas_tuberia}</td><td>${datosActuales.bombona}</td></tr>
                <tr><th colspan="3" style="background:#28a745; color:#fff;">Detalle por Casa y Habitantes</th></tr>
                <tr style="background:#f2f2f2;"><th>Número de Casa</th><th>Nombre Completo</th><th>Medicamentos / Problemas Médicos</th></tr>`;
            
            for (const [casa, personas] of Object.entries(habitantesPorCasa)) {
                let primeraVez = true;
                for (const [nombre, detalles] of Object.entries(personas)) {
                    let medsTexto = detalles.medicamentos.length > 0 ? detalles.medicamentos.join(', ') : 'Ninguno';
                    let probsTexto = detalles.problemas.length > 0 ? detalles.problemas.join(', ') : 'Ninguno';
                    
                    tablaHTML += `<tr>`;
                    tablaHTML += `<td style="font-weight:bold;">${primeraVez ? 'Casa ' + casa : ''}</td>`;
                    tablaHTML += `<td>${nombre}</td>`;
                    tablaHTML += `<td>Meds: ${medsTexto} <br>Probs: ${probsTexto}</td>`;
                    tablaHTML += `</tr>`;
                    primeraVez = false;
                }
            }

            if (Object.keys(habitantesPorCasa).length === 0) {
                tablaHTML += `<tr><td colspan="3" style="text-align:center;">No hay personas registradas en esta comunidad</td></tr>`;
            }

            tablaHTML += `</table>`;

            const blob = new Blob([tablaHTML], { type: "application/vnd.ms-excel" });
            const url = URL.createObjectURL(blob);
            const a = document.createElement("a");
            a.href = url;
            a.download = `Informe_${nombreComunaActual.replace(/\s/g, '_')}.xls`;
            a.click();
            URL.revokeObjectURL(url);
        }

        // ==========================================
        // FUNCIÓN PARA DESCARGAR PDF (AGRUPADO)
        // ==========================================
        function descargarPDF() {
            if (!datosActuales) return alert("No hay datos para exportar.");
            const habitantesPorCasa = agruparHabitantesPorCasa();
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            doc.setFontSize(16);
            doc.setTextColor(220, 53, 69);
            doc.text("Informe de Comunidad", 14, 20);
            doc.setFontSize(12);
            doc.setTextColor(0, 0, 0);
            doc.text(nombreComunaActual, 14, 28);

            doc.autoTable({
                startY: 35,
                head: [['Hogares', 'Personas', 'Gas Tubería', 'Bombona']],
                body: [[datosActuales.total_hogares, datosActuales.total_personas, datosActuales.gas_tuberia, datosActuales.bombona]],
                theme: 'grid'
            });

            let finalY = doc.lastAutoTable.finalY + 10;
            doc.setFontSize(11);
            doc.text("Detalle por Casa y Habitantes", 14, finalY);
            
            let pdfBody = [];
            for (const [casa, personas] of Object.entries(habitantesPorCasa)) {
                let primeraVez = true;
                for (const [nombre, detalles] of Object.entries(personas)) {
                    let medsTexto = detalles.medicamentos.length > 0 ? detalles.medicamentos.join(', ') : 'Ninguno';
                    let probsTexto = detalles.problemas.length > 0 ? detalles.problemas.join(', ') : 'Ninguno';
                    
                    pdfBody.push([
                        primeraVez ? 'Casa ' + casa : '',
                        nombre,
                        'Meds: ' + medsTexto + '\nProbs: ' + probsTexto
                    ]);
                    primeraVez = false;
                }
            }

            if (pdfBody.length === 0) {
                pdfBody.push(['Sin datos', 'Sin datos', 'No hay personas registradas']);
            }

            doc.autoTable({
                startY: finalY + 5,
                head: [['Número de Casa', 'Nombre Completo', 'Medicamentos / Problemas Médicos']],
                body: pdfBody,
                theme: 'striped',
                columnStyles: {
                    0: { fontStyle: 'bold', cellWidth: 30 },
                    2: { cellWidth: 'auto' }
                }
            });

            doc.save(`Informe_${nombreComunaActual.replace(/\s/g, '_')}.pdf`);
        }
        </script>
        <?php endif; ?>
    </main>
</body>
</html>  