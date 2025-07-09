<?php
session_start();
include 'includes/conexion.php';

// Mensaje por GET (PRG)
$mensaje = $_GET['msg'] ?? null;
$tipoMsg = $_GET['msgtype'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre_personalizado'] ?? '');
    $sucursal = $_POST['sucursal'] ?? 'Lanús';
    $comentarios = $_POST['comentario'] ?? [];

    if ($nombre === '') {
        $mensaje = 'Debés ingresar un nombre.';
        $tipoMsg = 'error';
    } else {
        $comentarios_validos = array_filter($comentarios, function($c) {
            return strlen(trim($c)) >= 5;
        });

        if (count($comentarios_validos) === 0) {
            $mensaje = 'El comentario debe tener al menos 5 caracteres.';
            $tipoMsg = 'error';
        } else {
            // Insertar nombre si no existe
            $stmt_nombre = $conexion->prepare("INSERT IGNORE INTO " . ($sucursal === 'Lanús' ? 'mecanicos' : 'nombres_personalizados') . " (nombre) VALUES (?)");
            $stmt_nombre->bind_param("s", $nombre);
            $stmt_nombre->execute();
            $stmt_nombre->close();
            
            $fecha = date('Y-m-d H:i:s');

            foreach ($comentarios_validos as $id => $comentario) {
                $comentario = trim($comentario);
                $stmt = $conexion->prepare("INSERT INTO comentarios (herramienta_id, nombre, sucursal, comentario, fecha) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("issss", $id, $nombre, $sucursal, $comentario, $fecha);
                $stmt->execute();
                $stmt->close();
            }

            $mensaje = 'Comentario registrado correctamente.';
            $tipoMsg = 'ok';
        }
    }

    // PRG - Redirigir con mensaje
    header("Location: dejar_comentario.php?msg=" . urlencode($mensaje) . "&msgtype=" . $tipoMsg);
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Dejar comentario</title>
  <link rel="stylesheet" href="css/styles.css">
  <link rel="stylesheet" href="css/all.min.css">
  <link rel="stylesheet" href="css/fontawesome.min.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
    .btn-main-blue {
      background: var(--vw-blue);
      color: #fff;
    }
    .btn-main-blue:hover {
      background: #001a5c;
    }
    .input-main, select.input-main {
      width: 100%;
      padding: 0.8em 1em;
      border: 2px solid #e5e7eb;
      border-radius: 0.7em;
      font-size: 1em;
      outline: none;
      transition: border 0.15s;
      background: #f9fafb;
    }
    .input-main:focus {
      border-color: var(--vw-blue);
      background: #fff;
    }
    .label-main {
      font-weight: 600;
      color: var(--vw-blue);
      margin-bottom: 0.3em;
      display: block;
    }
    .herramienta-card {
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 1em;
      padding: 1.2em;
      margin-bottom: 0.8em;
      box-shadow: 0 2px 8px #0001;
      transition: all 0.15s;
      cursor: pointer;
    }
    .herramienta-card:hover {
      box-shadow: 0 4px 16px #0002;
      border-color: var(--vw-blue);
    }
    .herramienta-card.selected {
      border-color: var(--vw-blue);
      background: #f0f9ff;
    }
    .stock-disponible { color: #16a34a; }
    .stock-agotado { color: #dc2626; }
    .comentario-textarea {
      width: 100%;
      margin-top: 0.8em;
      padding: 0.8em;
      border: 2px solid #e5e7eb;
      border-radius: 0.7em;
      font-size: 0.95em;
      outline: none;
      transition: border 0.15s;
      background: #f9fafb;
      resize: vertical;
      min-height: 80px;
    }
    .comentario-textarea:focus {
      border-color: var(--vw-blue);
      background: #fff;
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

<!-- HEADER FIJO CON LOGO Y TÍTULO -->
<header class="header-fixed-vw fixed top-0 left-0 w-full bg-white z-50 flex items-center px-8 py-2" style="height:68px;">
  <img src="logo-volskwagen.png" alt="Logo de VW" class="h-12 w-auto mr-4 select-none" draggable="false" style="pointer-events:none;">
  <span class="text-2xl font-bold text-[var(--vw-blue)]">Dejar comentario</span>
  <a href="index.php" class="ml-auto btn-main btn-main-outline text-sm"><i class="fa-solid fa-arrow-left"></i> Volver al panel</a>
</header>

<!-- CONTENIDO PRINCIPAL -->
<div class="max-w-4xl mx-auto pt-28 py-10 px-4">
  <form method="post" class="space-y-6 bg-white p-8 rounded-2xl shadow border border-gray-200" onsubmit="return validarEnvio()">

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
      <div>
        <label class="label-main">Sucursal</label>
        <select name="sucursal" id="sucursal" class="input-main" required>
          <option value="Lanús">Lanús</option>
          <option value="Osvaldo Cruz">Osvaldo Cruz</option>
        </select>
      </div>
      <div>
        <label class="label-main">Nombre</label>
        <input type="text" name="nombre_personalizado" id="nombre_personalizado" class="input-main" placeholder="Ej: Axel Perez" required>
        <div id="resultados_nombres" class="bg-white border rounded mt-1 hidden max-h-32 overflow-y-auto text-sm z-10"></div>
      </div>
    </div>

    <div>
      <label class="label-main"><i class="fa-solid fa-search mr-1"></i> Buscar herramienta</label>
      <input type="text" id="buscador" class="input-main" placeholder="Escribí nombre o código...">
      <div class="mt-4 max-h-[400px] overflow-y-auto" id="resultados"></div>
    </div>

    <div class="pt-4">
      <button type="submit" class="w-full btn-main btn-main-blue text-lg">
        <i class="fa-solid fa-paper-plane"></i> Enviar comentario
      </button>
    </div>
  </form>
</div>

<script src="fontawesome/js/all.min.js"></script>
<script>
let herramientaSeleccionada = null;

$('#buscador').on('input', function () {
  const q = this.value.trim();
  const resultados = $('#resultados');
  resultados.html('');
  herramientaSeleccionada = null;

  if (q.length < 1) return;

  $.getJSON('buscar_herramientas.php?q=' + encodeURIComponent(q), function (data) {
    if (data.length === 0) {
      resultados.html('<p class="text-gray-500 text-sm">No se encontraron herramientas.</p>');
      return;
    }

    data.forEach(h => {
      const stock = h.cantidad == 0 
        ? '<span class="stock-agotado text-sm"><i class="fa-solid fa-ban"></i> Sin stock</span>' 
        : '<span class="stock-disponible text-sm"><i class="fa-solid fa-check-circle"></i> En stock</span>';
      const img = h.imagen 
        ? `<img src="${h.imagen}" class="w-20 h-20 object-contain rounded">` 
        : '<div class="w-20 h-20 flex items-center justify-center bg-gray-200 text-sm text-gray-500 rounded"><i class="fa-solid fa-image text-2xl"></i></div>';

      const div = document.createElement('div');
      div.className = 'herramienta-card';
      div.innerHTML = `
        <div class="flex items-start gap-4">
          ${img}
          <div class="flex-1">
            <h3 class="font-semibold text-lg text-gray-800">${h.nombre}</h3>
            <p class="text-sm text-gray-600"><i class="fa-solid fa-barcode"></i> Código: ${h.codigo}</p>
            <p class="text-sm text-gray-500"><i class="fa-solid fa-location-dot"></i> ${h.ubicacion} | ${h.cantidad} unidades</p>
            ${stock}
          </div>
        </div>
      `;
      div.onclick = () => seleccionarHerramienta(h, div);
      resultados.append(div);
    });
  });
});

function seleccionarHerramienta(h, elemento) {
  herramientaSeleccionada = h;
  
  // Remover selección anterior
  $('.herramienta-card').removeClass('selected');
  $('.comentario-textarea').remove();
  
  // Marcar como seleccionada
  $(elemento).addClass('selected');

  const stock = h.cantidad == 0 
    ? '<span class="stock-agotado text-sm"><i class="fa-solid fa-ban"></i> Sin stock</span>' 
    : '<span class="stock-disponible text-sm"><i class="fa-solid fa-check-circle"></i> En stock</span>';
  const img = h.imagen 
    ? `<img src="${h.imagen}" class="w-20 h-20 object-contain rounded">` 
    : '<div class="w-20 h-20 flex items-center justify-center bg-gray-200 text-sm text-gray-500 rounded"><i class="fa-solid fa-image text-2xl"></i></div>';

  const textarea = `<textarea name="comentario[${h.id}]" class="comentario-textarea" rows="3" minlength="5" required placeholder="Escribí tu comentario sobre esta herramienta (mínimo 5 caracteres)..."></textarea>`;
  
  $(elemento).append(textarea);
  $(elemento).find('textarea').focus();
}

$('#nombre_personalizado').on('input', function () {
  const query = this.value.trim();
  const sucursal = $('#sucursal').val();
  const contenedor = $('#resultados_nombres');

  if (query.length < 2) {
    contenedor.hide();
    return;
  }

  $.getJSON('buscar_nombres.php?term=' + encodeURIComponent(query) + '&sucursal=' + encodeURIComponent(sucursal), function (data) {
    if (data.length === 0) {
      contenedor.html('<p class="p-2 text-gray-500">Sin coincidencias.</p>').show();
      return;
    }

    contenedor.html('');
    data.forEach(n => {
      contenedor.append(`<div class='p-2 hover:bg-gray-100 cursor-pointer rounded' data-nombre='${n}'><i class="fa-solid fa-user"></i> ${n}</div>`);
    });

    contenedor.find('div').on('click', function () {
      $('#nombre_personalizado').val($(this).data('nombre'));
      contenedor.hide();
    });

    contenedor.show();
  });
});

function validarEnvio() {
  const nombre = document.getElementById('nombre_personalizado').value.trim();
  if (!nombre) {
    alert('Ingresá el nombre.');
    return false;
  }
  const comentario = document.querySelector('textarea');
  if (!comentario || comentario.value.trim().length < 5) {
    alert('El comentario debe tener al menos 5 caracteres.');
    return false;
  }
  return true;
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