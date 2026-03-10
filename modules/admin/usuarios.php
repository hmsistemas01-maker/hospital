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
$sql = "SELECT u.* FROM usuarios u WHERE 1=1";
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
?>

<div class="fade-in">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl);">
        <div>
            <h1>👥 Gestión de Usuarios</h1>
            <p style="color: var(--gray-600);">Administra los usuarios del sistema y sus roles</p>
        </div>
        <a href="usuario_nuevo.php" class="btn btn-success">
            <span>➕</span> Nuevo Usuario
        </a>
    </div>

    <!-- Filtros rápidos por rol -->
    <div style="display: flex; gap: var(--spacing-sm); margin-bottom: var(--spacing-lg); flex-wrap: wrap;">
        <a href="usuarios.php" class="btn <?= !$rol_filtro ? 'btn-primary' : 'btn-outline' ?>">
            Todos (<?= $conteo['todos'] ?>)
        </a>
        <a href="usuarios.php?rol=admin" class="btn <?= $rol_filtro == 'admin' ? 'btn-primary' : 'btn-outline' ?>">
            👑 Admin (<?= $conteo['admin'] ?? 0 ?>)
        </a>
        <a href="usuarios.php?rol=doctor" class="btn <?= $rol_filtro == 'doctor' ? 'btn-primary' : 'btn-outline' ?>">
            🩺 Doctores (<?= $conteo['doctor'] ?? 0 ?>)
        </a>
        <a href="usuarios.php?rol=farmacia" class="btn <?= $rol_filtro == 'farmacia' ? 'btn-primary' : 'btn-outline' ?>">
            💊 Farmacia (<?= $conteo['farmacia'] ?? 0 ?>)
        </a>
        <a href="usuarios.php?rol=registro" class="btn <?= $rol_filtro == 'registro' ? 'btn-primary' : 'btn-outline' ?>">
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
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): 
                        // Obtener módulos del usuario
                        $stmt = $pdo->prepare("
                            SELECT m.nombre FROM usuario_modulos um
                            JOIN modulos m ON um.modulo_id = m.id
                            WHERE um.usuario_id = ?
                        ");
                        $stmt->execute([$u['id']]);
                        $modulos = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        
                        // Si es doctor, obtener su ID de la tabla doctores
                        $doctor_id = null;
                        if ($u['rol'] == 'doctor') {
                            $stmt2 = $pdo->prepare("SELECT id FROM doctores WHERE nombre LIKE ?");
                            $stmt2->execute(["%{$u['nombre']}%"]);
                            $doctor = $stmt2->fetch();
                            $doctor_id = $doctor ? $doctor['id'] : null;
                        }
                    ?>
                    <tr>
                        <td>#<?= $u['id'] ?></td>
                        <td><strong><?= htmlspecialchars($u['nombre'] ?: 'Sin nombre') ?></strong></td>
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
                            <?php foreach ($modulos as $m): ?>
                                <span class="badge badge-secondary"><?= $m ?></span>
                            <?php endforeach; ?>
                        </td>
                        <td>
                            <?php if ($u['activo']): ?>
                                <span class="badge badge-success">Activo</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display: flex; gap: var(--spacing-xs);">
                                <a href="editar_usuario.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline" title="Editar">
                                    ✏️
                                </a>
                                
                                <?php if ($u['rol'] == 'doctor' && $doctor_id): ?>
                                    <a href="horarios.php?doctor_id=<?= $doctor_id ?>" class="btn btn-sm btn-outline" title="Horarios">
                                        ⏰
                                    </a>
                                <?php endif; ?>
                                
                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                    <?php if ($u['activo']): ?>
                                        <a href="desactivar_usuario.php?id=<?= $u['id'] ?>" 
                                           class="btn btn-sm btn-outline" 
                                           style="border-color: var(--warning); color: var(--warning);"
                                           onclick="return confirm('¿Desactivar este usuario?')">
                                            ⭕
                                        </a>
                                    <?php else: ?>
                                        <a href="activar_usuario.php?id=<?= $u['id'] ?>" 
                                           class="btn btn-sm btn-outline"
                                           style="border-color: var(--success); color: var(--success);">
                                            ✅
                                        </a>
                                    <?php endif; ?>
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