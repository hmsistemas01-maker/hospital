<?php
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Obtener categorías
$categorias = $pdo->query("
    SELECT * FROM categorias_productos 
    ORDER BY nombre
")->fetchAll();

// Obtener productos con stock calculado desde lotes
$productos = $pdo->query("
    SELECT 
        p.*,
        c.nombre as categoria_nombre,
        -- Calcular stock actual desde lote
        COALESCE((SELECT SUM(cantidad_actual) FROM lote WHERE producto_id = p.id), 0) as stock_actual,
        CASE 
            WHEN COALESCE((SELECT SUM(cantidad_actual) FROM lote WHERE producto_id = p.id), 0) <= p.stock_minimo THEN 'bajo'
            WHEN COALESCE((SELECT SUM(cantidad_actual) FROM lote WHERE producto_id = p.id), 0) >= p.stock_maximo THEN 'exceso'
            ELSE 'normal'
        END as estado_stock,
        CASE
            WHEN COALESCE((SELECT SUM(cantidad_actual) FROM lote WHERE producto_id = p.id), 0) < p.stock_minimo 
            THEN p.stock_minimo - COALESCE((SELECT SUM(cantidad_actual) FROM lote WHERE producto_id = p.id), 0)
            ELSE 0
        END as cantidad_faltante
    FROM productos p
    LEFT JOIN categorias_productos c ON p.categoria_id = c.id
    WHERE p.activo = 1
    ORDER BY 
        CASE 
            WHEN COALESCE((SELECT SUM(cantidad_actual) FROM lote WHERE producto_id = p.id), 0) <= p.stock_minimo THEN 1
            WHEN COALESCE((SELECT SUM(cantidad_actual) FROM lote WHERE producto_id = p.id), 0) >= p.stock_maximo THEN 3
            ELSE 2
        END,
        p.nombre
")->fetchAll();

// Calcular estadísticas
$stats = [];
$stats['total_productos'] = count($productos);
$stats['stock_bajo'] = 0;
$stats['valor_total'] = 0;

foreach ($productos as $p) {
    if ($p['estado_stock'] == 'bajo') $stats['stock_bajo']++;
    $stats['valor_total'] += ($p['precio_unitario'] * $p['stock_actual']);
}

$valor_total_formateado = '$' . number_format($stats['valor_total'], 2);
?>


<div class="fade-in">
    <!-- Header con estadísticas -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl); flex-wrap: wrap; gap: var(--spacing-md);">
        <div>
            <h1>📦 Gestión de Productos</h1>
            <p style="color: var(--gray-600);">Catálogo general de productos</p>
        </div>
        <div style="display: flex; gap: var(--spacing-sm); flex-wrap: wrap;">
            <span class="badge badge-primary">Total: <?= $stats['total_productos'] ?></span>
            <span class="badge badge-warning">Stock bajo: <?= $stats['stock_bajo'] ?></span>
            <span class="badge badge-success">Valor: <?= $valor_total_formateado ?></span>
        </div>
    </div>

    <!-- Mensajes -->
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success">
            ✅ <?php 
            if ($_GET['msg'] == 'guardado') echo 'Producto guardado correctamente';
            if ($_GET['msg'] == 'actualizado') echo 'Producto actualizado correctamente';
            if ($_GET['msg'] == 'entrada') echo 'Entrada registrada correctamente';
            if ($_GET['msg'] == 'salida') echo 'Salida registrada correctamente';
            if ($_GET['msg'] == 'codigo_agregado') echo 'Código agregado correctamente';
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger">
            ❌ <?= htmlspecialchars($_GET['error']) ?>
        </div>
    <?php endif; ?>

    <!-- Botones de acción -->
    <div style="margin-bottom: var(--spacing-lg); display: flex; gap: var(--spacing-sm); flex-wrap: wrap;">
        <a href="#" onclick="mostrarFormularioProducto()" class="btn btn-success">
            <span>➕</span> Nuevo Producto
        </a>
        <a href="entradas.php" class="btn btn-primary">
            <span>⬆️</span> Registrar Entrada
        </a>
        <a href="salidas.php" class="btn btn-warning">
            <span>⬇️</span> Registrar Salida
        </a>
        <a href="movimientos.php" class="btn btn-outline">
            <span>📋</span> Ver Movimientos
        </a>
    </div>

    <!-- Formulario nuevo producto (oculto inicialmente) -->
    <div id="formProducto" style="display: none; margin-bottom: var(--spacing-xl);">
        <div class="card">
            <h3 style="color: var(--primary); margin-bottom: var(--spacing-lg);">➕ Nuevo Producto</h3>
            
            <form method="POST" action="guardar_producto.php" id="formNuevoProducto">
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Código principal</label>
                        <input type="text" name="codigo" class="form-control" required 
                               placeholder="Código de barras principal">
                    </div>
                    
                    <div class="form-group">
                        <label>Nombre</label>
                        <input type="text" name="nombre" class="form-control" required 
                               placeholder="Nombre del producto">
                    </div>
                </div>
                
                <!-- Campo para código secundario (opcional) -->
                <div class="form-group">
                    <label>Código secundario (opcional)</label>
                    <input type="text" name="codigo_secundario" class="form-control" 
                           placeholder="Otro código de barras si existe">
                    <small class="text-muted">Si el producto tiene otro código, puedes registrarlo aquí</small>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Categoría</label>
                        <select name="categoria_id" class="form-control" required>
                            <option value="">Seleccionar categoría...</option>
                            <?php foreach ($categorias as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Unidad de medida</label>
                        <select name="unidad_medida" class="form-control" required>
                            <option value="pieza">Pieza</option>
                            <option value="caja">Caja</option>
                            <option value="paquete">Paquete</option>
                            <option value="litro">Litro</option>
                            <option value="kilogramo">Kilogramo</option>
                            <option value="metro">Metro</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Stock inicial</label>
                        <input type="number" name="stock_actual" class="form-control" value="0" min="0">
                    </div>
                    
                    <div class="form-group">
                        <label>Precio unitario</label>
                        <input type="number" step="0.01" name="precio_unitario" class="form-control" value="0.00">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Stock mínimo</label>
                        <input type="number" name="stock_minimo" class="form-control" value="5">
                    </div>
                    
                    <div class="form-group">
                        <label>Stock máximo</label>
                        <input type="number" name="stock_maximo" class="form-control" value="100">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Ubicación</label>
                    <input type="text" name="ubicacion" class="form-control" placeholder="Ej: Estante A-1">
                </div>
                
                <button type="submit" class="btn btn-success">Guardar Producto</button>
                <button type="button" class="btn btn-outline" onclick="ocultarFormularioProducto()">Cancelar</button>
            </form>
        </div>
    </div>

    <!-- Tabla de productos -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-lg);">
            <h3>📋 Inventario Actual</h3>
            <input type="text" id="buscarProducto" class="form-control" placeholder="🔍 Buscar..." style="width: 250px;">
        </div>
        
        <?php if (empty($productos)): ?>
            <div class="alert alert-info">No hay productos registrados</div>
        <?php else: ?>
            <div class="table-container">
                <table id="tablaProductos">
                    <thead>
                        <tr>
                            <th>Códigos</th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Stock</th>
                            <th>Precio</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $p): 
                            // Obtener códigos secundarios del producto
                            $stmt = $pdo->prepare("
                                SELECT codigo_barras FROM productos_codigos 
                                WHERE producto_id = ? AND es_principal = 0
                            ");
                            $stmt->execute([$p['id']]);
                            $secundarios = $stmt->fetchAll();
                            
                            $clase_fila = $p['estado_stock'] == 'bajo' ? 'warning' : '';
                        ?>
                            <tr class="<?= $clase_fila ?>">
                                <td>
                                    <strong>🔵 <?= htmlspecialchars($p['codigo']) ?></strong>
                                    <?php if ($secundarios): ?>
                                        <br>
                                        <small style="color: var(--gray-600);">
                                        <?php foreach ($secundarios as $s): ?>
                                            🟡 <?= htmlspecialchars($s['codigo_barras']) ?><br>
                                        <?php endforeach; ?>
                                        </small>
                                    <?php endif; ?>
                                  
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($p['nombre']) ?></strong>
                                    <?php if ($p['descripcion']): ?>
                                        <br><small><?= htmlspecialchars(substr($p['descripcion'], 0, 50)) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($p['categoria_nombre'] ?? '') ?></td>
                                <td>
                                    <?= $p['stock_actual'] ?> <small><?= $p['unidad_medida'] ?></small>
                                    <?php if ($p['estado_stock'] == 'bajo' && $p['cantidad_faltante'] > 0): ?>
                                        <br><small class="text-danger">Faltan <?= $p['cantidad_faltante'] ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>$<?= number_format($p['precio_unitario'], 2) ?></td>
                                <td>
                                    <?php if ($p['estado_stock'] == 'bajo'): ?>
                                        <span class="badge badge-danger">Stock bajo</span>
                                    <?php elseif ($p['estado_stock'] == 'exceso'): ?>
                                        <span class="badge badge-info">Stock alto</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Normal</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: var(--spacing-xs);">
                                        <a href="editar_producto.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline">✏️</a>
                                        <a href="entradas.php?producto=<?= $p['id'] ?>" class="btn btn-sm btn-success">⬆️</a>
                                        <a href="salidas.php?producto=<?= $p['id'] ?>" class="btn btn-sm btn-warning">⬇️</a>
                                        <a href="codigos_producto.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline">🏷️</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function mostrarFormularioProducto() {
    document.getElementById('formProducto').style.display = 'block';
    window.scrollTo(0, document.getElementById('formProducto').offsetTop - 100);
}

function ocultarFormularioProducto() {
    document.getElementById('formProducto').style.display = 'none';
    document.getElementById('formNuevoProducto').reset();
}

// Búsqueda en tiempo real
document.getElementById('buscarProducto')?.addEventListener('keyup', function() {
    const texto = this.value.toLowerCase();
    const filas = document.querySelectorAll('#tablaProductos tbody tr');
    
    filas.forEach(fila => {
        const textoFila = fila.textContent.toLowerCase();
        fila.style.display = textoFila.includes(texto) ? '' : 'none';
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?>