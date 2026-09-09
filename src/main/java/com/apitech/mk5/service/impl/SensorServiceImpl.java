package com.apitech.mk5.service.impl;

import com.apitech.mk5.dto.request.SensorRequestDTO;
import com.apitech.mk5.dto.response.SensorResponseDTO;
import com.apitech.mk5.entity.apiario.Sensor;
import com.apitech.mk5.entity.apiario.SensorColmena;
import com.apitech.mk5.entity.empresa.Empresa;
import com.apitech.mk5.exception.ReglaDeNegocioException;
import com.apitech.mk5.exception.RecursoNoEncontradoException;
import com.apitech.mk5.mapper.SensorMapper;
import com.apitech.mk5.repository.apiario.MonitoreoRepository;
import com.apitech.mk5.repository.apiario.SensorColmenaRepository;
import com.apitech.mk5.repository.apiario.SensorRepository;
import com.apitech.mk5.repository.empresa.EmpresaRepository;
import com.apitech.mk5.service.SensorService;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.util.List;
import java.util.Optional;

@Service
@Transactional(readOnly = true)
public class SensorServiceImpl implements SensorService {

    private static final String ESTADO_ACTIVO = "activo";
    private static final String MONITOREO_ACTIVO = "activo";

    private final SensorRepository sensorRepository;
    private final EmpresaRepository empresaRepository;
    private final SensorColmenaRepository sensorColmenaRepository;
    private final MonitoreoRepository monitoreoRepository;

    public SensorServiceImpl(SensorRepository sensorRepository,
                              EmpresaRepository empresaRepository,
                              SensorColmenaRepository sensorColmenaRepository,
                              MonitoreoRepository monitoreoRepository) {
        this.sensorRepository = sensorRepository;
        this.empresaRepository = empresaRepository;
        this.sensorColmenaRepository = sensorColmenaRepository;
        this.monitoreoRepository = monitoreoRepository;
    }

    @Override
    @Transactional
    public SensorResponseDTO crear(SensorRequestDTO dto) {
        if (sensorRepository.existsByCodigo(dto.codigo())) {
            throw new ReglaDeNegocioException("Ya existe un sensor registrado con el codigo " + dto.codigo());
        }
        Empresa empresa = buscarEmpresaOFallar(dto.idEmpresa());
        Sensor sensor = SensorMapper.toEntity(dto, empresa);
        sensorRepository.save(sensor);
        return SensorMapper.toResponseDTO(sensor);
    }

    @Override
    public SensorResponseDTO obtenerPorId(Integer id) {
        return SensorMapper.toResponseDTO(buscarSensorOFallar(id));
    }

    @Override
    public List<SensorResponseDTO> listarPorEmpresa(Integer idEmpresa) {
        return sensorRepository.findByEmpresa_IdEmpresa(idEmpresa).stream()
                .map(SensorMapper::toResponseDTO)
                .toList();
    }

    @Override
    @Transactional
    public SensorResponseDTO actualizar(Integer id, SensorRequestDTO dto) {
        Sensor sensor = buscarSensorOFallar(id);

        boolean codigoCambio = !sensor.getCodigo().equals(dto.codigo());
        if (codigoCambio && sensorRepository.existsByCodigo(dto.codigo())) {
            throw new ReglaDeNegocioException("Ya existe otro sensor registrado con el codigo " + dto.codigo());
        }

        Empresa empresa = buscarEmpresaOFallar(dto.idEmpresa());
        SensorMapper.aplicarCambios(sensor, dto, empresa);
        return SensorMapper.toResponseDTO(sensor);
    }

    @Override
    @Transactional
    public SensorResponseDTO cambiarEstado(Integer id, String nuevoEstado) {
        Sensor sensor = buscarSensorOFallar(id);

        if (!ESTADO_ACTIVO.equals(nuevoEstado) && tieneMonitoreoActivo(sensor.getIdSensor())) {
            throw new ReglaDeNegocioException(
                    "No se puede desactivar el sensor " + sensor.getCodigo()
                            + " porque tiene un monitoreo activo; desactive primero el monitoreo");
        }

        sensor.setEstado(nuevoEstado);
        return SensorMapper.toResponseDTO(sensor);
    }

    /**
     * Replica {@code trg_sensores_estado_bu}: un sensor con una
     * asociacion activa que ademas tiene un monitoreo activo no puede
     * desactivarse directamente.
     */
    private boolean tieneMonitoreoActivo(Integer idSensor) {
        Optional<SensorColmena> asociacionActiva =
                sensorColmenaRepository.findBySensor_IdSensorAndActivoTrue(idSensor);
        if (asociacionActiva.isEmpty()) {
            return false;
        }
        Integer idAsociacion = asociacionActiva.get().getIdAsociacion();
        return monitoreoRepository.existsByAsociacion_IdAsociacionAndEstado(idAsociacion, MONITOREO_ACTIVO);
    }

    private Sensor buscarSensorOFallar(Integer id) {
        return sensorRepository.findById(id)
                .orElseThrow(() -> new RecursoNoEncontradoException("No existe un sensor con id " + id));
    }

    private Empresa buscarEmpresaOFallar(Integer id) {
        return empresaRepository.findById(id)
                .orElseThrow(() -> new RecursoNoEncontradoException("No existe una empresa con id " + id));
    }
}
