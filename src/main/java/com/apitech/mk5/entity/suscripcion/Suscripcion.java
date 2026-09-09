package com.apitech.mk5.entity.suscripcion;

import com.apitech.mk5.entity.empresa.Empresa;
import jakarta.persistence.*;
import java.time.LocalDate;

@Entity
@Table(name="suscripciones")
public class Suscripcion {
    @Id @GeneratedValue(strategy=GenerationType.IDENTITY) @Column(name="id_suscripcion") private Integer idSuscripcion;
    @ManyToOne(optional=false, fetch=FetchType.LAZY) @JoinColumn(name="id_empresa") private Empresa empresa;
    @ManyToOne(optional=false, fetch=FetchType.LAZY) @JoinColumn(name="id_plan") private PlanSuscripcion plan;
    @Enumerated(EnumType.STRING) @Column(nullable=false, length=20) private EstadoSuscripcion estado;
    @Column(name="fecha_inicio") private LocalDate fechaInicio;
    @Column(name="fecha_vencimiento") private LocalDate fechaVencimiento;
    @Column(name="renovacion_automatica", nullable=false) private boolean renovacionAutomatica;
    protected Suscripcion() {}
    public Suscripcion(Empresa empresa, PlanSuscripcion plan){this.empresa=empresa;this.plan=plan;this.estado=EstadoSuscripcion.PENDIENTE;this.renovacionAutomatica=false;}
    public Integer getIdSuscripcion(){return idSuscripcion;} public Empresa getEmpresa(){return empresa;} public PlanSuscripcion getPlan(){return plan;} public EstadoSuscripcion getEstado(){return estado;} public void setEstado(EstadoSuscripcion estado){this.estado=estado;} public LocalDate getFechaInicio(){return fechaInicio;} public LocalDate getFechaVencimiento(){return fechaVencimiento;} public void activar(){this.estado=EstadoSuscripcion.ACTIVA;this.fechaInicio=LocalDate.now();this.fechaVencimiento=LocalDate.now().plusDays(plan.getDuracionDias());}
    public enum EstadoSuscripcion { PENDIENTE, ACTIVA, SUSPENDIDA, VENCIDA, CANCELADA }
}
