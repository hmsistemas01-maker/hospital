<?php
require_once '../../config/config.php';
$modulo_requerido = 'farmacia';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Obtener productos de FARMACIA (solo los que tienen departamento = 'farmacia')
$productos = $pdo->query("
    SELECT p.*, cf.nombre as categoria_nombre, cf.control_lote, cf.control_vencimiento
    FROM productos p
    LEFT JOIN categorias_farmacia cf ON p.categoria_farmacia_id = cf.id
    WHERE p.departamento = 'farmacia' AND p.activo = 1
    ORDER BY p.nombre
")->fetchAll();

// Obtener proveedores
$proveedores = $pdo->query("
    SELECT id, nombre 
    FROM proveedores 
    WHERE activo = 1 
    ORDER BY nombre
")->fetchAll();

// Si viene de una requisición
$requisicion_id = $_GET['requisicion'] ?? null;
$items_requisicion = [];
$requisicion_info = null;

if ($requisicion_id) {
    // Obtener información de la requisición (solo de farmacia)
    $stmt = $pdo->prepare("
        SELECT * FROM requisiciones 
        WHERE id = ? AND departamento = 'farmacia'
    ");
    $stmt->execute([$requisicion_id]);
    $requisicion_info = $stmt->fetch();
    
    if ($requisicion_info) {
        // Obtener detalles
        $stmt = $pdo->prepare("
            SELECT rd.*, p.nombre as producto, p.codigo
            FROM requisicion_detalle rd
            JOIN productos p ON rd.producto_id = p.id
            WHERE rd.requisicion_id = ?
        ");
        $stmt->execute([$requisicion_id]);
        $items_requisicion = $stmt->fetchAll();
    }
}
?>

<div class="fade-in">
    <div class="d-flex justify-between align-center mb-4">
        <div>
            <h1>💊 Registrar Entrada a Farmacia</h1>
            <p class="text-gray-600">Registro de nuevos lotes de medicamentos</p>
        </div>
        <a href="index.php" class="btn btn-outline">
            <span>←</span> Volver
        </a>
    </div>

    <!-- Formulario principal -->
    <div class="card">
        <form method="POST" action="procesar_entrada.php" id="formEntrada">
            <input type="hidden" name="departamento" value="farmacia">
            <?php if ($requisicion_id): ?>
                <input type="hidden" name="requisicion_id" value="<?= $requisicion_id ?>">
            <?php endif; ?>

            <!-- Datos generales de la entrada -->
            <div class="form-row">
                <div class="form-group">
                    <label>Proveedor</label>
                    <select name="proveedor_id" class="form-control">
                        <option value="">-- Seleccionar proveedor --</option>
                        <?php foreach ($proveedores as $prov): ?>
                            <option value="<?= $prov['id'] ?>">
                                <?= htmlspecialchars($prov['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Factura/Referencia</label>
                    <input type="text" name="referencia" class="form-control" 
                           placeholder="Número de factura">
                </div>
                
                <div class="form-group">
                    <label>Fecha de recepción</label>
                    <input type="date" name="fecha" class="form-control" 
                           value="<?= date('Y-m-d') ?>">
                </div>
            </div>

            <!-- Productos a recibir -->
            <h4 style="margin-top: var(--spacing-lg);">Productos a recibir</h4>
            <div id="items-container">
                <?php if (!empty($items_requisicion)): ?>
                    <!-- Items desde requisición -->
                    <?php foreach ($items_requisicion as $item): ?>
                        <div class="item-row form-row" style="background: #f0f9ff; padding: 15px; margin-bottom: 15px; border-radius: 8px; border-left: 4px solid var(--primary);">
                            <input type="hidden" name="producto_id[]" value="<?= $item['producto_id'] ?>">
                            
                            <div class="form-group" style="flex: 2;">
                                <label>Producto</label>
                                <input type="text" class="form-control" 
                                       value="<?= htmlspecialchars($item['producto']) ?>" readonly>
                            </div>
                            
                            <div class="form-group" style="flex: 1;">
                                <label>Solicitado</label>
                                <input type="number" class="form-control" 
                                       value="<?= $item['cantidad_solicitada'] ?>" readonly>
                            </div>
                            
                            <div class="form-group" style="flex: 1;">
                                <label>Cantidad a recibir *</label>
                                <input type="number" name="cantidad[]" class="form-control cantidad-input" 
                                       required min="1" value="<?= $item['cantidad_solicitada'] ?>">
                            </div>
                            
                            <div class="form-group" style="flex: 1;">
                                <label>Precio unitario *</label>
                                <input type="number" name="precio_unitario[]" class="form-control precio-input" 
                                       required step="0.01" value="0.00">
                            </div>
                            
                            <div class="form-group lote-field" style="flex: 1;">
                                <label>Número de lote *</label>
                                <input type="text" name="lote[]" class="form-control" required>
                            </div>
                            
                            <div class="form-group vencimiento-field" style="flex: 1;">
                                <label>Fecha vencimiento *</label>
                                <input type="date" name="vencimiento[]" class="form-control" required>
                            </div>
                            
                            <div class="form-group" style="flex: 0.5;">
                                <label>Subtotal</label>
                                <input type="text" class="form-control subtotal-field" readonly value="$0.00">
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Item vacío para entrada manual -->
                    <div class="item-row form-row" style="background: #f9f9f9; padding: 15px; margin-bottom: 15px; border-radius: 8px;">
                        <div class="form-group" style="flex: 2;">
                            <label>Producto *</label>
                            <select name="producto_id[]" class="form-control producto-select" required>
                                <option value="">Seleccionar producto</option>
                                <?php foreach ($productos as $p): ?>
                                    <option value="<?= $p['id'] ?>" 
                                            data-lote="<?= $p['control_lote'] ?>"
                                            data-vencimiento="<?= $p['control_vencimiento'] ?>">
                                        <?= htmlspecialchars($p['codigo']) ?> - 
                                        <?= htmlspecialchars($p['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group" style="flex: 1;">
                            <label>Cantidad *</label>
                            <input type="number" name="cantidad[]" class="form-control cantidad-input" 
                                   required min="1" value="1">
                        </div>
                        
                        <div class="form-group" style="flex: 1;">
                            <label>Precio unit. *</label>
                            <input type="number" name="precio_unitario[]" class="form-control precio-input" 
                                   required step="0.01" value="0.00">
                        </div>
                        
                        <div class="form-group lote-field" style="flex: 1; display: none;">
                            <label>Número de lote *</label>
                            <input type="text" name="lote[]" class="form-control">
                        </div>
                        
                        <div class="form-group vencimiento-field" style="flex: 1; display: none;">
                            <label>Fecha vencimiento *</label>
                            <input type="date" name="vencimiento[]" class="form-control">
                        </div>
                        
                        <div class="form-group" style="flex: 0.5;">
                            <label>Subtotal</label>
                            <input type="text" class="form-control subtotal-field" readonly value="$0.00">
                        </div>
                        
                        <div class="form-group" style="flex: 0.5;">
                            <label>&nbsp;</label>
                            <button type="button" class="btn btn-danger btn-sm" onclick="eliminarItem(this)">✗</button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (empty($items_requisicion)): ?>
                <button type="button" class="btn btn-outline btn-sm" onclick="agregarItem()">
                    ➕ Agregar otro producto
                </button>
            <?php endif; ?>

            <!-- Totales -->
            <div style="margin-top: var(--spacing-lg); padding: var(--spacing-md); background: var(--gray-100); border-radius: var(--radius-md);">
                <div style="display: flex; justify-content: space-between; font-weight: bold;">
                    <span>Total productos: <span id="total-items">0</span></span>
                    <span>Total compra: $<span id="total-compra">0.00</span></span>
                </div>
            </div>

            <!-- Observaciones -->
            <div class="form-group" style="margin-top: var(--spacing-lg);">
                <label>Observaciones</label>
                <textarea name="observaciones" class="form-control" rows="3"></textarea>
            </div>

            <!-- Botones -->
            <div style="display: flex; gap: var(--spacing-md); margin-top: var(--spacing-xl);">
                <button type="submit" class="btn btn-success" style="flex: 1;">
                    💾 Registrar Entrada
                </button>
                <a href="index.php" class="btn btn-outline">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
// Función para agregar un nuevo item
function agregarItem() {
    const container = document.getElementById('items-container');
    const template = container.children[0].cloneNode(true);
    
    // Limpiar valores
    template.querySelectorAll('select').forEach(s => s.selectedIndex = 0);
    template.querySelectorAll('input[type="number"]').forEach(i => {
        if (i.classList.contains('cantidad-input')) i.value = '1';
        else if (i.classList.contains('precio-input')) i.value = '0.00';
    });
    template.querySelectorAll('input[type="text"]').forEach(i => i.value = '');
    template.querySelectorAll('input[type="date"]').forEach(i => i.value = '');
    template.querySelector('.subtotal-field').value = '$0.00';
    
    // Ocultar campos de lote y vencimiento
    const loteField = template.querySelector('.lote-field');
    const vencimientoField = template.querySelector('.vencimiento-field');
    if (loteField) loteField.style.display = 'none';
    if (vencimientoField) vencimientoField.style.display = 'none';
    
    container.appendChild(template);
    actualizarTotales();
}

// Función para eliminar un item
function eliminarItem(btn) {
    const container = document.getElementById('items-container');
    if (container.children.length > 1) {
        btn.closest('.item-row').remove();
        actualizarTotales();
    } else {
        alert('Debe haber al menos un producto');
    }
}

// Mostrar/ocultar campos de lote según el producto seleccionado
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('producto-select')) {
        const row = e.target.closest('.item-row');
        const selected = e.target.options[e.target.selectedIndex];
        const requiereLote = selected.dataset.lote === '1';
        const requiereVencimiento = selected.dataset.vencimiento === '1';
        
        const loteField = row.querySelector('.lote-field');
        const vencimientoField = row.querySelector('.vencimiento-field');
        const loteInput = loteField?.querySelector('input');
        const vencimientoInput = vencimientoField?.querySelector('input');
        
        if (loteField) {
            loteField.style.display = requiereLote ? 'block' : 'none';
            if (loteInput) loteInput.required = requiereLote;
        }
        
        if (vencimientoField) {
            vencimientoField.style.display = requiereVencimiento ? 'block' : 'none';
            if (vencimientoInput) vencimientoInput.required = requiereVencimiento;
        }
    }
});

// Actualizar subtotales y total general
function actualizarTotales() {
    let totalCompra = 0;
    let totalItems = 0;
    
    document.querySelectorAll('.item-row').forEach(row => {
        const cantidad = parseFloat(row.querySelector('input[name="cantidad[]"]')?.value) || 0;
        const precio = parseFloat(row.querySelector('input[name="precio_unitario[]"]')?.value) || 0;
        const subtotal = cantidad * precio;
        
        const subtotalField = row.querySelector('.subtotal-field');
        if (subtotalField) {
            subtotalField.value = `$${subtotal.toFixed(2)}`;
        }
        
        totalCompra += subtotal;
        totalItems += cantidad;
    });
    
    document.getElementById('total-items').textContent = totalItems;
    document.getElementById('total-compra').textContent = totalCompra.toFixed(2);
}

// Event listeners para cálculos en tiempo real
document.addEventListener('input', function(e) {
    if (e.target.classList.contains('cantidad-input') || e.target.classList.contains('precio-input')) {
        actualizarTotales();
    }
});

// Calcular totales iniciales
actualizarTotales();
</script>

<?php require_once '../../includes/footer.php'; ?>