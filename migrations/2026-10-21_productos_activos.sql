-- Ejecutar antes de desplegar el codigo que utiliza el estado de productos.
-- Los productos existentes quedan activos; no se modifica su historial.
ALTER TABLE productos ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1;
