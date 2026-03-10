<?php
require_once '../../config/config.php';
$modulo_requerido = 'admin';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Estadísticas
$stats = [];

// Total de usuarios
$stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
$stats['total_usuarios'] = $stmt->fetchColumn();

// Usuarios por rol
$stmt = $pdo->query("
    SELECT rol, COUNT(*) as total 
    FROM usuarios 
    WHERE rol IS NOT NULL AND rol != '' 
    GROUP BY rol
");
$usuarios_por_rol = $stmt->fetchAll();

// Doctores activos
$stmt = $pdo->query("SELECT COUNT(*) FROM doctores WHERE activo = 1");
$stats['doctores_activos'] = $stmt->fetchColumn();

// Usuarios inactivos
$stmt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE activo = 0");
$stats['usuarios_inactivos'] = $stmt->fetchColumn();

// Últimos usuarios registrados
$ultimos_usuarios = $pdo->query("
    SELECT id, nombre, usuario, rol, activo, fecha_registro 
    FROM usuarios 
    ORDER BY fecha_registro DESC 
    LIMIT 5
")->fetchAll();
?>

<div class="fade-in">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl);">
        <div>
            <h1>👑 Administración del Sistema</h1>
            <p style="color: var(--gray-600);">Gestión de usuarios, roles y permisos</p>
        </div>
        <a href="usuario_nuevo.php" class="btn btn-success">
            <span>➕</span> Nuevo Usuario
        </a>
    </div>

    <!-- Tarjetas de estadísticas -->
    <div class="stats-grid" style="margin-bottom: var(--spacing-xl);">
        <div class="stat-card primary">
            <div style="font-size: 2.5rem; margin-bottom: var(--spacing-sm);">👥</div>
            <div class="stat-value"><?= $stats['total_usuarios'] ?></div>
            <div class="stat-label">Total Usuarios</div>
        </div>
        
        <div class="stat-card success">
            <div style="font-size: 2.5rem; margin-bottom: var(--spacing-sm);">🩺</div>
            <div class="stat-value"><?= $stats['doctores_activos'] ?></div>
            <div class="stat-label">Doctores Activos</div>
        </div>
        
        <div class="stat-card <?= $stats['usuarios_inactivos'] > 0 ? 'warning' : 'success' ?>">
            <div style="font-size: 2.5rem; margin-bottom: var(--spacing-sm);">⭕</div>
            <div class="stat-value"><?= $stats['usuarios_inactivos'] ?></div>
            <div class="stat-label">Usuarios Inactivos</div>
        </div>
    </div>

    <!-- Gráfico de usuarios por rol -->
    <div class="card" style="margin-bottom: var(--spacing-xl);">
        <h3>📊 Usuarios por Rol</h3>
        <div style="display: flex; gap: var(--spacing-lg); flex-wrap: wrap; margin-top: var(--spacing-lg);">
            <?php 
            $rol_iconos = [
                'admin' => ['👑', 'Administradores'],
                'doctor' => ['🩺', 'Doctores'],
                'farmacia' => ['💊', 'Farmacia'],
                'registro' => ['📋', 'Registro']
            ];
            
            foreach ($usuarios_por_rol as $r): 
                $rol = $r['rol'];
                $total = $r['total'];
                $icono = $rol_iconos[$rol][0] ?? '👤';
                $nombre = $rol_iconos[$rol][1] ?? ucfirst($rol);
                $porcentaje = round(($total / $stats['total_usuarios']) * 100);
            ?>
                <div style="flex: 1; min-width: 150px; text-align: center;">
                    <div style="font-size: 2rem;"><?= $icono ?></div>
                    <div style="font-size: 1.5rem; font-weight: bold;"><?= $total ?></div>
                    <div><?= $nombre ?></div>
                    <div style="width: 100%; height: 8px; background: var(--gray-200); border-radius: 4px; margin-top: var(--spacing-sm);">
                        <div style="width: <?= $porcentaje ?>%; height: 100%; background: var(--primary); border-radius: 4px;"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

   <!-- Accesos rápidos -->
<h2 style="margin-bottom: var(--spacing-lg);">Accesos Rápidos</h2>
<div class="grid-modulos" style="margin-bottom: var(--spacing-xl);">
    <a href="usuarios.php" class="card-modulo primary">
        <div style="font-size: 2.5rem; margin-bottom: var(--spacing-sm);">👥</div>
        <h3>Gestión de Usuarios</h3>
        <p style="font-size: var(--font-size-sm); opacity: 0.8;">Crear, editar y asignar permisos</p>
    </a>
    
    <a href="usuarios.php?rol=doctor" class="card-modulo success">
        <div style="font-size: 2.5rem; margin-bottom: var(--spacing-sm);">🩺</div>
        <h3>Doctores</h3>
        <p style="font-size: var(--font-size-sm); opacity: 0.8;">Gestionar médicos y sus horarios</p>
    </a>
    
    <a href="usuarios.php?rol=admin" class="card-modulo warning">
        <div style="font-size: 2.5rem; margin-bottom: var(--spacing-sm);">👑</div>
        <h3>Administradores</h3>
        <p style="font-size: var(--font-size-sm); opacity: 0.8;">Usuarios con acceso total</p>
    </a>
    
    <a href="permisos.php" class="card-modulo info">
        <div style="font-size: 2.5rem; margin-bottom: var(--spacing-sm);">🔐</div>
        <h3>Permisos</h3>
        <p style="font-size: var(--font-size-sm); opacity: 0.8;">Gestionar permisos del sistema</p>
    </a>
</div>
    <!-- Últimos usuarios registrados -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-lg);">
            <h3>🆕 Últimos Usuarios Registrados</h3>
            <a href="usuarios.php" class="btn btn-sm btn-outline">Ver todos →</a>
        </div>
        
        <?php if (empty($ultimos_usuarios)): ?>
            <div class="alert alert-info">No hay usuarios registrados</div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Fecha Registro</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimos_usuarios as $u): ?>
                        <tr>
                            <td>#<?= $u['id'] ?></td>
                            <td><strong><?= htmlspecialchars($u['nombre'] ?? 'Sin nombre') ?></strong></td>
                            <td><?= htmlspecialchars($u['usuario']) ?></td>
                            <td>
                                <?php
                                $rol_icono = '';
                                switch($u['rol']) {
                                    case 'admin': $rol_icono = '👑'; break;
                                    case 'doctor': $rol_icono = '🩺'; break;
                                    case 'farmacia': $rol_icono = '💊'; break;
                                    case 'registro': $rol_icono = '📋'; break;
                                    default: $rol_icono = '👤';
                                }
                                ?>
                                <span class="badge badge-primary"><?= $rol_icono ?> <?= ucfirst($u['rol'] ?: 'Usuario') ?></span>
                            </td>
                            <td>
                                <?php if ($u['activo']): ?>
                                    <span class="badge badge-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($u['fecha_registro'])) ?></td>
                            <td>
                                <a href="editar_usuario.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline">✏️ Editar</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>