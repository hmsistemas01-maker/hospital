<?php
require_once '../../config/config.php';
$modulo_requerido = 'admin';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Filtros
$rol_filtro = $_GET['rol'] ?? '';
$busqueda = $_GET['buscar'] ?? '';

// Construir consulta
$sql = "SELECT u.*, 
               (SELECT COUNT(*) FROM doctor_horarios WHERE usuario_id = u.id) as total_horarios,
               (SELECT COUNT(*) FROM doctor_excepciones WHERE usuario_id = u.id) as total_excepciones
        FROM usuarios u 
        WHERE 1=1";
$params = [];

if (!empty($rol_filtro)) {
    $sql .= " AND u.rol = ?";
    $params[] = $rol_filtro;
}

if (!empty($busqueda)) {
    $sql .= " AND (u.nombre LIKE ? OR u.usuario LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
}

$sql .= " ORDER BY u.nombre ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$usuarios = $stmt->fetchAll();

// Contar por rol para los filtros
$conteo_roles = $pdo->query("
    SELECT rol, COUNT(*) as total
    FROM usuarios
    WHERE rol IS NOT NULL AND rol != ''
    GROUP BY rol
")->fetchAll();

$conteo = ['todos' => count($usuarios)];
foreach ($conteo_roles as $c) {
    $conteo[$c['rol']] = $c['total'];
}

// Mensajes de sesión
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<div class="fade-in">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl);">
        <div>
            <h1>👥 Gestión de Usuarios</h1>
            <p style="color: var(--gray-600);">Total: <strong><?= $conteo['todos'] ?></strong> usuarios</p>
        </div>
        <a href="usuario_nuevo.php" class="btn btn-success">
            <span>➕</span> Nuevo Usuario
        </a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Filtros por rol -->
    <div style="display: flex; gap: var(--spacing-sm); margin-bottom: var(--spacing-lg); flex-wrap: wrap;">
        <a href="usuarios.php" class="btn <?= !$rol_filtro ? 'btn-primary' : 'btn-outline' ?>">
            Todos (<?= $conteo['todos'] ?>)
        </a>
        <a href="?rol=admin" class="btn <?= $rol_filtro == 'admin' ? 'btn-primary' : 'btn-outline' ?>">
            👑 Admin (<?= $conteo['admin'] ?? 0 ?>)
        </a>
        <a href="?rol=doctor" class="btn <?= $rol_filtro == 'doctor' ? 'btn-primary' : 'btn-outline' ?>">
            🩺 Doctores (<?= $conteo['doctor'] ?? 0 ?>)
        </a>
        <a href="?rol=farmacia" class="btn <?= $rol_filtro == 'farmacia' ? 'btn-primary' : 'btn-outline' ?>">
            💊 Farmacia (<?= $conteo['farmacia'] ?? 0 ?>)
        </a>
        <a href="?rol=registro" class="btn <?= $rol_filtro == 'registro' ? 'btn-primary' : 'btn-outline' ?>">
            📋 Registro (<?= $conteo['registro'] ?? 0 ?>)
        </a>
    </div>

    <!-- Buscador -->
    <div class="card" style="margin-bottom: var(--spacing-lg);">
        <form method="GET" class="form-row" style="align-items: flex-end;">
            <?php if ($rol_filtro): ?>
                <input type="hidden" name="rol" value="<?= $rol_filtro ?>">
            <?php endif; ?>
            
            <div class="form-group" style="flex: 1;">
                <label>Buscar usuario</label>
                <input type="text" name="buscar" class="form-control" 
                       value="<?= htmlspecialchars($busqueda) ?>" 
                       placeholder="Nombre o usuario...">
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">🔍 Buscar</button>
                <?php if ($busqueda || $rol_filtro): ?>
                    <a href="usuarios.php" class="btn btn-outline">Limpiar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Tabla de usuarios -->
    <div class="card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Módulos</th>
                        <th>Horarios</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
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
                                <?php
                                // Obtener módulos del usuario
                                $stmt_mod = $pdo->prepare("SELECT m.nombre FROM usuario_modulos um JOIN modulos m ON um.modulo_id = m.id WHERE um.usuario_id = ?");
                                $stmt_mod->execute([$u['id']]);
                                $modulos_user = $stmt_mod->fetchAll();
                                $total_mod = count($modulos_user);
                                ?>
                                <span class="badge badge-info"><?= $total_mod ?> módulos</span>
                                <?php if ($total_mod > 0): ?>
                                    <br><small><?= implode(', ', array_column($modulos_user, 'nombre')) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($u['total_horarios'] > 0): ?>
                                    <span class="badge badge-success"><?= $u['total_horarios'] ?> horarios</span>
                                    <?php if ($u['total_excepciones'] > 0): ?>
                                        <br><small class="badge badge-warning"><?= $u['total_excepciones'] ?> exc.</small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Sin horarios</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($u['activo']): ?>
                                    <span class="badge badge-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: var(--spacing-xs); flex-wrap: wrap;">
                                    <!-- Botón de horarios -->
                                    <a href="horarios.php?usuario_id=<?= $u['id'] ?>" 
                                       class="btn btn-sm btn-outline" 
                                       title="Gestionar horarios"
                                       style="border-color: var(--info); color: var(--info);">
                                        ⏰
                                    </a>
                                    
                                    <!-- Botón de editar -->
                                    <a href="editar_usuario.php?id=<?= $u['id'] ?>" 
                                       class="btn btn-sm btn-outline" 
                                       title="Editar usuario">
                                        ✏️
                                    </a>
                                    
                                    <!-- Botones de activar/desactivar -->
                                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                        <?php if ($u['activo']): ?>
                                            <a href="desactivar_usuario.php?id=<?= $u['id'] ?>" 
                                               class="btn btn-sm btn-outline" 
                                               style="border-color: var(--warning); color: var(--warning);"
                                               onclick="return confirm('¿Desactivar este usuario?')"
                                               title="Desactivar">
                                                ⭕
                                            </a>
                                        <?php else: ?>
                                            <a href="activar_usuario.php?id=<?= $u['id'] ?>" 
                                               class="btn btn-sm btn-outline" 
                                               style="border-color: var(--success); color: var(--success);"
                                               title="Activar">
                                                ✅
                                            </a>
                                        <?php endif; ?>
                                        
                                        <!-- Botón de eliminar (borrado físico) -->
                                        <a href="eliminar_usuario.php?id=<?= $u['id'] ?>" 
                                           class="btn btn-sm btn-outline" 
                                           style="border-color: var(--danger); color: var(--danger);"
                                           onclick="return confirm('¿ESTÁS SEGURO? Esto eliminará permanentemente el usuario y todos sus datos relacionados. Esta acción NO SE PUEDE DESHACER.')"
                                           title="Eliminar permanentemente">
                                            🗑️
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>