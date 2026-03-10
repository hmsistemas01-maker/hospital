<?php
require_once '../../../config/config.php';
$modulo_requerido = 'farmacia';
require_once '../../../includes/auth.php';
require_once '../../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../productos.php");
    exit;
}

$codigo = trim($_POST['codigo'] ?? '');
$nombre = trim($_POST['nombre'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$categoria_id = $_POST['categoria_id'] ?? null;
$unidad_medida = $_POST['unidad_medida'] ?? 'pieza';
$stock_minimo = $_POST['stock_minimo'] ?? 10;
$stock_maximo = $_POST['stock_maximo'] ?? 1000;
$precio_unitario = $_POST['precio_unitario'] ?? 0;
$ubicacion = trim($_POST['ubicacion'] ?? '');

// NOTA: LOS CAMPOS requiere_receta, control_lote, control_vencimiento
// NO SE GUARDAN EN productos, están en categorias_productos
// Por eso NO los incluimos en el INSERT

try {
    // Validar campos obligatorios
    if (empty($codigo) || empty($nombre) || empty($categoria_id)) {
        throw new Exception("Código, nombre y categoría son obligatorios");
    }

    // Verificar si el código ya existe
    $stmt = $pdo->prepare("SELECT id FROM productos WHERE codigo = ?");
    $stmt->execute([$codigo]);
    if ($stmt->fetch()) {
        throw new Exception("El código '$codigo' ya está registrado");
    }

    // Insertar producto - SIN los campos de control
    $stmt = $pdo->prepare("
        INSERT INTO productos 
        (codigo, nombre, descripcion, categoria_id, unidad_medida, 
         stock_minimo, stock_maximo, precio_unitario, ubicacion, activo, fecha_registro)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
    ");

    $stmt->execute([
        $codigo, $nombre, $descripcion, $categoria_id, $unidad_medida,
        $stock_minimo, $stock_maximo, $precio_unitario, $ubicacion
    ]);

    header("Location: ../productos.php?tab=productos&success=Producto guardado correctamente");

} catch (Exception $e) {
    header("Location: ../productos.php?tab=nuevo_producto&error=" . urlencode($e->getMessage()));
}
exit;