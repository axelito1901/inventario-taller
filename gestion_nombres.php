<?php
session_start();
if (!isset($_SESSION['gerente'])) {
    header("Location: login.php");
    exit();
}

include 'includes/conexion.php';

$mensaje_error_lanus = "";
$mensaje_error_agregar = "";
$mensaje_error_editar = "";

// Eliminar Lanús
if (isset($_POST['eliminar_lanus'])) {
    $id = intval($_POST['eliminar_lanus']);
    $stmt = $conexion->prepare("SELECT COUNT(*) as total FROM prestamos WHERE mecanico_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result()->fetch_assoc();

    if ($resultado['total'] > 0) {
        $mensaje_error_lanus = "⚠️ No se puede eliminar. Este mecánico tiene préstamos registrados.";
    } else {
        $conexion->query("DELETE FROM mecanicos WHERE id = $id");
    }
}

// Eliminar Osvaldo Cruz
if (isset($_POST['eliminar_osvaldo'])) {
    $id = intval($_POST['eliminar_osvaldo']);
    $conexion->query("DELETE FROM nombres_personalizados WHERE id = $id");
}

// Editar nombres
if (isset($_POST['editar_id']) && isset($_POST['nuevo_nombre']) && isset($_POST['sucursal'])) {
    $id = intval($_POST['editar_id']);
    $nombre = trim($_POST['nuevo_nombre']);
    $tabla = $_POST['sucursal'] === 'Lanús' ? 'mecanicos' : 'nombres_personalizados';

    $stmt = $conexion->prepare("SELECT COUNT(*) as total FROM $tabla WHERE nombre = ? AND id != ?");
    $stmt->bind_param("si", $nombre, $id);
    $stmt->execute();
    $resultado = $stmt->get_result()->fetch_assoc();

    if ($resultado['total'] > 0) {
        $mensaje_error_editar = "⚠️ El nombre ya existe en la sucursal seleccionada.";
    } else {
        $stmt = $conexion->prepare("UPDATE $tabla SET nombre = ? WHERE id = ?");
        $stmt->bind_param("si", $nombre, $id);
        $stmt->execute();
    }
}

// Agregar nombres
if (isset($_POST['agregar_nombre']) && isset($_POST['agregar_sucursal'])) {
    $nombre = trim($_POST['agregar_nombre']);
    $tabla = $_POST['agregar_sucursal'] === 'Lanús' ? 'mecanicos' : 'nombres_personalizados';

    $stmt = $conexion->prepare("SELECT COUNT(*) as total FROM $tabla WHERE nombre = ?");
    $stmt->bind_param("s", $nombre);
    $stmt->execute();
    $resultado = $stmt->get_result()->fetch_assoc();

    if ($resultado['total'] > 0) {
        $mensaje_error_agregar = "⚠️ El nombre ya está registrado en la sucursal seleccionada.";
    } else {
        $stmt = $conexion->prepare("INSERT INTO $tabla (nombre) VALUES (?)");
        $stmt->bind_param("s", $nombre);
        $stmt->execute();
    }
}

// Obtener listas
$mecanicos = $conexion->query("SELECT id, nombre FROM mecanicos ORDER BY nombre ASC");
$osvaldo = $conexion->query("SELECT id, nombre FROM nombres_personalizados ORDER BY nombre ASC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de nombres</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">
    <style>
        mark {
            background-color: yellow;
            color: black;
        }
    </style>
</head>
<body>
<section class="section">
<div class="container">
    <h1 class="title is-3">Gestión de nombres por sucursal</h1>

    <div class="buttons mb-4">
        <a href="dashboard.php" class="button is-light">⬅ Volver al panel</a>
    </div>

    <form method="POST" class="box mb-5">
        <h2 class="subtitle is-4">➕ Agregar nuevo nombre</h2>

        <?php if ($mensaje_error_agregar): ?>
            <div class="notification is-danger is-light"><?= $mensaje_error_agregar ?></div>
        <?php endif; ?>

        <div class="field">
            <label class="label">Nombre</label>
            <div class="control">
                <input class="input" type="text" name="agregar_nombre" required>
            </div>
        </div>
        <div class="field">
            <label class="label">Sucursal</label>
            <div class="select is-fullwidth">
                <select name="agregar_sucursal" required>
                    <option value="Lanús">Lanús</option>
                    <option value="Osvaldo Cruz">Osvaldo Cruz</option>
                </select>
            </div>
        </div>
        <div class="control mt-3">
            <button class="button is-primary">Agregar</button>
        </div>
    </form>

    <?php if ($mensaje_error_editar): ?>
        <div class="notification is-danger is-light mb-5"><?= $mensaje_error_editar ?></div>
    <?php endif; ?>

    <div class="columns">
        <!-- LANÚS -->
        <div class="column">
            <h2 class="subtitle is-4">🔴 Lanús (Mecánicos)</h2>

            <?php if ($mensaje_error_lanus): ?>
                <div class="notification is-danger is-light"><?= $mensaje_error_lanus ?></div>
            <?php endif; ?>

            <div id="lista-lanus">
            <?php foreach ($mecanicos as $m): ?>
                <form method="POST" class="is-flex mb-2">
                    <input type="hidden" name="editar_id" value="<?= $m['id'] ?>">
                    <input type="hidden" name="sucursal" value="Lanús">
                    <input class="input mr-2" type="text" name="nuevo_nombre" value="<?= htmlspecialchars($m['nombre']) ?>" required>
                    <button class="button is-warning mr-1" name="editar">💾</button>
                    <button class="button is-danger" name="eliminar_lanus" value="<?= $m['id'] ?>" onclick="return confirm('¿Eliminar este nombre?')">🗑</button>
                </form>
            <?php endforeach; ?>
            </div>
        </div>

        <!-- OSVALDO CRUZ -->
        <div class="column">
            <h2 class="subtitle is-4">🔵 Osvaldo Cruz</h2>

            <div id="lista-osvaldo">
            <?php foreach ($osvaldo as $o): ?>
                <form method="POST" class="is-flex mb-2">
                    <input type="hidden" name="editar_id" value="<?= $o['id'] ?>">
                    <input type="hidden" name="sucursal" value="Osvaldo Cruz">
                    <input class="input mr-2" type="text" name="nuevo_nombre" value="<?= htmlspecialchars($o['nombre']) ?>" required>
                    <button class="button is-warning mr-1" name="editar">💾</button>
                    <button class="button is-danger" name="eliminar_osvaldo" value="<?= $o['id'] ?>" onclick="return confirm('¿Eliminar este nombre?')">🗑</button>
                </form>
            <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
</section>

<script>
function normalizar(texto) {
    return texto.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase();
}

function filtrar(input, tipo) {
    const filtro = normalizar(input.value);
    const contenedor = document.getElementById(`lista-${tipo}`);
    const formularios = contenedor.getElementsByTagName('form');

    for (let form of formularios) {
        const inputNombre = form.querySelector('input[name="nuevo_nombre"]');
        const original = inputNombre.value;
        const textoNormalizado = normalizar(original);

        if (textoNormalizado.includes(filtro)) {
            form.style.display = "flex";
            inputNombre.classList.add("has-background-warning-light");
        } else {
            form.style.display = "none";
            inputNombre.classList.remove("has-background-warning-light");
        }
    }
}
</script>

</body>
</html>
