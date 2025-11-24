<?php


class EditorModel
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function getTodasLasPreguntas()
    {
        $sql = "SELECT p.id, p.texto, c.nombre as categoria, c.color as categoria_color
                FROM preguntas p 
                JOIN categorias c ON p.categoria_id = c.id
                ORDER BY p.id DESC";
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

    public function marcarReporteRevisado($reporteId)
    {
        $sql = "UPDATE reportes SET estado = 'revisado' WHERE id = $reporteId";
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

        $resultado = $this->conexion->query($sql);
        return $this->getArrayResult($resultado);
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
        $resultado = $this->conexion->query($sql);
        return $this->getArrayResult($resultado);
    }


    public function getSugerenciasPendientes()
    {
        $sql = "SELECT ps.*, u.username as sugerido_por
            FROM preguntas_sugeridas ps
            JOIN usuario u ON ps.usuario_id = u.id
            WHERE ps.estado = 'pendiente'
            ORDER BY ps.fecha_sugerencia ASC";

        $resultado = $this->conexion->query($sql);
        return $this->getArrayResult($resultado);
    }

    public function rechazarSugerencia($sugerenciaId)
    {
        $sql = "UPDATE preguntas_sugeridas SET estado = 'rechazada' WHERE id = $sugerenciaId";
        return $this->conexion->query($sql);
    }

    public function aprobarSugerencia($sugerenciaId)
    {
        $sqlSugerencia = "SELECT * FROM preguntas_sugeridas WHERE id = $sugerenciaId";
        $sugerencia = $this->getSingleRow($this->conexion->query($sqlSugerencia));

        if (empty($sugerencia)) return false;

        $sqlCategoria = "SELECT id FROM categorias WHERE nombre = '{$sugerencia['categoria_nombre']}'";
        $categoria = $this->getSingleRow($this->conexion->query($sqlCategoria));

        if (empty($categoria)) {
            $this->conexion->query("INSERT INTO categorias (nombre) VALUES ('{$sugerencia['categoria_nombre']}')");
            $res = $this->conexion->query("SELECT LAST_INSERT_ID() as id");
            $row = $this->getSingleRow($res);
            $categoriaId = $row['id'];
        } else {
            $categoriaId = $categoria['id'];
        }

        $this->crearPreguntaCompleta([
            'texto' => $sugerencia['texto_pregunta'],
            'categoria_id' => $categoriaId,
            'respuesta_correcta' => $sugerencia['respuesta_correcta'],
            'respuesta_incorrecta1' => $sugerencia['respuesta_incorrecta1'],
            'respuesta_incorrecta2' => $sugerencia['respuesta_incorrecta2']
        ]);

        $sqlMarcar = "UPDATE preguntas_sugeridas SET estado = 'aprobada' WHERE id = $sugerenciaId";
        $this->conexion->query($sqlMarcar);

        return true;
    }




    public function getCategorias()
    {
        $sql = "SELECT id, nombre FROM categorias ORDER BY nombre ASC";
        return $this->conexion->query($sql);
    }

    public function getRespuestas($id_pregunta)
    {
        $sql = "SELECT id, texto, es_correcta FROM respuestas WHERE pregunta_id = '$id_pregunta'";
        $resultado = $this->conexion->query($sql);

        return $this->getArrayResult($resultado);
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
    public function crearCategoria($data)
    {
        $nombre = $data['nombre'];
        $color = $data['color'];
        $imagen = $data['imagen'];

        $sql = "INSERT INTO categorias (nombre, color, imagen) VALUES ('$nombre', '$color', '$imagen')";
        $this->conexion->query($sql);
        return true;

    }

    public function getPreguntaPorId($id)
    {
        $sql = "SELECT * FROM preguntas WHERE id = " . (int)$id;
        $pregunta = $this->getSingleRow($this->conexion->query($sql));

        if (empty($pregunta)) return null;

        $respuestas = $this->getRespuestas($id);

        foreach ($respuestas as $resp) {
            if ($resp['es_correcta'] == 1) {
                $pregunta['respuesta_correcta'] = $resp['texto'];
            } else {
                if (!isset($pregunta['respuesta_incorrecta1'])) {
                    $pregunta['respuesta_incorrecta1'] = $resp['texto'];
                } else {
                    $pregunta['respuesta_incorrecta2'] = $resp['texto'];
                }
            }
        }

        return $pregunta;
    }

    public function actualizarPregunta($data)
    {
        $id = (int)$data['id'];
        $texto = $data['texto'];
        $categoria_id = (int)$data['categoria_id'];

        $sqlPregunta = "UPDATE preguntas SET texto = '$texto', categoria_id = $categoria_id WHERE id = $id";
        $this->conexion->query($sqlPregunta);


        $this->conexion->query("DELETE FROM respuestas WHERE pregunta_id = $id");

        $resp_correcta = $data['respuesta_correcta'];
        $resp_incorrecta1 = $data['respuesta_incorrecta1'];
        $resp_incorrecta2 = $data['respuesta_incorrecta2'];

        $this->conexion->query("INSERT INTO respuestas (pregunta_id, texto, es_correcta) VALUES ($id, '$resp_correcta', 1)");
        $this->conexion->query("INSERT INTO respuestas (pregunta_id, texto, es_correcta) VALUES ($id, '$resp_incorrecta1', 0)");
        $this->conexion->query("INSERT INTO respuestas (pregunta_id, texto, es_correcta) VALUES ($id, '$resp_incorrecta2', 0)");

        return true;
    }

}