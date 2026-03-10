<?php
// Configuración del módulo de almacén

define('STOCK_BAJO_PORCENTAJE', 20); // % del stock mínimo para alerta
define('DIAS_ALERTA_VENCIMIENTO', 60); // días para considerar próximo a vencer
define('REQUISICIONES_AUTORIZACION', true); // si requiere autorización
define('ITEMS_POR_PAGINA', 25); // paginación

// Tipos de movimiento permitidos
$TIPOS_MOVIMIENTO = [
    'entrada' => 'Entrada',
    'salida' => 'Salida', 
    'ajuste' => 'Ajuste',
    'devolucion' => 'Devolución',
    'caducidad' => 'Caducidad',
    'merma' => 'Merma'
];

// Estados de requisición
$ESTADOS_REQUISICION = [
    'pendiente' => 'Pendiente',
    'autorizada' => 'Autorizada',
    'surtida' => 'Surtida',
    'cancelada' => 'Cancelada'
];
?>  