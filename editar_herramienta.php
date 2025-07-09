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
    $cantidad = intval($_POST['cantidad']);
    $imagen = $herramienta['imagen'];

    if ($_FILES['imagen']['tmp_name']) {
        $imagen_nombre = time() . "_" . basename($_FILES['imagen']['name']);
        $ruta = "imagenes/" . $imagen_nombre;
        move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta);
        $imagen = $ruta;
    }

    $update = $conexion->prepare("UPDATE herramientas SET codigo = ?, nombre = ?, ubicacion = ?, imagen = ?, cantidad = ? WHERE id = ?");
    $update->bind_param("ssssii", $codigo, $nombre, $ubicacion, $imagen, $cantidad, $id);
    $update->execute();

    $mensaje = "Herramienta actualizada correctamente.";

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
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/all.min.css">
    <link rel="stylesheet" href="css/fontawesome.min.css">
    <style>
        :root {
            --vw-blue: #00247D;
            --vw-gray: #F4F4F4;
        }
    </style>
</head>
<body class="bg-[var(--vw-gray)] min-h-screen text-gray-800 font-sans">
<div class="max-w-2xl mx-auto p-6">
    <div class="flex items-center gap-3 mb-6">
        <img src="logo-volskwagen.png" alt="Logo" class="h-12 w-auto drop-shadow">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-[var(--vw-blue)] tracking-tight">Editar herramienta</h1>
    </div>

    <div class="mb-6">
        <a href="listar_herramientas.php" class="inline-flex items-center gap-2 bg-white border border-gray-300 hover:bg-gray-100 text-[var(--vw-blue)] px-5 py-3 rounded-lg shadow font-semibold transition">
            <i class="fa-solid fa-arrow-left"></i> Volver a la lista
        </a>
    </div>

    <?php if (isset($mensaje)): ?>
        <div class="p-6 bg-green-100 text-green-800 rounded-2xl shadow border border-green-200 mb-6 font-semibold">
            <i class="fa-solid fa-check-circle mr-2"></i>
            <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl shadow border border-gray-200 p-8">
        <form method="post" enctype="multipart/form-data" class="space-y-6">
            <div>
                <label class="font-semibold text-[var(--vw-blue)] mb-2 block">Código de herramienta</label>
                <input 
                    type="text" 
                    name="codigo" 
                    value="<?= htmlspecialchars($herramienta['codigo']) ?>" 
                    required 
                    class="border-2 border-gray-200 rounded-lg px-4 py-3 w-full focus:ring-2 focus:ring-[var(--vw-blue)] focus:border-blue-400 outline-none transition"
                    placeholder="Ej: H001"
                >
            </div>

            <div>
                <label class="font-semibold text-[var(--vw-blue)] mb-2 block">Nombre de la herramienta</label>
                <input 
                    type="text" 
                    name="nombre" 
                    value="<?= htmlspecialchars($herramienta['nombre']) ?>" 
                    required 
                    class="border-2 border-gray-200 rounded-lg px-4 py-3 w-full focus:ring-2 focus:ring-[var(--vw-blue)] focus:border-blue-400 outline-none transition"
                    placeholder="Ej: Llave inglesa 12mm"
                >
            </div>

            <div>
                <label class="font-semibold text-[var(--vw-blue)] mb-2 block">Ubicación en taller</label>
                <input 
                    type="text" 
                    name="ubicacion" 
                    value="<?= htmlspecialchars($herramienta['ubicacion']) ?>" 
                    required 
                    class="border-2 border-gray-200 rounded-lg px-4 py-3 w-full focus:ring-2 focus:ring-[var(--vw-blue)] focus:border-blue-400 outline-none transition"
                    placeholder="Ej: Estante A - Cajón 3"
                >
            </div>

            <div>
                <label class="font-semibold text-[var(--vw-blue)] mb-2 block">Cantidad disponible</label>
                <input 
                    type="number" 
                    name="cantidad" 
                    value="<?= intval($herramienta['cantidad']) ?>" 
                    min="0" 
                    required 
                    class="border-2 border-gray-200 rounded-lg px-4 py-3 w-full focus:ring-2 focus:ring-[var(--vw-blue)] focus:border-blue-400 outline-none transition"
                    placeholder="0"
                >
            </div>

            <div class="bg-gray-50 p-4 rounded-lg">
                <label class="font-semibold text-[var(--vw-blue)] mb-2 block">Estado actual</label>
                <?php if ($herramienta['cantidad'] == 0): ?>
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-red-100 text-red-700 font-semibold shadow-sm">
                        <i class="fa-solid fa-exclamation-circle"></i> Sin stock
                    </span>
                <?php else: ?>
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-green-100 text-green-700 font-semibold shadow-sm">
                        <i class="fa-solid fa-check-circle"></i> En stock (<?= intval($herramienta['cantidad']) ?> unidades)
                    </span>
                <?php endif; ?>
            </div>

            <div>
                <label class="font-semibold text-[var(--vw-blue)] mb-2 block">Imagen de la herramienta</label>

                <?php if ($herramienta['imagen'] && file_exists($herramienta['imagen'])): ?>
                    <div class="mb-4">
                        <p class="text-sm text-gray-600 mb-2">Imagen actual:</p>
                        <img 
                            src="<?= htmlspecialchars($herramienta['imagen']) ?>" 
                            alt="Imagen actual" 
                            class="h-40 object-contain border-2 border-gray-200 rounded-lg p-2 bg-white shadow-sm"
                        >
                    </div>
                <?php else: ?>
                    <div class="mb-4 p-6 bg-gray-100 rounded-lg text-center text-gray-500">
                        <i class="fa-solid fa-image text-3xl mb-2"></i>
                        <p class="text-sm">No hay imagen actual</p>
                    </div>
                <?php endif; ?>

                <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 hover:border-[var(--vw-blue)] transition">
                    <label class="cursor-pointer block">
                        <div class="text-center">
                            <i class="fa-solid fa-cloud-upload-alt text-2xl text-gray-400 mb-2"></i>
                            <p class="text-sm text-gray-600 mb-1">Subir nueva imagen (opcional)</p>
                            <p class="text-xs text-gray-400">JPG, PNG, GIF - Máximo 5MB</p>
                        </div>
                        <input 
                            type="file" 
                            name="imagen" 
                            accept="image/*" 
                            class="hidden"
                        >
                    </label>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 pt-4">
                <button 
                    type="submit" 
                    class="flex-1 bg-[var(--vw-blue)] hover:bg-blue-900 text-white px-6 py-3 rounded-lg font-bold shadow transition flex items-center justify-center gap-2"
                >
                    <i class="fa-solid fa-save"></i>
                    <span>Guardar cambios</span>
                </button>
                <a 
                    href="listar_herramientas.php" 
                    class="flex-1 bg-gray-500 hover:bg-gray-600 text-blue-500 px-6 py-3 rounded-lg font-bold shadow transition flex items-center justify-center gap-2"
                >
                    <i class="fa-solid fa-times"></i>
                    <span>Cancelar</span>
                </a>
            </div>
        </form>
    </div>
</div>

<script src="fontawesome/js/all.min.js"></script>
<script>
// Mostrar nombre del archivo seleccionado
document.querySelector('input[type="file"]').addEventListener('change', function(e) {
    const fileName = e.target.files[0]?.name;
    if (fileName) {
        const label = e.target.closest('label');
        const textDiv = label.querySelector('div');
        textDiv.innerHTML = `
            <i class="fa-solid fa-file-image text-2xl text-green-500 mb-2"></i>
            <p class="text-sm text-green-600 mb-1">Archivo seleccionado:</p>
            <p class="text-xs font-semibold text-green-700">${fileName}</p>
        `;
    }
});
</script>
</body>
</html>