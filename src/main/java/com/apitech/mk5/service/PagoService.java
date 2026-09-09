package com.apitech.mk5.service;

import com.apitech.mk5.entity.empresa.Empresa;
import com.apitech.mk5.entity.suscripcion.*;
import com.apitech.mk5.repository.suscripcion.*;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;
import java.math.BigDecimal;
import java.util.List;

@Service
public class PagoService {
    private final PlanSuscripcionRepository planes; private final SuscripcionRepository suscripciones; private final PagoRepository pagos;
    public PagoService(PlanSuscripcionRepository planes, SuscripcionRepository suscripciones, PagoRepository pagos){this.planes=planes;this.suscripciones=suscripciones;this.pagos=pagos;}
    public List<PlanSuscripcion> planesActivos(){return planes.findByEstado("activo");}
    public List<Pago> pagosEmpresa(Integer idEmpresa){return pagos.findByEmpresa_IdEmpresaOrderByIdPagoDesc(idEmpresa);}
    public List<Suscripcion> suscripcionesEmpresa(Integer idEmpresa){return suscripciones.findByEmpresa_IdEmpresaOrderByIdSuscripcionDesc(idEmpresa);}
    public List<Pago> pagosPendientes(){return pagos.findByEstadoOrderByIdPagoDesc(Pago.EstadoPago.PENDIENTE);}
    @Transactional public Pago checkout(Empresa empresa, Integer idPlan, String metodo, String referencia){
        PlanSuscripcion plan=planes.findById(idPlan).orElseThrow();
        Suscripcion suscripcion=new Suscripcion(empresa,plan); suscripciones.save(suscripcion);
        return pagos.save(new Pago(empresa,suscripcion,plan.getPrecio(),metodo,referencia));
    }
    @Transactional public void verificar(Integer id){Pago p=pagos.findById(id).orElseThrow();p.verificar();p.getSuscripcion().activar();}
    @Transactional public void rechazar(Integer id){pagos.findById(id).orElseThrow().rechazar();}
    public BigDecimal totalPagos(){return pagos.findAll().stream().map(Pago::getValor).reduce(BigDecimal.ZERO,BigDecimal::add);}
}
