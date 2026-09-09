package com.apitech.mk5.service.impl;

import com.apitech.mk5.dto.request.ColmenaRequestDTO;
import com.apitech.mk5.dto.response.ColmenaResponseDTO;
import com.apitech.mk5.entity.apiario.Colmena;
import com.apitech.mk5.entity.empresa.Empresa;
import com.apitech.mk5.entity.usuario.Usuario;
import com.apitech.mk5.exception.RecursoNoEncontradoException;
import com.apitech.mk5.mapper.ColmenaMapper;
import com.apitech.mk5.repository.apiario.ColmenaRepository;
import com.apitech.mk5.repository.empresa.EmpresaRepository;
import com.apitech.mk5.repository.usuario.UsuarioRepository;
import com.apitech.mk5.service.ColmenaService;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.util.List;

@Service
@Transactional(readOnly = true)
public class ColmenaServiceImpl implements ColmenaService {

    private final ColmenaRepository colmenaRepository;
    private final EmpresaRepository empresaRepository;
    private final UsuarioRepository usuarioRepository;

    public ColmenaServiceImpl(ColmenaRepository colmenaRepository,
                               EmpresaRepository empresaRepository,
                               UsuarioRepository usuarioRepository) {
        this.colmenaRepository = colmenaRepository;
        this.empresaRepository = empresaRepository;
        this.usuarioRepository = usuarioRepository;
    }

    @Override
    @Transactional
    public ColmenaResponseDTO crear(ColmenaRequestDTO dto) {
        Empresa empresa = buscarEmpresaOFallar(dto.idEmpresa());
        Usuario usuarioRegistro = dto.idUsuarioRegistro() != null
                ? buscarUsuarioOFallar(dto.idUsuarioRegistro()) : null;

        Colmena colmena = ColmenaMapper.toEntity(dto, empresa, usuarioRegistro);
        colmenaRepository.save(colmena);
        return ColmenaMapper.toResponseDTO(colmena);
    }

    @Override
    public ColmenaResponseDTO obtenerPorId(Integer id) {
        return ColmenaMapper.toResponseDTO(buscarColmenaOFallar(id));
    }

    @Override
    public List<ColmenaResponseDTO> listarPorEmpresa(Integer idEmpresa) {
        return colmenaRepository.findByEmpresa_IdEmpresa(idEmpresa).stream()
                .map(ColmenaMapper::toResponseDTO)
                .toList();
    }

    @Override
    @Transactional
    public ColmenaResponseDTO actualizar(Integer id, ColmenaRequestDTO dto) {
        Colmena colmena = buscarColmenaOFallar(id);
        Empresa empresa = buscarEmpresaOFallar(dto.idEmpresa());
        Usuario usuarioRegistro = dto.idUsuarioRegistro() != null
                ? buscarUsuarioOFallar(dto.idUsuarioRegistro()) : null;

        ColmenaMapper.aplicarCambios(colmena, dto, empresa, usuarioRegistro);
        return ColmenaMapper.toResponseDTO(colmena);
    }

    @Override
    @Transactional
    public void eliminar(Integer id) {
        Colmena colmena = buscarColmenaOFallar(id);
        colmenaRepository.delete(colmena);
    }

    @Override
    @Transactional
    public ColmenaResponseDTO cambiarEstado(Integer id, String nuevoEstado) {
        Colmena colmena = buscarColmenaOFallar(id);
        colmena.setEstado(nuevoEstado);
        return ColmenaMapper.toResponseDTO(colmena);
    }

    private Colmena buscarColmenaOFallar(Integer id) {
        return colmenaRepository.findById(id)
                .orElseThrow(() -> new RecursoNoEncontradoException("No existe una colmena con id " + id));
    }

    private Empresa buscarEmpresaOFallar(Integer id) {
        return empresaRepository.findById(id)
                .orElseThrow(() -> new RecursoNoEncontradoException("No existe una empresa con id " + id));
    }

    private Usuario buscarUsuarioOFallar(Integer id) {
        return usuarioRepository.findById(id)
                .orElseThrow(() -> new RecursoNoEncontradoException("No existe un usuario con id " + id));
    }
}
