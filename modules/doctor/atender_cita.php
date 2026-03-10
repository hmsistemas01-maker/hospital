<?php
require_once '../../config/config.php';
$modulo_requerido = 'doctor';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Obtener ID de la cita
$cita_id = (int)($_GET['id'] ?? 0);

if (!$cita_id) {
    header("Location: index.php?error=ID de cita no válido");
    exit;
}

// Obtener información de la cita
$stmt = $pdo->prepare("
    SELECT c.*, 
           p.id as paciente_id, 
           p.nombre as paciente_nombre, 
           p.curp, 
           p.fecha_nacimiento,
           p.telefono,
           p.direccion,
           d.id as doctor_id,
           d.nombre as doctor_nombre
    FROM citas c
    JOIN pacientes p ON c.paciente_id = p.id
    JOIN doctores d ON c.doctor_id = d.id
    WHERE c.id = ? AND c.estado = 'pendiente'
");
$stmt->execute([$cita_id]);
$cita = $stmt->fetch();

if (!$cita) {
    header("Location: index.php?error=Cita no encontrada o ya fue atendida");
    exit;
}

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // 1. Actualizar estado de la cita
        $stmt = $pdo->prepare("UPDATE citas SET estado = 'atendida' WHERE id = ?");
        $stmt->execute([$cita_id]);
        
        // 2. Guardar en historial clínico
        $stmt = $pdo->prepare("
            INSERT INTO historial_clinico (
                paciente_id, doctor_id, fecha, diagnostico, notas,
                presion_arterial, temperatura, peso, altura, alergias, enfermedades_cronicas
            ) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $cita['paciente_id'],
            $cita['doctor_id'],
            $_POST['diagnostico'],
            $_POST['notas'],
            $_POST['presion_arterial'],
            $_POST['temperatura'],
            $_POST['peso'],
            $_POST['altura'],
            $_POST['alergias'],
            $_POST['enfermedades_cronicas']
        ]);
        $historial_id = $pdo->lastInsertId();
        
        // 3. Guardar receta médica si hay medicamentos
        if (isset($_POST['medicamentos']) && is_array($_POST['medicamentos'])) {
            // Crear la receta
            $stmt = $pdo->prepare("
                INSERT INTO recetas (paciente_id, doctor_id, fecha, estado, observaciones)
                VALUES (?, ?, NOW(), 'pendiente', ?)
            ");
            $stmt->execute([
                $cita['paciente_id'],
                $cita['doctor_id'],
                $_POST['observaciones_receta'] ?? null
            ]);
            $receta_id = $pdo->lastInsertId();
            
            // Guardar cada medicamento
            $stmt_detalle = $pdo->prepare("
                INSERT INTO receta_detalles (receta_id, producto_id, cantidad, indicaciones, despachado)
                VALUES (?, ?, ?, ?, 0)
            ");
            
            foreach ($_POST['medicamentos'] as $med) {
                if (!empty($med['producto_id']) && !empty($med['cantidad'])) {
                    $stmt_detalle->execute([
                        $receta_id,
                        $med['producto_id'],
                        $med['cantidad'],
                        $med['indicaciones'] ?? null
                    ]);
                }
            }
        }
        
        $pdo->commit();
        
        $_SESSION['success'] = "Atención registrada correctamente";
        header("Location: index.php");
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error al guardar: " . $e->getMessage();
    }
}

