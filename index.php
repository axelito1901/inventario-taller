<?php
session_start();
include 'includes/conexion.php';

// Mensaje por GET (PRG)
$mensaje = $_GET['msg'] ?? null;
$tipoMsg = $_GET['msgtype'] ?? null;

$prestadasQuery = $conexion->query("
    SELECT h.nombre AS herramienta, h.codigo, h.ubicacion,
           p.fecha_hora, p.sucursal, m.nombre AS mecanico, p.nombre_personalizado
    FROM prestamos p
    LEFT JOIN herramientas h ON p.herramienta_id = h.id
    LEFT JOIN mecanicos m ON p.mecanico_id = m.id
    WHERE p.devuelta = 0
    ORDER BY p.fecha_hora DESC
");

$prestadas = [];
while ($row = $prestadasQuery->fetch_assoc()) {
    $prestadas[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Préstamo de herramienta</title>
  <link rel="stylesheet" href="css/styles.css">
  <link rel="stylesheet" href="css/all.min.css">
  <link rel="stylesheet" href="css/fontawesome.min.css">
  <script src="js/jquery-3.6.0.min.js"></script>
  <style>
    :root {
      --vw-blue: #00247D;
      --vw-gray: #F4F4F4;
    }
    #boton-flotante {
      position: fixed;
      bottom: 20px;
      left: 50%;
      transform: translateX(-50%);
      z-index: 50;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
      width: 440px;
      max-width: 90vw;
    }
    @media (max-width: 640px) {
      #boton-flotante { width: 98vw; left: 1vw; transform: none; }
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
    .card-prestada {
      display: flex;
      flex-direction: column;
      gap: 0.3em;
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 1em;
      box-shadow: 0 2px 8px #0001;
      padding: 1.2em 1.5em;
      margin-bottom: 0.7em;
      font-size: 1.08em;
    }
    .card-prestada .fa-tools { color: var(--vw-blue); }
    .card-prestada .fa-user { color: #2563eb; }
    .card-prestada .fa-store { color: #f59e42; }
    .card-prestada .fa-clock { color: #64748b; }
    .card-prestada .sucursal-lanus { color: #dc2626; }
    .card-prestada .sucursal-oc { color: #2563eb; }
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
    .btn-main-blue {
      background: var(--vw-blue);
      color: #fff;
    }
    .btn-main-blue:hover {
      background: #001a5c;
    }
    .btn-main-yellow {
      background: #fde68a;
      color: #b45309;
    }
    .btn-main-yellow:hover {
      background: #fbbf24;
      color: #92400e;
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
  </style>
</head>
<body class="bg-[var(--vw-gray)] text-gray-800">

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

<!-- HEADER FIJO CON LOGO -->
<header class="header-fixed-vw fixed top-0 left-0 w-full bg-white z-50 flex items-center px-8 py-2" style="height:68px;">
  <img src="logo-volskwagen.png" alt="Logo de VW" class="h-12 w-auto mr-4 select-none" draggable="false" style="pointer-events:none;">
  <span class="text-2xl font-bold text-[var(--vw-blue)]">Préstamo de herramientas</span>
  <a href="login.php" class="ml-auto btn-main btn-main-outline text-sm"><i class="fa-solid fa-lock"></i> Iniciar sesión</a>
</header>

<!-- CONTENIDO PRINCIPAL CON PADDING TOP PARA NO TAPAR EL HEADER -->
<div class="max-w-5xl mx-auto p-6 pt-28">

  <div class="mb-4 flex gap-2 flex-wrap">
    <a href="dejar_comentario.php" class="btn-main btn-main-blue"><i class="fa-solid fa-comment-dots"></i> Dejar comentario</a>
    <a href="devolver.php" class="btn-main btn-main-yellow"><i class="fa-solid fa-box"></i> Devolver herramienta</a>
  </div>

  <form action="registrar_prestamo.php" method="post" class="space-y-4 bg-white p-6 rounded-2xl shadow border border-gray-200" onsubmit="return validarFormulario()">
    <div>
      <label class="label-main">Sucursal</label>
      <select name="sucursal" id="sucursal" class="input-main">
        <option value="Lanús">Lanús</option>
        <option value="Osvaldo Cruz">Osvaldo Cruz</option>
      </select>
    </div>

    <div>
      <label class="label-main">Nombre de quien retira</label>
      <input type="text" name="nombre_personalizado" id="nombre_personalizado" placeholder="Ej: Axel Perez" class="input-main" autocomplete="off">
      <div id="resultados_nombres" class="bg-white border rounded mt-1 hidden max-h-32 overflow-y-auto text-sm z-10"></div>
    </div>

    <div>
      <label class="label-main">Buscar herramienta</label>
      <input type="text" id="buscador_herramienta" class="input-main" placeholder="Escribí nombre o código">
      <div id="resultados" class="mt-2 space-y-2"></div>
    </div>

    <div>
      <label class="label-main">Herramientas seleccionadas</label>
      <div id="seleccionadas"></div>
    </div>

    <div id="boton-flotante" style="display: none;">
      <button type="submit" id="btnEnviar"
       class="w-full py-3 px-6 text-lg font-semibold bg-[var(--vw-blue)] text-white rounded-xl shadow-lg hover:bg-blue-900 transition-all duration-200 border-2 border-[var(--vw-blue)] focus:outline-none focus:ring-2 focus:ring-blue-400" disabled>
        <i class="fa-solid fa-paper-plane"></i> Registrar préstamo
      </button>
    </div>
  </form>

  <hr class="my-8">

  <h2 class="text-xl font-bold mb-4 text-[var(--vw-blue)]"><i class="fa-solid fa-clock-rotate-left mr-2"></i>Herramientas actualmente prestadas</h2>
  <?php if (count($prestadas) > 0): ?>
    <ul>
      <?php foreach ($prestadas as $row): ?>
        <?php
          $nombre = htmlspecialchars($row['mecanico'] ?? $row['nombre_personalizado']);
          $sucursal = htmlspecialchars($row['sucursal']);
          $color = $sucursal === 'Osvaldo Cruz' ? 'sucursal-oc' : 'sucursal-lanus';
        ?>
        <li class="card-prestada">
          <span><i class="fa-solid fa-tools"></i> <strong><?= htmlspecialchars($row['herramienta']) ?></strong> <span class="text-gray-500">(<?= htmlspecialchars($row['codigo']) ?> - <?= htmlspecialchars($row['ubicacion']) ?>)</span></span>
          <span><i class="fa-solid fa-user"></i> Prestada a <strong><?= $nombre ?></strong></span>
          <span><i class="fa-solid fa-store"></i> <span class="<?= $color ?>"><?= $sucursal ?></span></span>
          <span><i class="fa-solid fa-clock"></i> Desde el <?= date('d/m/Y H:i', strtotime($row['fecha_hora'])) ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php else: ?>
    <p class="text-gray-600">No hay herramientas prestadas actualmente.</p>
  <?php endif; ?>
</div>

<script src="fontawesome/js/all.min.js"></script>
<script>
let herramientasSeleccionadas = [];

$('#buscador_herramienta').on('input', function () {
  const q = this.value.trim();
  if (q.length < 1) return $('#resultados').hide();

  fetch('buscar_herramientas.php?q=' + encodeURIComponent(q))
    .then(res => res.json())
    .then(data => {
      const resDiv = $('#resultados');
      resDiv.empty();
      if (data.length === 0) {
        resDiv.append('<p class="text-sm text-gray-500">No se encontraron herramientas.</p>');
      } else {
        data.forEach(h => {
            const alreadySelected = herramientasSeleccionadas.some(s => s.id === h.id);
            const disabled = h.cantidad == 0 || alreadySelected ? 'opacity-50 pointer-events-none' : '';
            const prestada = h.cantidad == 0 ? 'bg-red-200' : (h.prestada ? 'bg-yellow-100 opacity-60 pointer-events-none' : 'bg-green-100');
            const selectedClass = alreadySelected ? 'border-blue-500 bg-blue-50' : '';
            const imagen = h.imagen
                ? `<img src="${h.imagen}" class="w-16 h-16 object-contain">`
                : '<div class="w-16 h-16 flex items-center justify-center bg-gray-200 text-xs text-gray-500">Sin imagen</div>';
            const mensaje = alreadySelected ? '<p class="text-sm text-blue-600 mt-1"><i class="fa-solid fa-check"></i> Ya seleccionada</p>' : '';

            const card = `
                <div class="flex items-center gap-4 p-3 border rounded ${disabled} ${prestada} ${selectedClass}" data-id="${h.id}">
                    ${imagen}
                    <div class="flex-1">
                    <strong>${h.nombre}</strong><br>
                    <small class="text-sm text-gray-600">Código: ${h.codigo}</small><br>
                    <small class="text-gray-500"><i class="fa-solid fa-location-dot"></i> ${h.ubicacion} | ${h.cantidad} en stock</small>
                    ${mensaje}
                    ${h.prestada ? '<br><span class="text-yellow-500"><i class="fa-solid fa-triangle-exclamation"></i> Ya prestada</span>' : ''}
                    ${h.cantidad == 0 ? '<br><span class="text-red-500"><i class="fa-solid fa-ban"></i> Sin stock</span>' : ''}
                    </div>
                </div>`;
        resDiv.append(card);
    });
        resDiv.show();

        $('#resultados div[data-id]').off('click').on('click', function () {
          const id = parseInt($(this).data('id'));
          const existente = herramientasSeleccionadas.find(h => h.id === id);
          if (!existente) {
            const h = data.find(x => x.id === id);
            herramientasSeleccionadas.push(h);
            actualizarVista();
            $('#buscador_herramienta').val('');
            $('#resultados').hide();
          }
        });
      }
    });
});

function actualizarVista() {
  const cont = $('#seleccionadas');
  cont.empty();
  herramientasSeleccionadas.forEach(h => {
    const imagen = h.imagen ? `<img src="${h.imagen}" class="w-16 h-16 object-contain">` : '<div class="w-16 h-16 flex items-center justify-center bg-gray-200 text-xs text-gray-500">Sin imagen</div>';
    const item = `
      <div class="flex items-center gap-4 bg-white border rounded p-3 mb-2 shadow">
        ${imagen}
        <div class="flex-1">
          <strong>${h.nombre}</strong>
          <br><small class="text-sm text-gray-600">Código: ${h.codigo}</small>
          <br><small class="text-gray-500"><i class="fa-solid fa-location-dot"></i> ${h.ubicacion}</small>
        </div>
        <input type="hidden" name="herramienta_id[]" value="${h.id}">
        <button class="text-red-500 text-lg font-bold remove" title="Quitar"><i class="fa-solid fa-xmark"></i></button>
      </div>`;
    cont.append(item);
  });

  const activo = herramientasSeleccionadas.length > 0;
  $('#btnEnviar').prop('disabled', !activo);
  $('#boton-flotante').toggle(activo); 

  $('.remove').click(function () {
    const idx = $(this).parent().index();
    herramientasSeleccionadas.splice(idx, 1);
    actualizarVista();
  });
}

function validarFormulario() {
  const nombre = $('#nombre_personalizado').val().trim();
  if (!nombre) {
    alert('Ingresá el nombre.');
    return false;
  }
  if (herramientasSeleccionadas.length === 0) {
    alert('Seleccioná al menos una herramienta.');
    return false;
  }
  return true;
}

$('#nombre_personalizado').on('input', function () {
  const query = $(this).val().trim();
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
      contenedor.append(`
      <div class="flex items-center justify-between p-3 border-b hover:bg-blue-50 cursor-pointer rounded text-blue-900 font-medium transition" data-nombre="${n}"><span><i class="fa-solid fa-user"></i> ${n}</span></div>
    `);
    });
    contenedor.find('div').on('click', function () {
      $('#nombre_personalizado').val($(this).data('nombre'));
      contenedor.hide();
    });
    contenedor.show();
  });
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