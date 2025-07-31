# Sistema de Puntos y Torneos - Test Suite

## Descripción

Este directorio contiene las pruebas unitarias e integración para el sistema de gestión de puntos y torneos. Las pruebas están diseñadas para validar la funcionalidad crítica del sistema según los requisitos especificados.

## Estructura de Pruebas

### Pruebas Unitarias (`/Unit`)

Las pruebas unitarias validan componentes individuales del sistema:

#### `UserTest.php`
- **Propósito**: Validar el modelo User y sus funciones de validación
- **Requisitos cubiertos**: 1.1, 1.2, 1.3, 1.4
- **Casos de prueba**:
  - Validación de registro de usuario (nickname, email, contraseña)
  - Verificación de existencia de nickname y email
  - Creación exitosa de usuarios
  - Autenticación de usuarios
  - Validación de imágenes de perfil

#### `PointTransactionTest.php`
- **Propósito**: Validar el modelo PointTransaction y lógica de asignación de puntos
- **Requisitos cubiertos**: 4.1, 4.2, 4.3, 4.4
- **Casos de prueba**:
  - Validación de transacciones de puntos
  - Verificación de eventos activos
  - Creación de transacciones exitosas
  - Actualización de puntos de usuario
  - Estadísticas de puntos por usuario

#### `ClaimTest.php`
- **Propósito**: Validar el modelo Claim y lógica de reclamos únicos
- **Requisitos cubiertos**: 6.2, 6.3, 6.5, 6.6, 6.7
- **Casos de prueba**:
  - Validación de reclamos
  - Verificación de unicidad (un usuario solo puede reclamar cada producto una vez)
  - Verificación de puntos suficientes sin deducción
  - Procesamiento de reclamos por encargados
  - Cancelación de reclamos

#### `SimpleTest.php`
- **Propósito**: Pruebas básicas que no requieren base de datos
- **Casos de prueba**:
  - Validación de lógica de negocio básica
  - Validación de formatos (email, fechas, JSON)
  - Validación de permisos basados en roles

### Pruebas de Integración (`/Integration`)

Las pruebas de integración validan flujos completos del sistema:

#### `UserRegistrationFlowTest.php`
- **Propósito**: Validar el flujo completo de registro y participación de usuarios
- **Requisitos cubiertos**: Todos los requisitos integrados
- **Casos de prueba**:
  - Flujo completo: registro → autenticación → asignación de puntos → reclamo de productos
  - Validación de restricciones de eventos activos
  - Verificación de permisos basados en roles

#### `TournamentPointsIntegrationTest.php`
- **Propósito**: Validar la integración entre torneos y asignación de puntos
- **Requisitos cubiertos**: 3.1, 3.2, 3.4, 4.1, 4.2, 4.4
- **Casos de prueba**:
  - Participación en torneos y asignación de puntos
  - Validación de eventos activos para torneos
  - Múltiples torneos en el mismo evento
  - Asignación concurrente de puntos

#### `ProductClaimIntegrationTest.php`
- **Propósito**: Validar el proceso completo de reclamo de productos
- **Requisitos cubiertos**: 4.1, 4.2, 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7
- **Casos de prueba**:
  - Flujo completo: ganar puntos → reclamar producto → procesamiento por encargado
  - Validación de puntos suficientes
  - Restricciones de unicidad
  - Flujo de trabajo de encargados de stand

## Configuración de Pruebas

### Requisitos

1. **PHP 7.4+** con extensiones:
   - PDO
   - MySQL
   - JSON

2. **Base de datos de prueba** (opcional):
   - MySQL/MariaDB
   - Base de datos: `sistema_puntos_test`
   - Usuario: `root` (sin contraseña por defecto)

### Configuración de Base de Datos

Si deseas ejecutar todas las pruebas (incluyendo las que requieren base de datos):

```sql
CREATE DATABASE sistema_puntos_test;
USE sistema_puntos_test;
-- El esquema se creará automáticamente desde database/schema.sql
```

### Ejecución de Pruebas

#### Ejecutar todas las pruebas:
```bash
php tests/run_tests.php
```

#### Ejecutar solo pruebas que no requieren base de datos:
```bash
# Las pruebas se ejecutarán automáticamente sin base de datos
# mostrando advertencias para las pruebas omitidas
php tests/run_tests.php
```

#### Probar conexión a base de datos:
```bash
php tests/test_db_connection.php
```

## Framework de Pruebas

El sistema utiliza un framework de pruebas personalizado y ligero que incluye:

### Funciones de Aserción

