<?php
// Script para ejecutar la migración de personal_comision
require_once 'db_personal.php';

try {
    // Leer el archivo de migración
    $migration = file_get_contents('migrations/add_name_fields_to_personal_comision.sql');

    // Ejecutar las sentencias SQL
    $statements = array_filter(array_map('trim', explode(';', $migration)));

    foreach ($statements as $statement) {
        if (!empty($statement) && !strpos($statement, '--') === 0) {
            if (!$conn_personal->query($statement)) {
                throw new Exception("Error en migración: " . $conn_personal->error);
            }
            echo "✓ Sentencia ejecutada\n";
        }
    }

    echo "\n✓ Migración completada exitosamente!\n";
    echo "Columnas agregadas: grado, nombres, paterno, materno\n";

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

$conn_personal->close();
?>
