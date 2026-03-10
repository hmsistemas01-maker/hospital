<?php
require_once '../../config/config.php';
$modulo_requerido = 'doctor';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$cita_id = (int) ($_GET['id'] ?? 0);

if (!$cita_id) {
    $_SESSION['error'] = "ID de cita no válido";
    header("Location: index.php");
    exit;
}

// Obtener datos de la cita con información completa
$stmt = $pdo->prepare("
    SELECT c.*, 
           p.id as paciente_id, 
           p.nombre as paciente_nombre, 
           p.curp, 
           p.fecha_nacimiento, 
           p.sexo,
           p.telefono as paciente_telefono,
           d.id as doctor_id,
           d.nombre as doctor_nombre,
           d.especialidad
    FROM citas c
    JOIN pacientes p ON c.paciente_id = p.id
    JOIN doctores d ON c.doctor_id = d.id
    WHERE c.id = ? AND c.estado = 'pendiente'
");
$stmt->execute([$cita_id]);
$cita = $stmt->fetch();

if (!$cita) {
    $_SESSION['error'] = "Cita no encontrada o ya no está pendiente";
    header("Location: index.php");
    exit;
}

// Verificar que la cita sea de hoy
if ($cita['fecha'] != date('Y-m-d')) {
    $_SESSION['error'] = "Solo se pueden atender citas del día actual";
    header("Location: index.php");
    exit;
}

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $diagnostico = trim($_POST['diagnostico'] ?? '');
    $notas = trim($_POST['notas'] ?? '');
    $receta = trim($_POST['receta'] ?? '');
    $presion_arterial = $_POST['presion_arterial'] ?? null;
    $temperatura = $_POST['temperatura'] ?? null;
    $peso = $_POST['peso'] ?? null;
    $altura = $_POST['altura'] ?? null;
    $alergias = trim($_POST['alergias'] ?? '');
    $enfermedades_cronicas = trim($_POST['enfermedades_cronicas'] ?? '');
    
    // Validaciones
    $errores = [];
    
    if (empty($diagnostico)) {
        $errores[] = "El diagnóstico es obligatorio";
    }
    
    if (empty($errores)) {
        try {
            $pdo->beginTransaction();
            
            // 1. Actualizar estado de la cita
            $stmt = $pdo->prepare("UPDATE citas SET estado = 'atendida' WHERE id = ?");
            $stmt->execute([$cita_id]);
            
            // 2. Crear entrada en historial clínico
            $stmt = $pdo->prepare("
                INSERT INTO historial_clinico (
                    paciente_id, 
                    doctor_id, 
                    fecha, 
                    diagnostico, 
                    notas,
                    presion_arterial,
                    temperatura,
                    peso,
                    altura,
                    alergias,
                    enfermedades_cronicas,
                    created_at
                ) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $cita['paciente_id'],
                $cita['doctor_id'],
                $diagnostico,
                $notas,
                $presion_arterial,
                $temperatura,
                $peso,
                $altura,
                $alergias,
                $enfermedades_cronicas
            ]);
            
            $historial_id = $pdo->lastInsertId();
            
            // 3. Si hay receta médica, guardarla
            if (!empty($receta)) {
                $stmt = $pdo->prepare("
                    INSERT INTO recetas (historial_id, indicaciones, fecha, estado) 
                    VALUES (?, ?, NOW(), 'pendiente')
                ");
                $stmt->execute([$historial_id, $receta]);
            }
            
            $pdo->commit();
            
            $_SESSION['success'] = "Atención médica registrada correctamente";
            header("Location: detalle_atencion.php?historial_id=$historial_id");
            exit;
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error al guardar: " . $e->getMessage();
        }
    }
}

require_once '../../includes/header.php';
?>

