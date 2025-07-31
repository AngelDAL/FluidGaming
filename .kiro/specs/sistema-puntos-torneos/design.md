# Documento de Diseño - Sistema de Puntos y Torneos

## Visión General

El sistema de gestión de puntos y torneos es una aplicación web que permite la gestión completa de eventos presenciales con torneos, asignación de puntos y reclamo de productos. El sistema está diseñado para funcionar en tiempo real durante eventos presenciales, con roles diferenciados para usuarios, asistentes, encargados de stands y administradores.

### Características Principales
- Gestión de eventos con fechas de inicio y fin
- Creación y administración de torneos con imágenes y especificaciones
- Sistema de puntos en tiempo real
- Leaderboard dinámico
- Reclamo presencial de productos en stands
- Panel administrativo con reportes y estadísticas

## Arquitectura

### Arquitectura General
El sistema seguirá una arquitectura de 3 capas con separación clara de responsabilidades:

```
┌─────────────────────────────────────┐
│      Frontend (HTML/JavaScript)    │
│  - Dashboard Usuario                │
│  - Panel Admin                      │
│  - Interfaz Asistentes/Encargados   │
└─────────────────────────────────────┘
                    │
┌─────────────────────────────────────┐
│         Backend API (PHP)          │
│  - Autenticación y Autorización     │
│  - Lógica de Negocio               │
│  - APIs REST                       │
└─────────────────────────────────────┘
                    │
┌─────────────────────────────────────┐
│        Base de Datos (MySQL)       │
│  - Datos de usuarios y eventos      │
│  - Transacciones de puntos          │
│  - Logs de actividad               │
└─────────────────────────────────────┘
```

### Patrones de Diseño
- **Repository Pattern**: Para abstracción de acceso a datos
- **Service Layer**: Para lógica de negocio
- **Observer Pattern**: Para notificaciones en tiempo real
- **Strategy Pattern**: Para diferentes tipos de torneos y reglas de puntuación

## Componentes e Interfaces

### 1. Gestión de Usuarios
**Componentes:**
- `UserService`: Registro, autenticación y gestión de perfiles
- `AuthMiddleware`: Validación de tokens y permisos
- `ProfileController`: Gestión de perfiles y imágenes

**Estructuras de Datos:**
```php
class User {
    public $id;
    public $nickname;
    public $email;
    public $profileImage;
    public $role; // 'user', 'assistant', 'stand_manager', 'admin'
    public $totalPoints;
    public $createdAt;
}

class UserProfile {
    public $userId;
    public $nickname;
    public $profileImage;
    public $bio;
}
```

### 2. Gestión de Eventos
**Componentes:**
- `EventService`: Creación y gestión de eventos
- `EventController`: API endpoints para eventos
- `EventValidator`: Validación de fechas y reglas de negocio

**Estructuras de Datos:**
```php
class Event {
    public $id;
    public $name;
    public $description;
    public $startDate;
    public $endDate;
    public $isActive;
    public $createdBy;
    public $tournaments;
}
```

### 3. Sistema de Torneos
**Componentes:**
- `TournamentService`: Gestión de torneos y participación
- `TournamentController`: API para torneos
- `GameImageUploader`: Manejo de imágenes de juegos

**Estructuras de Datos:**
```php
class Tournament {
    public $id;
    public $eventId;
    public $name;
    public $gameImage;
    public $scheduledTime;
    public $pointsReward;
    public $specifications; // JSON string
    public $participants; // JSON array
    public $status; // 'scheduled', 'active', 'completed'
}

class TournamentSpecs {
    public $gameMode; // "1vs1", "team", "battle_royale"
    public $rules; // array
    public $maxParticipants;
    public $duration;
}
```

### 4. Sistema de Puntos
**Componentes:**
- `PointsService`: Asignación y gestión de puntos
- `PointsController`: API para operaciones de puntos
- `PointsValidator`: Validación de reglas de negocio

**Estructuras de Datos:**
```php
class PointTransaction {
    public $id;
    public $userId;
    public $points;
    public $type; // 'earned', 'claimed'
    public $source; // 'tournament', 'challenge', 'bonus'
    public $tournamentId;
    public $assignedBy;
    public $timestamp;
    public $metadata; // JSON string
}
```

### 5. Leaderboard
**Componentes:**
- `LeaderboardService`: Cálculo y actualización de rankings
- `LeaderboardController`: API para leaderboard
- `RealTimeUpdater`: WebSocket para actualizaciones en tiempo real

**Estructuras de Datos:**
```php
class LeaderboardEntry {
    public $userId;
    public $nickname;
    public $profileImage;
    public $totalPoints;
    public $rank;
    public $lastUpdated;
}
```

### 6. Sistema de Reclamos
**Componentes:**
- `ClaimService`: Gestión de reclamos de productos
- `StandService`: Gestión de stands y productos
- `ClaimController`: API para reclamos

**Estructuras de Datos:**
```php
class Product {
    public $id;
    public $name;
    public $description;
    public $pointsRequired;
    public $standId;
    public $imageUrl;
    public $isActive;
}

class Stand {
    public $id;
    public $name;
    public $managerId;
    public $products; // array
    public $eventId;
}

class Claim {
    public $id;
    public $userId;
    public $productId;
    public $standId;
    public $processedBy;
    public $timestamp;
    public $status; // 'pending', 'completed'
}
```

