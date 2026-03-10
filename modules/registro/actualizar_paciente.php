<?php
require_once '../../config/config.php';
$modulo_requerido = 'registro';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

$id = (int) $_POST['id'];
$nombre = trim($_POST['nombre']);
$curp = strtoupper(trim($_POST['curp']));
$fecha_nacimiento = $_POST['fecha_nacimiento'] ?: null;
$sexo = $_POST['sexo'] ?: null;
$telefono = $_POST['telefono'];
$direccion = $_POST['direccion'];

// Validar CURP duplicada en otro paciente
$stmt = $pdo->prepare("
    SELECT id FROM pacientes 
    WHERE curp = ? AND id != ?
");
$stmt->execute([$curp, $id]);

if ($stmt->fetch()) {
    die("La CURP ya pertenece a otro paciente.");
}

$stmt = $pdo->prepare("
    UPDATE pacientes
    SET nombre=?, curp=?, fecha_nacimiento=?, sexo=?, telefono=?, direccion=?
    WHERE id=?
");

$stmt->execute([
    $nombre,
    $curp,
    $fecha_nacimiento,
    $sexo,
    $telefono,
    $direccion,
    $id
]);

header("Location: pacientes.php");
exit;
