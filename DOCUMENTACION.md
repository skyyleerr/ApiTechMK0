# ApiTech MK5

## Propósito

ApiTech MK5 es una aplicación web para administrar empresas apícolas, colmenas, sensores, mediciones, alertas, reportes y suscripciones. La versión MK5 conserva la portada, paleta, logotipo, sidebar, topbar, tarjetas, tablas y dashboard de ApiTech3.5, y reemplaza la implementación PHP por Spring Boot, Java, Spring Security, Spring Data JPA y Thymeleaf.

## Arquitectura

La aplicación utiliza una arquitectura MVC por capas. Los controladores reciben solicitudes HTTP, los servicios concentran las reglas de negocio, los repositorios encapsulan el acceso JPA y las entidades representan el dominio persistente. Los DTO evitan exponer directamente las entidades en la API. La seguridad aplica autenticación por formulario y autorización RBAC mediante roles.

| Capa | Responsabilidad | Paquetes principales |
|---|---|---|
| Presentación | Vistas Thymeleaf, navegación, formularios y validación visual | `templates`, `controller` |
| Aplicación | Casos de uso, transacciones y reglas de negocio | `service`, `service.impl` |
| Persistencia | Consultas derivadas, filtros y mapeo de tablas | `repository`, `entity` |
| Seguridad | Login, roles, sesiones y protección CSRF | `security`, `config` |
| Reportes | Exportación PDF, Excel y CSV | `ReporteService` |
| Pagos | Planes, checkout, historial y verificación administrativa | `PagoService`, `PagoController` |

## Ejecución

El proyecto usa H2 en modo archivo por defecto para facilitar la demostración. La aplicación crea datos iniciales de prueba, tres planes de suscripción y usuarios con contraseña `123456`. El checkout registra pagos pendientes y el equipo ApiTech puede verificarlos o rechazarlos desde `/admin/pagos`. La conexión puede cambiarse a MySQL mediante `DB_URL`, `DB_USER`, `DB_PASSWORD`, `DB_DRIVER`, `DB_DIALECT` y `DDL_AUTO`. Para ejecutar se utiliza `./mvnw spring-boot:run`; la portada queda disponible en `http://localhost:8080/` y el login en `http://localhost:8080/login`.

## Matriz de cumplimiento

| Criterio | Evidencia implementada |
|---|---|
| JDK, JVM, memoria y ciclo de vida | Java 21, Maven, documentación de arquitectura y entidades con separación de responsabilidades. |
| Aplicaciones estructuradas | Paquetes por capa, MVC, DTO, mappers, servicios y manejo global de excepciones. |
| Spring Boot, BD, Thymeleaf y GoF | Spring Data JPA, consultas derivadas, vistas Thymeleaf, CRUD de colmenas y uso de fachadas de servicio. |
| Autenticación y roles | Spring Security, BCrypt, roles `Admin_ApiTech`, `Admin_Cliente` y `Empleado_Cliente`, autorización por URL y método. |
| CRUD y validaciones | Alta, consulta, edición y eliminación de colmenas; DTO con Bean Validation y mensajes visuales. |
| Reportes multicriterio | Filtros por asociación, tipo y rango de fechas; exportación PDF, Excel y CSV. |
| Pasarela de pagos | Planes públicos, checkout autenticado, historial de pagos, estados y verificación/rechazo por personal ApiTech. |
| Usabilidad y accesibilidad | Navegación consistente, responsive, etiquetas de formulario, contraste, estados visuales y mensajes de resultado. |
| Calidad de software | Este documento, `MATRIZ_METRICAS.md`, `PLAN_CALIDAD.md`, `VERIFICACION_VALIDACION.md` y `MEJORA_CONTINUA_PDCA.md`. |
