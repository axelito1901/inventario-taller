<?php
session_start();
if (!isset($_SESSION['gerente'])) {
    header("Location: login.php");
    exit();
}

include 'includes/conexion.php';

$mensaje = '';
$imagenCargada = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = trim($_POST['codigo']);
    $nombre = trim($_POST['nombre']);
    $ubicacion = trim($_POST['ubicacion']);
    $imagen = null;

    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
        $nombreArchivo = uniqid('herr_') . '.' . $ext;
        $ruta = 'imagenes/' . $nombreArchivo;

        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta)) {
            $imagen = $ruta;
            $imagenCargada = $imagen;
        }
    }

    $stmt = $conexion->prepare("INSERT INTO herramientas (codigo, nombre, ubicacion, imagen) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $codigo, $nombre, $ubicacion, $imagen);
    $stmt->execute();

    $mensaje = "✅ Herramienta registrada correctamente.";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar herramienta</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">
</head>
<body>
<section class="section">
<div class="container">
    <h1 class="title is-3">➕ Agregar herramienta</h1>

    <?php if ($mensaje): ?>
        <div class="notification is-success"><?= $mensaje ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="field">
            <label class="label">Código</label>
            <div class="control">
                <input class="input" type="text" name="codigo" required>
            </div>
        </div>

        <div class="field">
            <label class="label">Nombre</label>
            <div class="control">
                <input class="input" type="text" name="nombre" required>
            </div>
        </div>

        <div class="field">
            <label class="label">Ubicación</label>
            <div class="control">
                <input class="input" type="text" name="ubicacion" required>
            </div>
        </div>

        <div class="field">
            <label class="label">Imagen</label>
            <?php if ($imagenCargada): ?>
                <figure class="image is-128x128 mb-2">
                    <img src="<?= htmlspecialchars($imagenCargada) ?>" alt="Imagen cargada">
                </figure>
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
                <button class="button is-primary" type="submit">Guardar herramienta</button>
                <a href="listar_herramientas.php" class="button is-light">Cancelar</a>
            </div>
        </div>
    </form>
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
