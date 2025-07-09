<?php
session_start();
if (!isset($_SESSION['gerente'])) {
    header("Location: login.php");
    exit();
}

include 'includes/conexion.php';

// Mensaje por GET (PRG)
$mensaje = $_GET['msg'] ?? null;
$tipoMsg = $_GET['msgtype'] ?? null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $codigo = trim($_POST['codigo']);
    $nombre = trim($_POST['nombre']);
    $ubicacion = trim($_POST['ubicacion']);
    $cantidad = intval($_POST['cantidad']);

    // Validaciones básicas
    if (empty($codigo) || empty($nombre) || empty($ubicacion)) {
        $mensaje = "Todos los campos son obligatorios.";
        $tipoMsg = "error";
    } else {
        // Verificar si el código ya existe
        $stmt_check = $conexion->prepare("SELECT id FROM herramientas WHERE codigo = ?");
        $stmt_check->bind_param("s", $codigo);
        $stmt_check->execute();
        $result = $stmt_check->get_result();
        
        if ($result->num_rows > 0) {
            $mensaje = "Ya existe una herramienta con ese código.";
            $tipoMsg = "error";
        } else {
            $imagen = null;
            if ($_FILES['imagen']['tmp_name']) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $file_ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
                
                if (in_array($file_ext, $allowed) && $_FILES['imagen']['size'] <= 5000000) { // 5MB max
                    $imagen_nombre = time() . "_" . uniqid() . "." . $file_ext;
                    $ruta = "imagenes/" . $imagen_nombre;
                    
                    if (!is_dir("imagenes/")) {
                        mkdir("imagenes/", 0755, true);
                    }
                    
                    if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta)) {
                        $imagen = $ruta;
                    }
                } else {
                    $mensaje = "Formato de imagen no válido o archivo muy grande (máx. 5MB).";
                    $tipoMsg = "error";
                }
            }

            if (!isset($mensaje)) {
                $stmt = $conexion->prepare("INSERT INTO herramientas (codigo, nombre, imagen, ubicacion, cantidad) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssi", $codigo, $nombre, $imagen, $ubicacion, $cantidad);
                
                if ($stmt->execute()) {
                    $mensaje = "Herramienta '$nombre' agregada correctamente.";
                    $tipoMsg = "ok";
                } else {
                    $mensaje = "Error al guardar la herramienta.";
                    $tipoMsg = "error";
                }
                $stmt->close();
            }
        }
        $stmt_check->close();
    }

    // PRG - Redirigir con mensaje
    header("Location: agregar_herramienta.php?msg=" . urlencode($mensaje) . "&msgtype=" . $tipoMsg);
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar herramienta</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/all.min.css">
    <link rel="stylesheet" href="css/fontawesome.min.css">
    <style>
        :root {
            --vw-blue: #00247D;
            --vw-gray: #F4F4F4;
        }
        .notificacion-flotante {
            position: fixed;
            top: 32px;
            left: 50%;
            transform: translateX(-50%);
            background: #18181b;
            color: #fff;
            padding: 1.1em 2.2em;
            border-radius: 1em;
            font-size: 1.08em;
            font-weight: 600;
            box-shadow: 0 6px 32px #0005;
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 0.8em;
            min-width: 320px;
            max-width: 90vw;
            text-align: center;
            animation: fadein 0.4s;
        }
        .notificacion-flotante .fa-check-circle { color: #22c55e; }
        .notificacion-flotante .fa-xmark { color: #f87171; }
        @keyframes fadein {
            from { opacity: 0; top: 0; }
            to { opacity: 1; top: 32px; }
        }
        @media (max-width: 500px) {
            .notificacion-flotante { font-size: 0.98em; min-width: 0; padding: 0.7em 1em; }
        }
        .preview-container {
            display: none;
            margin-top: 1rem;
        }
        .preview-image {
            max-width: 200px;
            max-height: 200px;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .file-input-wrapper {
            position: relative;
            display: inline-block;
            cursor: pointer;
            width: 100%;
        }
        .file-input-wrapper input[type=file] {
            position: absolute;
            left: -9999px;
        }
        .file-input-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            border: 2px dashed #d1d5db;
            border-radius: 0.5rem;
            background: #f9fafb;
            color: #6b7280;
            font-weight: 500;
            transition: all 0.2s;
            cursor: pointer;
        }
        .file-input-label:hover {
            border-color: var(--vw-blue);
            background: #eff6ff;
            color: var(--vw-blue);
        }
        .file-input-wrapper.has-file .file-input-label {
            border-color: #10b981;
            background: #ecfdf5;
            color: #065f46;
        }
    </style>
</head>
<body class="bg-[var(--vw-gray)] text-gray-800 min-h-screen font-sans">

<?php if ($mensaje): ?>
    <div class="notificacion-flotante">
        <?php if ($tipoMsg === "ok"): ?>
            <i class="fa-solid fa-check-circle"></i>
        <?php else: ?>
            <i class="fa-solid fa-xmark"></i>
        <?php endif; ?>
        <?= htmlspecialchars($mensaje) ?>
    </div>
<?php endif; ?>

<main class="max-w-2xl mx-auto p-6">
    <div class="flex items-center gap-3 mb-6">
        <img src="logo-volskwagen.png" alt="Logo" class="h-12 w-auto drop-shadow">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-[var(--vw-blue)] tracking-tight">Agregar herramienta</h1>
    </div>

    <div class="bg-white rounded-2xl shadow border border-gray-200 p-8">
        <form method="post" enctype="multipart/form-data" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block font-semibold text-[var(--vw-blue)] mb-2">
                        <i class="fa-solid fa-barcode mr-1"></i> Código *
                    </label>
                    <input type="text" name="codigo" required 
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-[var(--vw-blue)] focus:border-blue-400 outline-none transition"
                           placeholder="Ej: H001">
                </div>

                <div>
                    <label class="block font-semibold text-[var(--vw-blue)] mb-2">
                        <i class="fa-solid fa-tag mr-1"></i> Cantidad *
                    </label>
                    <input type="number" name="cantidad" min="0" required 
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-[var(--vw-blue)] focus:border-blue-400 outline-none transition"
                           placeholder="0">
                </div>
            </div>

            <div>
                <label class="block font-semibold text-[var(--vw-blue)] mb-2">
                    <i class="fa-solid fa-wrench mr-1"></i> Nombre de la herramienta *
                </label>
                <input type="text" name="nombre" required 
                       class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-[var(--vw-blue)] focus:border-blue-400 outline-none transition">
                       
            </div>

            <div>
                <label class="block font-semibold text-[var(--vw-blue)] mb-2">
                    <i class="fa-solid fa-map-marker-alt mr-1"></i> Ubicación *
                </label>
                <input type="text" name="ubicacion" required 
                       class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-[var(--vw-blue)] focus:border-blue-400 outline-none transition">
            </div>

            <div>
                <label class="block font-semibold text-[var(--vw-blue)] mb-2">
                    <i class="fa-solid fa-image mr-1"></i> Imagen (opcional)
                </label>
                <div class="file-input-wrapper">
                    <input type="file" name="imagen" accept="image/*" id="imagen" onchange="previewImage(this)">
                    <label for="imagen" class="file-input-label">
                        <i class="fa-solid fa-cloud-upload-alt text-xl"></i>
                        <span>Seleccionar imagen (máx. 5MB)</span>
                    </label>
                </div>
                <div id="preview-container" class="preview-container">
                    <img id="preview-image" class="preview-image" alt="Vista previa">
                    <button type="button" onclick="removeImage()" class="mt-2 text-red-600 hover:text-red-800 text-sm font-medium">
                        <i class="fa-solid fa-times mr-1"></i> Quitar imagen
                    </button>
                </div>
                <p class="text-sm text-gray-500 mt-1">Formatos: JPG, PNG, GIF, WebP</p>
            </div>

            <div class="flex flex-col sm:flex-row gap-4 pt-4">
                <button type="submit" class="flex-1 bg-[var(--vw-blue)] hover:bg-blue-900 text-white px-6 py-3 rounded-lg font-bold shadow transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-save"></i> Guardar herramienta
                </button>
                <a href="listar_herramientas.php" class="flex-1 text-center bg-white border border-gray-300 hover:bg-gray-100 text-[var(--vw-blue)] px-6 py-3 rounded-lg font-semibold shadow transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-arrow-left"></i> Volver a la lista
                </a>
            </div>
        </form>
    </div>
</main>

<script src="fontawesome/js/all.min.js"></script>
<script>
// Preview de imagen
function previewImage(input) {
    const file = input.files[0];
    const wrapper = input.closest('.file-input-wrapper');
    const previewContainer = document.getElementById('preview-container');
    const previewImage = document.getElementById('preview-image');
    const label = wrapper.querySelector('.file-input-label span');

    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImage.src = e.target.result;
            previewContainer.style.display = 'block';
            wrapper.classList.add('has-file');
            label.textContent = file.name;
        };
        reader.readAsDataURL(file);
    }
}

function removeImage() {
    const input = document.getElementById('imagen');
    const wrapper = input.closest('.file-input-wrapper');
    const previewContainer = document.getElementById('preview-container');
    const label = wrapper.querySelector('.file-input-label span');
    
    input.value = '';
    previewContainer.style.display = 'none';
    wrapper.classList.remove('has-file');
    label.textContent = 'Seleccionar imagen (máx. 5MB)';
}

// Notificación flotante auto-oculta
window.onload = function() {
    var notif = document.querySelector('.notificacion-flotante');
    if (notif) {
        setTimeout(function() {
            notif.style.display = 'none';
        }, 3000);
    }
}
</script>
</body>
</html> 