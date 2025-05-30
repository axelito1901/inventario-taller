<?php
session_start();
if (!isset($_SESSION['gerente'])) {
    header("Location: login.php");
    exit();
}

include 'includes/conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $codigo = $_POST['codigo'];
    $nombre = $_POST['nombre'];
    $ubicacion = $_POST['ubicacion'];
    $cantidad = intval($_POST['cantidad']);

    $imagen = null;
    if ($_FILES['imagen']['tmp_name']) {
        $imagen_nombre = time() . "_" . basename($_FILES['imagen']['name']);
        $ruta = "imagenes/" . $imagen_nombre;
        move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta);
        $imagen = $ruta;
    }

    $stmt = $conexion->prepare("INSERT INTO herramientas (codigo, nombre, imagen, ubicacion, cantidad) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $codigo, $nombre, $imagen, $ubicacion, $cantidad);
    $stmt->execute();

    $mensaje = "Herramienta agregada correctamente.";
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
        <h2 class="title is-3">Agregar herramienta</h2>

        <?php if (isset($mensaje)): ?>
            <div class="notification is-success">
                <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
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
                <label class="label">Cantidad</label>
                <div class="control">
                    <input class="input" type="number" name="cantidad" min="0" required>
                </div>
            </div>

            <div class="field">
                <label class="label">Imagen</label>
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
                </div>
            </div>
        </form>

        <a href="listar_herramientas.php" class="button is-light mt-4">⬅ Volver a la lista</a>
    </div>
</section>

<script>
document.querySelector('.file-input').addEventListener('change', function () {
    const fileName = document.getElementById('file-name');
    fileName.textContent = this.files.length > 0 ? this.files[0].name : 'Ningún archivo seleccionado';
});
</script>
</body>
</html>
