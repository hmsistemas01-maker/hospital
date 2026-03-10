<?php
require_once '../../config/config.php';
$modulo_requerido = 'registro';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: nuevo_paciente.php");
    exit;
}

$nombre = trim($_POST['nombre']);
$curp = strtoupper(trim($_POST['curp']));
$fecha_nacimiento = $_POST['fecha_nacimiento'] ?: null;
$sexo = $_POST['sexo'] ?: null;
$telefono = $_POST['telefono'] ?? null;
$email = $_POST['email'] ?? null;
$direccion = $_POST['direccion'] ?? null;
$numero_emergencia = $_POST['numero_emergencia'] ?? null;

// Validaciones básicas
if (empty($nombre) || empty($curp)) {
    $_SESSION['error'] = "Nombre y CURP son obligatorios";
    header("Location: nuevo_paciente.php");
    exit;
}

// Verificar CURP única
$stmt = $pdo->prepare("SELECT id FROM pacientes WHERE curp = ?");
$stmt->execute([$curp]);
if ($stmt->fetch()) {
    $_SESSION['error'] = "La CURP ya está registrada";
    header("Location: nuevo_paciente.php");
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO pacientes 
        (nombre, curp, fecha_nacimiento, sexo, telefono, email, direccion, numero_emergencia) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $nombre,
        $curp,
        $fecha_nacimiento,
        $sexo,
        $telefono,
        $email,
        $direccion,
        $numero_emergencia
    ]);
    
    header("Location: pacientes.php?success=Paciente registrado correctamente");
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Error al guardar: " . $e->getMessage();
    header("Location: nuevo_paciente.php");
}
exit;