<?php
if (!isset($_SESSION['usuario'])) {
    header("Location: " . BASE_URL . "login.php");
    exit;
}

if (isset($modulo_requerido)) {
    require_once __DIR__ . '/../config/db.php';

    $stmt = $pdo->prepare("
        SELECT 1 FROM usuario_modulos um
        JOIN modulos m ON um.modulo_id = m.id
        WHERE um.usuario_id = ? AND m.ruta = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $modulo_requerido]);

    if (!$stmt->fetch()) {
        die("Acceso denegado a este módulo");
    }
}
