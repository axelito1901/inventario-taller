<?php
session_start();
if (!isset($_SESSION['gerente'])) {
    header("Location: login.php");
    exit();
}

include 'includes/conexion.php';

if (!isset($_GET['id'])) {
    echo "ID de herramienta no especificado.";
    exit();
}

$id = $_GET['id'];

// Obtener los datos actuales de la herramienta
$stmt = $conexion->prepare("SELECT * FROM herramientas WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();
$herramienta = $resultado->fetch_assoc();

if (!$herramienta) {
    echo "Herramienta no encontrada.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $codigo = $_POST['codigo'];
    $nombre = $_POST['nombre'];
    $ubicacion = $_POST['ubicacion'];
    $imagen = $herramienta['imagen']; // Valor por defecto

    // Si hay nueva imagen
    if ($_FILES['imagen']['tmp_name']) {
        $imagen_nombre = time() . "_" . basename($_FILES['imagen']['name']);
        $ruta = "imagenes/" . $imagen_nombre;
        move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta);
        $imagen = $ruta;
    }

    $update = $conexion->prepare("UPDATE herramientas SET codigo = ?, nombre = ?, ubicacion = ?, imagen = ? WHERE id = ?");
    $update->bind_param("ssssi", $codigo, $nombre, $ubicacion, $imagen, $id);
    $update->execute();

    $mensaje = "Herramienta actualizada correctamente.";
    // Recargar datos actualizados
    $stmt = $conexion->prepare("SELECT * FROM herramientas WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $herramienta = $resultado->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar herramienta</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">
</head>
<body>
<section class="section">
    <div class="container">
        <h2 class="title is-3">Editar herramienta</h2>

        <?php if (isset($mensaje)): ?>
            <div class="notification is-success">
                <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <div class="field">
                <label class="label">Código</label>
                <div class="control">
                    <input class="input" type="text" name="codigo" value="<?= htmlspecialchars($herramienta['codigo']) ?>" required>
                </div>
            </div>

            <div class="field">
                <label class="label">Nombre</label>
                <div class="control">
                    <input class="input" type="text" name="nombre" value="<?= htmlspecialchars($herramienta['nombre']) ?>" required>
                </div>
            </div>

            <div class="field">
                <label class="label">Ubicación</label>
                <div class="control">
                    <input class="input" type="text" name="ubicacion" value="<?= htmlspecialchars($herramienta['ubicacion']) ?>" required>
                </div>
            </div>

            <div class="field">
                <label class="label">Imagen actual</label>
                <?php if ($herramienta['imagen']): ?>
                    <figure class="image is-128x128 mb-2">
                        <img src="<?= htmlspecialchars($herramienta['imagen']) ?>" alt="Imagen actual">
                    </figure>
                <?php else: ?>
                    <p>No hay imagen.</p>
                <?php endif; ?>
                <div class="file has-name is-boxed">
                    <label class="file-label">
                        <input class="file-input" type="file" name="imagen" accept="image/*">
                        <span class="file-cta">
                            <span class="file-icon">📁</span>
                            <span class="file-label">Elegir archivo…</span>
                        </span>
                        <span class="file-name" id="file-name">Ningún archivo seleccionado</span>
                    </label>
                </div>
            </div>

            <div class="field mt-4">
                <div class="control">
                    <button class="button is-primary" type="submit">Guardar cambios</button>
                </div>
            </div>
        </form>

        <a href="listar_herramientas.php" class="button is-light mt-4">⬅ Volver a la lista</a>
    </div>
</section>

<script>
const fileInput = document.querySelector('.file-input');
const fileName = document.getElementById('file-name');

fileInput.addEventListener('change', () => {
    if (fileInput.files.length > 0) {
        fileName.textContent = fileInput.files[0].name;
    } else {
        fileName.textContent = 'Ningún archivo seleccionado';
    }
});
</script>
</body>
</html>
