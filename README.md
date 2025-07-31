# Sistema de Puntos y Torneos

Sistema web para gestión de eventos, torneos y puntos canjeables.

## Estructura del Proyecto

```
├── config/           # Archivos de configuración
│   ├── config.php    # Configuración general
│   └── database.php  # Configuración de base de datos
├── controllers/      # Controladores MVC
├── database/         # Scripts de base de datos
│   ├── schema.sql    # Esquema de la base de datos
│   └── setup.php     # Script de instalación
├── includes/         # Archivos de utilidades
│   └── auth.php      # Funciones de autenticación
├── models/           # Modelos de datos
├── services/         # Servicios de lógica de negocio
├── uploads/          # Directorio para archivos subidos
├── views/            # Vistas HTML/PHP
└── index.php         # Punto de entrada principal
```

## Instalación

1. Configurar servidor web (Apache/Nginx) con PHP 7.4+
2. Configurar MySQL/MariaDB
3. Ejecutar el script de instalación de base de datos:
   ```bash
   cd database/
   php setup.php
   ```
4. Configurar permisos de escritura en el directorio `uploads/`

## Usuario Administrador por Defecto

- Email: admin@tournament.com
- Contraseña: Admin123
-- hashed: $2y$10$m4QegPHKssFmxjSG7tlBduLPxwWTLG0k9xutH8oUAEgSacYKiRpTa

## Requisitos del Sistema

- PHP 7.4 o superior
- MySQL 5.7 o superior / MariaDB 10.2+
- Extensiones PHP: PDO, PDO_MySQL, GD, JSON
- Servidor web con soporte para reescritura de URLs