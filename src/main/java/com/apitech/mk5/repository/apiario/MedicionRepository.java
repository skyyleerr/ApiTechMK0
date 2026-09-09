package com.apitech.mk5.repository.apiario;

import com.apitech.mk5.entity.apiario.Medicion;
import com.apitech.mk5.entity.apiario.TipoMedicion;
import org.springframework.data.domain.Page;
import org.springframework.data.domain.Pageable;
import org.springframework.data.jpa.repository.JpaRepository;

import java.time.LocalDateTime;
import java.util.Optional;

/**
 * Repositorio Spring Data JPA para {@link Medicion}.
 *
 * <p><b>Por que {@code Page} y no {@code List} en todos los metodos de
 * listado:</b> {@code mediciones} es la tabla de mayor volumen del
 * sistema. Un metodo que devuelva {@code List<Medicion>} sin paginar
 * podria, con el tiempo, intentar cargar millones de filas en memoria
 * de una sola vez. Usar {@link Pageable}/{@link Page} obliga a quien
 * llama (el futuro {@code service}, y eventualmente el modulo de
 * reportes de Anthony/Karen) a pedir siempre un tamano de pagina
 * explicito -- es una decision de diseno pensada para escalar, no una
 * complicacion innecesaria.</p>
 *
 * <p>El metodo con rango de fechas + tipo es, a proposito, el punto de
 * partida pensado para el futuro modulo de reportes multicriterio: ya
 * permite combinar asociacion, tipo de medicion y ventana temporal sin
 * que quien construya el reporte tenga que escribir una sola consulta
 * nueva a mano.</p>
 */
public interface MedicionRepository extends JpaRepository<Medicion, Long> {

    /**
     * Historial paginado de mediciones de una asociacion sensor-colmena,
     * mas recientes primero (orden definido por la base de datos segun
     * el indice {@code idx_asociacion}; para un orden explicito por
     * fecha, ver {@link #findByAsociacion_IdAsociacionOrderByFechaDesc}).
     */
    Page<Medicion> findByAsociacion_IdAsociacion(Integer idAsociacion, Pageable pageable);

    /**
     * Igual que el anterior, pero garantizando orden descendente por
     * fecha explicitamente (la mas reciente primero).
     */
    Page<Medicion> findByAsociacion_IdAsociacionOrderByFechaDesc(Integer idAsociacion, Pageable pageable);

    /**
     * Consulta multicriterio: mediciones de una asociacion, de un tipo
     * especifico, dentro de un rango de fechas. Pensada como base
     * reutilizable para reportes (filtrar solo por asociacion, solo por
     * tipo, o combinar ambos con fechas, sin escribir SQL nuevo).
     *
     * @param idAsociacion id de la asociacion sensor-colmena.
     * @param tipoMedicion tipo de medicion a filtrar.
     * @param desde        fecha/hora inicial (inclusive).
     * @param hasta        fecha/hora final (inclusive).
     * @param pageable     tamano de pagina y orden solicitados.
     */
    Page<Medicion> findByAsociacion_IdAsociacionAndTipoMedicionAndFechaBetween(
            Integer idAsociacion, TipoMedicion tipoMedicion,
            LocalDateTime desde, LocalDateTime hasta, Pageable pageable);

    /**
     * Ultima medicion registrada para una asociacion (la mas reciente).
     * Util para mostrar "valor actual" en un dashboard sin traer todo
     * el historial.
     */
    Optional<Medicion> findTopByAsociacion_IdAsociacionOrderByFechaDesc(Integer idAsociacion);

}
