<?php
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Procesar acciones
if (isset($_GET['desactivar'])) {
    $id = $_GET['desactivar'];
    $stmt = $pdo->prepare("UPDATE proveedores SET activo = 0 WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: proveedores.php?msg=desactivado");
    exit;
}

if (isset($_GET['activar'])) {
    $id = $_GET['activar'];
    $stmt = $pdo->prepare("UPDATE proveedores SET activo = 1 WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: proveedores.php?msg=activado");
    exit;
}

// Obtener proveedores
$proveedores = $pdo->query("
    SELECT p.*,
           (SELECT COUNT(*) FROM lote WHERE proveedor_id = p.id) as total_lotes,
           (SELECT COUNT(*) FROM lote WHERE proveedor_id = p.id AND fecha_vencimiento < CURDATE()) as lotes_vencidos
    FROM proveedores p
    ORDER BY p.activo DESC, p.nombre
")->fetchAll();

$activos = array_filter($proveedores, fn($p) => $p['activo'] == 1);
$inactivos = array_filter($proveedores, fn($p) => $p['activo'] == 0);
?>

<div class="fade-in">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl);">
        <div>
            <h1>🤝 Gestión de Proveedores</h1>
            <p style="color: var(--gray-600);">Administra las empresas que suministran productos</p>
        </div>
        <div>
            <span class="badge badge-success">Activos: <?= count($activos) ?></span>
            <span class="badge badge-secondary">Inactivos: <?= count($inactivos) ?></span>
        </div>
    </div>

    <!-- Mensajes -->
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success">
            ✅ Proveedor <?= $_GET['msg'] == 'guardado' ? 'guardado' : ($_GET['msg'] == 'actualizado' ? 'actualizado' : ($_GET['msg'] == 'desactivado' ? 'desactivado' : 'activado')) ?> correctamente
        </div>
    <?php endif; ?>

    <!-- Botón nuevo proveedor -->
    <div style="margin-bottom: var(--spacing-lg);">
        <a href="#" onclick="mostrarFormulario()" class="btn btn-success">
            <span>➕</span> Nuevo Proveedor
        </a>
    </div>

    <!-- Formulario nuevo proveedor (oculto inicialmente) -->
    <div id="formNuevo" style="display: none; margin-bottom: var(--spacing-xl);">
        <div class="card">
            <h3 style="color: var(--primary);">➕ Registrar Nuevo Proveedor</h3>
            
            <form method="POST" action="guardar_proveedor.php" id="formProveedor">
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Nombre o Razón Social</label>
                        <input type="text" name="nombre" class="form-control" required 
                               placeholder="Ej: Laboratorios Médicos SA de CV">
                    </div>
                    
                    <div class="form-group">
                        <label>RFC</label>
                        <input type="text" name="rfc" class="form-control" 
                               placeholder="Ej: ABC123456XYZ">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Persona de contacto</label>
                        <input type="text" name="contacto" class="form-control" 
                               placeholder="Ej: Juan Pérez">
                    </div>
                    
                    <div class="form-group">
                        <label>Teléfono</label>
                        <input type="tel" name="telefono" class="form-control" 
                               placeholder="Ej: 55-1234-5678">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" 
                               placeholder="Ej: contacto@proveedor.com">
                    </div>
                    
                    <div class="form-group">
                        <label>Dirección</label>
                        <input type="text" name="direccion" class="form-control" 
                               placeholder="Ej: Calle, número, colonia, ciudad">
                    </div>
                </div>
                
                <div style="display: flex; gap: var(--spacing-md); margin-top: var(--spacing-lg);">
                    <button type="submit" class="btn btn-success">
                        <span>💾</span> Guardar Proveedor
                    </button>
                    <button type="button" class="btn btn-outline" onclick="ocultarFormulario()">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de proveedores activos -->
    <div class="card">
        <h3>📋 Proveedores Activos</h3>
        
        <?php if (empty($activos)): ?>
            <div class="alert alert-info">
                No hay proveedores activos registrados
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Proveedor</th>
                            <th>Contacto</th>
                            <th>Teléfono/Email</th>
                            <th>RFC</th>
                            <th>Lotes</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activos as $p): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($p['nombre']) ?></strong>
                                <br>
                                <small><?= htmlspecialchars($p['direccion'] ?? '') ?></small>
                            </td>
                            <td><?= htmlspecialchars($p['contacto'] ?? '-') ?></td>
                            <td>
                                <?= htmlspecialchars($p['telefono'] ?? '-') ?>
                                <br>
                                <small><?= htmlspecialchars($p['email'] ?? '-') ?></small>
                            </td>
                            <td><?= htmlspecialchars($p['rfc'] ?? '-') ?></td>
                            <td>
                                <span class="badge badge-info"><?= $p['total_lotes'] ?> lotes</span>
                                <?php if ($p['lotes_vencidos'] > 0): ?>
                                    <span class="badge badge-danger"><?= $p['lotes_vencidos'] ?> vencidos</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: var(--spacing-xs);">
                                    <a href="editar_proveedor.php?id=<?= $p['id'] ?>" 
                                       class="btn btn-sm btn-outline" title="Editar">
                                        ✏️
                                    </a>
                                    <a href="proveedores.php?desactivar=<?= $p['id'] ?>" 
                                       class="btn btn-sm btn-outline" 
                                       style="border-color: var(--warning); color: var(--warning);"
                                       onclick="return confirm('¿Desactivar este proveedor?')"
                                       title="Desactivar">
                                        ⭕
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Tabla de proveedores inactivos -->
    <?php if (!empty($inactivos)): ?>
    <div class="card" style="margin-top: var(--spacing-lg);">
        <h3>📋 Proveedores Inactivos</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Proveedor</th>
                        <th>Contacto</th>
                        <th>Teléfono/Email</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inactivos as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['nombre']) ?></td>
                        <td><?= htmlspecialchars($p['contacto'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($p['telefono'] ?? '-') ?></td>
                        <td>
                            <a href="proveedores.php?activar=<?= $p['id'] ?>" 
                               class="btn btn-sm btn-success"
                               onclick="return confirm('¿Activar este proveedor?')">
                                ✅ Activar
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

<script>
function mostrarFormulario() {
    document.getElementById('formNuevo').style.display = 'block';
    window.scrollTo(0, document.getElementById('formNuevo').offsetTop - 100);
}

function ocultarFormulario() {
    document.getElementById('formNuevo').style.display = 'none';
    document.getElementById('formProveedor').reset();
}
</script>

<?php require_once '../../includes/footer.php'; ?>