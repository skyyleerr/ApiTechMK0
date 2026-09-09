package com.apitech.mk5.entity.suscripcion;

import com.apitech.mk5.entity.empresa.Empresa;
import jakarta.persistence.*;
import java.math.BigDecimal;
import java.time.LocalDate;

@Entity
@Table(name="pagos")
public class Pago {
    @Id @GeneratedValue(strategy=GenerationType.IDENTITY) @Column(name="id_pago") private Integer idPago;
    @ManyToOne(optional=false, fetch=FetchType.LAZY) @JoinColumn(name="id_empresa") private Empresa empresa;
    @ManyToOne(optional=false, fetch=FetchType.LAZY) @JoinColumn(name="id_suscripcion") private Suscripcion suscripcion;
    @Column(nullable=false, precision=12, scale=2) private BigDecimal valor;
    @Column(name="fecha_pago", nullable=false) private LocalDate fechaPago;
    @Column(name="metodo_pago", length=50) private String metodoPago;
    @Column(length=100) private String referencia;
    @Enumerated(EnumType.STRING) @Column(nullable=false, length=20) private EstadoPago estado;
    @Column(columnDefinition="TEXT") private String observaciones;
    protected Pago() {}
    public Pago(Empresa empresa, Suscripcion suscripcion, BigDecimal valor, String metodoPago, String referencia){this.empresa=empresa;this.suscripcion=suscripcion;this.valor=valor;this.metodoPago=metodoPago;this.referencia=referencia;this.fechaPago=LocalDate.now();this.estado=EstadoPago.PENDIENTE;}
    public Integer getIdPago(){return idPago;} public Empresa getEmpresa(){return empresa;} public Suscripcion getSuscripcion(){return suscripcion;} public BigDecimal getValor(){return valor;} public LocalDate getFechaPago(){return fechaPago;} public String getMetodoPago(){return metodoPago;} public String getReferencia(){return referencia;} public EstadoPago getEstado(){return estado;} public void verificar(){estado=EstadoPago.VERIFICADO;} public void rechazar(){estado=EstadoPago.RECHAZADO;}
    public enum EstadoPago { PENDIENTE, VERIFICADO, RECHAZADO }
}
