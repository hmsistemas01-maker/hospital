<?php
// Este módulo es manejado por farmacia, redirigimos a productos
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';

// Redirigir a productos con un mensaje claro
header("Location: productos.php?msg=info_lote");
exit;