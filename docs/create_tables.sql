# PROYECTOS
CREATE TABLE proyectos (
  proyecto_id int(11) NOT NULL AUTO_INCREMENT,
  usuario_id int(11) NOT NULL DEFAULT 0,
  nombre varchar(45) DEFAULT NULL,
  descripcion varchar(2000) DEFAULT NULL,
  costo_inicial DECIMAL(8,2) DEFAULT 0.0,
  total_donado DECIMAL(8,2) DEFAULT 0.0,
  fecha_inicio timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_fin timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status int(11) DEFAULT NULL COMMENT '0 - Baja, 1 - Activo, 2 - XXX',
  PRIMARY KEY (proyecto_id)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

# CAMBIOS EN PROYECTOS - Tabla intermedia para manejar los cambios
CREATE TABLE proyectos_cambios (
  proyecto_cambio_id int(11) NOT NULL AUTO_INCREMENT COMMENT '',
  proyecto_id int(11) NOT NULL DEFAULT 0,
  nombre varchar(45) DEFAULT NULL,
  descripcion varchar(2000) DEFAULT NULL,
  costo_inicial DECIMAL(8,2) DEFAULT 0.0,
  fecha_fin timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status int(11) DEFAULT NULL COMMENT '0 - Baja, 1 - Activo, 2 - XXX',
  justificacion varchar(2000) DEFAULT NULL,
  respuesta varchar(2000) DEFAULT NULL,
  fotos varchar(2000) DEFAULT NULL COMMENT 'Es un objeto json que tiene la estructura de las imágenes solo para correcciones',
  PRIMARY KEY (proyecto_cambio_id)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

# COMENTARIOS DE PROYECTOS -
CREATE TABLE proyectos_comentarios (
  proyecto_comentario_id int(11) NOT NULL AUTO_INCREMENT,
  proyecto_id int(11) NOT NULL,
  titulo varchar(150) NOT NULL,
  detalles varchar(2000) NOT NULL,
  parent_id int(11) NOT NULL,
  creador_id int(11) NOT NULL,
  votos_up int(11) NOT NULL,
  votos_down int(11) NOT NULL,
  fecha timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (proyecto_comentario_id)
) ENGINE=InnoDB AUTO_INCREMENT=103 DEFAULT CHARSET=utf8;

# FOTOS DE PROYECTOS -
CREATE TABLE proyectos_fotos (
  proyecto_foto_id int(11) NOT NULL AUTO_INCREMENT,
  proyecto_id int(11) DEFAULT NULL,
  main int(11) DEFAULT 0 COMMENT '0 - No Main 1- Foto principal',
  nombre varchar(45) DEFAULT NULL,
  carpeta varchar(45) DEFAULT NULL,
  PRIMARY KEY (proyecto_foto_id),
  KEY FOTOS_PROY_IDX (proyecto_id)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

# DONACIONES - Tabla de donaciones
CREATE TABLE donaciones (
  donacion_id int(11) NOT NULL AUTO_INCREMENT,
  proyecto_id int(11) DEFAULT NULL,
  donador_id int(11) NOT NULL DEFAULT 0,
  fecha timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  valor DECIMAL(8,2) DEFAULT 0.0,
  status int(11) NOT NULL COMMENT '0 - Iniciado, 1 - Pedido, 2 - Confirmado, 3 - Entregado, 4 - Cancelado',
  comprobante varchar(45) DEFAULT NULL COMMENT 'IMAGEN DEL COMPROBANTE',
  PRIMARY KEY (donacion_id)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
