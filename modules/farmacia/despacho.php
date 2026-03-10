<?php
require_once '../../config/config.php';
$modulo_requerido = 'farmacia';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Obtener recetas pendientes
$recetas_pendientes = $pdo->query("
    SELECT r.*, 
           p.nombre as paciente_nombre, 
           p.curp,
           d.nombre as doctor_nombre,
           COUNT(rd.id) as total_medicamentos,
           SUM(CASE WHEN rd.despachado = 1 THEN 1 ELSE 0 END) as despachados
    FROM recetas r
    JOIN pacientes p ON r.paciente_id = p.id
    JOIN doctores d ON r.doctor_id = d.id
    LEFT JOIN receta_detalles rd ON r.id = rd.receta_id
    WHERE r.estado = 'pendiente'
    GROUP BY r.id
    ORDER BY r.fecha ASC
")->fetchAll();

// Si se seleccionó una receta específica
$receta_id = $_GET['receta'] ?? null;
$receta_seleccionada = null;
$detalles_receta = [];
$edad = '';

if ($receta_id) {
    // Obtener datos de la receta
    $stmt = $pdo->prepare("
        SELECT r.*, p.nombre as paciente_nombre, p.curp, p.fecha_nacimiento,
               d.nombre as doctor_nombre, d.especialidad
        FROM recetas r
        JOIN pacientes p ON r.paciente_id = p.id
        JOIN doctores d ON r.doctor_id = d.id
        WHERE r.id = ? AND r.estado = 'pendiente'
    ");
    $stmt->execute([$receta_id]);
    $receta_seleccionada = $stmt->fetch();

    if ($receta_seleccionada) {
        // Calcular edad
        if ($receta_seleccionada['fecha_nacimiento']) {
            $nacimiento = new DateTime($receta_seleccionada['fecha_nacimiento']);
            $hoy = new DateTime();
            $edad = $nacimiento->diff($hoy)->y;
        }

        // Obtener detalles de la receta con stock disponible
        $stmt = $pdo->prepare("
            SELECT rd.*, 
                   p.id as producto_id,
                   p.nombre as producto_nombre, 
                   p.codigo,
                   cf.control_lote,
                   cf.control_vencimiento,
                   COALESCE(SUM(fl.cantidad_actual), 0) as stock_total
            FROM receta_detalles rd
            JOIN productos p ON rd.producto_id = p.id
            LEFT JOIN categorias_farmacia cf ON p.categoria_farmacia_id = cf.id
            LEFT JOIN farmacia_lotes fl ON p.id = fl.producto_id 
                AND fl.activo = 1 
                AND (fl.fecha_vencimiento IS NULL OR fl.fecha_vencimiento > CURDATE())
            WHERE rd.receta_id = ? AND rd.despachado = 0
            GROUP BY rd.id
        ");
        $stmt->execute([$receta_id]);
        $detalles_receta = $stmt->fetchAll();
    }
}

// Procesar mensajes de sesión
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<div class="fade-in">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl);">
        <div>
            <h1>💊 Despacho de Medicamentos</h1>
            <p style="color: var(--gray-600);">Dispensar medicamentos de recetas médicas</p>
        </div>
        <a href="index.php" class="btn btn-outline">
            <span>←</span> Volver
        </a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!$receta_seleccionada): ?>
        <!-- LISTADO DE RECETAS PENDIENTES -->
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-lg);">
                <h3>📋 Recetas Pendientes</h3>
                <span class="badge badge-primary">Total: <?= count($recetas_pendientes) ?></span>
            </div>
            
            <?php if (empty($recetas_pendientes)): ?>
                <div class="alert alert-info" style="text-align: center; padding: var(--spacing-xl);">
                    <p style="font-size: 1.2rem;">📭 No hay recetas pendientes</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Folio</th>
                                <th>Fecha</th>
                                <th>Paciente</th>
                                <th>CURP</th>
                                <th>Doctor</th>
                                <th>Medicamentos</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recetas_pendientes as $r): ?>
                                <tr>
                                    <td><strong>#<?= str_pad($r['id'], 5, '0', STR_PAD_LEFT) ?></strong></td>
                                    <td><?= date('d/m/Y H:i', strtotime($r['fecha'])) ?></td>
                                    <td><?= htmlspecialchars($r['paciente_nombre']) ?></td>
                                    <td><?= htmlspecialchars($r['curp'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($r['doctor_nombre']) ?></td>
                                    <td>
                                        <?= $r['total_medicamentos'] ?> medicamentos
                                        <?php if ($r['despachados'] > 0): ?>
                                            <br><small class="badge badge-info"><?= $r['despachados'] ?> despachados</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="despacho.php?receta=<?= $r['id'] ?>" 
                                           class="btn btn-sm btn-success">
                                            💊 Despachar
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <!-- FORMULARIO DE DESPACHO -->
        <div class="receta-card">
            <!-- Encabezado -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-lg); padding-bottom: var(--spacing-md); border-bottom: 2px solid var(--primary);">
                <div>
                    <h2 style="color: var(--primary); margin: 0;">RECETA MÉDICA</h2>
                    <p style="color: var(--gray-600);">Folio: #<?= str_pad($receta_seleccionada['id'], 5, '0', STR_PAD_LEFT) ?></p>
                </div>
                <div style="text-align: right;">
                    <p><strong>Fecha:</strong> <?= date('d/m/Y H:i', strtotime($receta_seleccionada['fecha'])) ?></p>
                </div>
            </div>

            <!-- Datos del paciente -->
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--spacing-md); margin-bottom: var(--spacing-lg); padding: var(--spacing-md); background: var(--gray-100); border-radius: var(--radius-md);">
                <div>
                    <strong>Paciente:</strong>
                    <p><?= htmlspecialchars($receta_seleccionada['paciente_nombre']) ?></p>
                </div>
                <div>
                    <strong>CURP:</strong>
                    <p><?= htmlspecialchars($receta_seleccionada['curp'] ?? 'N/A') ?></p>
                </div>
                <div>
                    <strong>Edad:</strong>
                    <p><?= $edad ?: 'N/A' ?> años</p>
                </div>
            </div>

            <!-- Datos del médico -->
            <div style="margin-bottom: var(--spacing-lg);">
                <strong>Médico:</strong> <?= htmlspecialchars($receta_seleccionada['doctor_nombre']) ?>
                <?php if ($receta_seleccionada['especialidad']): ?>
                    <br><small><?= htmlspecialchars($receta_seleccionada['especialidad']) ?></small>
                <?php endif; ?>
            </div>

            <!-- Formulario de despacho -->
            <form method="POST" action="procesar_despacho.php" id="formDespacho">
                <input type="hidden" name="receta_id" value="<?= $receta_seleccionada['id'] ?>">
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Medicamento</th>
                                <th>Cant. Solicitada</th>
                                <th>Stock Total</th>
                                <th>Lote a despachar</th>
                                <th>Cant. a surtir</th>
                                <th>Despachar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detalles_receta as $detalle): 
                                $stock_suficiente = $detalle['stock_total'] >= $detalle['cantidad'];
                                $clase_fila = !$stock_suficiente ? 'danger' : '';
                                
                                // Obtener lotes disponibles para este medicamento
                                $stmt_lotes = $pdo->prepare("
                                    SELECT id, numero_lote, cantidad_actual, fecha_vencimiento
                                    FROM farmacia_lotes
                                    WHERE producto_id = ? AND activo = 1 
                                          AND cantidad_actual > 0
                                          AND (fecha_vencimiento IS NULL OR fecha_vencimiento > CURDATE())
                                    ORDER BY fecha_vencimiento ASC
                                ");
                                $stmt_lotes->execute([$detalle['producto_id']]);
                                $lotes_disponibles = $stmt_lotes->fetchAll();
                            ?>
                                <tr class="<?= $clase_fila ?>">
                                    <td>
                                        <strong><?= htmlspecialchars($detalle['producto_nombre']) ?></strong>
                                        <br><small><?= $detalle['codigo'] ?></small>
                                    </td>
                                    <td class="text-center"><?= $detalle['cantidad'] ?></td>
                                    <td class="text-center">
                                        <span class="badge badge-<?= $stock_suficiente ? 'success' : 'danger' ?>">
                                            <?= $detalle['stock_total'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($detalle['control_lote'] && !empty($lotes_disponibles)): ?>
                                            <select name="lote[<?= $detalle['id'] ?>]" class="form-control" required>
                                                <option value="">Seleccionar lote</option>
                                                <?php foreach ($lotes_disponibles as $lote): ?>
                                                    <option value="<?= $lote['id'] ?>" 
                                                            data-stock="<?= $lote['cantidad_actual'] ?>"
                                                            data-vencimiento="<?= $lote['fecha_vencimiento'] ?>">
                                                        <?= $lote['numero_lote'] ?> 
                                                        (Stock: <?= $lote['cantidad_actual'] ?>)
                                                        <?= $lote['fecha_vencimiento'] ? 'Vence: ' . date('d/m/Y', strtotime($lote['fecha_vencimiento'])) : '' ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Sin control de lote</span>
                                            <input type="hidden" name="lote[<?= $detalle['id'] ?>]" value="">
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <input type="number" name="cantidad_surtida[<?= $detalle['id'] ?>]" 
                                               class="form-control cantidad-input" 
                                               min="1" 
                                               max="<?= min($detalle['cantidad'], $detalle['stock_total']) ?>"
                                               value="<?= min($detalle['cantidad'], $detalle['stock_total']) ?>"
                                               <?= !$stock_suficiente ? 'disabled' : '' ?>
                                               data-detalle="<?= $detalle['id'] ?>">
                                    </td>
                                    <td class="text-center">
                                        <input type="checkbox" name="despachar[]" 
                                               value="<?= $detalle['id'] ?>"
                                               <?= $stock_suficiente ? 'checked' : 'disabled' ?>
                                               class="despachar-checkbox"
                                               data-detalle="<?= $detalle['id'] ?>">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Observaciones -->
                <div class="form-group" style="margin-top: var(--spacing-lg);">
                    <label>Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="2"><?= htmlspecialchars($receta_seleccionada['observaciones'] ?? '') ?></textarea>
                </div>

                <!-- Botones -->
                <div style="display: flex; gap: var(--spacing-md); margin-top: var(--spacing-xl);">
                    <button type="submit" class="btn btn-success" style="flex: 1;" id="btnDespachar">
                        💊 Confirmar Despacho
                    </button>
                    <a href="despacho.php" class="btn btn-outline">Cancelar</a>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<script>
// Validar que al menos un medicamento esté seleccionado para despachar
document.getElementById('formDespacho')?.addEventListener('submit', function(e) {
    const checkboxes = document.querySelectorAll('input[name="despachar[]"]:checked');
    if (checkboxes.length === 0) {
        e.preventDefault();
        alert('Debe seleccionar al menos un medicamento para despachar');
    }
});

// Validar que la cantidad surtida no exceda el stock del lote seleccionado
document.querySelectorAll('select[name^="lote"]').forEach(select => {
    select.addEventListener('change', function() {
        const row = this.closest('tr');
        const cantidadInput = row.querySelector('input[type="number"]');
        const checkbox = row.querySelector('input[type="checkbox"]');
        const stockLote = this.options[this.selectedIndex]?.dataset.stock || 0;
        
        if (cantidadInput && stockLote > 0) {
            cantidadInput.max = Math.min(cantidadInput.max, stockLote);
            if (parseInt(cantidadInput.value) > parseInt(stockLote)) {
                cantidadInput.value = stockLote;
            }
        }
    });
});

// Actualizar max cuando cambia la cantidad
document.querySelectorAll('.cantidad-input').forEach(input => {
    input.addEventListener('change', function() {
        const row = this.closest('tr');
        const checkbox = row.querySelector('.despachar-checkbox');
        const select = row.querySelector('select');
        
        if (select) {
            const stockLote = select.options[select.selectedIndex]?.dataset.stock || 0;
            if (parseInt(this.value) > parseInt(stockLote)) {
                alert('La cantidad no puede ser mayor al stock del lote');
                this.value = stockLote;
            }
        }
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?>