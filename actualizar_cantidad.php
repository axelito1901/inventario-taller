<?php
session_start();
include 'includes/conexion.php';

// Mensaje por GET (PRG)
$mensaje = $_GET['msg'] ?? null;
$tipoMsg = $_GET['msgtype'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $id = intval($_POST['id']);

    if ($_POST['accion'] === 'sumar') {
        $stmt = $conexion->prepare("SELECT cantidad FROM herramientas WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($cantidad_actual);
        $stmt->fetch();
        $stmt->close();

        $nueva = $cantidad_actual + 1;

        $stmt = $conexion->prepare("INSERT INTO historial_stock (herramienta_id, stock_anterior, stock_nuevo) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $id, $cantidad_actual, $nueva);
        $stmt->execute();
        $stmt->close();

        $stmt = $conexion->prepare("UPDATE herramientas SET cantidad = ? WHERE id = ?");
        $stmt->bind_param("ii", $nueva, $id);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => true, 'cantidad' => $nueva, 'accion' => 'sumar']);
        exit;
    }

    if ($_POST['accion'] === 'deshacer') {
        $stmt = $conexion->prepare("SELECT stock_anterior FROM historial_stock WHERE herramienta_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($anterior);
        $stmt->fetch();
        $stmt->close();

        if (isset($anterior)) {
            $stmt = $conexion->prepare("UPDATE herramientas SET cantidad = ? WHERE id = ?");
            $stmt->bind_param("ii", $anterior, $id);
            $stmt->execute();
            $stmt->close();

            $stmt = $conexion->prepare("DELETE FROM historial_stock WHERE herramienta_id = ? ORDER BY id DESC LIMIT 1");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            echo json_encode(['success' => true, 'cantidad' => $anterior, 'accion' => 'deshacer']);
            exit;
        }
    }

    echo json_encode(['success' => false]);
    exit;
}

$resultado = $conexion->query("SELECT * FROM herramientas ORDER BY CAST(codigo AS UNSIGNED) ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Actualizar Stock</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/all.min.css">
    <link rel="stylesheet" href="css/fontawesome.min.css">
    <style>
        :root {
            --vw-blue: #00247D;
            --vw-gray: #F4F4F4;
        }
        body { 
            background: var(--vw-gray); 
        }
        header.header-fixed-vw {
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-bottom: 1px solid #e5e7eb;
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
        .btn-main {
            display: inline-flex;
            align-items: center;
            gap: 0.5em;
            font-weight: 600;
            border-radius: 0.7em;
            padding: 0.8em 1.5em;
            font-size: 1.08em;
            box-shadow: 0 2px 8px #0001;
            transition: background 0.15s, color 0.15s;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }
        .btn-main-outline {
            background: #fff;
            color: var(--vw-blue);
            border: 2px solid var(--vw-blue);
        }
        .btn-main-outline:hover {
            background: var(--vw-blue);
            color: #fff;
        }
        .herramienta-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 1.2em;
            padding: 1.5em;
            box-shadow: 0 2px 8px #0001;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        .herramienta-card:hover {
            box-shadow: 0 4px 16px #0002;
            transform: translateY(-2px);
        }
        .herramienta-card.actualizada {
            background: linear-gradient(135deg, #dcfce7, #bbf7d0);
            border-color: #22c55e;
            animation: pulso 0.6s ease-out;
        }
        @keyframes pulso {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }
        .imagen-herramienta {
            width: 80px;
            height: 80px;
            object-fit: contain;
            border-radius: 0.8em;
            border: 1px solid #e5e7eb;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .imagen-herramienta:hover {
            transform: scale(1.05);
        }
        .sin-imagen {
            width: 80px;
            height: 80px;
            background: #f3f4f6;
            color: #9ca3af;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.8em;
            border: 1px solid #e5e7eb;
        }
        .cantidad-display {
            background: var(--vw-blue);
            color: #fff;
            padding: 0.5em 1em;
            border-radius: 0.8em;
            font-weight: bold;
            font-size: 1.2em;
            min-width: 60px;
            text-align: center;
        }
        .btn-accion {
            display: inline-flex;
            align-items: center;
            gap: 0.4em;
            font-weight: 600;
            border-radius: 0.7em;
            padding: 0.6em 1.2em;
            font-size: 0.9em;
            border: none;
            cursor: pointer;
            transition: all 0.15s;
            box-shadow: 0 2px 4px #0001;
        }
        .btn-sumar {
            background: #22c55e;
            color: #fff;
        }
        .btn-sumar:hover {
            background: #16a34a;
            transform: translateY(-1px);
        }
        .btn-deshacer {
            background: #f59e0b;
            color: #fff;
        }
        .btn-deshacer:hover {
            background: #d97706;
            transform: translateY(-1px);
        }
        .check-icon {
            position: absolute;
            top: 10px;
            right: 10px;
            color: #22c55e;
            font-size: 1.5em;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .check-icon.visible {
            opacity: 1;
        }
        .modal-imagen { 
            display: none; 
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.8);
            z-index: 10000;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .modal-imagen.active { 
            display: flex; 
        }
        .modal-imagen img {
            max-height: 80vh;
            max-width: 90vw;
            border-radius: 1em;
            box-shadow: 0 8px 32px #0005;
        }
    </style>
</head>
<body class="bg-[var(--vw-gray)] min-h-screen text-gray-800 font-sans">

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

<!-- HEADER FIJO CON LOGO Y TÍTULO -->
<header class="header-fixed-vw fixed top-0 left-0 w-full bg-white z-50 flex items-center px-8 py-2" style="height:68px;">
  <img src="logo-volskwagen.png" alt="Logo de VW" class="h-12 w-auto mr-4 select-none" draggable="false" style="pointer-events:none;">
  <span class="text-2xl font-bold text-[var(--vw-blue)]"><i class="fa-solid fa-boxes-stacked mr-2"></i>Actualizar Stock</span>
  <a href="dashboard.php" class="ml-auto btn-main btn-main-outline text-sm"><i class="fa-solid fa-arrow-left"></i> Volver al panel</a>
</header>

<!-- CONTENIDO PRINCIPAL -->
<div class="max-w-7xl mx-auto pt-28 py-10 px-4">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <?php while ($h = $resultado->fetch_assoc()) { ?>
        <div id="fila-<?= $h['id'] ?>" class="herramienta-card">
            <div class="check-icon" id="check-<?= $h['id'] ?>">
                <i class="fa-solid fa-check-circle"></i>
            </div>
            
            <div class="flex flex-col items-center text-center mb-4">
                <?php if (!empty($h['imagen']) && file_exists($h['imagen'])): ?>
                    <img src="<?= $h['imagen'] ?>" class="imagen-herramienta mb-3"
                        onclick="mostrarModal('<?= $h['imagen'] ?>')" alt="Imagen de <?= htmlspecialchars($h['nombre']) ?>">
                <?php else: ?>
                    <div class="sin-imagen mb-3">
                        <i class="fa-solid fa-image text-2xl"></i>
                    </div>
                <?php endif; ?>
                
                <h3 class="font-bold text-lg text-[var(--vw-blue)] mb-1"><?= htmlspecialchars($h['nombre']) ?></h3>
                <p class="text-sm text-gray-500 mb-2">
                    <i class="fa-solid fa-barcode mr-1"></i><?= htmlspecialchars($h['codigo']) ?>
                </p>
                <p class="text-xs text-gray-400">
                    <i class="fa-solid fa-location-dot mr-1"></i><?= htmlspecialchars($h['ubicacion']) ?>
                </p>
            </div>
            
            <div class="flex items-center justify-center mb-4">
                <span class="text-gray-600 text-sm mr-2">Stock:</span>
                <span id="cantidad-<?= $h['id'] ?>" class="cantidad-display"><?= $h['cantidad'] ?></span>
            </div>
            
            <div class="flex gap-2 justify-center">
                <button class="btn-accion btn-sumar" onclick="actualizarCantidad(<?= $h['id'] ?>, 'sumar')">
                    <i class="fa-solid fa-plus"></i> Sumar
                </button>
                <button class="btn-accion btn-deshacer" onclick="actualizarCantidad(<?= $h['id'] ?>, 'deshacer')">
                    <i class="fa-solid fa-undo"></i> Deshacer
                </button>
            </div>
        </div>
        <?php } ?>
    </div>
</div>

<!-- MODAL IMAGEN -->
<div id="modalImagen" class="modal-imagen" onclick="cerrarModal()">
    <img id="imagenAmpliada" src="" alt="Imagen ampliada">
</div>

<script src="fontawesome/js/all.min.js"></script>
<script>
function mostrarModal(src) {
    document.getElementById("imagenAmpliada").src = src;
    document.getElementById("modalImagen").classList.add("active");
}

function cerrarModal() {
    document.getElementById("modalImagen").classList.remove("active");
}

function actualizarCantidad(id, accion) {
    const formData = new FormData();
    formData.append('id', id);
    formData.append('accion', accion);

    fetch('actualizar_cantidad.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const cantidadSpan = document.getElementById(`cantidad-${id}`);
            const checkIcon = document.getElementById(`check-${id}`);
            const fila = document.getElementById(`fila-${id}`);

            // Actualizar cantidad
            cantidadSpan.innerText = data.cantidad;
            
            // Mostrar feedback visual
            checkIcon.classList.add('visible');
            fila.classList.add('actualizada');

            // Mostrar notificación flotante
            mostrarNotificacion(
                data.accion === 'sumar' ? 'Stock actualizado correctamente' : 'Cambio deshecho correctamente',
                'ok'
            );

            // Ocultar efectos después de un tiempo
            setTimeout(() => {
                checkIcon.classList.remove('visible');
                fila.classList.remove('actualizada');
            }, 1500);
        } else {
            mostrarNotificacion('Error al actualizar el stock', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarNotificacion('Error de conexión', 'error');
    });
}

function mostrarNotificacion(mensaje, tipo) {
    // Remover notificación existente
    const existente = document.querySelector('.notificacion-flotante');
    if (existente) {
        existente.remove();
    }

    // Crear nueva notificación
    const notif = document.createElement('div');
    notif.className = 'notificacion-flotante';
    notif.innerHTML = `
        <i class="fa-solid ${tipo === 'ok' ? 'fa-check-circle' : 'fa-xmark'}"></i>
        ${mensaje}
    `;
    
    document.body.appendChild(notif);
    
    // Auto-ocultar después de 3 segundos
    setTimeout(() => {
        if (notif.parentNode) {
            notif.remove();
        }
    }, 3000);
}

// Notificación flotante auto-oculta (para mensajes del servidor)
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