## Modelos de Datos

### Esquema de Base de Datos

```sql
-- Usuarios
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nickname VARCHAR(50) UNIQUE NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  profile_image TEXT,
  role ENUM('user', 'assistant', 'stand_manager', 'admin') DEFAULT 'user',
  total_points INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Eventos
CREATE TABLE events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  start_date DATETIME NOT NULL,
  end_date DATETIME NOT NULL,
  is_active BOOLEAN DEFAULT true,
  created_by INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Torneos
CREATE TABLE tournaments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  event_id INT,
  name VARCHAR(255) NOT NULL,
  game_image TEXT,
  scheduled_time DATETIME NOT NULL,
  points_reward INT NOT NULL,
  specifications JSON,
  status ENUM('scheduled', 'active', 'completed') DEFAULT 'scheduled',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (event_id) REFERENCES events(id)
);

-- Transacciones de Puntos
CREATE TABLE point_transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  points INT NOT NULL,
  type ENUM('earned', 'claimed') NOT NULL,
  source ENUM('tournament', 'challenge', 'bonus') NOT NULL,
  tournament_id INT,
  assigned_by INT,
  timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  metadata JSON,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (tournament_id) REFERENCES tournaments(id),
  FOREIGN KEY (assigned_by) REFERENCES users(id)
);

-- Stands
CREATE TABLE stands (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  manager_id INT,
  event_id INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (manager_id) REFERENCES users(id),
  FOREIGN KEY (event_id) REFERENCES events(id)
);

-- Productos
CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  points_required INT NOT NULL,
  stand_id INT,
  image_url TEXT,
  is_active BOOLEAN DEFAULT true,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (stand_id) REFERENCES stands(id)
);

-- Reclamos
CREATE TABLE claims (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  product_id INT,
  stand_id INT,
  processed_by INT,
  timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  status ENUM('pending', 'completed') DEFAULT 'pending',
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (product_id) REFERENCES products(id),
  FOREIGN KEY (stand_id) REFERENCES stands(id),
  FOREIGN KEY (processed_by) REFERENCES users(id),
  UNIQUE KEY unique_user_product (user_id, product_id)
);
```

## Manejo de Errores

### Estrategia de Manejo de Errores
- **Validación de Entrada**: Validación exhaustiva en frontend y backend
- **Errores de Negocio**: Códigos de error específicos para reglas de negocio
- **Errores de Sistema**: Logging detallado y respuestas genéricas al usuario
- **Rollback de Transacciones**: Para operaciones críticas como asignación de puntos

### Códigos de Error Personalizados
```php
class ErrorCodes {
    const EVENT_NOT_ACTIVE = 'EVENT_001';
    const INSUFFICIENT_POINTS = 'POINTS_001';
    const PRODUCT_ALREADY_CLAIMED = 'CLAIM_001';
    const TOURNAMENT_FULL = 'TOURNAMENT_001';
    const UNAUTHORIZED_OPERATION = 'AUTH_001';
}
```

## Estrategia de Testing

### Niveles de Testing
1. **Unit Tests**: Servicios individuales y funciones utilitarias
2. **Integration Tests**: APIs y interacciones entre componentes
3. **End-to-End Tests**: Flujos completos de usuario
4. **Performance Tests**: Carga del leaderboard y operaciones en tiempo real

### Herramientas de Testing
- **PHPUnit**: Para unit tests y integration tests
- **Selenium**: Para end-to-end testing
- **Apache Bench (ab)**: Para performance testing
- **Postman/Newman**: Para testing de APIs

### Casos de Prueba Críticos
- Asignación simultánea de puntos por múltiples asistentes
- Actualización en tiempo real del leaderboard
- Prevención de reclamos duplicados
- Validación de fechas de eventos activos
- Manejo de concurrencia en reclamos de productos

## Consideraciones de Seguridad

### Autenticación y Autorización
- **JWT Tokens**: Para autenticación stateless
- **Role-Based Access Control (RBAC)**: Permisos basados en roles
- **Rate Limiting**: Para prevenir abuso de APIs
- **Input Sanitization**: Validación y sanitización de todas las entradas

### Protección de Datos
- **Encriptación de Contraseñas**: Usando bcrypt
- **HTTPS**: Para todas las comunicaciones
- **Validación de Archivos**: Para imágenes subidas
- **Logs de Auditoría**: Para operaciones críticas

## Consideraciones de Performance

### Optimizaciones
- **Caching**: Memcached o Redis para leaderboard y datos frecuentemente accedidos
- **Database Indexing**: Índices optimizados para consultas frecuentes
- **AJAX Polling**: Para actualizaciones periódicas del leaderboard
- **Image Optimization**: Compresión y redimensionamiento automático con PHP GD

### Escalabilidad
- **Horizontal Scaling**: Preparado para múltiples instancias
- **Database Sharding**: Por eventos para grandes volúmenes
- **CDN**: Para servir imágenes estáticas
- **Load Balancing**: Para distribución de carga