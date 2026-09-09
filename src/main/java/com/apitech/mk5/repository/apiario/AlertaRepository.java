package com.apitech.mk5.repository.apiario;

import com.apitech.mk5.entity.apiario.Alerta;
import org.springframework.data.domain.Page;
import org.springframework.data.domain.Pageable;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.Optional;

/**
 * Repositorio Spring Data JPA para {@link Alerta}.
 *
 * <p>Al igual que {@code MedicionRepository}, se usa {@code Page} en
 * vez de {@code List} en los metodos de listado: el volumen de alertas
 * de una empresa activa a lo largo del tiempo puede crecer bastante, y
 * no queremos que "dame las alertas de esta empresa" pueda convertirse
 * en traer miles de filas de una sola vez.</p>
 *
 * <p>El metodo con {@code severidad} + {@code estado} combinados
 * refleja directamente el indice {@code idx_severidad_estado} que ya
 * existe en el esquema SQL -- es la senal de que esa combinacion de
 * filtros se usa (o se espera usar) con frecuencia, tipicamente para un
 * panel de alertas activas ordenadas por severidad.</p>
 */
public interface AlertaRepository extends JpaRepository<Alerta, Integer> {

    /**
     * Alertas de una empresa en un estado especifico (por ejemplo,
     * solo las {@code "ACTIVA"}), paginadas.
     */
    Page<Alerta> findByIdEmpresaAndEstado(Integer idEmpresa, String estado, Pageable pageable);

    /**
     * Alertas de una empresa filtradas por severidad Y estado a la vez
     * -- corresponde al indice compuesto {@code idx_severidad_estado}.
     */
    Page<Alerta> findByIdEmpresaAndSeveridadAndEstado(
            Integer idEmpresa, String severidad, String estado, Pageable pageable);

    /**
     * Historial paginado de alertas de una colmena especifica.
     */
    Page<Alerta> findByColmena_IdColmena(Integer idColmena, Pageable pageable);

    /**
     * Alerta mas reciente de una colmena (util para mostrar "ultima
     * alerta" sin traer todo el historial).
     */
    Optional<Alerta> findTopByColmena_IdColmenaOrderByFechaDesc(Integer idColmena);

}
