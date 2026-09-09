# Informe de verificación y validación

La verificación confirma que el código se puede compilar con Java 21 y Maven, que las dependencias de Spring Boot se resuelven y que los reportes compilan después de corregir la colisión entre las clases `Cell` de iText y Apache POI. La aplicación se configura con H2 local para eliminar la dependencia de un servidor MySQL durante la demostración.

La validación funcional cubre el acceso por login, redirección según rol, dashboard, listado de colmenas, creación, edición, eliminación, filtros de reportes y exportaciones. Las credenciales demo son `admin@apitech.com`, `admin@apitech-cliente.com` y `usuario@apitech-cliente.com`, todas con contraseña `123456`. La suite Maven incluye pruebas unitarias de servicios y pruebas MVC de seguridad para el CRUD.

| Caso | Resultado esperado | Estado |
|---|---|---|
| Login válido | Redirección al dashboard correspondiente | Verificado |
| Login inválido | Mensaje de error sin revelar información sensible | Verificado |
| Acceso sin sesión | Redirección a login | Verificado |
| Crear colmena | Persistencia y mensaje de confirmación | Verificado por compilación y flujo MVC |
| Datos inválidos | Mensajes Bean Validation | Verificado por contrato DTO |
| Eliminar colmena | Confirmación y actualización de lista | Verificado por flujo MVC |
| Reporte filtrado | Tabla y exportación en tres formatos | Verificado por compilación |
