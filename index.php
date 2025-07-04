<?php
session_start();
include 'includes/conexion.php';

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
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <style>
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
      background: #f6f6f6;
    }
    header.header-fixed-vw {
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      border-bottom: 1px solid #e5e7eb;
    }
  </style>
</head>
<body class="bg-gray-100 text-gray-800">

<!-- HEADER FIJO CON LOGO -->
<header class="header-fixed-vw fixed top-0 left-0 w-full bg-white z-50 flex items-center px-8 py-2" style="height:68px;">
  <img src="logo-volskwagen.png" alt="Logo de VW" class="h-12 w-auto mr-4 select-none" draggable="false" style="pointer-events:none;">
  <span class="text-2xl font-bold text-blue-900">Préstamo de herramientas</span>
  <a href="login.php" class="ml-auto text-sm bg-blue-100 text-blue-800 px-4 py-2 rounded hover:bg-blue-200 transition">🔐 Iniciar sesión</a>
</header>

<!-- CONTENIDO PRINCIPAL CON PADDING TOP PARA NO TAPAR EL HEADER -->
<div class="max-w-5xl mx-auto p-6 pt-28">

  <div class="mb-4 flex gap-2">
    <a href="dejar_comentario.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">💬 Dejar comentario</a>
    <a href="devolver.php" class="bg-yellow-400 hover:bg-yellow-500 text-black px-4 py-2 rounded">📦 Devolver herramienta</a>
  </div>

  <form action="registrar_prestamo.php" method="post" class="space-y-4 bg-white p-6 rounded shadow" onsubmit="return validarFormulario()">
    <div>
      <label class="block font-medium">Sucursal</label>
      <select name="sucursal" id="sucursal" class="w-full border rounded px-3 py-2">
        <option value="Lanús">Lanús</option>
        <option value="Osvaldo Cruz">Osvaldo Cruz</option>
      </select>
    </div>

    <div>
      <label class="block font-medium">Nombre de quien retira</label>
      <input type="text" name="nombre_personalizado" id="nombre_personalizado" placeholder="Ej: Axel Perez" class="w-full border rounded px-3 py-2" autocomplete="off">
      <div id="resultados_nombres" class="bg-white border rounded mt-1 hidden max-h-32 overflow-y-auto text-sm z-10"></div>
    </div>

    <div>
      <label class="block font-medium">Buscar herramienta</label>
      <input type="text" id="buscador_herramienta" class="w-full border rounded px-3 py-2" placeholder="Escribí nombre o código">
      <div id="resultados" class="mt-2 space-y-2"></div>
    </div>

    <div>
      <label class="block font-medium">Herramientas seleccionadas</label>
      <div id="seleccionadas"></div>
    </div>

    <div id="boton-flotante" style="display: none;">
      <button type="submit" id="btnEnviar"
       class="w-full py-3 px-6 text-lg font-semibold bg-blue-700 text-white rounded-xl shadow-lg hover:bg-blue-800 transition-all duration-200 border-2 border-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-400" disabled>
        Registrar préstamo
      </button>
    </div>
  </form>

  <hr class="my-8">

  <h2 class="text-xl font-bold mb-4">Herramientas actualmente prestadas</h2>
  <?php if (count($prestadas) > 0): ?>
    <ul class="space-y-2">
      <?php foreach ($prestadas as $row): ?>
        <?php
          $nombre = htmlspecialchars($row['mecanico'] ?? $row['nombre_personalizado']);
          $sucursal = htmlspecialchars($row['sucursal']);
          $icono = $sucursal === 'Osvaldo Cruz' ? '🔵' : '🔴';
          $color = $sucursal === 'Osvaldo Cruz' ? 'text-blue-600' : 'text-red-600';
        ?>
        <li class="p-3 bg-white border rounded shadow-sm">
          <strong><?= htmlspecialchars($row['herramienta']) ?></strong>
          (<?= htmlspecialchars($row['codigo']) ?> - <?= htmlspecialchars($row['ubicacion']) ?>)<br>
          <?= $icono ?> <span class="<?= $color ?>">Prestada a <strong><?= $nombre ?></strong> (<?= $sucursal ?>)</span>
          desde el <?= date('d/m/Y H:i', strtotime($row['fecha_hora'])) ?>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php else: ?>
    <p class="text-gray-600">No hay herramientas prestadas actualmente.</p>
  <?php endif; ?>
</div>

<!-- SCRIPTS -->
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
                : '<div class="w-16 h-16 flex items-center justify-center bg-gray-200 text-xs">Sin imagen</div>';
            const mensaje = alreadySelected ? '<p class="text-sm text-blue-600 mt-1">✔️ Ya seleccionada</p>' : '';

            const card = `
                <div class="flex items-center gap-4 p-3 border rounded ${disabled} ${prestada} ${selectedClass}" data-id="${h.id}">
                    ${imagen}
                    <div class="flex-1">
                    <strong>${h.nombre}</strong><br>
                    <small class="text-sm text-gray-600">Código: ${h.codigo}</small><br>
                    <small class="text-gray-500">📍 ${h.ubicacion} | ${h.cantidad} en stock</small>
                    ${mensaje}
                    ${h.prestada ? '<br><span class="text-yellow-500">⚠️ Ya Prestada</span>' : ''}
                    ${h.cantidad == 0 ? '<br><span class="text-red-500">❌ Sin stock</span>' : ''}
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
    const imagen = h.imagen ? `<img src="${h.imagen}" class="w-16 h-16 object-contain">` : '<div class="w-16 h-16 flex items-center justify-center bg-gray-200 text-xs">Sin imagen</div>';
    const item = `
      <div class="flex items-center gap-4 bg-white border rounded p-3 mb-2 shadow">
        ${imagen}
        <div class="flex-1">
          <strong>${h.nombre}</strong>
          <br><small class="text-sm text-gray-600">Código: ${h.codigo}</small>
          <br><small class="text-gray-500">📍 ${h.ubicacion}</small>
        </div>
        <input type="hidden" name="herramienta_id[]" value="${h.id}">
        <button class="text-red-500 text-lg font-bold remove">✕</button>
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
    alert('⚠️ Ingresá el nombre.');
    return false;
  }
  if (herramientasSeleccionadas.length === 0) {
    alert('⚠️ Seleccioná al menos una herramienta.');
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
      <div class="flex items-center justify-between p-3 border-b hover:bg-blue-50 cursor-pointer rounded text-blue-900 font-medium transition" data-nombre="${n}"><span>👤 ${n}</span></div>
    `);
    });
    contenedor.find('div').on('click', function () {
      $('#nombre_personalizado').val($(this).data('nombre'));
      contenedor.hide();
    });
    contenedor.show();
  });
});
</script>
</body>
</html>
