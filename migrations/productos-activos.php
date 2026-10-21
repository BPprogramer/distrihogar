<?php
// Ejecutar por consola antes de publicar el nuevo codigo:
// php migrations/productos-activos.php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/../vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__ . '/../includes')->safeLoad();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $db = new mysqli($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASS'], $_ENV['DB_NAME']);
    $columna = $db->query("SHOW COLUMNS FROM productos LIKE 'activo'");
    if ($columna->num_rows) {
        echo "La columna activo ya existe. Se conservan los estados actuales.\n";
        exit(0);
    }
    $db->query(file_get_contents(__DIR__ . '/2026-10-21_productos_activos.sql'));
    $inactivos = $db->query('SELECT COUNT(*) AS total FROM productos WHERE activo <> 1 OR activo IS NULL')->fetch_assoc();
    if ((int) $inactivos['total'] !== 0) {
        throw new RuntimeException('Verificacion de estados fallida');
    }
    echo "Migración completada: todos los productos existentes están activos.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "No se pudo completar la migración. Revise la conexión y los permisos de la base de datos antes de publicar el código.\n");
    exit(1);
}
