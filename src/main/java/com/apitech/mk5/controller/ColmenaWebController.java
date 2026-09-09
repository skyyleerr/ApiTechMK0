package com.apitech.mk5.controller;

import com.apitech.mk5.dto.request.ColmenaRequestDTO;
import com.apitech.mk5.dto.response.ColmenaResponseDTO;
import com.apitech.mk5.repository.empresa.EmpresaRepository;
import com.apitech.mk5.security.UsuarioContexto;
import com.apitech.mk5.service.ColmenaService;
import jakarta.validation.Valid;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.validation.BindingResult;
import org.springframework.web.bind.annotation.*;

@Controller
@RequestMapping("/app/colmenas")
public class ColmenaWebController {
    private final ColmenaService service;
    private final UsuarioContexto contexto;
    private final EmpresaRepository empresas;

    public ColmenaWebController(ColmenaService service, UsuarioContexto contexto,
                                EmpresaRepository empresas) {
        this.service = service;
        this.contexto = contexto;
        this.empresas = empresas;
    }

    @GetMapping
    public String listar(Model model) {
        Integer idEmpresa = empresaActual();
        model.addAttribute("colmenas", service.listarPorEmpresa(idEmpresa));
        model.addAttribute("idEmpresa", idEmpresa);
        return "app/colmenas/lista";
    }

    @GetMapping("/nueva")
    public String nueva(Model model) {
        model.addAttribute("colmena", new ColmenaRequestDTO(empresaActual(), null, "", "", "Estable"));
        model.addAttribute("titulo", "Nueva colmena");
        model.addAttribute("accion", "/app/colmenas");
        return "app/colmenas/formulario";
    }

    @PostMapping
    public String crear(@Valid @ModelAttribute("colmena") ColmenaRequestDTO dto,
                        BindingResult result, Model model) {
        if (result.hasErrors()) {
            model.addAttribute("titulo", "Nueva colmena");
            model.addAttribute("accion", "/app/colmenas");
            return "app/colmenas/formulario";
        }
        service.crear(dto);
        return "redirect:/app/colmenas?creada";
    }

    @GetMapping("/{id}/editar")
    public String editar(@PathVariable Integer id, Model model) {
        ColmenaResponseDTO c = service.obtenerPorId(id);
        model.addAttribute("colmena", new ColmenaRequestDTO(c.empresa().idEmpresa(),
                c.usuarioRegistro() != null ? c.usuarioRegistro().idUsuario() : null,
                c.nombre(), c.ubicacion(), c.estado()));
        model.addAttribute("titulo", "Editar colmena");
        model.addAttribute("accion", "/app/colmenas/" + id);
        return "app/colmenas/formulario";
    }

    @PostMapping("/{id}")
    public String actualizar(@PathVariable Integer id,
                             @Valid @ModelAttribute("colmena") ColmenaRequestDTO dto,
                             BindingResult result, Model model) {
        if (result.hasErrors()) {
            model.addAttribute("titulo", "Editar colmena");
            model.addAttribute("accion", "/app/colmenas/" + id);
            return "app/colmenas/formulario";
        }
        service.actualizar(id, dto);
        return "redirect:/app/colmenas?actualizada";
    }

    @PostMapping("/{id}/eliminar")
    public String eliminar(@PathVariable Integer id) {
        service.eliminar(id);
        return "redirect:/app/colmenas?eliminada";
    }

    private Integer empresaActual() {
        Integer id = contexto.getIdEmpresaActual();
        if (id != null) return id;
        return empresas.findAll().stream().findFirst()
                .orElseThrow(() -> new IllegalStateException("No existe una empresa de demostración"))
                .getIdEmpresa();
    }
}
