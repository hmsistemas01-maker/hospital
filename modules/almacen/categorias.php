<?php
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Procesar eliminación (desactivar)
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    // Verificar que no tenga productos asociados
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM productos WHERE categoria_id = ?");
    $stmt->execute([$id]);
    $productos_asociados = $stmt->fetchColumn();
    
    if ($productos_asociados > 0) {
        header("Location: categorias.php?error=No se puede eliminar: tiene productos asociados");
        exit;
    }
    
    $stmt = $pdo->prepare("DELETE FROM categorias_productos WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: categorias.php?msg=eliminado");
    exit;
}

// Obtener categorías
$categorias = $pdo->query("
    SELECT c.*, 
           (SELECT COUNT(*) FROM productos WHERE categoria_id = c.id) as total_productos
    FROM categorias_productos c
    ORDER BY c.tipo, c.nombre
")->fetchAll();

// Estadísticas
$total_categorias = count($categorias);
$tipos = $pdo->query("SELECT tipo, COUNT(*) as total FROM categorias_productos GROUP BY tipo")->fetchAll();
?>

<div class="fade-in">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl);">
        <div>
            <h1>📂 Gestión de Categorías</h1>
            <p style="color: var(--gray-600);">Clasifica los productos del almacén</p>
        </div>
        <div>
            <span class="badge badge-primary">Total: <?= $total_categorias ?> categorías</span>
        </div>
    </div>

    <!-- Mensajes -->
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success">
            ✅ Categoría <?= $_GET['msg'] == 'guardado' ? 'guardada' : 'eliminada' ?> correctamente
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger">
            ❌ <?= htmlspecialchars($_GET['error']) ?>
        </div>
    <?php endif; ?>

    <!-- Formulario nueva categoría -->
    <div class="card">
        <h3 style="color: var(--primary); margin-bottom: var(--spacing-lg);">➕ Nueva Categoría</h3>
        
        <form method="POST" action="guardar_categoria.php" id="formCategoria">
            <div class="form-row">
                <div class="form-group">
                    <label class="required">Nombre de la categoría</label>
                    <input type="text" name="nombre" class="form-control" required 
                           placeholder="Ej: Analgésicos, Material de curación, etc."
                           maxlength="100">
                </div>
                
                <div class="form-group">
                    <label class="required">Tipo</label>
                    <select name="tipo" class="form-control" required>
                        <option value="">Seleccionar tipo...</option>
                        <option value="medicamento">💊 Medicamento</option>
                        <option value="material_medico">🩺 Material médico</option>
                        <option value="equipo">🔧 Equipo</option>
                        <option value="limpieza">🧹 Limpieza</option>
                        <option value="oficina">📎 Oficina</option>
                        <option value="alimento">🍎 Alimento</option>
                        <option value="otro">📦 Otro</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label>Descripción (opcional)</label>
                <textarea name="descripcion" class="form-control" rows="2" 
                          placeholder="Descripción de la categoría..."></textarea>
            </div>
            
            <div style="background: var(--gray-100); padding: var(--spacing-lg); border-radius: var(--radius-md); margin: var(--spacing-lg) 0;">
                <h4 style="margin-bottom: var(--spacing-md);">⚙️ Configuración de control</h4>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spacing-md);">
                    <label class="checkbox-card" style="display: flex; align-items: center; gap: var(--spacing-sm);">
                        <input type="checkbox" name="requiere_receta" value="1">
                        <span>🔖 Requiere receta médica</span>
                    </label>
                    
                    <label class="checkbox-card" style="display: flex; align-items: center; gap: var(--spacing-sm);">
                        <input type="checkbox" name="control_lote" value="1">
                        <span>🏷️ Control por lote</span>
                    </label>
                    
                    <label class="checkbox-card" style="display: flex; align-items: center; gap: var(--spacing-sm);">
                        <input type="checkbox" name="control_vencimiento" value="1">
                        <span>⏰ Control de vencimiento</span>
                    </label>
                </div>
                
                <small class="text-muted" style="display: block; margin-top: var(--spacing-sm);">
                    * Estas configuraciones aplicarán por defecto a todos los productos de esta categoría
                </small>
            </div>
            
            <div style="display: flex; gap: var(--spacing-md);">
                <button type="submit" class="btn btn-success">
                    <span>💾</span> Guardar Categoría
                </button>
                <button type="reset" class="btn btn-outline">
                    <span>🗑️</span> Limpiar
                </button>
            </div>
        </form>
    </div>

    <!-- Resumen por tipo -->
    <?php if (!empty($tipos)): ?>
    <div class="card" style="margin-top: var(--spacing-lg);">
        <h3>📊 Resumen por tipo</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: var(--spacing-md); margin-top: var(--spacing-md);">
            <?php foreach ($tipos as $t): ?>
                <div style="background: var(--gray-100); padding: var(--spacing-md); border-radius: var(--radius-md); text-align: center;">
                    <div style="font-size: 1.5rem; margin-bottom: var(--spacing-xs);">
                        <?php
                        $icono = [
                            'medicamento' => '💊',
                            'material_medico' => '🩺',
                            'equipo' => '🔧',
                            'limpieza' => '🧹',
                            'oficina' => '📎',
                            'alimento' => '🍎',
                            'otro' => '📦'
                        ][$t['tipo']] ?? '📁';
                        echo $icono;
                        ?>
                    </div>
                    <div style="font-weight: bold;"><?= ucfirst(str_replace('_', ' ', $t['tipo'])) ?></div>
                    <div class="badge badge-primary"><?= $t['total'] ?> categorías</div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tabla de categorías -->
    <div class="card" style="margin-top: var(--spacing-lg);">
        <h3>📋 Listado de categorías</h3>
        
        <?php if (empty($categorias)): ?>
            <div class="alert alert-info" style="text-align: center; padding: var(--spacing-xl);">
                <p style="font-size: 1.2rem;">📭 No hay categorías registradas</p>
                <p>Crea la primera categoría usando el formulario superior</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Configuración</th>
                            <th>Productos</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categorias as $c): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($c['nombre']) ?></strong></td>
                            <td>
                                <?php
                                $iconos_tipo = [
                                    'medicamento' => '💊',
                                    'material_medico' => '🩺',
                                    'equipo' => '🔧',
                                    'limpieza' => '🧹',
                                    'oficina' => '📎',
                                    'alimento' => '🍎',
                                    'otro' => '📦'
                                ];
                                $icono = $iconos_tipo[$c['tipo']] ?? '📁';
                                ?>
                                <span class="badge badge-info"><?= $icono ?> <?= ucfirst(str_replace('_', ' ', $c['tipo'])) ?></span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                                    <?php if ($c['requiere_receta']): ?>
                                        <span class="badge badge-warning">🔖 Receta</span>
                                    <?php endif; ?>
                                    <?php if ($c['control_lote']): ?>
                                        <span class="badge badge-primary">🏷️ Lote</span>
                                    <?php endif; ?>
                                    <?php if ($c['control_vencimiento']): ?>
                                        <span class="badge badge-danger">⏰ Vence</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-<?= $c['total_productos'] > 0 ? 'success' : 'secondary' ?>">
                                    <?= $c['total_productos'] ?> productos
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: var(--spacing-xs);">
                                    <?php if ($c['total_productos'] == 0): ?>
                                        <a href="categorias.php?eliminar=<?= $c['id'] ?>" 
                                           class="btn btn-sm btn-outline" 
                                           style="border-color: var(--danger); color: var(--danger);"
                                           onclick="return confirm('¿Eliminar esta categoría?')">
                                            🗑️ Eliminar
                                        </a>
                                    <?php else: ?>
                                        <span class="badge badge-secondary" title="No se puede eliminar: tiene productos asociados">
                                            🔒 En uso
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Script para validar formulario -->
<script>
document.getElementById('formCategoria')?.addEventListener('submit', function(e) {
    const nombre = this.querySelector('[name="nombre"]').value.trim();
    if (nombre.length < 3) {
        e.preventDefault();
        alert('El nombre debe tener al menos 3 caracteres');
        return;
    }
    
    const btn = this.querySelector('button[type="submit"]');
    btn.innerHTML = '⏳ Guardando...';
    btn.disabled = true;
});
</script>

<?php require_once '../../includes/footer.php'; ?>