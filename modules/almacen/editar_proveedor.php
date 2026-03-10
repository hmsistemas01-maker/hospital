<?php
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

if (!isset($_GET['id'])) {
    header("Location: proveedores.php");
    exit;
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM proveedores WHERE id = ?");
$stmt->execute([$id]);
$proveedor = $stmt->fetch();

if (!$proveedor) {
    header("Location: proveedores.php?error=Proveedor no encontrado");
    exit;
}
?>

<div class="fade-in">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl);">
        <div>
            <h1>✏️ Editar Proveedor</h1>
            <p style="color: var(--gray-600);">Modificando: <strong><?= htmlspecialchars($proveedor['nombre']) ?></strong></p>
        </div>
        <a href="proveedores.php" class="btn btn-outline">← Volver</a>
    </div>

    <div class="card" style="max-width: 800px; margin: 0 auto;">
        <form method="POST" action="actualizar_proveedor.php">
            <input type="hidden" name="id" value="<?= $proveedor['id'] ?>">
            
            <div class="form-row">
                <div class="form-group">
                    <label class="required">Nombre</label>
                    <input type="text" name="nombre" class="form-control" required 
                           value="<?= htmlspecialchars($proveedor['nombre']) ?>">
                </div>
                <div class="form-group">
                    <label>RFC</label>
                    <input type="text" name="rfc" class="form-control" 
                           value="<?= htmlspecialchars($proveedor['rfc'] ?? '') ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Contacto</label>
                    <input type="text" name="contacto" class="form-control" 
                           value="<?= htmlspecialchars($proveedor['contacto'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="tel" name="telefono" class="form-control" 
                           value="<?= htmlspecialchars($proveedor['telefono'] ?? '') ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" 
                           value="<?= htmlspecialchars($proveedor['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Dirección</label>
                    <input type="text" name="direccion" class="form-control" 
                           value="<?= htmlspecialchars($proveedor['direccion'] ?? '') ?>">
                </div>
            </div>
            
            <div style="display: flex; gap: var(--spacing-md); margin-top: var(--spacing-lg);">
                <button type="submit" class="btn btn-primary">Actualizar Proveedor</button>
                <a href="proveedores.php" class="btn btn-outline">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>