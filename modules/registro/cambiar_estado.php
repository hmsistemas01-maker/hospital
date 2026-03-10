<?php
require_once '../../config/config.php';
$modulo_requerido = 'registro';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

$id = (int) $_GET['id'];
$estado = (int) $_GET['estado'];

$stmt = $pdo->prepare("
    UPDATE pacientes
    SET activo = ?
    WHERE id = ?
");

$stmt->execute([$estado, $id]);

header("Location: pacientes.php");
exit;
