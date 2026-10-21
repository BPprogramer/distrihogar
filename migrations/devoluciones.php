<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__ . '/../includes')->safeLoad();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $db = new mysqli($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASS'], $_ENV['DB_NAME']);
    foreach (explode(';', file_get_contents(__DIR__ . '/2026-10-21_devoluciones.sql')) as $sql) {
        if (trim($sql)) $db->query($sql);
    }
    echo "Tablas de devoluciones listas. Se conservaron ventas, pagos y cajas existentes.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "No se pudo completar la migración de devoluciones. Revise conexión y permisos.\n");
    exit(1);
}
