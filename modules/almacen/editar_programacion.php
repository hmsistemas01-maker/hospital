<?php
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

if (!isset($_GET['id'])) {
    header("Location: programaciones.php");
    exit;
}

$id = $_GET['id'];

// Obtener programación
$stmt = $pdo->prepare("
    SELECT ep.*, p.nombre as producto_nombre, p.codigo
    FROM entradas_programadas ep
    JOIN productos p ON ep.producto_id = p.id
    WHERE ep.id = ?
");
$stmt->execute([$id]);
$programacion = $stmt->fetch();

if (!$programacion) {
    header("Location: programaciones.php?error=Programación no encontrada");
    exit;
}

// Obtener productos y proveedores
$productos = $pdo->query("SELECT id, codigo, nombre FROM productos WHERE activo = 1 ORDER BY nombre")->fetchAll();
$proveedores = $pdo->query("SELECT id, nombre FROM proveedores WHERE activo = 1 ORDER BY nombre")->fetchAll();
?>

<div class="fade-in">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl);">
        <div>
            <h1>✏️ Editar Programación</h1>
            <p style="color: var(--gray-600);">Modificando entrada programada</p>
        </div>
        <a href="programaciones.php" class="btn btn-outline">← Volver</a>
    </div>

    <div class="card" style="max-width: 600px; margin: 0 auto;">
        <form method="POST" action="actualizar_programacion.php">
            <input type="hidden" name="id" value="<?= $programacion['id'] ?>">
            
            <div class="form-group">
                <label class="required">Producto</label>
                <select name="producto_id" class="form-control" required>
                    <?php foreach ($productos as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $p['id'] == $programacion['producto_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['codigo']) ?> - <?= htmlspecialchars($p['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="required">Cantidad</label>
                <input type="number" name="cantidad" class="form-control" required value="<?= $programacion['cantidad'] ?>">
            </div>
            
            <div class="form-group">
                <label class="required">Fecha programada</label>
                <input type="date" name="fecha_programada" class="form-control" required value="<?= $programacion['fecha_programada'] ?>">
            </div>
            
            <div class="form-group">
                <label>Proveedor</label>
                <select name="proveedor_id" class="form-control">
                    <option value="">-- Sin proveedor --</option>
                    <?php foreach ($proveedores as $prov): ?>
                        <option value="<?= $prov['id'] ?>" <?= $prov['id'] == $programacion['proveedor_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($prov['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Observaciones</label>
                <textarea name="observaciones" class="form-control" rows="2"><?= htmlspecialchars($programacion['observaciones'] ?? '') ?></textarea>
            </div>
            
            <div style="display: flex; gap: var(--spacing-md);">
                <button type="submit" class="btn btn-primary">Actualizar</button>
                <a href="programaciones.php" class="btn btn-outline">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>