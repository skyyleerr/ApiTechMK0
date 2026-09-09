package com.apitech.mk5.security;

import java.io.IOException;

import org.springframework.security.core.Authentication;
import org.springframework.security.core.GrantedAuthority;
import org.springframework.security.web.authentication.AuthenticationSuccessHandler;

import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;

/**
 * Redirige al dashboard correcto después del login según el rol.
 *
 * <ul>
 *   <li>Admin_ApiTech / Empleado_ApiTech → /admin/dashboard</li>
 *   <li>Admin_Cliente / Empleado_Cliente → /app/dashboard</li>
 * </ul>
 */
public class RolBasedLoginSuccessHandler implements AuthenticationSuccessHandler {

    @Override
    public void onAuthenticationSuccess(HttpServletRequest request,
                                        HttpServletResponse response,
                                        Authentication authentication)
            throws IOException {

        String redirectUrl = "/app/dashboard"; // default

        for (GrantedAuthority authority : authentication.getAuthorities()) {
            String rol = authority.getAuthority();
            if (rol.equals("ROLE_Admin_ApiTech")
                    || rol.equals("ROLE_Empleado_ApiTech")) {
                redirectUrl = "/admin/dashboard";
                break;
            }
        }

        response.sendRedirect(request.getContextPath() + redirectUrl);
    }
}