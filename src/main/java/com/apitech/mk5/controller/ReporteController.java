package com.apitech.mk5.controller;

import com.apitech.mk5.entity.apiario.Medicion;
import com.apitech.mk5.entity.apiario.TipoMedicion;
import com.apitech.mk5.repository.apiario.ColmenaRepository;
import com.apitech.mk5.repository.apiario.MedicionRepository;
import com.apitech.mk5.repository.apiario.SensorColmenaRepository;
import com.apitech.mk5.security.UsuarioContexto;
import com.apitech.mk5.service.ReporteService;
import jakarta.servlet.http.HttpServletResponse;
import org.springframework.data.domain.Page;
import org.springframework.data.domain.PageRequest;
import org.springframework.format.annotation.DateTimeFormat;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;

import java.io.IOException;
import java.time.LocalDateTime;

/**
 * Controlador de reportes con filtros multicriterio (ítem 6).
 *
 * <p>Permite filtrar mediciones por colmena, tipo de sensor,
 * rango de fechas y rango de valores, y exportar los resultados
 * en PDF, Excel o CSV.</p>
 */
@Controller
@RequestMapping("/app/reportes")
@PreAuthorize("hasAnyRole('Admin_Cliente','Empleado_Cliente')")
public class ReporteController {

    private final MedicionRepository medicionRepository;
    private final ColmenaRepository colmenaRepository;
    private final SensorColmenaRepository sensorColmenaRepository;
    private final ReporteService reporteService;
    private final UsuarioContexto usuarioContexto;

    public ReporteController(MedicionRepository medicionRepository,
                             ColmenaRepository colmenaRepository,
                             SensorColmenaRepository sensorColmenaRepository,
                             ReporteService reporteService,
                             UsuarioContexto usuarioContexto) {
        this.medicionRepository = medicionRepository;
        this.colmenaRepository = colmenaRepository;
        this.sensorColmenaRepository = sensorColmenaRepository;
        this.reporteService = reporteService;
        this.usuarioContexto = usuarioContexto;
    }

    /**
     * Vista principal de reportes con formulario de filtros.
     */
    @GetMapping
    public String verReportes(
            @RequestParam(required = false) Integer idAsociacion,
            @RequestParam(required = false) TipoMedicion tipoMedicion,
            @RequestParam(required = false)
                @DateTimeFormat(iso = DateTimeFormat.ISO.DATE_TIME)
                LocalDateTime desde,
            @RequestParam(required = false)
                @DateTimeFormat(iso = DateTimeFormat.ISO.DATE_TIME)
                LocalDateTime hasta,
            @RequestParam(defaultValue = "0") int page,
            @RequestParam(defaultValue = "20") int size,
            Model model) {

        Integer idEmpresa = usuarioContexto.getIdEmpresaActual();

        // Datos para los selects del formulario
        model.addAttribute("colmenas",
                colmenaRepository.findByEmpresa_IdEmpresa(idEmpresa));
        model.addAttribute("tiposMedicion", TipoMedicion.values());
        model.addAttribute("asociaciones",
                sensorColmenaRepository.findAll().stream()
                        .filter(sc -> sc.getIdEmpresa().equals(idEmpresa))
                        .toList());

        // Parámetros actuales del filtro (para mantenerlos en el form)
        model.addAttribute("idAsociacion", idAsociacion);
        model.addAttribute("tipoMedicion", tipoMedicion);
        model.addAttribute("desde", desde);
        model.addAttribute("hasta", hasta);

        // Aplicar filtros
        Page<Medicion> resultados = null;
        if (idAsociacion != null && tipoMedicion != null
                && desde != null && hasta != null) {
            resultados = medicionRepository
                    .findByAsociacion_IdAsociacionAndTipoMedicionAndFechaBetween(
                            idAsociacion, tipoMedicion, desde, hasta,
                            PageRequest.of(page, size));
        } else if (idAsociacion != null) {
            resultados = medicionRepository
                    .findByAsociacion_IdAsociacionOrderByFechaDesc(
                            idAsociacion, PageRequest.of(page, size));
        }

        model.addAttribute("resultados", resultados);
        model.addAttribute("hayResultados",
                resultados != null && resultados.hasContent());

        return "app/reportes";
    }

    /**
     * Exporta el reporte en PDF.
     * Solo Admin_Cliente puede exportar (ítem 6).
     */
    @GetMapping("/exportar/pdf")
    @PreAuthorize("hasRole('Admin_Cliente')")
    public void exportarPdf(
            @RequestParam Integer idAsociacion,
            @RequestParam TipoMedicion tipoMedicion,
            @RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE_TIME)
                LocalDateTime desde,
            @RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE_TIME)
                LocalDateTime hasta,
            HttpServletResponse response) throws IOException {

        response.setContentType("application/pdf");
        response.setHeader("Content-Disposition",
                "attachment; filename=\"reporte-apitech.pdf\"");

        Page<Medicion> mediciones = medicionRepository
                .findByAsociacion_IdAsociacionAndTipoMedicionAndFechaBetween(
                        idAsociacion, tipoMedicion, desde, hasta,
                        PageRequest.of(0, 1000));

        reporteService.generarPdf(mediciones.getContent(), response.getOutputStream());
    }

    /**
     * Exporta el reporte en Excel.
     */
    @GetMapping("/exportar/excel")
    @PreAuthorize("hasRole('Admin_Cliente')")
    public void exportarExcel(
            @RequestParam Integer idAsociacion,
            @RequestParam TipoMedicion tipoMedicion,
            @RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE_TIME)
                LocalDateTime desde,
            @RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE_TIME)
                LocalDateTime hasta,
            HttpServletResponse response) throws IOException {

        response.setContentType(
                "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        response.setHeader("Content-Disposition",
                "attachment; filename=\"reporte-apitech.xlsx\"");

        Page<Medicion> mediciones = medicionRepository
                .findByAsociacion_IdAsociacionAndTipoMedicionAndFechaBetween(
                        idAsociacion, tipoMedicion, desde, hasta,
                        PageRequest.of(0, 1000));

        reporteService.generarExcel(mediciones.getContent(), response.getOutputStream());
    }

    /**
     * Exporta el reporte en CSV.
     */
    @GetMapping("/exportar/csv")
    @PreAuthorize("hasRole('Admin_Cliente')")
    public void exportarCsv(
            @RequestParam Integer idAsociacion,
            @RequestParam TipoMedicion tipoMedicion,
            @RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE_TIME)
                LocalDateTime desde,
            @RequestParam @DateTimeFormat(iso = DateTimeFormat.ISO.DATE_TIME)
                LocalDateTime hasta,
            HttpServletResponse response) throws IOException {

        response.setContentType("text/csv; charset=UTF-8");
        response.setHeader("Content-Disposition",
                "attachment; filename=\"reporte-apitech.csv\"");
        // BOM para compatibilidad con Excel
        response.getOutputStream().write(new byte[]{(byte)0xEF, (byte)0xBB, (byte)0xBF});

        Page<Medicion> mediciones = medicionRepository
                .findByAsociacion_IdAsociacionAndTipoMedicionAndFechaBetween(
                        idAsociacion, tipoMedicion, desde, hasta,
                        PageRequest.of(0, 10000));

        reporteService.generarCsv(mediciones.getContent(), response.getWriter());
    }
}