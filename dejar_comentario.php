<?php
session_start();
include 'includes/conexion.php';

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre_personalizado'] ?? '');
    $sucursal = $_POST['sucursal'] ?? 'Lanús';
    $comentarios = $_POST['comentario'] ?? [];

    if ($nombre === '') {
        $mensaje = '❌ Debés ingresar un nombre.';
    } else {
        $comentarios_validos = array_filter($comentarios, function($c) {
            return strlen(trim($c)) >= 5;
        });

        if (count($comentarios_validos) === 0) {
            $mensaje = '❌ El comentario debe tener al menos 5 caracteres.';
        } else {
            $conexion->query("INSERT IGNORE INTO " . ($sucursal === 'Lanús' ? 'mecanicos' : 'nombres_personalizados') . " (nombre) VALUES ('$nombre')");
            $fecha = date('Y-m-d H:i:s');

            foreach ($comentarios_validos as $id => $comentario) {
                $comentario = trim($comentario);
                $stmt = $conexion->prepare("INSERT INTO comentarios (herramienta_id, nombre, sucursal, comentario, fecha) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("issss", $id, $nombre, $sucursal, $comentario, $fecha);
                $stmt->execute();
            }

            $mensaje = '✅ Comentario registrado correctamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
    <div class="fixed top-4 left-4 z-50">
      <img src="logo-volskwagen.png" alt="Logo de la empresa" class="h-16 w-auto">
    </div>
  <title>Dejar comentario</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">
<div class="max-w-4xl mx-auto py-10 px-4">
  <h1 class="text-3xl font-bold text-blue-900 mb-6">📝 Dejar comentario sobre una herramienta</h1>

  <?php if ($mensaje): ?>
    <div class="mb-6 p-4 <?= str_starts_with($mensaje, '✅') ? 'bg-green-100 text-green-800 border-green-300' : 'bg-red-100 text-red-700 border-red-300' ?> border rounded shadow">
      <?= htmlspecialchars($mensaje) ?>
    </div>
  <?php endif; ?>

  <form method="post" class="space-y-6 bg-white p-6 rounded shadow" onsubmit="return validarEnvio()">

    <div>
      <a href="index.php" class="inline-block bg-white border border-blue-700 text-blue-700 px-4 py-2 rounded hover:bg-blue-700 hover:text-white transition duration-300 shadow-sm">
        ⬅ Volver al panel
      </a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label class="block font-medium mb-1">Sucursal</label>
        <select name="sucursal" id="sucursal" class="w-full border rounded px-4 py-2" required>
          <option value="Lanús">Lanús</option>
          <option value="Osvaldo Cruz">Osvaldo Cruz</option>
        </select>
      </div>
      <div>
        <label class="block font-medium mb-1">Nombre</label>
        <input type="text" name="nombre_personalizado" id="nombre_personalizado" class="w-full border rounded px-4 py-2" placeholder="Ej: Axel Perez" required>
        <div id="resultados_nombres" class="bg-white border rounded mt-1 hidden max-h-32 overflow-y-auto text-sm z-10"></div>
      </div>
    </div>

    <div>
      <label class="block font-medium mb-2">Buscar herramienta</label>
      <input type="text" id="buscador" class="w-full px-4 py-2 border rounded" placeholder="Escribí nombre o código...">
      <div class="mt-4 border rounded-lg shadow bg-white p-4 max-h-[400px] overflow-y-auto" id="resultados"></div>
    </div>

    <div>
      <button type="submit" class="w-full bg-blue-700 text-white font-semibold px-4 py-2 rounded hover:bg-blue-800 transition">
        Enviar comentario
      </button>
    </div>
  </form>
</div>

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
      const stock = h.cantidad == 0 ? '<span class="text-red-600 text-sm">🔴 Sin stock</span>' : '<span class="text-green-600 text-sm">🟢 En stock</span>';
      const img = h.imagen ? `<img src="${h.imagen}" class="w-20 h-20 object-contain rounded">` : '<div class="w-20 h-20 flex items-center justify-center bg-gray-200 text-sm text-gray-500 rounded">Sin imagen</div>';

      const div = document.createElement('div');
      div.className = 'bg-white border rounded-lg p-4 flex items-start gap-4 shadow hover:shadow-md transition mb-4 cursor-pointer hover:bg-blue-50';
      div.innerHTML = `
        ${img}
        <div class="flex-1">
          <h3 class="font-semibold text-lg text-gray-800">${h.nombre}</h3>
          <p class="text-sm text-gray-600">Código: ${h.codigo}</p>
          <p class="text-sm text-gray-500">📍 ${h.ubicacion} | ${h.cantidad} unidades</p>
          ${stock}
        </div>
      `;
      div.onclick = () => seleccionarHerramienta(h);
      resultados.append(div);
    });
  });
});

function seleccionarHerramienta(h) {
  herramientaSeleccionada = h;
  const resultados = $('#resultados');
  resultados.html('');

  const stock = h.cantidad == 0 ? '<span class="text-red-600 text-sm">🔴 Sin stock</span>' : '<span class="text-green-600 text-sm">🟢 En stock</span>';
  const img = h.imagen ? `<img src="${h.imagen}" class="w-20 h-20 object-contain rounded">` : '<div class="w-20 h-20 flex items-center justify-center bg-gray-200 text-sm text-gray-500 rounded">Sin imagen</div>';

  const html = `
    <div class="bg-white border rounded-lg p-4 flex items-start gap-4 shadow mb-4">
      ${img}
      <div class="flex-1">
        <h3 class="font-semibold text-lg text-gray-800">${h.nombre}</h3>
        <p class="text-sm text-gray-600">Código: ${h.codigo}</p>
        <p class="text-sm text-gray-500">📍 ${h.ubicacion} | ${h.cantidad} unidades</p>
        ${stock}
        <textarea name="comentario[${h.id}]" class="w-full mt-3 px-3 py-2 border rounded text-sm" rows="3" minlength="5" required placeholder="Escribí al menos 5 caracteres..."></textarea>
      </div>
    </div>`;
  resultados.html(html);
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
      contenedor.append(`<div class='p-2 hover:bg-gray-100 cursor-pointer' data-nombre='${n}'>${n}</div>`);
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
    alert('⚠️ Ingresá el nombre.');
    return false;
  }
  const comentario = document.querySelector('textarea');
  if (!comentario || comentario.value.trim().length < 5) {
    alert('⚠️ El comentario debe tener al menos 5 caracteres.');
    return false;
  }
  return true;
}
</script>
</body>
</html>
