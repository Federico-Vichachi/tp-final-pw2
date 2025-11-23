<?php

class GameModel
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function iniciarPartida($usuarioId)
    {
        $usuario = $this->getUsuarioById($usuarioId);
        $nivelUsuario = $usuario['nivel'] ?? 1;

        $codigoPartida = uniqid('partida_', true);
        $fechaInicio = date('Y-m-d H:i:s');

        $sql = "INSERT INTO partidas (codigo_partida, usuario_id, estado, fecha_inicio, nivel_usuario) 
                VALUES ('$codigoPartida', $usuarioId, 'en_curso', '$fechaInicio', $nivelUsuario)";

        $this->conexion->query($sql);

        $sql = "SELECT id, codigo_partida, nivel_usuario FROM partidas WHERE codigo_partida = '$codigoPartida'";
        $partida = $this->conexion->query($sql);

        return empty($partida) ? null : $partida[0];
    }

    public function finalizarPartida($partidaId, $puntajeFinal)
    {
        $fechaFin = date('Y-m-d H:i:s');

        $sql = "UPDATE partidas 
                SET estado = 'finalizada', 
                    puntaje_final = $puntajeFinal,
                    fecha_fin = '$fechaFin'
                WHERE id = $partidaId";

        return $this->conexion->query($sql);
    }

    public function actualizarPuntosUsuario($usuarioId, $puntosGanados)
    {
        $sql = "UPDATE usuario 
                SET puntos_acumulados = puntos_acumulados + $puntosGanados 
                WHERE id = $usuarioId";

        $this->conexion->query($sql);
        $this->actualizarNivelUsuario($usuarioId);
    }

    public function registrarRespuesta($partidaId, $preguntaId, $preguntaFallada, $tiempoRespuesta = 0, $nivelPregunta = 1)
    {
        $fallada = $preguntaFallada ? 1 : 0;
        $fechaRespuesta = date('Y-m-d H:i:s');

        $sql = "INSERT INTO historial_preguntas 
                (partida_id, pregunta_id, pregunta_fallada, fecha_respuesta, tiempo_respuesta, nivel_pregunta_partida) 
                VALUES ($partidaId, $preguntaId, $fallada, '$fechaRespuesta', $tiempoRespuesta, $nivelPregunta)";

        $this->conexion->query($sql);
        $this->actualizarEstadisticasPregunta($preguntaId, $fallada);
    }

    public function getPreguntaAleatoria($preguntasVistas, $usuarioId, $categoria = null)
    {
        $usuario = $this->getUsuarioById($usuarioId);
        $nivelUsuario = $usuario['nivel'] ?? 1;

        $pregunta = $this->obtenerPreguntaPorNivel($preguntasVistas, $nivelUsuario, $categoria);

        if(empty($pregunta)) {
            $pregunta = $this->obtenerPreguntaPorRangoNivel($preguntasVistas, $nivelUsuario, 2 , $categoria);
        }

        if(empty($pregunta)) {
            return [];
        }

        $respuestas = $this->getRespuestas($pregunta['pregunta_id']);
        shuffle($respuestas);

        $respuestaCorrecta = $this->getRespuestaCorrecta($pregunta['pregunta_id']);

        return [
            "pregunta" => $pregunta,
            "respuestas" => $respuestas,
            "respuesta_correcta" => $respuestaCorrecta
        ];
    }

    public function verificarRespuesta($idRespuesta)
    {
        if ($idRespuesta == null) {
            return false;
        }

        $id = (int)$idRespuesta;
        $sql = "SELECT es_correcta FROM respuestas WHERE id = $id";
        $resultado = $this->conexion->query($sql);

        $fila = $this->getSingleRow($resultado);
        if (empty($fila)) {
            return false;
        }

        $esCorrecta = $fila['es_correcta'];
        return $esCorrecta == 1;
    }

    public function getRespuestaCorrecta($preguntaId)
    {
        $id = (int)$preguntaId;

        $sql = "SELECT texto 
                FROM respuestas 
                WHERE pregunta_id = $id 
                  AND es_correcta = 1 LIMIT 1";

        $resultado = $this->conexion->query($sql);
        $fila = $this->getSingleRow($resultado);
        return empty($fila) ? 'No disponible' : $fila['texto'];
    }

    public function getRankingPartidas()
    {
        $partidas = $this->getMejoresPartidas();

        if (empty($partidas)) {
            return [];
        }

        $rankingCompleto = [];
        $usuariosProcesados = [];

        foreach ($partidas as $partida) {
            if (isset($partida['usuario_id'])) {
                if (in_array($partida['usuario_id'], $usuariosProcesados)) {
                    continue;
                }

                $usuario = $this->getUsuarioById($partida['usuario_id']);
                if ($usuario && !empty($usuario)) {
                    $rankingCompleto[] = array_merge($partida, $usuario);
                    $usuariosProcesados[] = $partida['usuario_id'];
                }
            }
        }

        return $rankingCompleto;
    }

    public function getRankingJugadores()
    {
        $jugadores = $this->getMejoresJugadores();

        if (empty($jugadores)) {
            return [];
        }

        return $jugadores;
    }

    private function actualizarNivelUsuario($usuarioId)
    {
        $sql = "SELECT puntos_acumulados FROM usuario WHERE id = $usuarioId";
        $resultado = $this->conexion->query($sql);

        $fila = $this->getSingleRow($resultado);
        if (empty($fila)) return;

        $puntos = $fila['puntos_acumulados'];
        $nuevoNivel = max(1, min(10, floor($puntos / 100) + 1));

        $sql = "UPDATE usuario SET nivel = $nuevoNivel WHERE id = $usuarioId";
        $this->conexion->query($sql);
    }

    private function actualizarEstadisticasPregunta($preguntaId, $fallada)
    {
        if ($fallada) {
            $sql = "UPDATE preguntas 
                    SET veces_fallada = veces_fallada + 1 
                    WHERE id = $preguntaId";
        } else {
            $sql = "UPDATE preguntas 
                    SET veces_acertada = veces_acertada + 1 
                    WHERE id = $preguntaId";
        }
        $this->conexion->query($sql);
        $this->calcularRatioDificultad($preguntaId);
    }

    private function calcularRatioDificultad($preguntaId)
    {
        $sql = "SELECT veces_acertada, veces_fallada 
                FROM preguntas 
                WHERE id = $preguntaId";
        $resultado = $this->conexion->query($sql);

        $fila = $this->getSingleRow($resultado);
        if (empty($fila)) return;

        $acertadas = $fila['veces_acertada'];
        $falladas = $fila['veces_fallada'];
        $total = $acertadas + $falladas;

        if ($total > 0) {
            $ratio = $acertadas / $total;

            $nivel = max(1, min(10, ceil((1 - $ratio) * 10)));
            $sql = "UPDATE preguntas 
                    SET ratio_dificultad = $ratio, nivel_pregunta = $nivel 
                    WHERE id = $preguntaId";
            $this->conexion->query($sql);
        }
    }

    private function obtenerPreguntaPorNivel($preguntasVistas, $nivelUsuario, $categoria = null)
    {
        $sql = "SELECT p.id AS pregunta_id, 
                   p.texto AS pregunta, 
                   p.nivel_pregunta AS nivel,
                   c.nombre AS categoria,
                   c.color AS color_categoria,
                   c.imagen AS imagen_categoria
            FROM preguntas p
            JOIN categorias c ON p.categoria_id = c.id
            WHERE p.esta_activa = 1 
              AND p.nivel_pregunta = $nivelUsuario";

        if($categoria && !empty($categoria)){
            $sql .= " AND c.nombre = '$categoria'";
        }

        if(!empty($preguntasVistas)){
            $idsExcluidos = implode(",", $preguntasVistas);
            if (!empty($idsExcluidos)) {
                $sql .= " AND p.id NOT IN ($idsExcluidos)";
            }
        }

        $sql .= " ORDER BY RAND() LIMIT 1";

        $resultado = $this->conexion->query($sql);
        return $this->getSingleRow($resultado);
    }

    private function obtenerPreguntaPorRangoNivel($preguntasVistas, $nivelUsuario, $rango = 2, $categoria = null)
    {
        $nivelMin = max(1, $nivelUsuario - $rango);
        $nivelMax = min(10, $nivelUsuario + $rango);

        $sql = "SELECT p.id AS pregunta_id, 
                   p.texto AS pregunta, 
                   p.nivel_pregunta AS nivel,
                   c.nombre AS categoria,
                   c.color AS color_categoria,
                   c.imagen AS imagen_categoria
            FROM preguntas p
            JOIN categorias c ON p.categoria_id = c.id
            WHERE p.esta_activa = 1 
              AND p.nivel_pregunta BETWEEN $nivelMin AND $nivelMax";

        if ($categoria) {
            $sql .= " AND c.nombre = '$categoria'";
        }

        if(!empty($preguntasVistas)){
            $idsExcluidos = implode(",", $preguntasVistas);
            if (!empty($idsExcluidos)) {
                $sql .= " AND p.id NOT IN ($idsExcluidos)";
            }
        }

        $sql .= " ORDER BY ABS(p.nivel_pregunta - $nivelUsuario), RAND() LIMIT 1";

        $resultado = $this->conexion->query($sql);
        return $this->getSingleRow($resultado);
    }

    private function getArrayResult($resultado)
    {
        if (is_array($resultado)) {
            return $resultado;
        }

        if (is_object($resultado) && get_class($resultado) === 'mysqli_result') {
            if ($resultado->num_rows > 0) {
                return $resultado->fetch_all(MYSQLI_ASSOC);
            } else {
                return [];
            }
        }

        return [];
    }

    private function getSingleRow($resultado)
    {
        $arrayResult = $this->getArrayResult($resultado);
        return empty($arrayResult) ? [] : $arrayResult[0];
    }


    private function getMejoresPartidas()
    {
        $sql = "SELECT *
            FROM partidas P1
            WHERE P1.puntaje_final = (
                SELECT MAX(P2.puntaje_final)
                FROM partidas P2
                WHERE P2.usuario_id = P1.usuario_id
            )
            ORDER BY P1.puntaje_final DESC";

        $resultado = $this->conexion->query($sql);
        return $this->getArrayResult($resultado);
    }

    private function getMejoresJugadores()
    {
        $sql = "SELECT *
            FROM usuario U
            WHERE U.rol = 'usuario'
            ORDER BY U.puntos_acumulados DESC";

        $resultado = $this->conexion->query($sql);
        return $this->getArrayResult($resultado);
    }

    public function getUsuarioById($usuarioId)
    {
        $sql = "SELECT id, nombre_completo, username, nivel, puntos_acumulados, 
                   pais, ciudad, latitud, longitud, foto_perfil
            FROM usuario
            WHERE id = $usuarioId";

        $resultado = $this->conexion->query($sql);
        $usuario = $this->getSingleRow($resultado);

        if ($usuario) {
            $usuario['latitud'] = (float)$usuario['latitud'];
            $usuario['longitud'] = (float)$usuario['longitud'];
        }

        return $usuario;
    }

    private function obtenerPreguntaDeBD($preguntasVistas)
    {
        $sql = "SELECT p.id AS pregunta_id, 
                   p.texto AS pregunta, 
                   c.nombre AS categoria
            FROM preguntas p
            JOIN categorias c ON p.categoria_id = c.id
            WHERE p.esta_activa = 1";

        if(!empty($preguntasVistas)){
            $idsExcluidos = implode(",", $preguntasVistas);
            $sql .= " AND p.id NOT IN ($idsExcluidos)";
        }

        $sql .= " ORDER BY RAND() LIMIT 1";

        $resultado = $this->conexion->query($sql);
        return empty($resultado) ? [] : $resultado[0];
    }

    public function getRespuestas($id_pregunta)
    {
        $sql = "SELECT id, texto, es_correcta
                FROM respuestas 
                WHERE pregunta_id = '$id_pregunta'";

        $resultado = $this->conexion->query($sql);
        return $this->getArrayResult($resultado);
    }

    public function guardarReporte($preguntaId, $usuarioId, $motivo)
    {
        $sql = "INSERT INTO reportes (pregunta_id, usuario_id, motivo) 
            VALUES ($preguntaId, $usuarioId, '$motivo')";

        $this->conexion->query($sql);
    }

    public function getReportesPendientes()
    {
        $sql = "SELECT r.id, r.motivo, r.fecha_reporte,
                   p.texto as pregunta_texto,
                   p.id as pregunta_id,
                   u.username as reportado_por
            FROM reportes r
            JOIN preguntas p ON r.pregunta_id = p.id
            JOIN usuario u ON r.usuario_id = u.id
            WHERE r.estado = 'pendiente'
            ORDER BY r.fecha_reporte DESC";
        return $this->conexion->query($sql);
    }

    public function getReportesRevisados()
    {
        $sql = "SELECT r.id, r.motivo, r.fecha_reporte,
               p.texto as pregunta_texto,
               u.username as reportado_por
            FROM reportes r
            JOIN preguntas p ON r.pregunta_id = p.id
            JOIN usuario u ON r.usuario_id = u.id
            WHERE r.estado = 'revisado'
            ORDER BY r.fecha_reporte DESC";
        return $this->conexion->query($sql);
    }

    public function marcarReporteRevisado($reporteId)
    {
        $sql = "UPDATE reportes SET estado = 'revisado' WHERE id = $reporteId";
        $this->conexion->query($sql);
    }

    public function getTodasLasPreguntas()
    {
        $sql = "SELECT p.id, p.texto, c.nombre as categoria 
                FROM preguntas p 
                JOIN categorias c ON p.categoria_id = c.id
                ORDER BY p.id DESC";
        return $this->conexion->query($sql);
    }

    public function getCategorias()
    {
        $sql = "SELECT id, nombre, color, imagen FROM categorias ORDER BY nombre ASC";
        return $this->conexion->query($sql);
    }

    public function crearPreguntaCompleta($data)
    {
        $texto = $data['texto'];
        $categoria_id = (int)$data['categoria_id'];
        $resp_correcta = $data['respuesta_correcta'];
        $resp_incorrecta1 = $data['respuesta_incorrecta1'];
        $resp_incorrecta2 = $data['respuesta_incorrecta2'];

        $sqlPregunta = "INSERT INTO preguntas (texto, categoria_id) VALUES ('$texto', $categoria_id)";
        $this->conexion->query($sqlPregunta);

        $sqlUltimoId = "SELECT LAST_INSERT_ID() as last_id";
        $resultadoId = $this->conexion->query($sqlUltimoId);
        $preguntaId = $resultadoId[0]['last_id'];

        $sqlResp1 = "INSERT INTO respuestas (pregunta_id, texto, es_correcta) VALUES ($preguntaId, '$resp_correcta', 1)";
        $this->conexion->query($sqlResp1);

        $sqlResp2 = "INSERT INTO respuestas (pregunta_id, texto, es_correcta) VALUES ($preguntaId, '$resp_incorrecta1', 0)";
        $this->conexion->query($sqlResp2);

        $sqlResp3 = "INSERT INTO respuestas (pregunta_id, texto, es_correcta) VALUES ($preguntaId, '$resp_incorrecta2', 0)";
        $this->conexion->query($sqlResp3);

        return true;
    }

    public function eliminarPregunta($preguntaId)
    {
        $id = (int)$preguntaId;
        $this->conexion->query("DELETE FROM reportes WHERE pregunta_id = $id");
        $this->conexion->query("DELETE FROM historial_preguntas WHERE pregunta_id = $id");
        $this->conexion->query("DELETE FROM respuestas WHERE pregunta_id = $id");
        $this->conexion->query("DELETE FROM preguntas WHERE id = $id");
        return true;
    }

    public function getPartidasByUsuarioId($usuarioId)
    {
        $sql = "SELECT codigo_partida, fecha_inicio, puntaje_final 
            FROM partidas 
            WHERE usuario_id = $usuarioId 
            ORDER BY fecha_inicio DESC";

        return $this->conexion->query($sql);
    }

    public function getEstadisticasJugador($usuarioId)
    {
        return [
            'total_partidas' => $this->getTotalPartidas($usuarioId),
            'preguntas_correctas' => $this->getPreguntasCorrectas($usuarioId),
            'preguntas_falladas' => $this->getPreguntasFalladas($usuarioId),
            'mejor_puntaje' => $this->getMejorPuntaje($usuarioId)
        ];
    }

    private function getTotalPartidas($usuarioId)
    {
        $sql = "SELECT COUNT(*) as total FROM partidas WHERE usuario_id = $usuarioId";
        $resultado = $this->conexion->query($sql);
        return $resultado[0]['total'] ?? 0;
    }

    private function getPreguntasCorrectas($usuarioId)
    {
        $sql = "SELECT COUNT(*) as correctas 
            FROM historial_preguntas hp 
            JOIN partidas p ON hp.partida_id = p.id 
            WHERE p.usuario_id = $usuarioId AND hp.pregunta_fallada = 0";
        $resultado = $this->conexion->query($sql);
        return $resultado[0]['correctas'] ?? 0;
    }

    private function getPreguntasFalladas($usuarioId)
    {
        $sql = "SELECT COUNT(*) as falladas 
            FROM historial_preguntas hp 
            JOIN partidas p ON hp.partida_id = p.id 
            WHERE p.usuario_id = $usuarioId AND hp.pregunta_fallada = 1";
        $resultado = $this->conexion->query($sql);
        return $resultado[0]['falladas'] ?? 0;
    }

    private function getMejorPuntaje($usuarioId)
    {
        $sql = "SELECT MAX(puntaje_final) as mejor_puntaje 
            FROM partidas 
            WHERE usuario_id = $usuarioId";
        $resultado = $this->conexion->query($sql);
        return $resultado[0]['mejor_puntaje'] ?? 0;
    }
    public function crearCategoria($data)
    {
        $nombre = $data['nombre'];
        $color = $data['color'];
        $imagen = $data['imagen'];

        $sql = "INSERT INTO categorias (nombre, color, imagen) VALUES ('$nombre', '$color', '$imagen')";
        $this->conexion->query($sql);
        return true;

    }
}