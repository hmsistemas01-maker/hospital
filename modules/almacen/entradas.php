<?php
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Obtener productos de ALMACÉN (solo los que tienen departamento = 'almacen')
$productos = $pdo->query("
    SELECT p.*, ca.nombre as categoria_nombre
    FROM productos p
    LEFT JOIN categorias_almacen ca ON p.categoria_almacen_id = ca.id
    WHERE p.departamento = 'almacen' AND p.activo = 1
    ORDER BY p.nombre
")->fetchAll();

// Obtener proveedores
$proveedores = $pdo->query("SELECT id, nombre FROM proveedores WHERE activo = 1 ORDER BY nombre")->fetchAll();
?>

<div class="fade-in">
    <div class="d-flex justify-between align-center mb-4">
        <div>
            <h1>📦 Registrar Entrada a Almacén</h1>
            <p class="text-gray-600">Registro de productos generales</p>
        </div>
        <a href="index.php" class="btn btn-outline">
            <span>←</span> Volver
        </a>
    </div>

    <div class="card">
        <form method="POST" action="procesar_entrada.php">
            <input type="hidden" name="departamento" value="almacen">
            
            <div class="form-row">
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
                    <label>Factura/Referencia</label>
                    <input type="text" name="referencia" class="form-control">
                </div>
            </div>

            <div class="form-group">
                <label>Producto</label>
                <select name="producto_id" class="form-control" required>
                    <option value="">Seleccionar producto</option>
                    <?php foreach ($productos as $p): ?>
                        <option value="<?= $p['id'] ?>">
                            <?= htmlspecialchars($p['codigo']) ?> - <?= htmlspecialchars($p['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Cantidad</label>
                    <input type="number" name="cantidad" class="form-control" required min="1" value="1">
                </div>
                
                <div class="form-group">
                    <label>Precio unitario</label>
                    <input type="number" name="precio_unitario" class="form-control" step="0.01" value="0.00">
                </div>
            </div>

            <div class="form-group">
                <label>Observaciones</label>
                <textarea name="observaciones" class="form-control" rows="2"></textarea>
            </div>

            <button type="submit" class="btn btn-success">Registrar Entrada</button>
            <a href="index.php" class="btn btn-outline">Cancelar</a>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>