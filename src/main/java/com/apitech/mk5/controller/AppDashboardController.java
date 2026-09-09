package com.apitech.mk5.controller;

import com.apitech.mk5.entity.usuario.Usuario;
import com.apitech.mk5.repository.apiario.AlertaRepository;
import com.apitech.mk5.repository.apiario.ColmenaRepository;
import com.apitech.mk5.repository.apiario.SensorRepository;
import com.apitech.mk5.security.UsuarioContexto;
import org.springframework.data.domain.PageRequest;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.ResponseBody;
import java.util.Map;

/**
 * Dashboard del cliente (Admin_Cliente y Empleado_Cliente).
 *
 * <p>Muestra resumen de colmenas, sensores y alertas activas
 * de la empresa del usuario autenticado.</p>
 */
@Controller
@RequestMapping("/app")
@PreAuthorize("hasAnyRole('Admin_Cliente','Empleado_Cliente')")
public class AppDashboardController {

    private final ColmenaRepository colmenaRepository;
    private final SensorRepository sensorRepository;
    private final AlertaRepository alertaRepository;
    private final UsuarioContexto usuarioContexto;

    public AppDashboardController(ColmenaRepository colmenaRepository,
                                  SensorRepository sensorRepository,
                                  AlertaRepository alertaRepository,
                                  UsuarioContexto usuarioContexto) {
        this.colmenaRepository = colmenaRepository;
        this.sensorRepository = sensorRepository;
        this.alertaRepository = alertaRepository;
        this.usuarioContexto = usuarioContexto;
    }

    @GetMapping("/dashboard")
    public String dashboard(Model model) {
        Usuario usuario = usuarioContexto.getUsuarioActual();
        Integer idEmpresa = usuarioContexto.getIdEmpresaActual();

        model.addAttribute("usuario", usuario);
        model.addAttribute("totalColmenas",
                colmenaRepository.findByEmpresa_IdEmpresa(idEmpresa).size());
        model.addAttribute("colmenas",
                colmenaRepository.findByEmpresa_IdEmpresa(idEmpresa));
        model.addAttribute("totalSensores",
                sensorRepository.findByEmpresa_IdEmpresa(idEmpresa).size());
        model.addAttribute("alertasActivas",
                alertaRepository.findByIdEmpresaAndEstado(
                        idEmpresa, "ACTIVA",
                        PageRequest.of(0, 5)).getTotalElements());
        model.addAttribute("ultimasAlertas",
                alertaRepository.findByIdEmpresaAndEstado(
                        idEmpresa, "ACTIVA",
                        PageRequest.of(0, 5)).getContent());

        if (usuarioContexto.tieneRol("Admin_Cliente")) {
            return "app/dashboard-admin-cliente";
        }
        return "app/dashboard-empleado-cliente";
    }

    @GetMapping("/generar_datos.php")
    @ResponseBody
    public Map<String,Object> datosDashboard() {
        Integer idEmpresa = usuarioContexto.getIdEmpresaActual();
        var colmenas = colmenaRepository.findByEmpresa_IdEmpresa(idEmpresa).stream()
                .map(c -> Map.of("id", c.getIdColmena(), "nombre", c.getNombre(),
                        "temperatura", 0, "produccion", 0, "estado", c.getEstado()))
                .toList();
        return Map.of("success", true, "colmenas", colmenas,
                "colmenas_count", colmenas.size(), "temperatura_promedio", 0,
                "produccion_total", 0, "alertas_activas", 0);
    }
}
