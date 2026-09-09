package com.apitech.mk5.controller;

import com.apitech.mk5.entity.suscripcion.*;
import com.apitech.mk5.repository.empresa.EmpresaRepository;
import com.apitech.mk5.security.UsuarioContexto;
import com.apitech.mk5.service.PagoService;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.*;

@Controller
public class PagoController {
    private final PagoService service; private final UsuarioContexto contexto; private final EmpresaRepository empresas;
    public PagoController(PagoService service, UsuarioContexto contexto, EmpresaRepository empresas){this.service=service;this.contexto=contexto;this.empresas=empresas;}
    @GetMapping("/planes") public String planes(Model model){model.addAttribute("planes",service.planesActivos());return "planes";}
    @GetMapping("/app/pagos") public String pagos(Model model){Integer id=empresaActual();model.addAttribute("planes",service.planesActivos());model.addAttribute("suscripciones",service.suscripcionesEmpresa(id));model.addAttribute("pagos",service.pagosEmpresa(id));return "app/pagos";}
    @PostMapping("/app/pagos/checkout") public String checkout(@RequestParam Integer idPlan,@RequestParam String metodo,@RequestParam String referencia){service.checkout(empresaActualEntity(),idPlan,metodo,referencia);return "redirect:/app/pagos?enviado";}
    @GetMapping("/admin/pagos") public String admin(Model model){model.addAttribute("pagos",service.pagosPendientes());model.addAttribute("totalPagos",service.totalPagos());return "admin/pagos";}
    @PostMapping("/admin/pagos/{id}/verificar") public String verificar(@PathVariable Integer id){service.verificar(id);return "redirect:/admin/pagos?verificado";}
    @PostMapping("/admin/pagos/{id}/rechazar") public String rechazar(@PathVariable Integer id){service.rechazar(id);return "redirect:/admin/pagos?rechazado";}
    private Integer empresaActual(){Integer id=contexto.getIdEmpresaActual();if(id!=null)return id;return empresas.findAll().stream().findFirst().orElseThrow().getIdEmpresa();}
    private com.apitech.mk5.entity.empresa.Empresa empresaActualEntity(){return empresas.findById(empresaActual()).orElseThrow();}
}
