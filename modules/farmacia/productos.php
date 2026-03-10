<?php
require_once '../../config/config.php';
$modulo_requerido = 'farmacia';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

// ============================================
// OBTENER DATOS PARA LAS PESTAÑAS
// ============================================

// Productos con stock actual y cálculo de estado
$productos = $pdo->query("
    SELECT p.*, 
           c.nombre as categoria_nombre,
           c.requiere_receta,
           c.control_lote,
           c.control_vencimiento,
           COALESCE((SELECT SUM(cantidad_actual) FROM lote WHERE producto_id = p.id), 0) as stock_total,
           CASE 
               WHEN COALESCE((SELECT SUM(cantidad_actual) FROM lote WHERE producto_id = p.id), 0) = 0 THEN 'critico'
               WHEN COALESCE((SELECT SUM(cantidad_actual) FROM lote WHERE producto_id = p.id), 0) <= p.stock_minimo THEN 'bajo'
               ELSE 'normal'
           END as estado_stock
    FROM productos p
    LEFT JOIN categorias_productos c ON p.categoria_id = c.id
    WHERE p.activo = 1
    ORDER BY 
        CASE 
            WHEN COALESCE((SELECT SUM(cantidad_actual) FROM lote WHERE producto_id = p.id), 0) = 0 THEN 1
            WHEN COALESCE((SELECT SUM(cantidad_actual) FROM lote WHERE producto_id = p.id), 0) <= p.stock_minimo THEN 2
            ELSE 3
        END,
        p.nombre
")->fetchAll();

// Categorías para select
$categorias = $pdo->query("SELECT * FROM categorias_productos ORDER BY nombre")->fetchAll();

// Proveedores
$proveedores = $pdo->query("SELECT * FROM proveedores WHERE activo = 1 ORDER BY nombre")->fetchAll();

// Requisiciones (TODAS, no solo pendientes)
$requisiciones = $pdo->query("
    SELECT r.*, u.usuario,
           (SELECT COUNT(*) FROM requisicion_detalle WHERE requisicion_id = r.id) as total_items,
           (SELECT SUM(cantidad_solicitada) FROM requisicion_detalle WHERE requisicion_id = r.id) as total_cantidad
    FROM requisiciones r
    JOIN usuarios u ON r.solicitante_id = u.id
    ORDER BY 
        CASE r.estado 
            WHEN 'pendiente' THEN 1
            WHEN 'surtida' THEN 2
            ELSE 3
        END,
        r.fecha_solicitud DESC
")->fetchAll();

// Productos para requisiciones
$productos_select = $pdo->query("SELECT id, nombre, codigo FROM productos WHERE activo = 1 ORDER BY nombre")->fetchAll();

// Mensajes
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
$tab = $_GET['tab'] ?? 'productos'; // pestaña activa
?>

<div class="fade-in">
    <!-- HEADER CON BOTÓN VOLVER -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl);">
        <div>
            <h1>📦 Gestión de Productos y Requisiciones</h1>
            <p style="color: var(--gray-600);">Administra productos, proveedores y solicitudes</p>
        </div>
        <a href="index.php" class="btn btn-outline">
            <span>←</span> Volver al Dashboard
        </a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- PESTAÑAS -->
    <div style="display: flex; gap: var(--spacing-sm); margin-bottom: var(--spacing-lg); border-bottom: 2px solid var(--gray-200); flex-wrap: wrap;">
        <a href="?tab=productos" class="btn <?= $tab == 'productos' ? 'btn-primary' : 'btn-outline' ?>" style="border-radius: 0; border-bottom: none;">
            📦 Productos
        </a>
        <a href="?tab=nuevo_producto" class="btn <?= $tab == 'nuevo_producto' ? 'btn-primary' : 'btn-outline' ?>" style="border-radius: 0; border-bottom: none;">
            ➕ Nuevo Producto
        </a>
        <a href="?tab=proveedores" class="btn <?= $tab == 'proveedores' ? 'btn-primary' : 'btn-outline' ?>" style="border-radius: 0; border-bottom: none;">
            🤝 Proveedores
        </a>
        <a href="?tab=nuevo_proveedor" class="btn <?= $tab == 'nuevo_proveedor' ? 'btn-primary' : 'btn-outline' ?>" style="border-radius: 0; border-bottom: none;">
            ➕ Nuevo Proveedor
        </a>
        <a href="?tab=requisiciones" class="btn <?= $tab == 'requisiciones' ? 'btn-primary' : 'btn-outline' ?>" style="border-radius: 0; border-bottom: none;">
            📝 Requisiciones
        </a>
        <a href="?tab=nueva_requisicion" class="btn <?= $tab == 'nueva_requisicion' ? 'btn-primary' : 'btn-outline' ?>" style="border-radius: 0; border-bottom: none;">
            ➕ Nueva Requisición
        </a>
    </div>

    <!-- ===== CONTENIDO DE PESTAÑAS ===== -->

    <?php if ($tab == 'productos'): ?>
        <!-- Lista de productos -->
   <div class="card">
    <h3>📋 Productos Registrados</h3>
    
    <?php if (empty($productos)): ?>
        <p class="text-muted">No hay productos registrados</p>
    <?php else: ?>
    
        <!-- CONTADORES DE STOCK -->
        <div style="display: flex; gap: var(--spacing-lg); margin-bottom: var(--spacing-lg); padding: var(--spacing-md); background: var(--gray-100); border-radius: var(--radius-md); flex-wrap: wrap;">
            <?php
            $total_productos = count($productos);
            $stock_bajo = 0;
            $stock_critico = 0;
            $stock_normal = 0;
            
            foreach ($productos as $p) {
                if ($p['estado_stock'] == 'critico') $stock_critico++;
                elseif ($p['estado_stock'] == 'bajo') $stock_bajo++;
                else $stock_normal++;
            }
            ?>
            
            <div style="flex: 1; text-align: center; min-width: 120px;">
                <span class="badge badge-success" style="font-size: 1.1rem; padding: 8px 15px;">
                    ✅ Normal: <?= $stock_normal ?>
                </span>
            </div>
            <div style="flex: 1; text-align: center; min-width: 120px;">
                <span class="badge badge-warning" style="font-size: 1.1rem; padding: 8px 15px;">
                    ⚠️ Stock bajo: <?= $stock_bajo ?>
                </span>
            </div>
            <div style="flex: 1; text-align: center; min-width: 120px;">
                <span class="badge badge-danger" style="font-size: 1.1rem; padding: 8px 15px;">
                    🔴 Crítico: <?= $stock_critico ?>
                </span>
            </div>
        </div>
        
        <div class="stock-farmacia">
            <?php foreach ($productos as $p): ?>
                <div class="stock-item <?= $p['estado_stock'] ?>">
                    <div class="stock-info">
                        <h4><?= htmlspecialchars($p['nombre']) ?></h4>
                        <small>Código: <?= $p['codigo'] ?></small>
                        <br><small>Categoría: <?= $p['categoria_nombre'] ?? 'N/A' ?></small>
                        <br><small>Stock: <?= $p['stock_total'] ?> / Mín: <?= $p['stock_minimo'] ?></small>
                        <?php if ($p['requiere_receta']): ?>
                            <br><small class="badge-warning">Requiere receta</small>
                        <?php endif; ?>
                    </div>
                    <div class="stock-cantidad <?= $p['estado_stock'] ?>">
                        <?= $p['stock_total'] ?>
                        <?php if ($p['estado_stock'] == 'critico'): ?>
                            <span style="font-size: 1rem;">🔴</span>
                        <?php elseif ($p['estado_stock'] == 'bajo'): ?>
                            <span style="font-size: 1rem;">⚠️</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
      <?php endif; ?>
</div>

<?php elseif ($tab == 'nuevo_producto'): ?>
    <!-- ============================================ -->
<!-- NUEVO PRODUCTO -->
<!-- ============================================ -->
<div class="card">
    <h3>➕ Registrar Nuevo Producto</h3>
    <form method="POST" action="procesar/guardar_producto.php">
        <div class="form-row">
            <div class="form-group">
                <label>Código *</label>
                <input type="text" name="codigo" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Nombre *</label>
                <input type="text" name="nombre" class="form-control" required>
            </div>
        </div>

        <div class="form-group">
            <label>Descripción</label>
            <textarea name="descripcion" class="form-control" rows="2"></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Categoría *</label>
                <select name="categoria_id" class="form-control" required>
                    <option value="">Seleccionar categoría</option>
                    <?php foreach ($categorias as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Proveedor preferido</label>
                <select name="proveedor_id" class="form-control">
                    <option value="">Seleccionar proveedor</option>
                    <?php foreach ($proveedores as $prov): ?>
                        <option value="<?= $prov['id'] ?>"><?= htmlspecialchars($prov['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Unidad Medida</label>
                <select name="unidad_medida" class="form-control">
                    <option value="pieza">Pieza</option>
                    <option value="caja">Caja</option>
                    <option value="frasco">Frasco</option>
                    <option value="tableta">Tableta</option>
                    <option value="ml">ML</option>
                    <option value="litro">Litro</option>
                </select>
            </div>
            <div class="form-group">
                <label>Ubicación</label>
                <input type="text" name="ubicacion" class="form-control" placeholder="Ej: Estante A-1">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Stock Mínimo</label>
                <input type="number" name="stock_minimo" class="form-control" value="10">
            </div>
            <div class="form-group">
                <label>Stock Máximo</label>
                <input type="number" name="stock_maximo" class="form-control" value="1000">
            </div>
            <div class="form-group">
                <label>Precio Unitario (sugerido)</label>
                <input type="number" name="precio_unitario" class="form-control" value="0.00" step="0.01">
            </div>
        </div>

        <!-- CHECKBOXES INFORMATIVOS -->
        <div class="checkbox-group" style="margin: var(--spacing-md) 0; padding: var(--spacing-md); background: var(--gray-100); border-radius: var(--radius-md);">
            <p style="margin-bottom: var(--spacing-sm); color: var(--gray-700);">
                <strong>ⓘ Propiedades de la categoría seleccionada:</strong>
            </p>
            
            <label style="margin-right: var(--spacing-lg); display: inline-flex; align-items: center; gap: 5px;">
                <input type="checkbox" id="preview_requiere_receta" disabled> 
                Requiere receta
            </label>
            
            <label style="margin-right: var(--spacing-lg); display: inline-flex; align-items: center; gap: 5px;">
                <input type="checkbox" id="preview_control_lote" disabled> 
                Control por lote
            </label>
            
            <label style="display: inline-flex; align-items: center; gap: 5px;">
                <input type="checkbox" id="preview_control_vencimiento" disabled> 
                Control de vencimiento
            </label>
            
            <p style="margin-top: var(--spacing-sm); font-size: 0.85rem; color: var(--gray-500);">
                Estos valores se heredan automáticamente de la categoría seleccionada
            </p>
        </div>

        <!-- CAMPOS OCULTOS (para evitar errores) -->
        <input type="hidden" name="requiere_receta" value="0">
        <input type="hidden" name="control_lote" value="0">
        <input type="hidden" name="control_vencimiento" value="0">

        <button type="submit" class="btn btn-success">Guardar Producto</button>
    </form>
</div>

<!-- SCRIPT (DEBE IR FUERA DEL FORMULARIO) -->
<script>
// Actualizar checkboxes según la categoría seleccionada
document.querySelector('select[name="categoria_id"]').addEventListener('change', function() {
    const categoriaId = this.value;
    
    if (!categoriaId) {
        document.getElementById('preview_requiere_receta').checked = false;
        document.getElementById('preview_control_lote').checked = false;
        document.getElementById('preview_control_vencimiento').checked = false;
        return;
    }
    
    fetch('ajax/obtener_categoria.php?id=' + categoriaId)
        .then(response => response.json())
        .then(data => {
            document.getElementById('preview_requiere_receta').checked = data.requiere_receta == 1;
            document.getElementById('preview_control_lote').checked = data.control_lote == 1;
            document.getElementById('preview_control_vencimiento').checked = data.control_vencimiento == 1;
        })
        .catch(error => {
            console.log('Error al obtener categoría:', error);
        });
});
</script>

    <?php elseif ($tab == 'proveedores'): ?>
    <!-- ============================================ -->
    <!-- LISTA DE PROVEEDORES -->
    <!-- ============================================ -->
    <div class="card">
        <h3>🤝 Proveedores Registrados</h3>
        
        <?php if (empty($proveedores)): ?>
            <p class="text-muted">No hay proveedores registrados</p>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>RFC</th>
                            <th>Nombre</th>
                            <th>Contacto</th>
                            <th>Teléfono</th>
                            <th>Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($proveedores as $prov): ?>
                        <tr>
                            <td><?= htmlspecialchars($prov['rfc'] ?? 'N/A') ?></td>
                            <td><strong><?= htmlspecialchars($prov['nombre']) ?></strong></td>
                            <td><?= htmlspecialchars($prov['contacto'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($prov['telefono'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($prov['email'] ?? 'N/A') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <?php elseif ($tab == 'nuevo_proveedor'): ?>
    <!-- ============================================ -->
    <!-- NUEVO PROVEEDOR -->
    <!-- ============================================ -->
    <div class="card">
        <h3>➕ Registrar Nuevo Proveedor</h3>
        <form method="POST" action="procesar/guardar_proveedor.php">
            <div class="form-row">
                <div class="form-group">
                    <label>RFC *</label>
                    <input type="text" name="rfc" class="form-control" required placeholder="XXXX000101XXX">
                </div>
                <div class="form-group">
                    <label>Nombre *</label>
                    <input type="text" name="nombre" class="form-control" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Contacto</label>
                    <input type="text" name="contacto" class="form-control" placeholder="Nombre del contacto">
                </div>
                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="text" name="telefono" class="form-control" placeholder="555-1234">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" placeholder="proveedor@empresa.com">
                </div>
                <div class="form-group">
                    <label>Dirección</label>
                    <input type="text" name="direccion" class="form-control" placeholder="Calle, número, colonia">
                </div>
            </div>

            <button type="submit" class="btn btn-success">Guardar Proveedor</button>
        </form>
    </div>

    <?php elseif ($tab == 'requisiciones'): ?>
    <!-- ============================================ -->
    <!-- LISTA DE REQUISICIONES -->
    <!-- ============================================ -->
    <div class="card">
        <h3>📝 Historial de Requisiciones</h3>
        
        <?php if (empty($requisiciones)): ?>
            <p class="text-muted">No hay requisiciones registradas</p>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Folio</th>
                            <th>Fecha</th>
                            <th>Solicitante</th>
                            <th>Productos</th>
                            <th>Cantidad</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requisiciones as $r): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($r['numero_requisicion']) ?></strong></td>
                            <td><?= date('d/m/Y', strtotime($r['fecha_solicitud'])) ?></td>
                            <td><?= htmlspecialchars($r['usuario']) ?></td>
                            <td><?= $r['total_items'] ?> productos</td>
                            <td><?= $r['total_cantidad'] ?> uds</td>
                            <td>
                                <?php if ($r['estado'] == 'pendiente'): ?>
                                    <span class="badge badge-warning">Pendiente</span>
                                <?php elseif ($r['estado'] == 'surtida'): ?>
                                    <span class="badge badge-success">Surtida</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Cancelada</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($r['estado'] == 'pendiente'): ?>
                                    <a href="entrada.php?requisicion=<?= $r['id'] ?>" class="btn btn-sm btn-success">
                                        📥 Recibir
                                    </a>
                                <?php else: ?>
                                    <a href="entrada.php?requisicion=<?= $r['id'] ?>" class="btn btn-sm btn-outline">
                                        👁️ Ver
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <?php elseif ($tab == 'nueva_requisicion'): ?>
    <!-- ============================================ -->
    <!-- NUEVA REQUISICIÓN -->
    <!-- ============================================ -->
    <div class="card">
        <h3>➕ Nueva Requisición a Proveedor</h3>
        <form method="POST" action="procesar/guardar_requisicion.php">
            <div class="form-row">
                <div class="form-group">
                    <label>Proveedor *</label>
                    <select name="proveedor_id" class="form-control" required>
                        <option value="">Seleccionar proveedor</option>
                        <?php foreach ($proveedores as $prov): ?>
                            <option value="<?= $prov['id'] ?>"><?= htmlspecialchars($prov['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Fecha requerida</label>
                    <input type="date" name="fecha_requerida" class="form-control">
                </div>
            </div>

            <h4 style="margin-top: var(--spacing-lg);">Productos a solicitar</h4>
            <div id="requisicion-items">
                <div class="item-row form-row" style="background: #f9f9f9; padding: 15px; margin-bottom: 15px; border-radius: 8px;">
                    <div class="form-group" style="flex: 2;">
                        <label>Producto *</label>
                        <select name="producto_id[]" class="form-control" required>
                            <option value="">Seleccionar producto</option>
                            <?php foreach ($productos_select as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?> (<?= $p['codigo'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Cantidad *</label>
                        <input type="number" name="cantidad[]" class="form-control" required min="1" value="1">
                    </div>
                    <div class="form-group" style="flex: 0.5;">
                        <label>&nbsp;</label>
                        <button type="button" class="btn btn-danger btn-sm" onclick="eliminarItem(this)">✗</button>
                    </div>
                </div>
            </div>

            <button type="button" class="btn btn-outline btn-sm" onclick="agregarItem()">➕ Agregar otro producto</button>

            <div class="form-group" style="margin-top: var(--spacing-lg);">
                <label>Observaciones</label>
                <textarea name="observaciones" class="form-control" rows="3" placeholder="Motivo de la requisición..."></textarea>
            </div>

            <div style="display: flex; gap: var(--spacing-md); margin-top: var(--spacing-xl);">
                <button type="submit" class="btn btn-primary">Enviar Requisición</button>
                <a href="?tab=requisiciones" class="btn btn-outline">Cancelar</a>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>

<script>
function agregarItem() {
    const container = document.getElementById('requisicion-items');
    const template = container.children[0].cloneNode(true);
    
    // Limpiar valores
    template.querySelectorAll('select').forEach(s => s.selectedIndex = 0);
    template.querySelectorAll('input').forEach(i => i.value = '1');
    
    container.appendChild(template);
}

function eliminarItem(btn) {
    const container = document.getElementById('requisicion-items');
    if (container.children.length > 1) {
        btn.closest('.item-row').remove();
    } else {
        alert('Debe haber al menos un producto');
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?>