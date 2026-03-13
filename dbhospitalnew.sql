-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 13-03-2026 a las 19:32:52
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `dbhospitalnew`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `almacen_lotes`
--

CREATE TABLE `almacen_lotes` (
  `id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `numero_lote` varchar(50) NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `cantidad_inicial` int(11) NOT NULL,
  `cantidad_actual` int(11) NOT NULL,
  `proveedor_id` int(11) DEFAULT NULL,
  `fecha_entrada` date NOT NULL,
  `ubicacion` varchar(100) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias_almacen`
--

CREATE TABLE `categorias_almacen` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `tipo` enum('material_medico','oficina','limpieza','equipo','otro') NOT NULL,
  `descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categorias_almacen`
--

INSERT INTO `categorias_almacen` (`id`, `nombre`, `tipo`, `descripcion`) VALUES
(1, 'Material de curación', 'material_medico', 'Gasas, vendas, apósitos'),
(2, 'Equipo médico', 'equipo', 'Equipos médicos reutilizables'),
(3, 'Artículos de limpieza', 'limpieza', 'Productos de limpieza y desinfección'),
(4, 'Papelería', 'oficina', 'Artículos de oficina');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias_farmacia`
--

CREATE TABLE `categorias_farmacia` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `tipo` enum('medicamento','material_medico','vacuna','suero','otro') NOT NULL,
  `descripcion` text DEFAULT NULL,
  `requiere_receta` tinyint(1) DEFAULT 0,
  `control_lote` tinyint(1) DEFAULT 0,
  `control_vencimiento` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categorias_farmacia`
--

INSERT INTO `categorias_farmacia` (`id`, `nombre`, `tipo`, `descripcion`, `requiere_receta`, `control_lote`, `control_vencimiento`) VALUES
(1, 'Analgésicos', 'medicamento', NULL, 1, 1, 1),
(2, 'Antibióticos', 'medicamento', NULL, 1, 1, 1),
(3, 'Antiinflamatorios', 'medicamento', NULL, 1, 1, 1),
(4, 'Vitaminas', 'medicamento', NULL, 0, 1, 1),
(5, 'Material de curación', 'material_medico', NULL, 0, 1, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `citas`
--

CREATE TABLE `citas` (
  `id` int(11) NOT NULL,
  `paciente_id` int(11) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `hora` time DEFAULT NULL,
  `motivo` text DEFAULT NULL,
  `estado` enum('pendiente','atendida','cancelada') DEFAULT 'pendiente',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `fecha_creacion` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `doctores`
--

CREATE TABLE `doctores` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `especialidad` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `doctores`
--

INSERT INTO `doctores` (`id`, `nombre`, `especialidad`, `telefono`, `email`, `activo`, `fecha_registro`) VALUES
(6, 'alejandra', 'ginecologo', '', '', 1, '2026-03-13 18:04:04'),
(7, 'fklasklfas', 'deprueba', '', '', 1, '2026-03-13 18:05:40');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `doctor_excepciones`
--

CREATE TABLE `doctor_excepciones` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `tipo` enum('vacaciones','permiso','capacitacion','festivo','horario_especial') NOT NULL,
  `motivo` text DEFAULT NULL,
  `hora_entrada` time DEFAULT NULL,
  `hora_salida` time DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `creado_por` int(11) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `doctor_horarios`
--

CREATE TABLE `doctor_horarios` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `dia` enum('Lunes','Martes','Miercoles','Jueves','Viernes','Sabado') DEFAULT NULL,
  `hora_inicio` time DEFAULT NULL,
  `hora_fin` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `doctor_horarios`
--

INSERT INTO `doctor_horarios` (`id`, `doctor_id`, `usuario_id`, `dia`, `hora_inicio`, `hora_fin`) VALUES
(1, NULL, 3, 'Lunes', '08:00:00', '15:00:00'),
(2, NULL, 3, 'Martes', '08:00:00', '15:00:00'),
(3, NULL, 5, 'Martes', '08:00:00', '15:30:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `farmacia_lotes`
--

CREATE TABLE `farmacia_lotes` (
  `id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `numero_lote` varchar(50) NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `cantidad_inicial` int(11) NOT NULL,
  `cantidad_actual` int(11) NOT NULL,
  `proveedor_id` int(11) DEFAULT NULL,
  `fecha_entrada` date NOT NULL,
  `ubicacion` varchar(100) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_clinico`
--

CREATE TABLE `historial_clinico` (
  `id` int(11) NOT NULL,
  `paciente_id` int(11) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `diagnostico` text DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `presion_arterial` varchar(20) DEFAULT NULL,
  `temperatura` varchar(10) DEFAULT NULL,
  `peso` varchar(10) DEFAULT NULL,
  `altura` varchar(10) DEFAULT NULL,
  `alergias` text DEFAULT NULL,
  `enfermedades_cronicas` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modulos`
--

CREATE TABLE `modulos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) DEFAULT NULL,
  `ruta` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `modulos`
--

INSERT INTO `modulos` (`id`, `nombre`, `ruta`) VALUES
(1, 'Registro Pacientes', 'registro'),
(2, 'Citas', 'citas'),
(3, 'Doctor', 'doctor'),
(4, 'Farmacia', 'farmacia'),
(5, 'Administración', 'admin'),
(6, 'Almacén', 'almacen'),
(7, 'Inventarios', 'inventarios');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimientos_inventario`
--

CREATE TABLE `movimientos_inventario` (
  `id` int(11) NOT NULL,
  `departamento` enum('almacen','farmacia') NOT NULL,
  `producto_id` int(11) NOT NULL,
  `lote_id` int(11) DEFAULT NULL,
  `tipo_movimiento` enum('entrada','salida','ajuste','devolucion','caducidad','merma') NOT NULL,
  `cantidad` int(11) NOT NULL,
  `motivo` varchar(200) DEFAULT NULL,
  `referencia` varchar(100) DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha_movimiento` timestamp NOT NULL DEFAULT current_timestamp(),
  `destino` varchar(100) DEFAULT NULL,
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Disparadores `movimientos_inventario`
--
DELIMITER $$
CREATE TRIGGER `after_movimiento_insert` AFTER INSERT ON `movimientos_inventario` FOR EACH ROW BEGIN
    DECLARE v_precio_unitario DECIMAL(10,2);
    
    SELECT precio_unitario INTO v_precio_unitario 
    FROM productos 
    WHERE id = NEW.producto_id;
    
    INSERT INTO stock_actual (departamento, producto_id, cantidad_total, valor_total)
    VALUES (
        NEW.departamento,
        NEW.producto_id,
        CASE 
            WHEN NEW.tipo_movimiento IN ('entrada','devolucion') THEN NEW.cantidad
            WHEN NEW.tipo_movimiento IN ('salida','caducidad','merma') THEN -NEW.cantidad
            ELSE NEW.cantidad
        END,
        CASE 
            WHEN NEW.tipo_movimiento IN ('entrada','devolucion') THEN NEW.cantidad * v_precio_unitario
            WHEN NEW.tipo_movimiento IN ('salida','caducidad','merma') THEN -NEW.cantidad * v_precio_unitario
            ELSE NEW.cantidad * v_precio_unitario
        END
    )
    ON DUPLICATE KEY UPDATE 
        cantidad_total = cantidad_total + VALUES(cantidad_total),
        valor_total = valor_total + VALUES(valor_total);
    
    IF NEW.lote_id IS NOT NULL THEN
        IF NEW.departamento = 'almacen' THEN
            UPDATE almacen_lotes 
            SET cantidad_actual = cantidad_actual + 
                CASE 
                    WHEN NEW.tipo_movimiento IN ('entrada','devolucion') THEN NEW.cantidad
                    WHEN NEW.tipo_movimiento IN ('salida','caducidad','merma') THEN -NEW.cantidad
                    ELSE NEW.cantidad
                END
            WHERE id = NEW.lote_id;
        ELSE
            UPDATE farmacia_lotes 
            SET cantidad_actual = cantidad_actual + 
                CASE 
                    WHEN NEW.tipo_movimiento IN ('entrada','devolucion') THEN NEW.cantidad
                    WHEN NEW.tipo_movimiento IN ('salida','caducidad','merma') THEN -NEW.cantidad
                    ELSE NEW.cantidad
                END
            WHERE id = NEW.lote_id;
        END IF;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pacientes`
--

CREATE TABLE `pacientes` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `curp` char(18) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `sexo` enum('M','F') DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `nombre_emergencia` varchar(100) DEFAULT NULL,
  `numero_emergencia` varchar(20) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pacientes`
--

INSERT INTO `pacientes` (`id`, `nombre`, `curp`, `fecha_nacimiento`, `sexo`, `telefono`, `email`, `direccion`, `nombre_emergencia`, `numero_emergencia`, `fecha_registro`, `activo`) VALUES
(1, 'Juan Pérez González', 'JUPG850315HDFRRN01', '1985-03-15', 'M', '555-1111', NULL, NULL, NULL, NULL, '2026-03-13 17:42:24', 1),
(2, 'María García López', 'MAGL900722MDFRRN02', '1990-07-22', 'F', '555-2222', NULL, NULL, NULL, NULL, '2026-03-13 17:42:24', 1),
(3, 'Ana Sánchez Díaz', 'ANSD820530MDFRRN03', '1982-05-30', 'F', '555-3333', NULL, NULL, NULL, NULL, '2026-03-13 17:42:24', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos`
--

CREATE TABLE `permisos` (
  `id` int(11) NOT NULL,
  `modulo_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `clave` varchar(50) NOT NULL COMMENT 'Identificador único del permiso',
  `descripcion` text DEFAULT NULL,
  `orden` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `departamento` enum('almacen','farmacia') NOT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `nombre` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `categoria_almacen_id` int(11) DEFAULT NULL,
  `categoria_farmacia_id` int(11) DEFAULT NULL,
  `unidad_medida` enum('pieza','caja','frasco','tableta','capsula','ampolleta','ml','litro','gramo','kilogramo','metro','otro') DEFAULT 'pieza',
  `stock_minimo` int(11) DEFAULT 10,
  `stock_maximo` int(11) DEFAULT 1000,
  `precio_unitario` decimal(10,2) DEFAULT 0.00,
  `ubicacion` varchar(100) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `departamento`, `codigo`, `nombre`, `descripcion`, `categoria_almacen_id`, `categoria_farmacia_id`, `unidad_medida`, `stock_minimo`, `stock_maximo`, `precio_unitario`, `ubicacion`, `activo`, `fecha_registro`) VALUES
(1, 'almacen', 'GASA001', 'Gasas estériles 10x10', NULL, 1, NULL, 'pieza', 200, 2000, 0.30, NULL, 1, '2026-03-13 17:41:34'),
(2, 'almacen', 'VENDA002', 'Venda elástica 5cm', NULL, 1, NULL, 'pieza', 50, 500, 2.50, NULL, 1, '2026-03-13 17:41:34'),
(3, 'almacen', 'JABO003', 'Jabón quirúrgico', NULL, 3, NULL, 'litro', 20, 200, 4.00, NULL, 1, '2026-03-13 17:41:34'),
(4, 'almacen', 'PAPE004', 'Resma papel bond', NULL, 4, NULL, 'pieza', 10, 100, 5.00, NULL, 1, '2026-03-13 17:41:34'),
(5, 'farmacia', 'PARA001', 'Paracetamol 500mg', NULL, NULL, 1, 'tableta', 100, 1000, 0.50, NULL, 1, '2026-03-13 17:41:34'),
(6, 'farmacia', 'IBUP002', 'Ibuprofeno 400mg', NULL, NULL, 3, 'tableta', 50, 800, 0.75, NULL, 1, '2026-03-13 17:41:34'),
(7, 'farmacia', 'AMOX003', 'Amoxicilina 500mg', NULL, NULL, 2, 'capsula', 30, 500, 1.20, NULL, 1, '2026-03-13 17:41:34'),
(8, 'farmacia', 'VITA004', 'Vitamina C 1000mg', NULL, NULL, 4, 'tableta', 30, 300, 1.80, NULL, 1, '2026-03-13 17:41:34');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos_codigos`
--

CREATE TABLE `productos_codigos` (
  `id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `codigo_barras` varchar(50) NOT NULL,
  `es_principal` tinyint(1) DEFAULT 0,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedores`
--

CREATE TABLE `proveedores` (
  `id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `rfc` varchar(13) DEFAULT NULL,
  `contacto` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `proveedores`
--

INSERT INTO `proveedores` (`id`, `nombre`, `rfc`, `contacto`, `telefono`, `email`, `direccion`, `activo`, `fecha_registro`) VALUES
(1, 'Farmacéutica Nacional', 'FAN880101ABC', 'Lic. Juan Pérez', '555-1234', 'ventas@farmaceutica.com', NULL, 1, '2026-03-13 17:41:21'),
(2, 'Distribuidora Médica', 'DIM890202DEF', 'María García', '555-5678', 'maria@distmedica.com', NULL, 1, '2026-03-13 17:41:21'),
(3, 'Laboratorios Genéricos', 'LAG900303GHI', 'Dr. Carlos López', '555-9012', 'carlos@labgenericos.com', NULL, 1, '2026-03-13 17:41:21');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recetas`
--

CREATE TABLE `recetas` (
  `id` int(11) NOT NULL,
  `paciente_id` int(11) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `estado` enum('pendiente','despachada','cancelada') DEFAULT 'pendiente',
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `receta_detalles`
--

CREATE TABLE `receta_detalles` (
  `id` int(11) NOT NULL,
  `receta_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `indicaciones` text DEFAULT NULL,
  `despachado` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `requisiciones`
--

CREATE TABLE `requisiciones` (
  `id` int(11) NOT NULL,
  `departamento` enum('almacen','farmacia') NOT NULL,
  `numero_requisicion` varchar(50) DEFAULT NULL,
  `solicitante_id` int(11) NOT NULL,
  `fecha_solicitud` timestamp NOT NULL DEFAULT current_timestamp(),
  `estado` enum('pendiente','autorizada','surtida','cancelada') DEFAULT 'pendiente',
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `requisicion_detalle`
--

CREATE TABLE `requisicion_detalle` (
  `id` int(11) NOT NULL,
  `requisicion_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad_solicitada` int(11) NOT NULL,
  `cantidad_entregada` int(11) DEFAULT 0,
  `observaciones` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `stock_actual`
--

CREATE TABLE `stock_actual` (
  `id` int(11) NOT NULL,
  `departamento` enum('almacen','farmacia') NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad_total` int(11) DEFAULT 0,
  `valor_total` decimal(12,2) DEFAULT 0.00,
  `ultima_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `usuario` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `rol` enum('doctor','farmacia','admin','registro') DEFAULT NULL,
  `activo` tinyint(4) DEFAULT 1,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `usuario`, `password`, `rol`, `activo`, `fecha_registro`) VALUES
(1, 'Administrador', 'admin', '$2y$10$pvDgzG9XQaBzWuRMohPH1.y05089ciL3N3ExChFlW.4o/7jk8t.z.', 'admin', 1, '2026-03-13 17:43:28'),
(3, 'alejandra', 'dzepeda', '$2y$10$zHX45foOuu.1g2B5KoDSAOy4McnpA26kTF9XwTE.6Nm1eg1rZiM/i', 'doctor', 1, '2026-03-13 18:04:04'),
(4, 'Registro', 'rregistro', '$2y$10$lcymHGYQb/dVtTISMqazVe8Co8wdBiKSRBrxzM0GsK1JCRarscQZG', 'registro', 1, '2026-03-13 18:04:46'),
(5, 'Informacion y citas', 'njkdfsdkl', '$2y$10$P9HOLku1jYYdnR2rpHtsUePF4kjsL.0sZtQrnbuwHOKcxQoRorxkq', 'registro', 1, '2026-03-13 18:05:40');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario_modulos`
--

CREATE TABLE `usuario_modulos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `modulo_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuario_modulos`
--

INSERT INTO `usuario_modulos` (`id`, `usuario_id`, `modulo_id`) VALUES
(15, 1, 5),
(16, 1, 6),
(17, 1, 2),
(18, 1, 3),
(19, 1, 4),
(20, 1, 7),
(21, 1, 1),
(23, 3, 3),
(24, 4, 1),
(27, 5, 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario_permisos`
--

CREATE TABLE `usuario_permisos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `permiso_id` int(11) NOT NULL,
  `fecha_asignacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `asignado_por` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_movimientos_hoy`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_movimientos_hoy` (
`id` int(11)
,`codigo` varchar(50)
,`producto` varchar(200)
,`tipo_movimiento` enum('entrada','salida','ajuste','devolucion','caducidad','merma')
,`cantidad` int(11)
,`motivo` varchar(200)
,`usuario` varchar(100)
,`fecha_movimiento` timestamp
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_productos_por_vencer`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_productos_por_vencer` (
`id` int(11)
,`codigo` varchar(50)
,`nombre` varchar(200)
,`numero_lote` varchar(50)
,`fecha_vencimiento` date
,`cantidad_actual` int(11)
,`dias_para_vencer` int(7)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `v_movimientos_hoy`
--
DROP TABLE IF EXISTS `v_movimientos_hoy`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_movimientos_hoy`  AS SELECT `m`.`id` AS `id`, `p`.`codigo` AS `codigo`, `p`.`nombre` AS `producto`, `m`.`tipo_movimiento` AS `tipo_movimiento`, `m`.`cantidad` AS `cantidad`, `m`.`motivo` AS `motivo`, `u`.`nombre` AS `usuario`, `m`.`fecha_movimiento` AS `fecha_movimiento` FROM ((`movimientos_inventario` `m` join `productos` `p` on(`m`.`producto_id` = `p`.`id`)) join `usuarios` `u` on(`m`.`usuario_id` = `u`.`id`)) WHERE cast(`m`.`fecha_movimiento` as date) = curdate() ORDER BY `m`.`fecha_movimiento` DESC ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_productos_por_vencer`
--
DROP TABLE IF EXISTS `v_productos_por_vencer`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_productos_por_vencer`  AS SELECT `p`.`id` AS `id`, `p`.`codigo` AS `codigo`, `p`.`nombre` AS `nombre`, `l`.`numero_lote` AS `numero_lote`, `l`.`fecha_vencimiento` AS `fecha_vencimiento`, `l`.`cantidad_actual` AS `cantidad_actual`, to_days(`l`.`fecha_vencimiento`) - to_days(curdate()) AS `dias_para_vencer` FROM (`farmacia_lotes` `l` join `productos` `p` on(`l`.`producto_id` = `p`.`id`)) WHERE `l`.`activo` = 1 AND `l`.`cantidad_actual` > 0 AND `l`.`fecha_vencimiento` is not null AND `l`.`fecha_vencimiento` <= curdate() + interval 90 day ORDER BY `l`.`fecha_vencimiento` ASC ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `almacen_lotes`
--
ALTER TABLE `almacen_lotes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `producto_id` (`producto_id`),
  ADD KEY `proveedor_id` (`proveedor_id`);

--
-- Indices de la tabla `categorias_almacen`
--
ALTER TABLE `categorias_almacen`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `categorias_farmacia`
--
ALTER TABLE `categorias_farmacia`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `citas`
--
ALTER TABLE `citas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paciente_id` (`paciente_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indices de la tabla `doctores`
--
ALTER TABLE `doctores`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `doctor_excepciones`
--
ALTER TABLE `doctor_excepciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `creado_por` (`creado_por`),
  ADD KEY `idx_usuario_id` (`usuario_id`);

--
-- Indices de la tabla `doctor_horarios`
--
ALTER TABLE `doctor_horarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `idx_usuario_id` (`usuario_id`);

--
-- Indices de la tabla `farmacia_lotes`
--
ALTER TABLE `farmacia_lotes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `producto_id` (`producto_id`),
  ADD KEY `proveedor_id` (`proveedor_id`);

--
-- Indices de la tabla `historial_clinico`
--
ALTER TABLE `historial_clinico`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paciente_id` (`paciente_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indices de la tabla `modulos`
--
ALTER TABLE `modulos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  ADD PRIMARY KEY (`id`),
  ADD KEY `producto_id` (`producto_id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_movimientos_fecha` (`fecha_movimiento`),
  ADD KEY `idx_departamento` (`departamento`);

--
-- Indices de la tabla `pacientes`
--
ALTER TABLE `pacientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `curp` (`curp`);

--
-- Indices de la tabla `permisos`
--
ALTER TABLE `permisos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave` (`clave`),
  ADD KEY `modulo_id` (`modulo_id`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `categoria_almacen_id` (`categoria_almacen_id`),
  ADD KEY `categoria_farmacia_id` (`categoria_farmacia_id`);

--
-- Indices de la tabla `productos_codigos`
--
ALTER TABLE `productos_codigos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo_barras` (`codigo_barras`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `recetas`
--
ALTER TABLE `recetas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paciente_id` (`paciente_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indices de la tabla `receta_detalles`
--
ALTER TABLE `receta_detalles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `receta_id` (`receta_id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `requisiciones`
--
ALTER TABLE `requisiciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_requisicion` (`numero_requisicion`),
  ADD KEY `solicitante_id` (`solicitante_id`);

--
-- Indices de la tabla `requisicion_detalle`
--
ALTER TABLE `requisicion_detalle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `requisicion_id` (`requisicion_id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `stock_actual`
--
ALTER TABLE `stock_actual`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `departamento_producto` (`departamento`,`producto_id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- Indices de la tabla `usuario_modulos`
--
ALTER TABLE `usuario_modulos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `modulo_id` (`modulo_id`);

--
-- Indices de la tabla `usuario_permisos`
--
ALTER TABLE `usuario_permisos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario_permiso` (`usuario_id`,`permiso_id`),
  ADD KEY `permiso_id` (`permiso_id`),
  ADD KEY `asignado_por` (`asignado_por`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `almacen_lotes`
--
ALTER TABLE `almacen_lotes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `categorias_almacen`
--
ALTER TABLE `categorias_almacen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `categorias_farmacia`
--
ALTER TABLE `categorias_farmacia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `citas`
--
ALTER TABLE `citas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `doctores`
--
ALTER TABLE `doctores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `doctor_excepciones`
--
ALTER TABLE `doctor_excepciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `doctor_horarios`
--
ALTER TABLE `doctor_horarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `farmacia_lotes`
--
ALTER TABLE `farmacia_lotes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `historial_clinico`
--
ALTER TABLE `historial_clinico`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `modulos`
--
ALTER TABLE `modulos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pacientes`
--
ALTER TABLE `pacientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `permisos`
--
ALTER TABLE `permisos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `productos_codigos`
--
ALTER TABLE `productos_codigos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `recetas`
--
ALTER TABLE `recetas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `receta_detalles`
--
ALTER TABLE `receta_detalles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `requisiciones`
--
ALTER TABLE `requisiciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `requisicion_detalle`
--
ALTER TABLE `requisicion_detalle`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `stock_actual`
--
ALTER TABLE `stock_actual`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `usuario_modulos`
--
ALTER TABLE `usuario_modulos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de la tabla `usuario_permisos`
--
ALTER TABLE `usuario_permisos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `almacen_lotes`
--
ALTER TABLE `almacen_lotes`
  ADD CONSTRAINT `almacen_lotes_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`),
  ADD CONSTRAINT `almacen_lotes_ibfk_2` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`);

--
-- Filtros para la tabla `citas`
--
ALTER TABLE `citas`
  ADD CONSTRAINT `citas_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`),
  ADD CONSTRAINT `citas_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctores` (`id`),
  ADD CONSTRAINT `fk_citas_created_by` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `doctor_excepciones`
--
ALTER TABLE `doctor_excepciones`
  ADD CONSTRAINT `doctor_excepciones_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctores` (`id`),
  ADD CONSTRAINT `doctor_excepciones_ibfk_2` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `farmacia_lotes`
--
ALTER TABLE `farmacia_lotes`
  ADD CONSTRAINT `farmacia_lotes_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`),
  ADD CONSTRAINT `farmacia_lotes_ibfk_2` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`);

--
-- Filtros para la tabla `historial_clinico`
--
ALTER TABLE `historial_clinico`
  ADD CONSTRAINT `historial_clinico_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`),
  ADD CONSTRAINT `historial_clinico_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctores` (`id`);

--
-- Filtros para la tabla `permisos`
--
ALTER TABLE `permisos`
  ADD CONSTRAINT `permisos_ibfk_1` FOREIGN KEY (`modulo_id`) REFERENCES `modulos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `productos_ibfk_almacen` FOREIGN KEY (`categoria_almacen_id`) REFERENCES `categorias_almacen` (`id`),
  ADD CONSTRAINT `productos_ibfk_farmacia` FOREIGN KEY (`categoria_farmacia_id`) REFERENCES `categorias_farmacia` (`id`);

--
-- Filtros para la tabla `productos_codigos`
--
ALTER TABLE `productos_codigos`
  ADD CONSTRAINT `productos_codigos_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `recetas`
--
ALTER TABLE `recetas`
  ADD CONSTRAINT `recetas_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`),
  ADD CONSTRAINT `recetas_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctores` (`id`);

--
-- Filtros para la tabla `receta_detalles`
--
ALTER TABLE `receta_detalles`
  ADD CONSTRAINT `receta_detalles_ibfk_1` FOREIGN KEY (`receta_id`) REFERENCES `recetas` (`id`),
  ADD CONSTRAINT `receta_detalles_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`);

--
-- Filtros para la tabla `requisiciones`
--
ALTER TABLE `requisiciones`
  ADD CONSTRAINT `requisiciones_ibfk_1` FOREIGN KEY (`solicitante_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `requisicion_detalle`
--
ALTER TABLE `requisicion_detalle`
  ADD CONSTRAINT `requisicion_detalle_ibfk_1` FOREIGN KEY (`requisicion_id`) REFERENCES `requisiciones` (`id`),
  ADD CONSTRAINT `requisicion_detalle_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`);

--
-- Filtros para la tabla `stock_actual`
--
ALTER TABLE `stock_actual`
  ADD CONSTRAINT `stock_actual_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`);

--
-- Filtros para la tabla `usuario_modulos`
--
ALTER TABLE `usuario_modulos`
  ADD CONSTRAINT `usuario_modulos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `usuario_modulos_ibfk_2` FOREIGN KEY (`modulo_id`) REFERENCES `modulos` (`id`);

--
-- Filtros para la tabla `usuario_permisos`
--
ALTER TABLE `usuario_permisos`
  ADD CONSTRAINT `usuario_permisos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `usuario_permisos_ibfk_2` FOREIGN KEY (`permiso_id`) REFERENCES `permisos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `usuario_permisos_ibfk_3` FOREIGN KEY (`asignado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
