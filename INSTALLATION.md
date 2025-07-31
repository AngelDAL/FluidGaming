# Guía de Instalación y Configuración
## Sistema de Puntos y Torneos

### Requisitos del Sistema

#### Requisitos Mínimos
- **PHP**: 7.4 o superior (recomendado PHP 8.0+)
- **MySQL**: 5.7 o superior (recomendado MySQL 8.0+)
- **Servidor Web**: Apache 2.4+ o Nginx 1.18+
- **Memoria RAM**: 512MB mínimo (recomendado 2GB+)
- **Espacio en Disco**: 1GB mínimo (recomendado 5GB+)

#### Extensiones PHP Requeridas
```bash
php-pdo
php-pdo-mysql
php-json
php-mbstring
php-gd
php-curl
php-zip
php-xml
```

#### Extensiones PHP Opcionales (Recomendadas)
```bash
php-opcache    # Para mejor rendimiento
php-redis      # Para caché avanzado
php-memcached  # Para caché distribuido
```

### Instalación en Producción

#### 1. Preparación del Servidor

##### Para Ubuntu/Debian:
```bash
# Actualizar sistema
sudo apt update && sudo apt upgrade -y

# Instalar Apache, PHP y MySQL
sudo apt install apache2 php php-mysql mysql-server -y

# Instalar extensiones PHP
sudo apt install php-pdo php-json php-mbstring php-gd php-curl php-zip php-xml -y

# Instalar extensiones opcionales
sudo apt install php-opcache php-redis php-memcached -y

# Habilitar módulos de Apache
sudo a2enmod rewrite
sudo a2enmod ssl
sudo systemctl restart apache2
```

##### Para CentOS/RHEL:
```bash
# Instalar repositorio EPEL
sudo yum install epel-release -y

# Instalar Apache, PHP y MySQL
sudo yum install httpd php php-mysql mysql-server -y

# Instalar extensiones PHP
sudo yum install php-pdo php-json php-mbstring php-gd php-curl php-zip php-xml -y

# Iniciar servicios
sudo systemctl start httpd mysql
sudo systemctl enable httpd mysql
```

#### 2. Configuración de la Base de Datos

```bash
# Conectar a MySQL como root
mysql -u root -p

# Crear base de datos y usuario
CREATE DATABASE tournament_points CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'tournament_user'@'localhost' IDENTIFIED BY 'tu_contraseña_segura';
GRANT ALL PRIVILEGES ON tournament_points.* TO 'tournament_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

#### 3. Despliegue de la Aplicación

```bash
# Crear directorio de la aplicación
sudo mkdir -p /var/www/tournament-system
cd /var/www/tournament-system

# Clonar o copiar archivos de la aplicación
# (Asumiendo que tienes los archivos en un repositorio o archivo comprimido)

# Establecer permisos correctos
sudo chown -R www-data:www-data /var/www/tournament-system
sudo chmod -R 755 /var/www/tournament-system
sudo chmod -R 775 /var/www/tournament-system/uploads
sudo chmod -R 775 /var/www/tournament-system/cache
sudo chmod -R 775 /var/www/tournament-system/logs
```

#### 4. Configuración del Entorno

```bash
# Copiar archivo de configuración de ejemplo
cp .env.example .env

# Editar configuración
nano .env
```

Configurar las siguientes variables en `.env`:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com

DB_HOST=localhost
DB_NAME=tournament_points
DB_USER=tournament_user
DB_PASS=tu_contraseña_segura

LOG_LEVEL=error
CACHE_ENABLED=true
```

#### 5. Inicialización de la Base de Datos

```bash
# Ejecutar script de configuración de base de datos
php database/setup.php

# Aplicar índices de rendimiento
php database/apply_performance_indexes.php

# Verificar conexión
php test_connection.php
```

#### 6. Configuración del Servidor Web

##### Apache Virtual Host:
```apache
<VirtualHost *:80>
    ServerName tu-dominio.com
    DocumentRoot /var/www/tournament-system
    
    # Redireccionar a HTTPS
    Redirect permanent / https://tu-dominio.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName tu-dominio.com
    DocumentRoot /var/www/tournament-system
    
    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /path/to/your/certificate.crt
    SSLCertificateKeyFile /path/to/your/private.key
    
    # Security Headers
    Header always set X-Frame-Options DENY
    Header always set X-Content-Type-Options nosniff
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    
    # Directory Configuration
    <Directory /var/www/tournament-system>
        AllowOverride All
        Require all granted
        
        # Prevent access to sensitive files
        <Files ".env">
            Require all denied
        </Files>
        
        <Files "*.log">
            Require all denied
        </Files>
    </Directory>
    
    # Logs
    ErrorLog ${APACHE_LOG_DIR}/tournament-system_error.log
    CustomLog ${APACHE_LOG_DIR}/tournament-system_access.log combined
</VirtualHost>
```

