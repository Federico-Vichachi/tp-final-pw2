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
        $dificultadUsuario = $this->calcularDificultadUsuario($usuarioId);

        $codigoPartida = uniqid('partida_', true);
        $fechaInicio = date('Y-m-d H:i:s');

        $sql = "INSERT INTO partidas (codigo_partida, usuario_id, estado, fecha_inicio, nivel_usuario) 
                VALUES ('$codigoPartida', $usuarioId, 'en_curso', '$fechaInicio', {$usuario['nivel']})";

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

    public function registrarRespuesta($partidaId, $preguntaId, $preguntaFallada, $tiempoRespuesta = 0, $dificultadPregunta = 'intermedia')
    {
        $fallada = $preguntaFallada ? 1 : 0;
        $fechaRespuesta = date('Y-m-d H:i:s');

        $sql = "INSERT INTO historial_preguntas 
                (partida_id, pregunta_id, pregunta_fallada, fecha_respuesta, tiempo_respuesta, dificultad_pregunta_partida) 
                VALUES ($partidaId, $preguntaId, $fallada, '$fechaRespuesta', $tiempoRespuesta, '$dificultadPregunta')";

        $this->conexion->query($sql);
        $this->actualizarEstadisticasPregunta($preguntaId, $fallada);
    }

    public function getPreguntaAleatoria($preguntasVistas, $usuarioId, $categoria = null)
    {
        $usuario = $this->getUsuarioById($usuarioId);
        $dificultadUsuario = $this->calcularDificultadUsuario($usuarioId);

        // Obtener las últimas 10 preguntas acertadas
        $preguntasExcluidas = $this->getUltimasPreguntasAcertadas($usuarioId);
        $preguntasVistas = array_merge($preguntasVistas, $preguntasExcluidas);

        // Buscar pregunta de la misma dificultad
        $pregunta = $this->obtenerPreguntaPorDificultad($preguntasVistas, $dificultadUsuario, $categoria);

        // Si no encuentra, buscar en dificultad superior
        if(empty($pregunta)) {
            $dificultadSuperior = $this->getDificultadSuperior($dificultadUsuario);
            if ($dificultadSuperior) {
                $pregunta = $this->obtenerPreguntaPorDificultad($preguntasVistas, $dificultadSuperior, $categoria);
            }
        }

        // Si aún no encuentra, buscar cualquier pregunta activa
        if(empty($pregunta)) {
            $pregunta = $this->obtenerPreguntaCualquiera($preguntasVistas, $categoria);
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
        $sql = "SELECT veces_acertada, veces_fallada, ratio_dificultad
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

            // Aplicar suavizado (promedio ponderado)
            $ratioAnterior = $fila['ratio_dificultad'];
            $pesoAnterior = 0.3; // 30% del valor anterior
            $pesoActual = 0.7;   // 70% del nuevo valor

            $ratioSuavizado = ($ratioAnterior * $pesoAnterior) + ($ratio * $pesoActual);

            // Clasificar dificultad según el ratio suavizado
            $dificultad = $this->clasificarDificultad($ratioSuavizado);

            $sql = "UPDATE preguntas 
                    SET ratio_dificultad = $ratioSuavizado, 
                        dificultad = '$dificultad' 
                    WHERE id = $preguntaId";
            $this->conexion->query($sql);
        }
    }

    private function clasificarDificultad($ratio)
    {
        if ($ratio >= 0.7 && $ratio <= 1.0) {
            return 'facil';
        } elseif ($ratio > 0.3 && $ratio < 0.7) {
            return 'intermedia';
        } else {
            return 'dificil';
        }
    }

    private function calcularDificultadUsuario($usuarioId)
    {
        // Obtener estadísticas del usuario de los últimos 30 días
        $sql = "SELECT 
                   COUNT(*) as total_preguntas,
                   SUM(CASE WHEN hp.pregunta_fallada = 0 THEN 1 ELSE 0 END) as correctas
                FROM historial_preguntas hp
                JOIN partidas p ON hp.partida_id = p.id
                WHERE p.usuario_id = $usuarioId
                AND hp.fecha_respuesta >= DATE_SUB(NOW(), INTERVAL 30 DAY)";

        $resultado = $this->conexion->query($sql);
        $estadisticas = $this->getSingleRow($resultado);

        if (empty($estadisticas) || $estadisticas['total_preguntas'] == 0) {
            return 'intermedia';
        }

        $ratio = $estadisticas['correctas'] / $estadisticas['total_preguntas'];

        // Aplicar suavizado (promedio ponderado con valor inicial 0.5)
        $ratioSuavizado = ($ratio * 0.7) + (0.5 * 0.3);

        return $this->clasificarDificultad($ratioSuavizado);
    }

    private function getDificultadSuperior($dificultadActual)
    {
        switch ($dificultadActual) {
            case 'facil': return 'intermedia';
            case 'intermedia': return 'dificil';
            case 'dificil': return null;
            default: return 'intermedia';
        }
    }

    private function obtenerPreguntaPorDificultad($preguntasVistas, $dificultad, $categoria = null)
    {
        $sql = "SELECT p.id AS pregunta_id, 
                   p.texto AS pregunta, 
                   p.dificultad AS dificultad,
                   p.ratio_dificultad AS ratio,
                   c.nombre AS categoria,
                   c.color AS color_categoria,
                   c.imagen AS imagen_categoria
            FROM preguntas p
            JOIN categorias c ON p.categoria_id = c.id
            WHERE p.esta_activa = 1 
              AND p.dificultad = '$dificultad'";

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

    private function obtenerPreguntaCualquiera($preguntasVistas, $categoria = null)
    {
        $sql = "SELECT p.id AS pregunta_id, 
                   p.texto AS pregunta, 
                   p.dificultad AS dificultad,
                   p.ratio_dificultad AS ratio,
                   c.nombre AS categoria,
                   c.color AS color_categoria,
                   c.imagen AS imagen_categoria
            FROM preguntas p
            JOIN categorias c ON p.categoria_id = c.id
            WHERE p.esta_activa = 1";

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

    private function getUltimasPreguntasAcertadas($usuarioId)
    {
        $sql = "SELECT hp.pregunta_id
                FROM historial_preguntas hp
                JOIN partidas p ON hp.partida_id = p.id
                WHERE p.usuario_id = $usuarioId
                AND hp.pregunta_fallada = 0
                ORDER BY hp.fecha_respuesta DESC
                LIMIT 10";

        $resultado = $this->conexion->query($sql);
        $preguntas = $this->getArrayResult($resultado);

        return array_column($preguntas, 'pregunta_id');
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



    public function getCategorias()
    {
        $sql = "SELECT id, nombre, color, imagen FROM categorias ORDER BY nombre ASC";
        return $this->conexion->query($sql);
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

    public function guardarReporte($preguntaId, $usuarioId, $motivo)
    {
        $sql = "INSERT INTO reportes (pregunta_id, usuario_id, motivo) 
            VALUES ($preguntaId, $usuarioId, '$motivo')";

        $this->conexion->query($sql);
    }

}