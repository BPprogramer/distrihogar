CREATE TABLE IF NOT EXISTS devoluciones (
 id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
 venta_id INT NOT NULL,
 usuario_id INT NOT NULL,
 caja_id INT NULL,
 fecha DATETIME NOT NULL,
 motivo VARCHAR(500) NOT NULL,
 total DECIMAL(15,2) NOT NULL,
 deuda_reducida DECIMAL(15,2) NOT NULL,
 reembolso DECIMAL(15,2) NOT NULL,
 saldo_anterior DECIMAL(15,2) NOT NULL,
 saldo_posterior DECIMAL(15,2) NOT NULL,
 solicitud CHAR(64) NOT NULL UNIQUE,
 INDEX (venta_id), INDEX (caja_id), INDEX (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS devoluciones_detalle (
 id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
 devolucion_id INT NOT NULL,
 linea_id INT NOT NULL,
 producto_id INT NOT NULL,
 nombre VARCHAR(255) NOT NULL,
 cantidad INT NOT NULL,
 reintegrar TINYINT NOT NULL,
 valor DECIMAL(15,2) NOT NULL,
 INDEX (linea_id), INDEX (devolucion_id),
 FOREIGN KEY (devolucion_id) REFERENCES devoluciones(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
