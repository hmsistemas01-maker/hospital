<?php
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

$producto_id = $_GET['id'] ?? 0;

// Obtener producto
$stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
$stmt->execute([$producto_id]);
$producto = $stmt->fetch();

if (!$producto) {
    header("Location: productos.php");
    exit;
}

// Obtener códigos del producto
$stmt = $pdo->prepare("
    SELECT * FROM productos_codigos 
    WHERE producto_id = ? 
    ORDER BY es_principal DESC, id
");
$stmt->execute([$producto_id]);
$codigos = $stmt->fetchAll();
?>

<div class="fade-in">
    <h1>🏷️ Códigos de Barras</h1>
    <p>Producto: <strong><?= htmlspecialchars($producto['nombre']) ?></strong></p>
    
    <!-- Formulario para agregar código -->
    <div class="card">
        <h3>➕ Agregar nuevo código</h3>
        <form method="POST" action="guardar_codigo.php">
            <input type="hidden" name="producto_id" value="<?= $producto_id ?>">
            <div class="form-row">
                <div class="form-group">
                    <label>Código de barras</label>
                    <input type="text" name="codigo" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-success">Agregar</button>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Lista de códigos -->
    <div class="card">
        <h3>📋 Códigos registrados</h3>
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Tipo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($codigos as $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c['codigo_barras']) ?></td>
                    <td>
                        <?php if ($c['es_principal']): ?>
                            <span class="badge badge-primary">Principal</span>
                        <?php else: ?>
                            <span class="badge badge-secondary">Secundario</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!$c['es_principal']): ?>
                            <a href="eliminar_codigo.php?id=<?= $c['id'] ?>" 
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('¿Eliminar este código?')">🗑️</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>