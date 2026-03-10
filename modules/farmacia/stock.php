<?php
require_once '../../config/config.php';
$modulo_requerido = 'farmacia';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

$stock = $pdo->query("
    SELECT fs.*, p.nombre, p.codigo, p.unidad_medida,
           l.numero_lote, l.fecha_vencimiento,
           DATEDIFF(l.fecha_vencimiento, CURDATE()) as dias_vencer
    FROM farmacia_stock fs
    JOIN productos p ON fs.id_producto = p.id
    LEFT JOIN lote l ON fs.id_lote = l.id
    WHERE fs.stock_actual > 0
    ORDER BY 
        CASE 
            WHEN l.fecha_vencimiento < CURDATE() THEN 1
            WHEN l.fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 2
            ELSE 3
        END,
        p.nombre
")->fetchAll();
?>

<div class="fade-in">
    <h1>📦 Stock en Farmacia</h1>

    <div class="stock-farmacia">
        <?php foreach ($stock as $s): 
            $clase = 'normal';
            if ($s['dias_vencer'] < 0) $clase = 'critico';
            elseif ($s['dias_vencer'] <= 30) $clase = 'bajo';
            elseif ($s['stock_actual'] <= $s['stock_minimo']) $clase = 'bajo';
        ?>
        <div class="stock-item <?= $clase ?>">
            <div class="stock-info">
                <h4><?= htmlspecialchars($s['nombre']) ?></h4>
                <small>Código: <?= $s['codigo'] ?></small>
                <?php if ($s['numero_lote']): ?>
                    <br><small>Lote: <?= $s['numero_lote'] ?></small>
                <?php endif; ?>
                <?php if ($s['fecha_vencimiento']): ?>
                    <br><small>Vence: <?= date('d/m/Y', strtotime($s['fecha_vencimiento'])) ?></small>
                <?php endif; ?>
            </div>
            <div class="stock-cantidad"><?= $s['stock_actual'] ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>