# Plan de gestión de calidad

La calidad se gestiona desde cuatro frentes: prevención, verificación, validación y mejora. La prevención utiliza convenciones de paquetes, DTO, validaciones, BCrypt, CSRF y revisión de cambios. La verificación se realiza con compilación Maven, pruebas unitarias y revisión de endpoints. La validación se realiza mediante los flujos de login, dashboard, CRUD y reportes con datos de demostración.

| Actividad | Responsable | Evidencia | Frecuencia |
|---|---|---|---|
| Compilación y pruebas | Desarrollo | Informe Maven | Cada cambio |
| Revisión de seguridad | Desarrollo | Configuración RBAC/CSRF | Cada release |
| Prueba de formularios | QA | Casos de aceptación | Cada funcionalidad |
| Revisión visual responsive | QA | Capturas y checklist | Cada release |
| Revisión de métricas | Equipo | Matriz actualizada | Iteración |
