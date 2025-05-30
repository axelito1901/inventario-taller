<?php
include 'includes/conexion.php';

if (isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $conexion->query("UPDATE herramientas SET cantidad = 1 WHERE id = $id");
    echo 'ok';
}
?>