<div class="fade-in">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl);">
        <div>
            <h1>🩺 Atender Cita Médica</h1>
            <p style="color: var(--gray-600);">Registrar diagnóstico y tratamiento</p>
        </div>
        <a href="index.php" class="btn btn-outline">
            <span>←</span> Volver al Panel
        </a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Información del paciente y cita -->
    <div class="card" style="margin-bottom: var(--spacing-lg);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-md);">
            <h3 style="margin: 0;">👤 Información del Paciente</h3>
            <span class="badge badge-primary">Cita #<?= $cita_id ?></span>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: var(--spacing-md); background: var(--gray-100); padding: var(--spacing-lg); border-radius: var(--radius-md);">
            <div>
                <label style="font-weight: bold; color: var(--gray-600);">Nombre:</label>
                <div style="font-size: 1.1rem;"><?= htmlspecialchars($cita['paciente_nombre']) ?></div>
            </div>
            <div>
                <label style="font-weight: bold; color: var(--gray-600);">CURP:</label>
                <div><?= htmlspecialchars($cita['curp'] ?? 'N/A') ?></div>
            </div>
            <div>
                <label style="font-weight: bold; color: var(--gray-600);">Fecha Nac.:</label>
                <div><?= $cita['fecha_nacimiento'] ? date('d/m/Y', strtotime($cita['fecha_nacimiento'])) : 'N/A' ?></div>
            </div>
            <div>
                <label style="font-weight: bold; color: var(--gray-600);">Sexo:</label>
                <div><?= $cita['sexo'] == 'M' ? 'Masculino' : 'Femenino' ?></div>
            </div>
            <div>
                <label style="font-weight: bold; color: var(--gray-600);">Teléfono:</label>
                <div><?= htmlspecialchars($cita['paciente_telefono'] ?? 'N/A') ?></div>
            </div>
            <div>
                <label style="font-weight: bold; color: var(--gray-600);">Fecha Cita:</label>
                <div><?= date('d/m/Y', strtotime($cita['fecha'])) ?> a las <?= substr($cita['hora'], 0, 5) ?></div>
            </div>
            <?php if (!empty($cita['motivo'])): ?>
            <div style="grid-column: span 2;">
                <label style="font-weight: bold; color: var(--gray-600);">Motivo de consulta:</label>
                <div><?= nl2br(htmlspecialchars($cita['motivo'])) ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Formulario de atención médica -->
    <div class="card">
        <h3>📋 Registro de Atención</h3>
        
        <form method="POST" action="" id="formAtencion">
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: var(--spacing-md); margin-bottom: var(--spacing-lg);">
                <div class="form-group">
                    <label>Presión Arterial</label>
                    <input type="text" name="presion_arterial" class="form-control" 
                           placeholder="Ej: 120/80">
                </div>
                <div class="form-group">
                    <label>Temperatura (°C)</label>
                    <input type="text" name="temperatura" class="form-control" 
                           placeholder="Ej: 36.5">
                </div>
                <div class="form-group">
                    <label>Peso (kg)</label>
                    <input type="text" name="peso" class="form-control" 
                           placeholder="Ej: 70.5">
                </div>
                <div class="form-group">
                    <label>Altura (m)</label>
                    <input type="text" name="altura" class="form-control" 
                           placeholder="Ej: 1.75">
                </div>
            </div>
            
            <div class="form-group">
                <label class="required">Diagnóstico</label>
                <textarea name="diagnostico" class="form-control" rows="4" required 
                          placeholder="Descripción detallada del diagnóstico..."></textarea>
            </div>
            
            <div class="form-group">
                <label>Notas adicionales</label>
                <textarea name="notas" class="form-control" rows="3" 
                          placeholder="Observaciones, recomendaciones, estudios solicitados..."></textarea>
            </div>
            
            <div class="form-group">
                <label>Alergias conocidas</label>
                <textarea name="alergias" class="form-control" rows="2" 
                          placeholder="Ej: Penicilina, aspirina, etc."></textarea>
            </div>
            
            <div class="form-group">
                <label>Enfermedades crónicas</label>
                <textarea name="enfermedades_cronicas" class="form-control" rows="2" 
                          placeholder="Ej: Diabetes, hipertensión, etc."></textarea>
            </div>
            
            <div class="form-group">
                <label>Receta médica</label>
                <textarea name="receta" class="form-control" rows="4" 
                          placeholder="Medicamentos, dosis, indicaciones..."></textarea>
                <small style="color: var(--gray-500);">Se generará una receta pendiente para farmacia</small>
            </div>
            
            <div style="display: flex; gap: var(--spacing-md); margin-top: var(--spacing-xl);">
                <button type="submit" class="btn btn-success" style="flex: 1;">
                    ✅ Finalizar Atención
                </button>
                <a href="index.php" class="btn btn-outline">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
// Validar campos numéricos
document.querySelector('input[name="temperatura"]').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9.]/g, '');
});

document.querySelector('input[name="peso"]').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9.]/g, '');
});

document.querySelector('input[name="altura"]').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9.]/g, '');
});

// Confirmar antes de enviar
document.getElementById('formAtencion').addEventListener('submit', function(e) {
    if (!confirm('¿Registrar esta atención médica? Una vez guardada no se podrá modificar.')) {
        e.preventDefault();
    }
});
</script>

<?php require_once '../../includes/footer.php'; ?>