<?php
require_once '../../../config/config.php';
$modulo_requerido = 'farmacia';
require_once '../../../includes/auth.php';
require_once '../../../config/db.php';

header('Content-Type: application/json');

$termino = $_GET['q'] ?? '';

if (strlen($termino) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            id,
            nombre,
            telefono,
            fecha_nacimiento,
            sexo
        FROM pacientes
        WHERE nombre LIKE ? OR telefono LIKE ? OR id LIKE ?
        ORDER BY 
            CASE 
                WHEN id = ? THEN 1
                WHEN telefono = ? THEN 2
                ELSE 3
            END,
            nombre
        LIMIT 10
    ");
    
    $busqueda = "%$termino%";
    $stmt->execute([$busqueda, $busqueda, $busqueda, $termino, $termino]);
    $resultados = $stmt->fetchAll();
    
    // Formatear fecha de nacimiento
    foreach ($resultados as &$r) {
        if ($r['fecha_nacimiento']) {
            $r['fecha_nacimiento'] = date('d/m/Y', strtotime($r['fecha_nacimiento']));
        }
        $r['edad'] = $r['fecha_nacimiento'] ? calcularEdad($r['fecha_nacimiento']) : 'N/A';
    }
    
    echo json_encode($resultados);
    
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

function calcularEdad($fecha_nacimiento) {
    if (!$fecha_nacimiento) return 'N/A';
    $fecha = new DateTime($fecha_nacimiento);
    $hoy = new DateTime();
    $edad = $hoy->diff($fecha);
    return $edad->y . ' años';
}
?>