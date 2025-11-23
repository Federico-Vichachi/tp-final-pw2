<?php


class AdminModel
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function getConteoTotalUsuarios()
    {
        $sql = "SELECT COUNT(*) as total FROM usuario WHERE rol = 'usuario'";
        $result = $this->conexion->query($sql);
        return $result[0]['total'] ?? 0;
    }

    public function getConteoTotalPreguntas()
    {
        $sql = "SELECT COUNT(*) as total FROM preguntas WHERE esta_activa = 1";
        $result = $this->conexion->query($sql);
        return $result[0]['total'] ?? 0;
    }

    public function getPartidasJugadasHoy()
    {
        $sql = "SELECT COUNT(*) as total FROM partidas WHERE DATE(fecha_inicio) = CURDATE()";
        $result = $this->conexion->query($sql);
        return $result[0]['total'] ?? 0;
    }


    public function getPreguntasPorCategoria()
    {
        $sql = "SELECT c.nombre, COUNT(p.id) as cantidad 
                FROM preguntas p
                JOIN categorias c ON p.categoria_id = c.id
                WHERE p.esta_activa = 1
                GROUP BY c.nombre
                ORDER BY cantidad DESC";
        return $this->conexion->query($sql);
    }

    public function getUsuariosPorPais()
    {
        $sql = "SELECT pais, COUNT(id) as cantidad
                FROM usuario
                WHERE rol = 'usuario' AND pais IS NOT NULL AND pais != ''
                GROUP BY pais
                ORDER BY cantidad DESC
                LIMIT 10";
        return $this->conexion->query($sql);
    }

    public function getRegistrosNuevosPorDia($dias = 7)
    {
        $sql = "SELECT DATE(fecha_registro) as fecha, COUNT(id) as cantidad
                FROM usuario
                WHERE fecha_registro >= CURDATE() - INTERVAL " . intval($dias) . " DAY
                GROUP BY fecha
                ORDER BY fecha ASC";
        return $this->conexion->query($sql);
    }
}