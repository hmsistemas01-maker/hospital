<?php
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

$producto_id = $_GET['producto'] ?? null;
$faltante = $_GET['faltante'] ?? null;
$todos = isset($_GET['todos']);

// Obtener productos para el select
$productos = $pdo->query("
    SELECT id, codigo, nombre, unidad_medida
    FROM productos 
    WHERE activo = 1 
    ORDER BY nombre
")->fetchAll();

// Obtener proveedores
$proveedores = $pdo->query("SELECT * FROM proveedores WHERE activo = 1 ORDER BY nombre")->fetchAll();
?>

<div class="fade-in">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl);">
        <div>
            <h1>📅 Programar Entrada de Productos</h1>
            <p style="color: var(--gray-600);">Planifica las compras y recepciones futuras</p>
        </div>
        <a href="index.php" class="btn btn-outline">← Volver</a>
    </div>

    <div class="card" style="max-width: 600px; margin: 0 auto;">
        <h3 style="color: var(--primary);">Nueva programación</h3>
        
        <form method="POST" action="guardar_programacion.php">
            <div class="form-group">
                <label class="required">Producto</label>
                <select name="producto_id" class="form-control" required <?= $todos ? 'disabled' : '' ?>>
                    <option value="">Seleccionar producto...</option>
                    <?php foreach ($productos as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $producto_id == $p['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['codigo']) ?> - <?= htmlspecialchars($p['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="required">Cantidad</label>
                <input type="number" name="cantidad" class="form-control" required min="1" value="<?= $faltante ?: 1 ?>">
            </div>
            
            <div class="form-group">
                <label class="required">Fecha programada</label>
                <input type="date" name="fecha_programada" class="form-control" required value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
                <small class="text-muted">Fecha estimada de recepción</small>
            </div>
            
            <div class="form-group">
                <label>Proveedor</label>
                <select name="proveedor_id" class="form-control">
                    <option value="">-- Sin proveedor --</option>
                    <?php foreach ($proveedores as $prov): ?>
                        <option value="<?= $prov['id'] ?>"><?= htmlspecialchars($prov['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Observaciones</label>
                <textarea name="observaciones" class="form-control" rows="2" placeholder="Notas adicionales..."></textarea>
            </div>
            
            <div style="display: flex; gap: var(--spacing-md); margin-top: var(--spacing-lg);">
                <button type="submit" class="btn btn-success" style="flex: 1;">📅 Programar Entrada</button>
                <a href="index.php" class="btn btn-outline">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>