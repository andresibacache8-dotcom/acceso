<?php
/**
 * Script para ejecutar migración 006 - Crear tabla guardia_servicio
 * Ejecutar desde línea de comandos: php run_migration_006.php
 */

require_once __DIR__ . '/../api/database/db_acceso.php';

echo "=== MIGRACIÓN 006: CREAR TABLA GUARDIA_SERVICIO ===\n\n";

try {
    // Leer el archivo SQL
    $sqlFile = __DIR__ . '/migrations/006_create_guardia_servicio.sql';

    if (!file_exists($sqlFile)) {
        throw new Exception("Archivo SQL no encontrado: $sqlFile");
    }

    $sql = file_get_contents($sqlFile);

    // Separar por delimitadores y ejecutar cada statement
    // Primero eliminar comentarios de línea completa
    $lines = explode("\n", $sql);
    $cleanedLines = [];
    $inComment = false;

    foreach ($lines as $line) {
        $trimmed = trim($line);

        // Detectar inicio/fin de bloque de comentario
        if (strpos($trimmed, '/*') === 0) {
            $inComment = true;
        }

        if (!$inComment && $trimmed !== '' && strpos($trimmed, '--') !== 0) {
            $cleanedLines[] = $line;
        }

        if (strpos($trimmed, '*/') !== false) {
            $inComment = false;
        }
    }

    $sql = implode("\n", $cleanedLines);

    // Ejecutar statements separados por ;
    $statements = explode(';', $sql);
    $executed = 0;
    $errors = 0;

    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement) || $statement === 'DELIMITER $$' || $statement === 'DELIMITER ;') {
            continue;
        }

        // Si contiene DELIMITER, es un trigger - tratarlo especial
        if (strpos($statement, 'CREATE TRIGGER') !== false) {
            // Los triggers necesitan ejecutarse completos con su DELIMITER
            // Por ahora saltamos triggers en este script simple
            echo "⚠️ Trigger detectado - ejecutar manualmente si es necesario\n";
            continue;
        }

        try {
            if ($conn_acceso->query($statement)) {
                $executed++;
                echo "✓ Statement ejecutado exitosamente\n";
            } else {
                $errors++;
                echo "✗ Error en statement: " . $conn_acceso->error . "\n";
                echo "SQL: " . substr($statement, 0, 100) . "...\n";
            }
        } catch (Exception $e) {
            $errors++;
            echo "✗ Excepción: " . $e->getMessage() . "\n";
        }
    }

    echo "\n=== RESUMEN ===\n";
    echo "Statements ejecutados: $executed\n";
    echo "Errores: $errors\n";

    // Verificar que la tabla existe
    echo "\n=== VERIFICACIÓN ===\n";
    $result = $conn_acceso->query("SHOW TABLES FROM acceso_pro_db LIKE 'guardia_servicio'");

    if ($result && $result->num_rows > 0) {
        echo "✓ Tabla 'guardia_servicio' creada exitosamente\n";

        // Mostrar estructura
        $result = $conn_acceso->query("DESCRIBE acceso_pro_db.guardia_servicio");
        echo "\nESTRUCTURA DE LA TABLA:\n";
        echo str_repeat("-", 80) . "\n";
        printf("%-20s %-30s %-10s %-10s\n", "Campo", "Tipo", "Null", "Key");
        echo str_repeat("-", 80) . "\n";

        while ($row = $result->fetch_assoc()) {
            printf("%-20s %-30s %-10s %-10s\n",
                $row['Field'],
                $row['Type'],
                $row['Null'],
                $row['Key']
            );
        }

        // Mostrar índices
        $result = $conn_acceso->query("SHOW INDEX FROM acceso_pro_db.guardia_servicio");
        echo "\nÍNDICES CREADOS:\n";
        echo str_repeat("-", 80) . "\n";

        while ($row = $result->fetch_assoc()) {
            echo "- " . $row['Key_name'] . " en columna " . $row['Column_name'] . "\n";
        }
    } else {
        echo "✗ ERROR: La tabla 'guardia_servicio' NO fue creada\n";
    }

    echo "\n✓ Migración completada\n";

} catch (Exception $e) {
    echo "✗ ERROR CRÍTICO: " . $e->getMessage() . "\n";
    exit(1);
}
