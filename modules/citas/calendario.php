<?php
require_once '../../config/config.php';
$modulo_requerido = 'citas';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

$mes = (int)($_GET['mes'] ?? date('m'));
$año = (int)($_GET['año'] ?? date('Y'));

// Validar mes y año
if ($mes < 1 || $mes > 12) $mes = date('m');
if ($año < 2020 || $año > 2030) $año = date('Y');

$primer_dia = mktime(0, 0, 0, $mes, 1, $año);
$dias_en_mes = date('t', $primer_dia);
$nombre_mes = date('F', $primer_dia);
$dia_semana_inicio = date('w', $primer_dia);
$dia_semana_inicio = $dia_semana_inicio == 0 ? 6 : $dia_semana_inicio - 1; // Lunes=0

// Obtener citas del mes
$fecha_inicio = "$año-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-01";
$fecha_fin = "$año-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-$dias_en_mes";

$stmt = $pdo->prepare("
    SELECT DATE(fecha) as fecha, 
           COUNT(*) as total,
           SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
           SUM(CASE WHEN estado = 'atendida' THEN 1 ELSE 0 END) as atendidas,
           SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) as canceladas
    FROM citas 
    WHERE fecha BETWEEN ? AND ?
    GROUP BY DATE(fecha)
");
$stmt->execute([$fecha_inicio, $fecha_fin]);
$citas_por_dia = [];
while ($row = $stmt->fetch()) {
    $dia = (int)date('j', strtotime($row['fecha']));
    $citas_por_dia[$dia] = $row;
}

// Nombres de meses en español
$meses_es = [
    'January' => 'Enero', 'February' => 'Febrero', 'March' => 'Marzo',
    'April' => 'Abril', 'May' => 'Mayo', 'June' => 'Junio',
    'July' => 'Julio', 'August' => 'Agosto', 'September' => 'Septiembre',
    'October' => 'Octubre', 'November' => 'Noviembre', 'December' => 'Diciembre'
];
?>

<div class="fade-in">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl);">
        <div>
            <h1>📅 Calendario de Citas</h1>
            <p style="color: var(--gray-600);"><?= $meses_es[$nombre_mes] ?> <?= $año ?></p>
        </div>
        <div style="display: flex; gap: var(--spacing-sm);">
            <a href="?mes=<?= $mes-1 < 1 ? 12 : $mes-1 ?>&año=<?= $mes-1 < 1 ? $año-1 : $año ?>" class="btn btn-outline">
                ← Anterior
            </a>
            <a href="?mes=<?= date('m') ?>&año=<?= date('Y') ?>" class="btn btn-primary">
                Hoy
            </a>
            <a href="?mes=<?= $mes+1 > 12 ? 1 : $mes+1 ?>&año=<?= $mes+1 > 12 ? $año+1 : $año ?>" class="btn btn-outline">
                Siguiente →
            </a>
            <a href="nueva.php" class="btn btn-success">
                ➕ Nueva Cita
            </a>
        </div>
    </div>

    <!-- Leyenda -->
    <div style="display: flex; gap: var(--spacing-lg); margin-bottom: var(--spacing-lg);">
        <div style="display: flex; align-items: center; gap: var(--spacing-xs);">
            <div style="width: 12px; height: 12px; background: var(--warning); border-radius: 2px;"></div>
            <span>Pendiente</span>
        </div>
        <div style="display: flex; align-items: center; gap: var(--spacing-xs);">
            <div style="width: 12px; height: 12px; background: var(--success); border-radius: 2px;"></div>
            <span>Atendida</span>
        </div>
        <div style="display: flex; align-items: center; gap: var(--spacing-xs);">
            <div style="width: 12px; height: 12px; background: var(--danger); border-radius: 2px;"></div>
            <span>Cancelada</span>
        </div>
    </div>

    <div class="card">
        <!-- Días de la semana -->
        <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: var(--spacing-sm); text-align: center; font-weight: bold; margin-bottom: var(--spacing-md);">
            <div>Lunes</div>
            <div>Martes</div>
            <div>Miércoles</div>
            <div>Jueves</div>
            <div>Viernes</div>
            <div>Sábado</div>
            <div>Domingo</div>
        </div>
        
        <!-- Días del mes -->
        <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: var(--spacing-sm);">
            <?php for ($i = 0; $i < $dia_semana_inicio; $i++): ?>
                <div style="min-height: 120px; background: var(--gray-100); border-radius: var(--radius-md);"></div>
            <?php endfor; ?>
            
            <?php for ($dia = 1; $dia <= $dias_en_mes; $dia++): 
                $tiene_citas = isset($citas_por_dia[$dia]);
                $es_hoy = ($dia == date('j') && $mes == date('m') && $año == date('Y'));
                $data = $citas_por_dia[$dia] ?? null;
            ?>
                <div style="background: <?= $es_hoy ? 'var(--primary-soft)' : 'white' ?>; 
                            border: 1px solid var(--gray-200); 
                            border-radius: var(--radius-md); 
                            min-height: 120px; 
                            padding: var(--spacing-sm);
                            position: relative;">
                    <div style="display: flex; justify-content: space-between;">
                        <strong style="font-size: 1.2rem;"><?= $dia ?></strong>
                        <a href="nueva.php?fecha=<?= $año ?>-<?= str_pad($mes, 2, '0', STR_PAD_LEFT) ?>-<?= str_pad($dia, 2, '0', STR_PAD_LEFT) ?>" 
                           style="text-decoration: none; font-size: 1.2rem;">➕</a>
                    </div>
                    
                    <?php if ($tiene_citas): ?>
                        <div style="margin-top: var(--spacing-sm);">
                            <div style="font-size: var(--font-size-xs); color: var(--gray-600);">
                                Total: <strong><?= $data['total'] ?></strong>
                            </div>
                            <div style="display: flex; gap: 2px; margin-top: 4px;">
                                <?php if ($data['pendientes'] > 0): ?>
                                    <div style="flex: 1; height: 4px; background: var(--warning);" title="<?= $data['pendientes'] ?> pendientes"></div>
                                <?php endif; ?>
                                <?php if ($data['atendidas'] > 0): ?>
                                    <div style="flex: 1; height: 4px; background: var(--success);" title="<?= $data['atendidas'] ?> atendidas"></div>
                                <?php endif; ?>
                                <?php if ($data['canceladas'] > 0): ?>
                                    <div style="flex: 1; height: 4px; background: var(--danger);" title="<?= $data['canceladas'] ?> canceladas"></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <a href="lista.php?fecha=<?= $año ?>-<?= str_pad($mes, 2, '0', STR_PAD_LEFT) ?>-<?= str_pad($dia, 2, '0', STR_PAD_LEFT) ?>" 
                           style="position: absolute; bottom: 5px; right: 5px; font-size: var(--font-size-sm);">
                            Ver →
                        </a>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>