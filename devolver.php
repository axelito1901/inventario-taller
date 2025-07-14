<?php
session_start();
include 'includes/conexion.php';

// Mensaje por GET (PRG)
$mensaje = $_GET['msg'] ?? null;
$tipoMsg = $_GET['msgtype'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['devolver_id'])) {
    $id = intval($_POST['devolver_id']);
    $comentario = trim($_POST['comentario'] ?? '');
    $fecha_devolucion = date('Y-m-d H:i:s');

    $stmt = $conexion->prepare("UPDATE prestamos SET devuelta = 1, fecha_devolucion = ?, comentario_devolucion = ? WHERE id = ?");
    $stmt->bind_param("ssi", $fecha_devolucion, $comentario, $id);
    $success = $stmt->execute();
    $stmt->close();

    if ($success) {
        $mensaje = "Herramienta devuelta correctamente.";
        $tipoMsg = "ok";
    } else {
        $mensaje = "Error al devolver la herramienta.";
        $tipoMsg = "error";
    }

    // PRG - Redirigir con mensaje
    header("Location: devolver.php?msg=" . urlencode($mensaje) . "&msgtype=" . $tipoMsg);
    exit();
}

$prestamos = $conexion->query("
    SELECT p.id, h.nombre AS herramienta, h.codigo, h.ubicacion, p.fecha_hora, m.nombre AS mecanico, p.nombre_personalizado, p.sucursal
    FROM prestamos p
    LEFT JOIN herramientas h ON p.herramienta_id = h.id
    LEFT JOIN mecanicos m ON p.mecanico_id = m.id
    WHERE p.devuelta = 0
    ORDER BY p.fecha_hora ASC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Devolver herramienta</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/all.min.css">
    <link rel="stylesheet" href="css/fontawesome.min.css">
    <script src="js/jquery-3.6.0.min.js"></script>
    <style>
      :root {
        --vw-blue: #00247D;
        --vw-gray: #F4F4F4;
      }
      body { background: var(--vw-gray); }
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
      .btn-devolver {
        background: #fbbf24;
        color: #92400e;
        font-size: 1.02em;
        padding: 0.5em 1.7em; /* Aumenta el padding horizontal */
        font-weight: 600;
        border-radius: .7em;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: .6em;
        justify-content: center;
        transition: background .15s, color .15s;
        min-width: 120px;
        white-space: nowrap;
      }

      .btn-devolver:hover {
        background: #f59e0b;
        color: #78350f;
      }
      .tabla-prestamos {
        background: #fff;
        border-radius: 1em;
        overflow-x: auto;
        box-shadow: 0 2px 8px #0001;
        border: 1px solid #e5e7eb;
        width: 100%;
      }
      .tabla-prestamos th, .tabla-prestamos td {
        padding: 1em 0.7em;
        text-align: left;
        vertical-align: top;
        word-break: break-word;
      }
      .tabla-prestamos th {
        background: var(--vw-blue);
        color: #fff;
        font-weight: 600;
        white-space: nowrap;
      }
      .tabla-prestamos tr {
        border-bottom: 1px solid #f3f4f6;
      }
      .tabla-prestamos tr:hover {
        background: #f8fafc;
      }
      .tabla-prestamos td textarea {
        width: 100%;
        min-width: 140px;
        max-width: 220px;
        min-height: 48px;
        font-size: 0.98em;
      }
      .codigo-txt {
        font-size: .92em;
        color: #666;
        margin-top: 2px;
        display: block;
        white-space: nowrap;
      }
      .sucursal-lanus { color: #dc2626; font-weight: 600; }
      .sucursal-oc { color: #2563eb; font-weight: 600; }
      .comentario-input {
        width: 100%;
        padding: 0.5em;
        border: 1px solid #d1d5db;
        border-radius: 0.5em;
        font-size: 0.97em;
        outline: none;
        transition: border 0.15s;
        background: #f9fafb;
        resize: vertical;
        min-height: 48px;
        max-width: 220px;
      }
      .comentario-input:focus {
        border-color: var(--vw-blue);
        background: #fff;
      }
      /* Modal confirmación */
      .modal-bg {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.6);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 10000;
      }
      .modal-confirm {
        background: #fff;
        border-radius: 1.2em;
        padding: 2.5em 2em 2em 2em;
        max-width: 400px;
        width: 100%;
        box-shadow: 0 8px 32px #0005;
        text-align: center;
        position: relative;
      }
      .modal-confirm h2 {
        color: #f59e0b;
        font-size: 1.25em;
        font-weight: bold;
        margin-bottom: 0.7em;
        display: flex;
        align-items: center;
        gap: 0.5em;
        justify-content: center;
      }
      .modal-confirm p {
        color: #222;
        margin-bottom: 1.5em;
      }
      .modal-confirm .btns {
        display: flex;
        flex-direction: column;
        gap: 0.7em;
      }
      .modal-confirm button,
      .modal-confirm .btn-cancel {
        font-weight: bold;
        border: none;
        border-radius: 0.7em;
        padding: 0.7em 0;
        width: 100%;
        font-size: 1em;
        cursor: pointer;
        box-shadow: 0 2px 8px #0001;
        transition: background 0.15s, color 0.15s;
        text-decoration: none;
        display: block;
        text-align: center;
      }
      .modal-confirm button {
        background: #fbbf24;
        color: #92400e;
      }
      .modal-confirm button:hover {
        background: #f59e0b;
        color: #78350f;
      }
      .modal-confirm .btn-cancel {
        background: #f3f4f6;
        color: #222;
      }
      .modal-confirm .btn-cancel:hover {
        background: #e5e7eb;
      }
      /* Responsive */
      @media (max-width: 900px) {
        .tabla-prestamos th, .tabla-prestamos td { font-size: 0.97em; padding: .7em .4em; }
        .comentario-input { min-width: 80px; max-width: 140px; }
      }
      @media (max-width: 600px) {
        .tabla-prestamos th, .tabla-prestamos td { font-size: .93em; padding: .6em .2em; }
        .comentario-input { min-width: 60px; max-width: 100px; }
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

<!-- Modal confirmación -->
<div id="modalConfirmar" class="modal-bg">
  <div class="modal-confirm">
    <h2><i class="fa-solid fa-box"></i> Confirmar devolución</h2>
    <p>¿Confirmar la devolución de esta herramienta?<br>
      <span id="modalHerramienta" class="font-semibold text-[var(--vw-blue)]"></span>
    </p>
    <form id="formDevolver" method="POST" class="btns">
      <input type="hidden" name="devolver_id" id="modalDevolverID">
      <input type="hidden" name="comentario" id="modalComentario">
      <button type="submit">Sí, devolver</button>
      <a href="#" class="btn-cancel" onclick="cerrarModal()">Cancelar</a>
    </form>
  </div>
</div>

<!-- HEADER FIJO CON LOGO Y TÍTULO -->
<header class="header-fixed-vw fixed top-0 left-0 w-full bg-white z-50 flex items-center px-8 py-2" style="height:68px;">
  <img src="logo-volskwagen.png" alt="Logo de VW" class="h-12 w-auto mr-4 select-none" draggable="false" style="pointer-events:none;">
  <span class="text-2xl font-bold text-[var(--vw-blue)]">Devolver herramienta</span>
  <a href="index.php" class="ml-auto btn-main btn-main-outline text-sm"><i class="fa-solid fa-arrow-left"></i> Volver al inicio</a>
</header>

<!-- CONTENIDO PRINCIPAL -->
<div class="max-w-6xl mx-auto pt-28 py-10 px-4">
    <?php if ($prestamos->num_rows > 0): ?>
        <div class="overflow-x-auto">
            <table class="min-w-full tabla-prestamos">
                <thead>
                    <tr>
                        <th><i class="fa-solid fa-tools mr-1"></i> Herramienta</th>
                        <th><i class="fa-solid fa-user mr-1"></i> Prestada a</th>
                        <th><i class="fa-solid fa-store mr-1"></i> Sucursal</th>
                        <th><i class="fa-solid fa-location-dot mr-1"></i> Ubicación</th>
                        <th><i class="fa-solid fa-clock mr-1"></i> Fecha del préstamo</th>
                        <th><i class="fa-solid fa-comment mr-1"></i> Comentario (opcional)</th>
                        <th><i class="fa-solid fa-cog mr-1"></i> Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($p = $prestamos->fetch_assoc()): ?>
                        <tr id="fila-<?= $p['id'] ?>">
                            <td>
                                <strong><?= htmlspecialchars($p['herramienta']) ?></strong>
                                <?php if ($p['codigo']): ?>
                                    <span class="codigo-txt">Código: <?= htmlspecialchars($p['codigo']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($p['mecanico'] ?? $p['nombre_personalizado']) ?></td>
                            <td>
                                <span class="<?= $p['sucursal'] === 'Osvaldo Cruz' ? 'sucursal-oc' : 'sucursal-lanus' ?>">
                                    <?= htmlspecialchars($p['sucursal']) ?>
                                </span>
                            </td>
                            <td>
                                <?= htmlspecialchars($p['ubicacion']) ?>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($p['fecha_hora'])) ?></td>
                            <td>
                                <textarea class="comentario-input" rows="2" placeholder="Comentarios sobre la devolución..." id="comentario-<?= $p['id'] ?>"></textarea>
                            </td>
                            <td>
                              <button class="btn-devolver" style="min-width:120px;white-space:nowrap;" onclick="confirmarDevolucion(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['herramienta'])) ?>')">
                                <i class="fa-solid fa-box"></i> Devolver
                              </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="p-6 bg-blue-100 text-blue-800 rounded-2xl border border-blue-300 text-center">
            <i class="fa-solid fa-info-circle text-2xl mb-2"></i>
            <p class="font-semibold">No hay herramientas prestadas actualmente.</p>
        </div>
    <?php endif; ?>
</div>

<script src="fontawesome/js/all.min.js"></script>
<script>
function confirmarDevolucion(id, herramienta) {
    const comentario = document.getElementById(`comentario-${id}`).value.trim();
    
    document.getElementById('modalDevolverID').value = id;
    document.getElementById('modalComentario').value = comentario;
    document.getElementById('modalHerramienta').textContent = herramienta;
    document.getElementById('modalConfirmar').style.display = 'flex';
}

function cerrarModal() {
    document.getElementById('modalConfirmar').style.display = 'none';
}

// Cerrar modal al hacer clic fuera
document.getElementById('modalConfirmar').addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModal();
    }
});

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
