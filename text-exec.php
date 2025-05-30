<?php
echo "<pre>";
echo "exec habilitado: ";
echo function_exists('exec') ? "✅ SÍ\n" : "❌ NO\n";
echo "Deshabilitado por configuración: ";
print_r(ini_get('disable_functions'));