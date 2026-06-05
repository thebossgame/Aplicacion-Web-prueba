<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../seccion/inicio_seccion.php");
    exit;
}

 $seccion = $_GET['seccion'] ?? 'inicio';
 $accion = $_GET['accion'] ?? '';

 $usuario_id = $_SESSION['usuario']['id'];
 $usuario_nombre = $_SESSION['usuario']['nombre_completo'];
 $usuario_cedula = $_SESSION['usuario']['cedula'];
 $usuario_casa = $_SESSION['usuario']['numero_casa'];

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

 $mensaje = $_SESSION['ficha_mensaje'] ?? '';
 $tipo_mensaje = $_SESSION['ficha_tipo'] ?? '';
unset($_SESSION['ficha_mensaje'], $_SESSION['ficha_tipo']);

 $publicaciones = [];
if ($seccion === 'inicio') {
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

 $ficha = null;
 $personas = [];
if ($seccion === 'integrantes') {
    $stmt = $pdo->prepare("SELECT * FROM fichas_hogar WHERE usuario_id = ?");
    $stmt->execute([$usuario_id]);
    $ficha = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($ficha) {
        $ficha_id = $ficha['id'];
        $stmt2 = $pdo->prepare("SELECT * FROM personas_hogar WHERE ficha_id = ? ORDER BY id");
        $stmt2->execute([$ficha_id]);
        while ($p = $stmt2->fetch(PDO::FETCH_ASSOC)) {
            $p['medicamentos'] = [];
            $personas[] = $p;
        }
        if (!empty($personas)) {
            $ids = array_column($personas, 'id');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt3 = $pdo->prepare("SELECT * FROM medicamentos_persona WHERE persona_id IN ($placeholders)");
            $stmt3->execute($ids);
            while ($m = $stmt3->fetch(PDO::FETCH_ASSOC)) {
                foreach ($personas as &$per) {
                    if ($per['id'] == $m['persona_id']) {
                        $per['medicamentos'][] = $m;
                    }
                }
                unset($per);
            }
        }
    }
}

 $datos_edicion = 'null';
if ($accion === 'editar' && $ficha) {
    $edicion = [
        'comunidad' => $ficha['comunidad'],
        'tipo_gas' => $ficha['tipo_gas'],
        'bombonas_detalle' => $ficha['bombonas_detalle'],
        'personas' => []
    ];
    foreach ($personas as $p) {
        $meds_nombres = [];
        $otro_med = '';
        foreach ($p['medicamentos'] as $m) {
            $meds_nombres[] = $m['medicamento'];
            if ($m['medicamento'] === 'Otros') $otro_med = $m['otro_detalle'] ?? '';
        }
        $edicion['personas'][] = [
            'nombre_completo' => $p['nombre_completo'],
            'cedula' => $p['cedula'],
            'correo' => $p['correo'],
            'telefono' => $p['telefono'],
            'medicamentos' => $meds_nombres,
            'otro_medicamento' => $otro_med,
            'tiene_problema' => $p['tiene_problema_medico'] == 1,
            'descripcion_problema' => $p['descripcion_problema']
        ];
    }
    $datos_edicion = json_encode($edicion, JSON_UNESCAPED_UNICODE);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Usuario</title>
    <link rel="stylesheet" href="../css/estilos.css">
    <style>
        .menu-center {
            flex: 1;
            text-align: center;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.3rem;
            flex-wrap: wrap;
        }
        .menu-center a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 0.8rem;
            border-radius: 5px;
            transition: background 0.3s;
            font-size: 0.9rem;
        }
        .menu-center a:hover { background: #555; }
        .menu-center a.active { background: #667eea; }

        /* ESTILOS DEL MENÚ DESPLEGABLE */
        .dropdown {
            position: relative;
            display: inline-block;
        }
        .dropbtn {
            color: white !important;
            text-decoration: none;
            padding: 0.5rem 0.8rem;
            border-radius: 5px;
            transition: background 0.3s;
            font-size: 0.9rem;
            cursor: pointer;
            display: inline-block;
        }
        .dropbtn:hover { background: #555; }
        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #333;
            min-width: 320px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.4);
            z-index: 100;
            border-radius: 0 0 8px 8px;
            top: 100%;
            left: 0;
            max-height: 400px;
            overflow-y: auto;
        }
        .dropdown-content a {
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #444;
            font-size: 0.85rem;
        }
        .dropdown-content a:hover {
            background-color: #dc3545;
        }
        .dropdown-content .badge-file {
            background: #667eea;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.7rem;
            font-weight: bold;
        }
        .dropdown:hover .dropdown-content {
            display: block;
        }

        .contenedor-seccion { max-width: 900px; margin: 2rem auto; padding: 0 1rem; }
        .perfil-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); max-width: 500px; margin: 0 auto; text-align: center; }
        .perfil-card .perfil-icono { width: 70px; height: 70px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 2rem; }
        .perfil-card p { margin: 0.8rem 0; font-size: 1.05rem; }
        .perfil-card strong { display: block; font-size: 0.85rem; opacity: 0.85; margin-bottom: 0.3rem; }

        .pub-card { background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); padding: 1.5rem; margin-bottom: 1.5rem; }
        .pub-card h3 { color: #333; margin-bottom: 0.5rem; }
        .pub-card .pub-fecha { color: #888; font-size: 0.85rem; margin-bottom: 0.8rem; }
        .pub-card .pub-contenido { color: #444; line-height: 1.6; white-space: pre-wrap; }
        .pub-card img { width: 100%; max-height: 350px; object-fit: cover; border-radius: 6px; margin: 0.5rem 0; }
        .pub-card iframe { width: 100%; aspect-ratio: 16/9; border: none; border-radius: 6px; margin: 0.5rem 0; }

        .ficha-card { background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); padding: 1.5rem; margin-bottom: 1.5rem; }
        .ficha-header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 1rem 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; }
        .ficha-dato { margin: 0.6rem 0; font-size: 0.95rem; color: #333; }
        .ficha-dato span { color: #667eea; font-weight: bold; }
        .ficha-persona { background: #f8f9fa; border-left: 4px solid #667eea; padding: 1rem; border-radius: 0 6px 6px 0; margin-bottom: 0.8rem; }
        .ficha-persona h4 { color: #667eea; margin-bottom: 0.4rem; }
        .ficha-persona p { margin: 0.2rem 0; font-size: 0.9rem; color: #555; }
        .ficha-meds { color: #d35400; font-size: 0.85rem; margin-top: 0.4rem; }
        .ficha-problema { color: #c0392b; font-size: 0.85rem; margin-top: 0.4rem; background: #fdedec; padding: 0.4rem 0.6rem; border-radius: 4px; }

        .btn-accion { display: inline-block; padding: 0.7rem 1.5rem; border-radius: 5px; text-decoration: none; font-weight: bold; cursor: pointer; border: none; font-size: 0.95rem; transition: opacity 0.2s; }
        .btn-accion:hover { opacity: 0.85; }
        .btn-editar { background: #ffc107; color: #333; }
        .btn-guardar { background: #28a745; color: white; width: auto; }

        .form-integrantes { background: #fff; border: 2px solid #667eea; border-radius: 10px; padding: 1.5rem; max-width: 700px; margin: 0 auto; }
        .form-integrantes label { display: block; margin-bottom: 0.4rem; font-weight: bold; color: #333; }
        .form-integrantes input[type="text"], .form-integrantes input[type="number"], .form-integrantes input[type="email"], .form-integrantes input[type="tel"], .form-integrantes select, .form-integrantes textarea { width: 100%; padding: 0.7rem; border: 1px solid #ddd; border-radius: 5px; font-family: Arial, sans-serif; box-sizing: border-box; margin-bottom: 0.8rem; }
        .persona-bloque { background: #f8f9fa; border-left: 4px solid #28a745; padding: 1rem; border-radius: 0 6px 6px 0; margin-bottom: 1rem; }
        .persona-bloque h4 { color: #28a745; margin-bottom: 0.6rem; cursor: pointer; }
        .persona-campos { display: none; }
        .persona-campos.open { display: block; }
        .meds-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 4px; background: #fff; padding: 8px; border-radius: 6px; border: 1px solid #eee; margin-bottom: 0.5rem; }
        .meds-grid label { display: flex; align-items: center; gap: 6px; font-size: 0.82rem; font-weight: normal; cursor: pointer; padding: 2px; }
        .meds-grid input[type="checkbox"] { width: 16px; height: 16px; }
        .campo-oculto { display: none; }
        @media (max-width: 600px) { .meds-grid { grid-template-columns: 1fr; } .menu-center { gap: 0.1rem; } .menu-center a { padding: 0.4rem 0.5rem; font-size: 0.78rem; } .dropdown-content { min-width: 250px; } }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <img src="../img/logo.jpeg" alt="Logo" class="logo-img">
        </div>
        <div class="menu-center">
            <a href="contenedor_usuario.php" class="<?php echo $seccion === 'inicio' ? 'active' : ''; ?>">Inicio</a>
            <a href="contenedor_usuario.php?seccion=info" class="<?php echo $seccion === 'info' ? 'active' : ''; ?>">Información</a>
            <a href="contenedor_usuario.php?seccion=integrantes" class="<?php echo $seccion === 'integrantes' ? 'active' : ''; ?>">Integrantes del Hogar</a>
            
            <!-- MENÚ DESPLEGABLE DE CARTAS -->
<div class="dropdown">
    <a href="#" class="dropbtn">Solicitud Carta de Residencia ▾</a>
    <div class="dropdown-content">
        <a href="descargar_carta.php?archivo=armando">Armando Molero 1 <span class="badge-file">WORD</span></a>
        <a href="descargar_carta.php?archivo=ernesto">Ernesto Che Guevara Siglo XXI <span class="badge-file">WORD</span></a>
        <a href="descargar_carta.php?archivo=exito">Exito Revolucionario <span class="badge-file">PDF</span></a>
        <a href="descargar_carta.php?archivo=fuerza">Fuerza Patriota <span class="badge-file">WORD</span></a>
        <a href="descargar_carta.php?archivo=general">General en Jefe Rafael Urdaneta <span class="badge-file">PDF</span></a>
        <a href="descargar_carta.php?archivo=rosa">Rosa Ines de Chavez <span class="badge-file">PDF</span></a>
        <a href="descargar_carta.php?archivo=sitio">Sitio Revolucionado <span class="badge-file">PDF</span></a>
    </div>
</div>
        </div>
        <div class="menu-right">
            <a href="cerrar_sesion.php">Cerrar Sesión</a>
        </div>
    </header>

    <main class="contenedor-seccion">
        <?php if ($mensaje): ?>
            <div class="mensaje <?= $tipo_mensaje ?>" style="margin-bottom:1.5rem;"><?= $mensaje ?></div>
        <?php endif; ?>

        <?php if ($seccion === 'inicio'): ?>
        <h2 style="color:#333; margin-bottom:1.5rem;">Periódico Digital</h2>
        <?php if (empty($publicaciones)): ?>
            <div style="text-align:center; padding:3rem; color:#888;">
                <p style="font-size:2rem; margin-bottom:0.5rem;">📰</p>
                <p>No hay publicaciones aún.</p>
            </div>
        <?php else: ?>
            <?php foreach ($publicaciones as $pub): ?>
            <div class="pub-card">
                <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.5rem;">
                    <span style="background:#667eea; color:#fff; width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:0.8rem;">A</span>
                    <span style="font-size:0.85rem; color:#888;"><?= date('d/m/Y', strtotime($pub['fecha_publicacion'])) ?></span>
                </div>
                <h3><?= htmlspecialchars($pub['titulo']) ?></h3>
                <div class="pub-contenido"><?= htmlspecialchars($pub['contenido']) ?></div>
                <?php foreach ($pub['imagenes'] as $img): ?>
                    <!-- IMPORTANTE: Se añade ../ para arreglar la imagen en la vista de usuario -->
                    <img src="../<?= htmlspecialchars($img) ?>" alt="Imagen">
                <?php endforeach; ?>
                <?php foreach ($pub['videos'] as $url): ?>
                    <?php
                    $yt_id = '';
                    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/', $url, $m)) { $yt_id = $m[1]; }
                    if ($yt_id): ?>
                    <iframe src="https://www.youtube.com/embed/<?= $yt_id ?>" allowfullscreen></iframe>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php elseif ($seccion === 'info'): ?>
        <h2 style="color:#333; margin-bottom:1.5rem;">Mi Perfil</h2>
        <div class="perfil-card">
            <div class="perfil-icono">👤</div>
            <p><strong>Nombre Completo</strong><?= htmlspecialchars($usuario_nombre) ?></p>
            <p><strong>Cédula</strong><?= htmlspecialchars($usuario_cedula) ?></p>
            <p><strong>Número de Casa</strong><?= htmlspecialchars($usuario_casa) ?></p>
        </div>

        <?php elseif ($seccion === 'integrantes'): ?>
        <?php if ($ficha && $accion !== 'editar'): ?>
        <h2 style="color:#333; margin-bottom:0.3rem;">Ficha del Hogar</h2>
        <p style="color:#888; margin-bottom:1.5rem;">Casa <?= htmlspecialchars($ficha['numero_casa']) ?></p>
        <div class="ficha-card">
            <div class="ficha-header">
                <strong>Comunidad:</strong> <?= htmlspecialchars($ficha['comunidad']) ?>
                &nbsp;|&nbsp; <strong>Gas:</strong> <?= htmlspecialchars($ficha['tipo_gas']) ?>
                <?php if ($ficha['tipo_gas'] === 'Bombona' && $ficha['bombonas_detalle']): ?>
                    &nbsp;|&nbsp; <strong>Bombonas:</strong> <?= htmlspecialchars($ficha['bombonas_detalle']) ?>
                <?php endif; ?>
                &nbsp;|&nbsp; <strong>Personas:</strong> <?= count($personas) ?>
            </div>
            <?php foreach ($personas as $idx => $p): ?>
            <div class="ficha-persona">
                <h4>Persona <?= $idx + 1 ?>: <?= htmlspecialchars($p['nombre_completo']) ?></h4>
                <p><span>Cédula:</span> <?= htmlspecialchars($p['cedula']) ?></p>
                <p><span>Casa:</span> <?= htmlspecialchars($ficha['numero_casa']) ?></p>
                <?php if ($p['correo']): ?><p><span>Correo:</span> <?= htmlspecialchars($p['correo']) ?></p><?php endif; ?>
                <?php if ($p['telefono']): ?><p><span>Teléfono:</span> <?= htmlspecialchars($p['telefono']) ?></p><?php endif; ?>
                <?php if (!empty($p['medicamentos'])): ?>
                    <div class="ficha-meds"><strong>Medicamentos:</strong><?php $meds_texto = []; foreach ($p['medicamentos'] as $m) { if ($m['medicamento'] === 'Otros' && $m['otro_detalle']) { $meds_texto[] = 'Otros: ' . htmlspecialchars($m['otro_detalle']); } else { $meds_texto[] = htmlspecialchars($m['medicamento']); } } echo implode(', ', $meds_texto); ?></div>
                <?php endif; ?>
                <?php if ($p['tiene_problema_medico'] == 1 && $p['descripcion_problema']): ?>
                    <div class="ficha-problema"><strong>Problema médico:</strong> <?= htmlspecialchars($p['descripcion_problema']) ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <a href="contenedor_usuario.php?seccion=integrantes&accion=editar" class="btn-accion btn-editar" style="margin-top:1rem;">✏️ Editar Información</a>
        </div>
        <?php else: ?>
        <h2 style="color:#333; margin-bottom:0.3rem;">Integrantes del Hogar</h2>
        <p style="color:#888; margin-bottom:1.5rem;">Casa <?= htmlspecialchars($usuario_casa) ?></p>
        <form id="form-integrantes" method="POST" action="guardar_informacion.php" class="form-integrantes">
            <input type="hidden" name="usuario_id" value="<?= $usuario_id ?>">
            <input type="hidden" name="numero_casa" value="<?= htmlspecialchars($usuario_casa) ?>">
            <label>1. Comunidad</label>
            <select name="comunidad" required>
                <option value="">Selecciona una comunidad</option>
                <?php $comunidades = ['Rosa Ines Chavez','Exito Revolucionario','Fuerza Patriotica','El Sitio Revolucionario','Che Guevara Siglo XXI','General En Jefe Rafael Urdaneta','Armando Molero 1']; foreach ($comunidades as $com): $sel = ($accion === 'editar' && $ficha && $ficha['comunidad'] === $com) ? 'selected' : ''; echo '<option value="'.htmlspecialchars($com).'" '.$sel.'>'.htmlspecialchars($com).'</option>'; endforeach; ?>
            </select>
            <label>2. ¿Cuántas personas viven en la casa? (máximo 30)</label>
            <input type="number" id="int-cantidad" min="1" max="30" required value="<?= $accion === 'editar' && $ficha ? count($personas) : '1' ?>">
            <div id="personas-container"></div>
            <label>5. Tipo de Gas</label>
            <div style="display:flex; gap:1.5rem; margin-bottom:0.8rem;">
                <label style="display:flex;align-items:center;gap:6px;font-weight:normal;cursor:pointer;"><input type="radio" name="tipo_gas" value="Gas de tuberia" required <?= ($accion === 'editar' && $ficha && $ficha['tipo_gas'] === 'Gas de tuberia') ? 'checked' : '' ?> onchange="toggleBombona()"> Gas de tubería</label>
                <label style="display:flex;align-items:center;gap:6px;font-weight:normal;cursor:pointer;"><input type="radio" name="tipo_gas" value="Bombona" <?= ($accion === 'editar' && $ficha && $ficha['tipo_gas'] === 'Bombona') ? 'checked' : '' ?> onchange="toggleBombona()"> Bombona</label>
            </div>
            <div id="bombona-field" class="campo-oculto"><label>Describa qué bombonas tiene</label><textarea name="bombonas_detalle" rows="2" placeholder="Ej: Bombona de 10kg..."><?= $accion === 'editar' && $ficha ? htmlspecialchars($ficha['bombonas_detalle']) : '' ?></textarea></div>
            <button type="submit" class="btn-accion btn-guardar" style="margin-top:1rem;">💾 Guardar</button>
        </form>
        <?php endif; ?>
        <?php endif; ?>
    </main>

    <script>
    const datosEdicion = <?= $datos_edicion ?>;
    const MEDICAMENTOS = ['Acetaminofen','Ibuprofeno','Valsartan','Omeprazol','Amoxicilina','Aspirina','Atorvastatina','Enalapril','Loratadina','Diclofenal','Ciprofloxacina','Salbutamol','Levotiroxina','Otros'];

    function toggleBombona() { const val = document.querySelector('input[name="tipo_gas"]:checked')?.value; document.getElementById('bombona-field').style.display = val === 'Bombona' ? 'block' : 'none'; }
    function toggleCampos(idx) { const campos = document.getElementById('campos-' + idx); const flecha = document.getElementById('flecha-' + idx); if (campos.classList.contains('open')) { campos.classList.remove('open'); flecha.textContent = '▶'; } else { campos.classList.add('open'); flecha.textContent = '▼'; } }
    function toggleOtroMed(idx) { const cb = document.querySelector('input[data-idx="'+idx+'"][data-med="Otros"]'); document.getElementById('otro-med-'+idx).style.display = (cb && cb.checked) ? 'block' : 'none'; }
    function toggleProblema(idx) { const cb = document.getElementById('problema-cb-'+idx); document.getElementById('problema-field-'+idx).style.display = (cb && cb.checked) ? 'block' : 'none'; }

    function generarCampos(cantidad, datosExistentes) {
        cantidad = Math.max(1, Math.min(30, parseInt(cantidad) || 1));
        const container = document.getElementById('personas-container');
        let html = '';
        for (let i = 0; i < cantidad; i++) {
            const p = (datosExistentes && datosExistentes[i]) || {};
            const meds = p.medicamentos || [];
            const tieneProblema = p.tiene_problema || false;
            const primerOpen = i === 0 ? 'open' : '';
            html += '<div class="persona-bloque"><h4 onclick="toggleCampos('+i+')"><span id="flecha-'+i+'" style="font-size:0.7rem;margin-right:6px;">'+(primerOpen?'▼':'▶')+'</span>Persona '+(i+1)+(p.nombre_completo?' - '+p.nombre_completo:'')+'</h4><div id="campos-'+i+'" class="persona-campos '+primerOpen+'">';
            html += '<label>Nombre Completo</label><input type="text" name="persona_nombre['+i+']" value="'+(p.nombre_completo||'')+'" required>';
            html += '<label>Cédula</label><input type="text" name="persona_cedula['+i+']" value="'+(p.cedula||'')+'" required>';
            html += '<label>Correo Electrónico</label><input type="email" name="persona_correo['+i+']" value="'+(p.correo||'')+'">';
            html += '<label>Teléfono</label><input type="tel" name="persona_telefono['+i+']" value="'+(p.telefono||'')+'">';
            html += '<label style="margin-top:0.6rem;">3. Consume algún medicamento</label><div class="meds-grid">';
            MEDICAMENTOS.forEach(function(med) { const checked = meds.includes(med) ? 'checked' : ''; const esOtros = med === 'Otros'; const onchange = esOtros ? ' onchange="toggleOtroMed('+i+')"' : ''; html += '<label><input type="checkbox" name="medicamentos['+i+'][]" value="'+med+'" data-idx="'+i+'" data-med="'+med+'" '+checked+onchange+'> '+med+'</label>'; });
            html += '</div><div id="otro-med-'+i+'" class="campo-oculto"><input type="text" name="otro_medicamento['+i+']" placeholder="Especifique el medicamento" value="'+(p.otro_medicamento||'')+'"></div>';
            html += '<label style="margin-top:0.6rem;">4. Presenta problemas médicos</label><label style="display:flex;align-items:center;gap:6px;font-weight:normal;cursor:pointer;"><input type="checkbox" name="tiene_problema['+i+']" id="problema-cb-'+i+'" value="1" '+(tieneProblema?'checked':'')+' onchange="toggleProblema('+i+')"> Sí</label>';
            html += '<div id="problema-field-'+i+'" class="campo-oculto"><textarea name="descripcion_problema['+i+']" rows="2" placeholder="Describa el problema médico">'+(p.descripcion_problema||'')+'</textarea></div>';
            html += '</div></div>';
        }
        container.innerHTML = html;
    }

    document.getElementById('int-cantidad').addEventListener('input', function() { generarCampos(this.value, null); });
    document.addEventListener('DOMContentLoaded', function() { if (datosEdicion) { generarCampos(datosEdicion.personas.length, datosEdicion.personas); } else { generarCampos(document.getElementById('int-cantidad').value, null); } });
    </script>
</body>
</html>