- `assertTrue($condition, $message)` - Verifica que la condición sea verdadera
- `assertFalse($condition, $message)` - Verifica que la condición sea falsa
- `assertEquals($expected, $actual, $message)` - Verifica igualdad
- `assertNotEquals($expected, $actual, $message)` - Verifica desigualdad
- `assertNotFalse($value, $message)` - Verifica que el valor no sea false
- `assertEmpty($value, $message)` - Verifica que el valor esté vacío
- `assertNotEmpty($value, $message)` - Verifica que el valor no esté vacío
- `assertContains($needle, $haystack, $message)` - Verifica que contenga elemento
- `assertArrayHasKey($key, $array, $message)` - Verifica que el array tenga la clave
- `assertIsInt($value, $message)` - Verifica que sea entero
- `assertGreaterThan($expected, $actual, $message)` - Verifica mayor que
- `assertGreaterThanOrEqual($expected, $actual, $message)` - Verifica mayor o igual

### Clase Base de Pruebas

`BaseTestCase` proporciona:
- Configuración automática de base de datos de prueba
- Métodos `setUp()` y `tearDown()` para cada prueba
- Métodos auxiliares para crear datos de prueba
- Limpieza automática de datos entre pruebas

## Cobertura de Requisitos

### Requisitos Validados por Pruebas Unitarias

- **1.1-1.4**: Registro y autenticación de usuarios
- **4.1-4.4**: Asignación de puntos y validaciones
- **6.2, 6.3, 6.5-6.7**: Sistema de reclamos y unicidad

### Requisitos Validados por Pruebas de Integración

- **Todos los requisitos**: Flujos completos de usuario
- **2.3, 4.3**: Validación de eventos activos
- **3.1, 3.2, 3.4**: Gestión de torneos
- **7.1, 7.2**: Funcionalidad administrativa

## Casos de Prueba Críticos

### Validación de Unicidad de Reclamos
- Verifica que un usuario no pueda reclamar el mismo producto múltiples veces
- Valida que diferentes usuarios puedan reclamar el mismo producto

### Validación de Puntos Suficientes
- Verifica puntos antes de crear reclamo
- Re-verifica puntos antes de procesar reclamo
- Maneja cambios de puntos entre creación y procesamiento

### Validación de Eventos Activos
- Solo permite asignación de puntos durante eventos activos
- Valida fechas de eventos para torneos

### Validación de Permisos
- Solo usuarios con roles apropiados pueden asignar puntos
- Validación de permisos para procesamiento de reclamos

## Resultados de Pruebas

El sistema de pruebas proporciona:

- **Resumen detallado** de pruebas pasadas/fallidas
- **Mensajes descriptivos** para fallos
- **Cobertura por clase** de prueba
- **Estadísticas finales** del conjunto de pruebas

### Ejemplo de Salida

```
=== Sistema de Puntos y Torneos - Test Suite ===

✓ Test database connection successful

=== Running Unit Tests ===

Running SimpleTest...
  ✓ testBasicAssertions
  ✓ testUserValidationLogic
  ✓ testPointsValidationLogic
  ✓ testRoleBasedPermissions
  ✓ testJsonValidation
  ✓ testDateValidation
  Tests passed: 6, Failed: 0

=== Test Summary ===
Unit Tests - Passed: 6, Failed: 0
Integration Tests - Passed: 0, Failed: 0
Total Tests: 6, Passed: 6, Failed: 0

🎉 All tests passed!
```

## Mantenimiento de Pruebas

### Agregar Nuevas Pruebas

1. **Pruebas Unitarias**: Crear archivo en `/Unit/` con sufijo `Test.php`
2. **Pruebas de Integración**: Crear archivo en `/Integration/` con sufijo `Test.php`
3. **Extender BaseTestCase** para pruebas que requieren base de datos
4. **Usar métodos de aserción** apropiados para validaciones

### Mejores Prácticas

- **Nombres descriptivos** para métodos de prueba
- **Mensajes claros** en aserciones
- **Datos de prueba aislados** entre casos
- **Validación de casos límite** y errores
- **Documentación de requisitos** cubiertos

## Limitaciones Conocidas

- Las pruebas requieren configuración manual de base de datos
- No hay cobertura de código automatizada
- Framework de pruebas básico (sin mocking avanzado)
- Algunas pruebas pueden requerir datos específicos del sistema

## Futuras Mejoras

- Integración con PHPUnit para funcionalidades avanzadas
- Cobertura de código automatizada
- Pruebas de rendimiento
- Pruebas de carga para operaciones concurrentes
- Mocking de dependencias externas