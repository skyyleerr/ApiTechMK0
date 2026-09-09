# Matriz de métricas de calidad

| Dimensión | Métrica | Método | Meta |
|---|---|---|---|
| Funcionalidad | Casos de uso implementados / planificados | Revisión de rutas y pruebas | >= 90% |
| Corrección | Pruebas exitosas / pruebas ejecutadas | `mvn test` | >= 95% |
| Mantenibilidad | Código organizado por capas | Inspección estructural | Sin dependencias de vista en repositorios |
| Seguridad | Rutas protegidas / rutas sensibles | Revisión de SecurityConfig | 100% |
| Rendimiento | Tiempo de respuesta de dashboard | Medición HTTP local | < 2 s en demo |
| Usabilidad | Formularios con etiquetas y validación | Revisión manual WCAG básica | 100% |
| Fiabilidad | Errores no controlados en flujos CRUD | Pruebas negativas | 0 críticos |
