<?php
require_once '../../config/config.php';
$modulo_requerido = 'doctor';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Obtener el ID del doctor logueado
$doctor_id = null;
$nombre_usuario = $_SESSION['usuario'];

// Buscar el doctor por nombre en la tabla doctores
// Primero, intentamos buscar exactamente por el nombre del usuario
$stmt = $pdo->prepare("SELECT id, nombre FROM doctores WHERE nombre LIKE ? AND activo = 1");
$stmt->execute(["%$nombre_usuario%"]);
$doctor = $stmt->fetch();

if ($doctor) {
    $doctor_id = $doctor['id'];
} else {
    // Si no encuentra, intentamos buscar por el nombre completo del usuario
    $stmt = $pdo->prepare("
        SELECT d.id, d.nombre 
        FROM doctores d
        JOIN usuarios u ON u.nombre LIKE CONCAT('%', d.nombre, '%')
        WHERE u.id = ? AND d.activo = 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $doctor = $stmt->fetch();
    if ($doctor) {
        $doctor_id = $doctor['id'];
    }
}

// Si aún no encontramos el doctor, buscamos en la tabla usuarios para ver su rol
if (!$doctor_id) {
    $stmt = $pdo->prepare("SELECT nombre, rol FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $usuario_info = $stmt->fetch();
    
    if ($usuario_info && $usuario_info['rol'] == 'doctor') {
        // Es un doctor pero no tiene registro en tabla doctores, lo creamos
        $stmt = $pdo->prepare("
            INSERT INTO doctores (nombre, especialidad, activo, fecha_registro) 
            VALUES (?, 'General', 1, NOW())
        ");
        $stmt->execute([$usuario_info['nombre']]);
        $doctor_id = $pdo->lastInsertId();
    }
}

// Debug - puedes comentar esto después de verificar
if (!$doctor_id) {
    echo "<!-- Debug: No se encontró doctor para el usuario: $nombre_usuario -->";
} else {
    echo "<!-- Debug: Doctor ID encontrado: $doctor_id -->";
}

// Estadísticas para el doctor
$stats = [];

// Citas de hoy para ESTE doctor específico
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM citas 
    WHERE doctor_id = ? AND fecha = CURDATE() AND estado = 'pendiente'
");
$stmt->execute([$doctor_id]);
$stats['citas_hoy'] = $stmt->fetchColumn();

// Citas atendidas hoy
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM citas 
    WHERE doctor_id = ? AND fecha = CURDATE() AND estado = 'atendida'
");
$stmt->execute([$doctor_id]);
$stats['atendidas_hoy'] = $stmt->fetchColumn();

// Total pacientes atendidos (histórico)
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT paciente_id) FROM citas 
    WHERE doctor_id = ? AND estado = 'atendida'
");
$stmt->execute([$doctor_id]);
$stats['total_pacientes'] = $stmt->fetchColumn();

// Próximas citas del día
$citas_hoy = $pdo->prepare("
    SELECT c.*, p.nombre as paciente_nombre, p.telefono, p.curp
    FROM citas c
    JOIN pacientes p ON c.paciente_id = p.id
    WHERE c.doctor_id = ? AND c.fecha = CURDATE() AND c.estado = 'pendiente'
    ORDER BY c.hora ASC
");
$citas_hoy->execute([$doctor_id]);
$citas_hoy = $citas_hoy->fetchAll();

// Citas de mañana
$citas_manana = $pdo->prepare("
    SELECT c.*, p.nombre as paciente_nombre
    FROM citas c
    JOIN pacientes p ON c.paciente_id = p.id
    WHERE c.doctor_id = ? AND c.fecha = DATE_ADD(CURDATE(), INTERVAL 1 DAY) AND c.estado = 'pendiente'
    ORDER BY c.hora ASC
");
$citas_manana->execute([$doctor_id]);
$citas_manana = $citas_manana->fetchAll();

// Historial reciente (últimas 5 citas atendidas)
$historial = $pdo->prepare("
    SELECT c.*, p.nombre as paciente_nombre
    FROM citas c
    JOIN pacientes p ON c.paciente_id = p.id
    WHERE c.doctor_id = ? AND c.estado = 'atendida'
    ORDER BY c.fecha DESC, c.hora DESC
    LIMIT 5
");
$historial->execute([$doctor_id]);
$historial = $historial->fetchAll();
?>

<div class="fade-in">
    <!-- Header del módulo -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl);">
        <div>
            <h1>🩺 Panel del Doctor</h1>
            <p style="color: var(--gray-600);">
                Bienvenido, Dr. <?= htmlspecialchars($doctor['nombre'] ?? $_SESSION['usuario']) ?>
                <?php if ($doctor_id): ?>
                    <span style="margin-left: var(--spacing-sm);" class="badge badge-success">ID: <?= $doctor_id ?></span>
                <?php endif; ?>
            </p>
        </div>
        <div style="display: flex; gap: var(--spacing-sm);">
            <a href="mi_perfil.php" class="btn btn-outline">
                <span>👤</span> Mi Perfil
            </a>
            <a href="horarios.php?doctor_id=<?= $doctor_id ?>" class="btn btn-outline">
                <span>⏰</span> Mis Horarios
            </a>
        </div>
    </div>

    <?php if (!$doctor_id): ?>
        <div class="alert alert-danger">
            <strong>Error:</strong> No se pudo identificar su perfil de doctor. 
            Contacte al administrador del sistema.
        </div>
    <?php endif; ?>

    <!-- Tarjetas de estadísticas -->
    <div class="stats-grid" style="margin-bottom: var(--spacing-xl);">
        <div class="stat-card primary">
            <div style="font-size: 2.5rem; margin-bottom: var(--spacing-sm);">📅</div>
            <div class="stat-value"><?= $stats['citas_hoy'] ?></div>
            <div class="stat-label">Citas Pendientes Hoy</div>
        </div>
        
        <div class="stat-card success">
            <div style="font-size: 2.5rem; margin-bottom: var(--spacing-sm);">✅</div>
            <div class="stat-value"><?= $stats['atendidas_hoy'] ?></div>
            <div class="stat-label">Atendidas Hoy</div>
        </div>
        
        <div class="stat-card info">
            <div style="font-size: 2.5rem; margin-bottom: var(--spacing-sm);">👥</div>
            <div class="stat-value"><?= $stats['total_pacientes'] ?></div>
            <div class="stat-label">Pacientes Atendidos</div>
        </div>
    </div>

    <!-- Citas de hoy -->
    <div class="card" style="margin-bottom: var(--spacing-xl);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-lg);">
            <h3>⏰ Citas de Hoy <span style="font-size: 0.9rem; color: var(--gray-500);">(<?= date('d/m/Y') ?>)</span></h3>
            <span class="badge badge-primary">Total: <?= count($citas_hoy) ?></span>
        </div>
        
        <?php if (empty($citas_hoy)): ?>
            <div class="alert alert-success" style="text-align: center; padding: var(--spacing-xl);">
                <p style="font-size: 1.2rem;">🎉 ¡No tienes citas pendientes para hoy!</p>
                <p style="margin-top: var(--spacing-sm);">Disfruta tu día</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Hora</th>
                            <th>Paciente</th>
                            <th>CURP</th>
                            <th>Teléfono</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($citas_hoy as $c): ?>
                        <tr>
                            <td><strong><?= substr($c['hora'], 0, 5) ?></strong></td>
                            <td><?= htmlspecialchars($c['paciente_nombre']) ?></td>
                            <td><?= htmlspecialchars($c['curp'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($c['telefono'] ?? 'N/A') ?></td>
                            <td>
                                <a href="atender_cita.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-success">
                                    🩺 Atender
                                </a>
                                <a href="../citas/detalle.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline">
                                    📋 Detalle
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Citas de mañana -->
    <?php if (!empty($citas_manana)): ?>
    <div class="card" style="margin-bottom: var(--spacing-xl);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-lg);">
            <h3>📆 Citas para Mañana <span style="font-size: 0.9rem; color: var(--gray-500);">(<?= date('d/m/Y', strtotime('+1 day')) ?>)</span></h3>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Hora</th>
                        <th>Paciente</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($citas_manana as $c): ?>
                    <tr>
                        <td><strong><?= substr($c['hora'], 0, 5) ?></strong></td>
                        <td><?= htmlspecialchars($c['paciente_nombre']) ?></td>
                        <td>
                            <a href="../citas/detalle.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline">
                                📋 Ver
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Historial reciente -->
    <?php if (!empty($historial)): ?>
    <div class="card">
        <h3>📋 Últimos Pacientes Atendidos</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Paciente</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historial as $h): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($h['fecha'])) ?> <?= substr($h['hora'], 0, 5) ?></td>
                        <td><?= htmlspecialchars($h['paciente_nombre']) ?></td>
                        <td>
                          <a href="../citas/lista.php?paciente_id=<?= $h['paciente_id'] ?>" class="btn btn-sm btn-outline">
    📋 Ver Citas
</a>
<a href="../registro/editar_paciente.php?id=<?= $h['paciente_id'] ?>" class="btn btn-sm btn-outline">
    👤 Ver Paciente
</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer.php'; ?>
