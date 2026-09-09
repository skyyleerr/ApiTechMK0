package com.apitech.mk5.config;

import com.apitech.mk5.entity.apiario.Colmena;
import com.apitech.mk5.entity.empresa.Empresa;
import com.apitech.mk5.entity.usuario.Rol;
import com.apitech.mk5.entity.usuario.Usuario;
import com.apitech.mk5.entity.suscripcion.PlanSuscripcion;
import com.apitech.mk5.repository.apiario.ColmenaRepository;
import com.apitech.mk5.repository.empresa.EmpresaRepository;
import com.apitech.mk5.repository.usuario.RolRepository;
import com.apitech.mk5.repository.usuario.UsuarioRepository;
import com.apitech.mk5.repository.suscripcion.PlanSuscripcionRepository;
import org.springframework.boot.CommandLineRunner;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;
import org.springframework.security.crypto.password.PasswordEncoder;
import java.math.BigDecimal;

@Configuration
public class DemoDataConfig {
    @Bean
    CommandLineRunner cargarDatosDemo(EmpresaRepository empresas, RolRepository roles,
                                      UsuarioRepository usuarios, ColmenaRepository colmenas,
                                      PlanSuscripcionRepository planes,
                                      PasswordEncoder encoder) {
        return args -> {
            Rol adminApi = roles.findByNombreRol("Admin_ApiTech")
                    .orElseGet(() -> roles.save(new Rol("Admin_ApiTech", "Administrador de la plataforma")));
            Rol empleadoApi = roles.findByNombreRol("Empleado_ApiTech")
                    .orElseGet(() -> roles.save(new Rol("Empleado_ApiTech", "Operador de la plataforma")));
            Rol adminCliente = roles.findByNombreRol("Admin_Cliente")
                    .orElseGet(() -> roles.save(new Rol("Admin_Cliente", "Administrador de empresa cliente")));
            Rol empleadoCliente = roles.findByNombreRol("Empleado_Cliente")
                    .orElseGet(() -> roles.save(new Rol("Empleado_Cliente", "Operador de empresa cliente")));
            Empresa empresa = empresas.findByNit("900123456-7")
                    .orElseGet(() -> empresas.save(new Empresa("900123456-7", "Apícola El Panal S.A.S.",
                            "contacto@elpanal.co", "3001234567", "Vereda El Roble", "activa")));
            if (planes.count() == 0) {
                planes.save(new PlanSuscripcion("Plan Básico", new BigDecimal("49000"), 30, "activo"));
                planes.save(new PlanSuscripcion("Plan Profesional", new BigDecimal("99000"), 30, "activo"));
                planes.save(new PlanSuscripcion("Plan Empresarial", new BigDecimal("199000"), 90, "activo"));
            }
            crearUsuario(usuarios, encoder, "admin@apitech.com", "Administrador", "ApiTech", adminApi, null);
            crearUsuario(usuarios, encoder, "operador@apitech.com", "Operador", "ApiTech", empleadoApi, null);
            Usuario cliente = crearUsuario(usuarios, encoder, "admin@apitech-cliente.com", "Administradora", "El Panal", adminCliente, empresa);
            crearUsuario(usuarios, encoder, "usuario@apitech-cliente.com", "Operario", "El Panal", empleadoCliente, empresa);
            if (colmenas.findByEmpresa_IdEmpresa(empresa.getIdEmpresa()).isEmpty()) {
                colmenas.save(new Colmena(empresa, cliente, "Panal Norte", "Sector La Montaña", "Estable"));
                colmenas.save(new Colmena(empresa, cliente, "Panal Sur", "Sector El Roble", "Advertencia"));
            }
        };
    }

    private Usuario crearUsuario(UsuarioRepository repo, PasswordEncoder encoder, String correo,
                                 String nombre, String apellido, Rol rol, Empresa empresa) {
        return repo.findByCorreo(correo).orElseGet(() -> repo.save(new Usuario(
                empresa, rol, nombre, apellido, correo, encoder.encode("123456"), "activo")));
    }
}
