<?php
header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($_GET['comuna']) || empty($_GET['comuna'])) {
        echo json_encode(["error" => "No comuna"]);
        exit;
    }

    $comuna = $_GET['comuna'];

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "gestion_comunitaria";

    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Total hogares
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM fichas_hogar WHERE comunidad = ?");
    $stmt->execute([$comuna]);
    $total_hogares = (int)$stmt->fetchColumn();

    // Total personas
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total FROM personas_hogar ph
        INNER JOIN fichas_hogar fh ON ph.ficha_id = fh.id
        WHERE fh.comunidad = ?
    ");
    $stmt->execute([$comuna]);
    $total_personas = (int)$stmt->fetchColumn();

    // Gas tubería vs bombona
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM fichas_hogar WHERE comunidad = ? AND tipo_gas = 'Gas de tuberia'");
    $stmt->execute([$comuna]);
    $gas_tuberia = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM fichas_hogar WHERE comunidad = ? AND tipo_gas = 'Bombona'");
    $stmt->execute([$comuna]);
    $bombona = (int)$stmt->fetchColumn();

    // Personas con medicamentos
    $personas_medicamentos = [];
    $stmt = $pdo->prepare("
        SELECT ph.nombre_completo, fh.numero_casa, mp.medicamento, mp.otro_detalle
        FROM personas_hogar ph
        INNER JOIN fichas_hogar fh ON ph.ficha_id = fh.id
        INNER JOIN medicamentos_persona mp ON mp.persona_id = ph.id
        WHERE fh.comunidad = ?
        ORDER BY ph.nombre_completo ASC
    ");
    $stmt->execute([$comuna]);

    $personas_map = [];
    while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $clave = $fila['nombre_completo'] . '|' . $fila['numero_casa'];
        if (!isset($personas_map[$clave])) {
            $personas_map[$clave] = [
                'nombre' => $fila['nombre_completo'],
                'casa' => $fila['numero_casa'],
                'medicamentos' => []
            ];
        }
        $med_texto = $fila['medicamento'];
        if ($fila['medicamento'] === 'Otros' && $fila['otro_detalle']) {
            $med_texto .= ': ' . $fila['otro_detalle'];
        }
        $personas_map[$clave]['medicamentos'][] = $med_texto;
    }
    foreach ($personas_map as &$pm) {
        $pm['medicamentos'] = implode(', ', $pm['medicamentos']);
    }
    unset($pm);
    $personas_medicamentos = array_values($personas_map);

    // Personas con problemas médicos
    $personas_problemas = [];
    $stmt = $pdo->prepare("
        SELECT ph.nombre_completo, fh.numero_casa, ph.descripcion_problema
        FROM personas_hogar ph
        INNER JOIN fichas_hogar fh ON ph.ficha_id = fh.id
        WHERE fh.comunidad = ? AND ph.tiene_problema_medico = 1
        AND ph.descripcion_problema IS NOT NULL AND ph.descripcion_problema != ''
        ORDER BY ph.nombre_completo ASC
    ");
    $stmt->execute([$comuna]);
    while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $personas_problemas[] = [
            'nombre' => $fila['nombre_completo'],
            'casa' => $fila['numero_casa'],
            'problema' => $fila['descripcion_problema']
        ];
    }

    echo json_encode([
        "total_hogares" => $total_hogares,
        "total_personas" => $total_personas,
        "gas_tuberia" => $gas_tuberia,
        "bombona" => $bombona,
        "personas_medicamentos" => $personas_medicamentos,
        "personas_problemas" => $personas_problemas
    ], JSON_UNESCAPED_UNICODE);

} catch(Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}