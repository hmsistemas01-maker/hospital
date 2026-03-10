<?php
require_once '../../config/config.php';
$modulo_requerido = 'farmacia';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Obtener recetas pendientes (SOLO de farmacia)
$recetas_pendientes = $pdo->query("
    SELECT r.*, 
           p.nombre as paciente_nombre, 
           d.nombre as doctor_nombre,
           COUNT(rd.id) as total_medicamentos
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

if ($receta_id) {
    // Obtener datos de la receta
    $stmt = $pdo->prepare("
        SELECT r.*, p.nombre as paciente_nombre, p.curp, 
               p.fecha_nacimiento, d.nombre as doctor_nombre
        FROM recetas r
        JOIN pacientes p ON r.paciente_id = p.id
        JOIN doctores d ON r.doctor_id = d.id
        WHERE r.id = ? AND r.estado = 'pendiente'
    ");
    $stmt->execute([$receta_id]);
    $receta_seleccionada = $stmt->fetch();

    if ($receta_seleccionada) {
        // Calcular edad del paciente
        $edad = '';
        if ($receta_seleccionada['fecha_nacimiento']) {
            $nacimiento = new DateTime($receta_seleccionada['fecha_nacimiento']);
            $hoy = new DateTime();
            $edad = $nacimiento->diff($hoy)->y;
        }

        // Obtener detalles de la receta con stock disponible en farmacia
        $stmt = $pdo->prepare("
            SELECT rd.*, 
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
?>

<div class="fade-in">
    <div class="d-flex justify-between align-center mb-4">
        <div>
            <h1>💊 Despacho de Medicamentos</h1>
            <p class="text-gray-600">Dispensar medicamentos de recetas médicas</p>
        </div>
        <a href="index.php" class="btn btn-outline">
            <span>←</span> Volver
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">✅ Despacho realizado correctamente</div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger">❌ <?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <?php if (!$receta_seleccionada): ?>
        <!-- Listado de recetas pendientes -->
        <div class="card">
            <h3>📋 Recetas Pendientes</h3>
            
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
                                    <td><?= htmlspecialchars($r['doctor_nombre']) ?></td>
                                    <td><?= $r['total_medicamentos'] ?> medicamentos</td>
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
        <!-- Formato de Receta Médica -->
        <div class="receta-card">
            <div class="receta-header">
                <div><h3>HOSPITAL GENERAL</h3></div>
                <div><h3>RECETA MÉDICA</h3></div>
                <div><h3>FOLIO: #<?= str_pad($receta_seleccionada['id'], 5, '0', STR_PAD_LEFT) ?></h3></div>
            </div>
            
            <!-- Datos del paciente -->
            <div class="receta-datos-paciente">
                <div>
                    <p><strong>PACIENTE:</strong> <?= htmlspecialchars($receta_seleccionada['paciente_nombre']) ?></p>
                    <p><strong>CURP:</strong> <?= htmlspecialchars($receta_seleccionada['curp'] ?? 'N/A') ?></p>
                </div>
                <div>
                    <p><strong>FECHA:</strong> <?= date('d/m/Y', strtotime($receta_seleccionada['fecha'])) ?></p>
                    <p><strong>EDAD:</strong> <?= $edad ?? 'N/A' ?> años</p>
                </div>
                <div>
                    <p><strong>MÉDICO:</strong> <?= htmlspecialchars($receta_seleccionada['doctor_nombre']) ?></p>
                </div>
            </div>

            <!-- Tabla de medicamentos -->
            <form method="POST" action="procesar_despacho.php">
                <input type="hidden" name="receta_id" value="<?= $receta_seleccionada['id'] ?>">
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Medicamento</th>
                                <th>Cantidad</th>
                                <th>Stock</th>
                                <th>Lote a despachar</th>
                                <th>Cantidad a surtir</th>
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
                                                            data-stock="<?= $lote['cantidad_actual'] ?>">
                                                        <?= $lote['numero_lote'] ?> 
                                                        (Stock: <?= $lote['cantidad_actual'] ?>)
                                                        <?= $lote['fecha_vencimiento'] ? 'Vence: ' . date('d/m/Y', strtotime($lote['fecha_vencimiento'])) : '' ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Sin control de lote</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <input type="number" name="cantidad_surtida[<?= $detalle['id'] ?>]" 
                                               class="form-control" 
                                               min="1" 
                                               max="<?= min($detalle['cantidad'], $detalle['stock_total']) ?>"
                                               value="<?= $detalle['cantidad'] ?>"
                                               <?= !$stock_suficiente ? 'disabled' : '' ?>>
                                    </td>
                                    <td class="text-center">
                                        <input type="checkbox" name="despachar[]" 
                                               value="<?= $detalle['id'] ?>"
                                               <?= $stock_suficiente ? 'checked' : 'disabled' ?>>
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
                    <button type="submit" class="btn btn-success" style="flex: 1;">
                        💊 Confirmar Despacho
                    </button>
                    <a href="despacho.php" class="btn btn-outline">Cancelar</a>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer.php'; ?>