##### Nginx Configuration:
```nginx
server {
    listen 80;
    server_name tu-dominio.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name tu-dominio.com;
    root /var/www/tournament-system;
    index index.php index.html;
    
    # SSL Configuration
    ssl_certificate /path/to/your/certificate.crt;
    ssl_certificate_key /path/to/your/private.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    
    # Security Headers
    add_header X-Frame-Options DENY;
    add_header X-Content-Type-Options nosniff;
    add_header X-XSS-Protection "1; mode=block";
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains";
    
    # PHP Configuration
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # Deny access to sensitive files
    location ~ /\.(env|git) {
        deny all;
    }
    
    location ~ \.log$ {
        deny all;
    }
    
    # Static files caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

#### 7. Configuración de PHP para Producción

Editar `/etc/php/8.0/apache2/php.ini` (o la ruta correspondiente):

```ini
# Configuración básica
memory_limit = 256M
max_execution_time = 30
max_input_time = 60
post_max_size = 10M
upload_max_filesize = 5M

# Seguridad
expose_php = Off
display_errors = Off
log_errors = On
error_log = /var/log/php/error.log

# Sesiones
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1

# OPcache (recomendado para producción)
opcache.enable = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 4000
opcache.revalidate_freq = 60
opcache.fast_shutdown = 1
```

#### 8. Configuración de Tareas Programadas (Cron)

```bash
# Editar crontab
sudo crontab -e

# Agregar las siguientes tareas
# Limpiar notificaciones antiguas cada hora
0 * * * * /usr/bin/php /var/www/tournament-system/cleanup_notifications.php

# Limpiar caché expirado cada 30 minutos
*/30 * * * * /usr/bin/php /var/www/tournament-system/cache_manager.php clean

# Verificar eventos activos cada 5 minutos
*/5 * * * * /usr/bin/php /var/www/tournament-system/check_events.php

# Backup diario a las 2:00 AM
0 2 * * * /usr/bin/php /var/www/tournament-system/backup_system.php

# Rotar logs semanalmente
0 0 * * 0 find /var/www/tournament-system/logs -name "*.log" -mtime +7 -delete
```

### Configuración de Desarrollo

#### 1. Instalación Local

```bash
# Clonar repositorio
git clone <repository-url> tournament-system
cd tournament-system

# Configurar entorno de desarrollo
cp .env.example .env
```

Editar `.env` para desarrollo:
```env
APP_ENV=development
APP_DEBUG=true
DB_HOST=localhost
DB_NAME=tournament_points
DB_USER=root
DB_PASS=
LOG_LEVEL=debug
```

#### 2. Configuración de Base de Datos Local

```bash
# Crear base de datos
mysql -u root -p -e "CREATE DATABASE tournament_points CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Ejecutar setup
php database/setup.php
php database/apply_performance_indexes.php
```

#### 3. Servidor de Desarrollo

```bash
# Usar servidor integrado de PHP
php -S localhost:8000

# O configurar virtual host local
```

### Monitoreo y Mantenimiento

#### 1. Monitoreo de Logs

```bash
# Ver logs de errores en tiempo real
tail -f logs/error-$(date +%Y-%m-%d).log

# Ver logs de actividad
tail -f logs/activity-$(date +%Y-%m-%d).log

# Buscar errores específicos
php -f search_logs.php "error_message"
```

#### 2. Monitoreo de Rendimiento

```bash
# Estadísticas de caché
php cache_manager.php stats

# Prueba de rendimiento
php cache_manager.php test

# Limpiar caché si es necesario
php cache_manager.php clear
```

#### 3. Backup y Restauración

```bash
# Backup manual
php backup_system.php

# Restaurar desde backup
mysql -u tournament_user -p tournament_points < backups/backup-YYYY-MM-DD.sql
```

### Solución de Problemas Comunes

#### 1. Problemas de Permisos
```bash
sudo chown -R www-data:www-data /var/www/tournament-system
sudo chmod -R 755 /var/www/tournament-system
sudo chmod -R 775 uploads/ cache/ logs/
```

#### 2. Problemas de Base de Datos
```bash
# Verificar conexión
php test_connection.php

# Recrear índices
php database/apply_performance_indexes.php
```

#### 3. Problemas de Rendimiento
```bash
# Limpiar caché
php cache_manager.php clear

# Verificar logs de rendimiento
tail -f logs/performance-$(date +%Y-%m-%d).log
```

### Actualizaciones

#### 1. Backup Antes de Actualizar
```bash
php backup_system.php
```

#### 2. Actualizar Código
```bash
# Hacer backup de configuración
cp .env .env.backup

# Actualizar archivos
# (proceso específico según método de despliegue)

# Restaurar configuración
cp .env.backup .env
```

#### 3. Actualizar Base de Datos
```bash
# Ejecutar migraciones si las hay
php database/migrate.php

# Aplicar nuevos índices
php database/apply_performance_indexes.php
```

### Seguridad

#### 1. Configuración de Firewall
```bash
# UFW (Ubuntu)
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

#### 2. Configuración SSL/TLS
- Usar certificados válidos (Let's Encrypt recomendado)
- Configurar HSTS
- Usar TLS 1.2 o superior

#### 3. Actualizaciones de Seguridad
```bash
# Mantener sistema actualizado
sudo apt update && sudo apt upgrade -y

# Monitorear logs de seguridad
tail -f logs/security-$(date +%Y-%m-%d).log
```

### Soporte

Para soporte técnico o reportar problemas:
1. Revisar logs de error
2. Verificar configuración
3. Consultar documentación
4. Contactar al equipo de desarrollo

---

**Nota**: Esta guía asume un entorno Linux. Para Windows Server, adaptar los comandos y rutas según corresponda.