// Obtener medicamentos para el select (productos de farmacia)
$medicamentos = $pdo->query("
    SELECT p.id, p.codigo, p.nombre, p.precio_unitario
    FROM productos p
    WHERE p.departamento = 'farmacia' AND p.activo = 1
    ORDER BY p.nombre
")->fetchAll();
?>

<div class="fade-in">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl);">
        <div>
            <h1>🩺 Atender Cita</h1>
            <p style="color: var(--gray-600);">Paciente: <strong><?= htmlspecialchars($cita['paciente_nombre']) ?></strong></p>
        </div>
        <a href="index.php" class="btn btn-outline">
            <span>←</span> Volver
        </a>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" id="formAtencion">
        <!-- Datos de la cita (solo información) -->
        <div class="card" style="margin-bottom: var(--spacing-lg);">
            <h3>📋 Información de la Cita</h3>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--spacing-md);">
                <div>
                    <strong>Fecha:</strong><br>
                    <?= date('d/m/Y', strtotime($cita['fecha'])) ?> <?= $cita['hora'] ?>
                </div>
                <div>
                    <strong>Doctor:</strong><br>
                    <?= htmlspecialchars($cita['doctor_nombre']) ?>
                </div>
                <div>
                    <strong>Motivo:</strong><br>
                    <?= htmlspecialchars($cita['motivo'] ?? 'No especificado') ?>
                </div>
            </div>
        </div>

        <!-- Signos Vitales -->
        <div class="card" style="margin-bottom: var(--spacing-lg);">
            <h3>📊 Signos Vitales</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>Presión Arterial</label>
                    <input type="text" name="presion_arterial" class="form-control" placeholder="Ej: 120/80">
                </div>
                <div class="form-group">
                    <label>Temperatura (°C)</label>
                    <input type="text" name="temperatura" class="form-control" placeholder="Ej: 36.5">
                </div>
                <div class="form-group">
                    <label>Peso (kg)</label>
                    <input type="text" name="peso" class="form-control" placeholder="Ej: 70.5">
                </div>
                <div class="form-group">
                    <label>Altura (m)</label>
                    <input type="text" name="altura" class="form-control" placeholder="Ej: 1.75">
                </div>
            </div>
        </div>

        <!-- Diagnóstico y Notas -->
        <div class="card" style="margin-bottom: var(--spacing-lg);">
            <h3>🔍 Diagnóstico</h3>
            <div class="form-group">
                <label>Diagnóstico *</label>
                <textarea name="diagnostico" class="form-control" rows="3" required></textarea>
            </div>
            <div class="form-group">
                <label>Notas adicionales</label>
                <textarea name="notas" class="form-control" rows="2"></textarea>
            </div>
        </div>

        <!-- Antecedentes -->
        <div class="card" style="margin-bottom: var(--spacing-lg);">
            <h3>📝 Antecedentes</h3>
            <div class="form-group">
                <label>Alergias</label>
                <textarea name="alergias" class="form-control" rows="2" placeholder="Si no tiene, escribir 'Ninguna'"></textarea>
            </div>
            <div class="form-group">
                <label>Enfermedades Crónicas</label>
                <textarea name="enfermedades_cronicas" class="form-control" rows="2" placeholder="Ej: Diabetes, Hipertensión"></textarea>
            </div>
        </div>

        <!-- SECCIÓN DE RECETA MÉDICA -->
        <div class="card" style="margin-bottom: var(--spacing-lg); border-left: 4px solid var(--success);">
            <h3 style="color: var(--success);">💊 Receta Médica</h3>
            <p style="color: var(--gray-600); margin-bottom: var(--spacing-lg);">
                Los medicamentos recetados aparecerán automáticamente en el módulo de Farmacia
            </p>

            <div id="medicamentos-container">
                <!-- Plantilla para un medicamento -->
                <div class="medicamento-item form-row" style="margin-bottom: 15px; padding: 15px; background: var(--gray-100); border-radius: var(--radius-md);">
                    <div class="form-group" style="flex: 2;">
                        <label>Medicamento *</label>
                        <select name="medicamentos[0][producto_id]" class="form-control" required>
                            <option value="">-- Seleccionar medicamento --</option>
                            <?php foreach ($medicamentos as $med): ?>
                                <option value="<?= $med['id'] ?>">
                                    <?= htmlspecialchars($med['codigo']) ?> - <?= htmlspecialchars($med['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Cantidad *</label>
                        <input type="number" name="medicamentos[0][cantidad]" class="form-control" min="1" value="1" required>
                    </div>
                    <div class="form-group" style="flex: 2;">
                        <label>Indicaciones</label>
                        <input type="text" name="medicamentos[0][indicaciones]" class="form-control" 
                               placeholder="Ej: 1 tableta cada 8 horas por 7 días">
                    </div>
                    <div class="form-group" style="flex: 0.5;">
                        <label>&nbsp;</label>
                        <button type="button" class="btn btn-danger btn-sm" onclick="eliminarMedicamento(this)" title="Eliminar">✗</button>
                    </div>
                </div>
            </div>

            <div style="display: flex; gap: var(--spacing-md); margin-bottom: var(--spacing-lg);">
                <button type="button" class="btn btn-outline btn-sm" onclick="agregarMedicamento()">
                    ➕ Agregar otro medicamento
                </button>
            </div>

            <div class="form-group">
                <label>Observaciones de la receta</label>
                <textarea name="observaciones_receta" class="form-control" rows="2" 
                          placeholder="Indicaciones generales para el paciente..."></textarea>
            </div>
        </div>

        <!-- Botones -->
        <div style="display: flex; gap: var(--spacing-md); margin-top: var(--spacing-xl);">
            <button type="submit" class="btn btn-success" style="flex: 1; padding: var(--spacing-lg);">
                ✅ Guardar Atención y Receta
            </button>
            <a href="index.php" class="btn btn-outline">Cancelar</a>
        </div>
    </form>
</div>

<script>
let medIndex = 1;

function agregarMedicamento() {
    const container = document.getElementById('medicamentos-container');
    const template = container.children[0].cloneNode(true);
    
    // Actualizar índices en name
    template.innerHTML = template.innerHTML.replace(/medicamentos\[0\]/g, `medicamentos[${medIndex}]`);
    
    // Limpiar valores
    const select = template.querySelector('select');
    if (select) select.selectedIndex = 0;
    
    template.querySelectorAll('input').forEach(input => {
        if (input.type === 'number') input.value = '1';
        else input.value = '';
    });
    
    container.appendChild(template);
    medIndex++;
}

function eliminarMedicamento(btn) {
    const container = document.getElementById('medicamentos-container');
    if (container.children.length > 1) {
        btn.closest('.medicamento-item').remove();
    } else {
        alert('Debe haber al menos un medicamento');
    }
}

// Validar antes de enviar
document.getElementById('formAtencion').addEventListener('submit', function(e) {
    const medicamentos = document.querySelectorAll('select[name^="medicamentos"]');
    let tieneMedicamentos = false;
    
    medicamentos.forEach(select => {
        if (select.value !== '') tieneMedicamentos = true;
    });
    
    if (!tieneMedicamentos) {
        if (!confirm('¿No va a recetar ningún medicamento? Puede continuar si es solo consulta.')) {
            e.preventDefault();
        }
    }
});
</script>

<?php require_once '../../includes/footer.php'; ?>