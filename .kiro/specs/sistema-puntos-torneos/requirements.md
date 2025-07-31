# Documento de Requisitos

## Introducción

El sistema de gestión de puntos y torneos es una aplicación que permite a los usuarios participar en eventos y torneos para ganar puntos, los cuales pueden ser canjeados posteriormente. El sistema funciona por eventos con fechas de inicio y fin definidas, donde los administradores pueden gestionar torneos, asignar puntos con ayuda de asistentes, y los usuarios pueden competir en un leaderboard global.

## Requisitos

### Requisito 1

**Historia de Usuario:** Como usuario, quiero crear una cuenta personalizada para poder participar en torneos y eventos del sistema.

#### Criterios de Aceptación

1. CUANDO un usuario accede al sistema por primera vez ENTONCES el sistema DEBERÁ mostrar una opción de registro
2. CUANDO un usuario se registra ENTONCES el sistema DEBERÁ permitir ingresar nickname, email, contraseña e imagen de perfil
3. CUANDO un usuario sube una imagen de perfil o selecciona una de las que ya estan pre-establecidas ENTONCES el sistema DEBERÁ validar el formato y tamaño de la imagen
4. CUANDO un usuario completa el registro ENTONCES el sistema DEBERÁ crear la cuenta y redirigir al dashboard principal

### Requisito 2

**Historia de Usuario:** Como administrador, quiero crear y gestionar eventos con fechas específicas para controlar cuándo los usuarios pueden obtener puntos.

#### Criterios de Aceptación

1. CUANDO un administrador crea un evento ENTONCES el sistema DEBERÁ permitir definir fecha de inicio y fecha de fin
2. CUANDO un administrador crea un evento ENTONCES el sistema DEBERÁ permitir asignar un nombre y descripción al evento
3. CUANDO la fecha actual está fuera del rango del evento ENTONCES el sistema NO DEBERÁ permitir la obtención de puntos
4. CUANDO un evento está activo ENTONCES el sistema DEBERÁ mostrar el evento en el dashboard de usuarios

### Requisito 3

**Historia de Usuario:** Como administrador, quiero organizar torneos dentro de eventos para que los usuarios puedan competir y ganar puntos.

#### Criterios de Aceptación

1. CUANDO un administrador crea un torneo ENTONCES el sistema DEBERÁ permitir definir horarios específicos
2. CUANDO un administrador crea un torneo ENTONCES el sistema DEBERÁ permitir establecer la cantidad de puntos a otorgar
3. CUANDO un administrador crea un torneo ENTONCES el sistema DEBERÁ permitir subir una imagen representativa del juego
4. CUANDO un administrador crea un torneo ENTONCES el sistema DEBERÁ permitir agregar especificaciones opcionales (modalidad 1vs1, primera sangre, etc.)
5. CUANDO un torneo está programado ENTONCES el sistema DEBERÁ notificar a los usuarios registrados
6. CUANDO un torneo finaliza ENTONCES el sistema DEBERÁ actualizar automáticamente los puntos de los participantes

### Requisito 4

**Historia de Usuario:** Como asistente, quiero poder asignar puntos a los usuarios cuando completen correctamente juegos o challenges para ayudar al administrador.

#### Criterios de Aceptación

1. CUANDO un asistente verifica una participación exitosa ENTONCES el sistema DEBERÁ permitir asignar puntos al usuario
2. CUANDO un asistente asigna puntos ENTONCES el sistema DEBERÁ registrar quién asignó los puntos y cuándo
3. CUANDO un asistente intenta asignar puntos fuera de un evento activo ENTONCES el sistema NO DEBERÁ permitir la asignación
4. CUANDO se asignan puntos ENTONCES el sistema DEBERÁ actualizar inmediatamente el balance del usuario

### Requisito 5

**Historia de Usuario:** Como usuario, quiero ver un leaderboard para comparar mis puntos con otros usuarios y conocer mi posición en la competencia.

#### Criterios de Aceptación

1. CUANDO un usuario accede al leaderboard ENTONCES el sistema DEBERÁ mostrar los usuarios ordenados por puntos de mayor a menor
2. CUANDO el leaderboard se actualiza ENTONCES el sistema DEBERÁ reflejar los cambios en tiempo real
3. CUANDO un usuario ve el leaderboard ENTONCES el sistema DEBERÁ destacar su posición actual
4. CUANDO hay empates en puntos ENTONCES el sistema DEBERÁ usar criterios de desempate (fecha de obtención de puntos)

### Requisito 6

**Historia de Usuario:** Como usuario, quiero reclamar productos en stands participantes mostrando mi puntaje completado al encargado del stand, pudiendo reclamar cada producto solo una vez.

#### Criterios de Aceptación

1. CUANDO un usuario tiene suficientes puntos ENTONCES el sistema DEBERÁ mostrar los productos disponibles para reclamar en stands
2. CUANDO un usuario muestra su puntaje a un encargado de stand ENTONCES el sistema DEBERÁ permitir al encargado verificar el puntaje del usuario
3. CUANDO un usuario ya reclamó un producto ENTONCES el sistema NO DEBERÁ permitir reclamar el mismo producto nuevamente
4. CUANDO un encargado de stand procesa un reclamo ENTONCES el sistema DEBERÁ registrar que el usuario ya reclamó ese producto específico
5. CUANDO se realiza un reclamo ENTONCES el sistema DEBERÁ registrar la transacción con fecha, encargado responsable, stand y detalles del producto
6. CUANDO un usuario no tiene suficientes puntos ENTONCES el sistema NO DEBERÁ permitir al encargado procesar el reclamo
7. CUANDO un encargado confirma la entrega del producto ENTONCES el sistema DEBERÁ marcar la transacción como completada sin reducir puntos

### Requisito 7

**Historia de Usuario:** Como administrador, quiero tener reportes y estadísticas sobre la distribución de puntos para tomar mejores decisiones sobre los premios.

#### Criterios de Aceptación

1. CUANDO un administrador accede a reportes ENTONCES el sistema DEBERÁ mostrar estadísticas de puntos por evento
2. CUANDO se genera un reporte ENTONCES el sistema DEBERÁ incluir datos de participación por torneo
3. CUANDO se consultan estadísticas ENTONCES el sistema DEBERÁ mostrar tendencias de canje de premios
4. CUANDO se exporta un reporte ENTONCES el sistema DEBERÁ generar archivos en formato CSV o PDF

### Requisito 8

**Historia de Usuario:** Como usuario, quiero recibir notificaciones sobre torneos, eventos y cambios en mi puntuación para mantenerme informado.

#### Criterios de Aceptación

1. CUANDO se programa un nuevo torneo ENTONCES el sistema DEBERÁ notificar a todos los usuarios registrados
2. CUANDO un usuario gana puntos ENTONCES el sistema DEBERÁ enviar una notificación de confirmación
3. CUANDO un evento está por finalizar ENTONCES el sistema DEBERÁ enviar recordatorios a los usuarios
4. CUANDO hay cambios en el leaderboard ENTONCES el sistema DEBERÁ notificar a los usuarios afectados