package com.apitech.mk5.controller;

import com.apitech.mk5.repository.apiario.AlertaRepository;
import com.apitech.mk5.repository.apiario.SensorRepository;
import com.apitech.mk5.repository.empresa.EmpresaRepository;
import com.apitech.mk5.repository.usuario.UsuarioRepository;
import com.apitech.mk5.security.UsuarioContexto;
import com.apitech.mk5.dto.request.SensorRequestDTO;
import com.apitech.mk5.dto.request.UsuarioRequestDTO;
import com.apitech.mk5.service.SensorService;
import com.apitech.mk5.service.UsuarioService;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.servlet.mvc.support.RedirectAttributes;
import com.apitech.mk5.entity.apiario.TipoMedicion;

@Controller
@RequestMapping("/app")
@PreAuthorize("hasAnyRole('Admin_Cliente','Empleado_Cliente')")
public class ModulePageController {
    private final UsuarioContexto contexto;
    private final EmpresaRepository empresas;
    private final SensorRepository sensores;
    private final UsuarioRepository usuarios;
    private final AlertaRepository alertas;
    private final SensorService sensorService;
    private final UsuarioService usuarioService;

    public ModulePageController(UsuarioContexto contexto, EmpresaRepository empresas,
                                SensorRepository sensores, UsuarioRepository usuarios,
                                AlertaRepository alertas, SensorService sensorService,
                                UsuarioService usuarioService) {
        this.contexto = contexto; this.empresas = empresas; this.sensores = sensores;
        this.usuarios = usuarios; this.alertas = alertas;
        this.sensorService = sensorService; this.usuarioService = usuarioService;
    }

    @GetMapping({"/sensores", "/produccion", "/alertas", "/configuracion", "/usuarios", "/ayuda", "/importar"})
    public String modulo(Model model, jakarta.servlet.http.HttpServletRequest request) {
        Integer empresa = empresaActual();
        String modulo = request.getRequestURI().substring(request.getRequestURI().lastIndexOf('/') + 1);
        model.addAttribute("modulo", modulo);
        model.addAttribute("titulo", titulo(modulo));
        model.addAttribute("empresaId", empresa);
        model.addAttribute("sensores", sensores.findByEmpresa_IdEmpresa(empresa));
        model.addAttribute("usuarios", usuarios.findByEmpresa_IdEmpresa(empresa));
        model.addAttribute("alertas", alertas.findByIdEmpresaAndEstado(empresa, "ACTIVA", org.springframework.data.domain.PageRequest.of(0, 20)).getContent());
        return "app/module";
    }

    @PostMapping("/sensores/registrar")
    @PreAuthorize("hasRole('Admin_Cliente')")
    public String registrarSensor(@RequestParam String codigo,
                                  @RequestParam TipoMedicion tipo,
                                  @RequestParam(required = false, defaultValue = "") String modelo,
                                  @RequestParam(required = false, defaultValue = "") String fabricante,
                                  RedirectAttributes redirect) {
        sensorService.crear(new SensorRequestDTO(empresaActual(), codigo, tipo,
                modelo, fabricante, true, "activo"));
        redirect.addFlashAttribute("mensaje", "Sensor registrado correctamente.");
        return "redirect:/app/sensores";
    }

    @PostMapping("/usuarios/crear")
    @PreAuthorize("hasRole('Admin_Cliente')")
    public String crearUsuario(@RequestParam Integer idRol,
                               @RequestParam String nombre,
                               @RequestParam(required = false, defaultValue = "") String apellido,
                               @RequestParam String correo,
                               @RequestParam String password,
                               RedirectAttributes redirect) {
        usuarioService.crear(new UsuarioRequestDTO(empresaActual(), idRol, nombre,
                apellido, correo, password, "activo"));
        redirect.addFlashAttribute("mensaje", "Usuario creado correctamente.");
        return "redirect:/app/usuarios";
    }

    private String titulo(String modulo) {
        return switch (modulo) {
            case "sensores" -> "Sensores";
            case "produccion" -> "Producción";
            case "alertas" -> "Alertas";
            case "configuracion" -> "Configuración";
            case "usuarios" -> "Usuarios";
            case "importar" -> "Importar Datos";
            default -> "Ayuda";
        };
    }
    private Integer empresaActual() {
        Integer id = contexto.getIdEmpresaActual();
        if (id != null) return id;
        return empresas.findAll().stream().findFirst().orElseThrow().getIdEmpresa();
    }
}
