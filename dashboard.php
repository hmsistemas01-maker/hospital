<?php
require_once 'config/config.php';
require_once 'includes/auth.php';
require_once 'config/db.php';
require_once 'includes/header.php';

// ... (tu código PHP existente, pero sin la parte de mostrar módulos) ...

// Obtener estadísticas (igual que antes)
// ... tu código de estadísticas ...

// Ya no necesitas obtener los módulos aquí porque están en el sidebar
?>

<div class="fade-in">
    <!-- Tarjetas de estadísticas (igual que antes) -->
    <?php if (!empty($stats)): ?>
        <div class="stats-grid" style="margin-bottom: var(--spacing-xl);">
            <!-- ... tus tarjetas ... -->
        </div>
    <?php endif; ?>

    <!-- Mensaje de bienvenida -->
    <div class="card" style="text-align: center; padding: var(--spacing-xxl);">
        <div style="font-size: 4rem; margin-bottom: var(--spacing-lg);">🏥</div>
        <h2>Bienvenido al Sistema de Gestión Hospitalaria</h2>
        <p style="color: var(--gray-600); max-width: 600px; margin: var(--spacing-lg) auto;">
            Utiliza el menú lateral para navegar entre los diferentes módulos del sistema.
            Cada módulo tiene funciones específicas para su área.
        </p>
        
        <!-- Accesos rápidos -->
        <div style="display: flex; gap: var(--spacing-md); justify-content: center; flex-wrap: wrap;">
            <?php foreach ($modulos as $m): ?>
                <a href="modules/<?= $m['ruta'] ?>/index.php" class="btn btn-outline" style="display: flex; align-items: center; gap: var(--spacing-sm);">
                    <?php
                    $iconos = [
                        'Administracion' => '👑',
                        'Almacén' => '📦',
                        'Citas' => '📅',
                        'Doctor' => '🩺',
                        'Farmacia' => '💊',
                        'Registro Pacientes' => '📋'
                    ];
                    echo $iconos[$m['nombre']] ?? '🔷';
                    ?>
                    <?= $m['nombre'] ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Actividad reciente (igual que antes) -->
    <?php if (usuarioTieneModulo($pdo, $_SESSION['user_id'], 'almacen') || 
              usuarioTieneModulo($pdo, $_SESSION['user_id'], 'citas')): ?>
        <div class="card" style="margin-top: var(--spacing-xl);">
            <!-- ... tu actividad reciente ... -->
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>