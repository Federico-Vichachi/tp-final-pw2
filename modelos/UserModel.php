<?php
class UserModel
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function getUsuario($user, $password)
    {
        $sql = "SELECT * FROM usuario WHERE username = '$user'";
        $result = $this->conexion->query($sql);

        if (is_array($result) && count($result) > 0) {
            $usuario = $result[0];

            if (password_verify($password, $usuario['password'])
                && $usuario['cuenta_activa'] == 1) {
                return $usuario;
            }
        }

        return null;
    }

    public function registrarUsuario($datos, $file) {
        if (empty($datos['nombre_completo'])
            || empty($datos['email'])
            || empty($datos['password'])
            || empty($datos['username'])) {
            return [
                'ok' => false,
                'errores' => "Todos los campos obligatorios deben estar completos."
            ];
        }

        $validarDatos = "SELECT id 
                     FROM usuario 
                     WHERE email = '{$datos['email']}' 
                        OR username = '{$datos['username']}'";
        $validarResultado = $this->conexion->query($validarDatos);

        if (is_array($validarResultado) && count($validarResultado) > 0) {
            return [
                'ok' => false,
                'errores' => "El email o nombre de usuario ya están registrados."
            ];
        }

        list($codigoValidacion, $cuentaActiva, $rol, $fechaRegistro) = $this->prepararDatos();
        $rutaFoto = $this->procesarImagen($file);

        $sqlInsert = $this->queryRegistrar($datos, $rutaFoto, $codigoValidacion, $cuentaActiva, $rol, $fechaRegistro);
        $resultadoInsert = $this->conexion->query($sqlInsert);
        return $this->errores($resultadoInsert);
    }

    public function prepararDatos()
    {
        $codigoValidacion = uniqid();
        $cuentaActiva = 0;
        $rol = "usuario";
        $fechaRegistro = date('Y-m-d H:i:s');
        return array($codigoValidacion, $cuentaActiva, $rol, $fechaRegistro);
    }

    public function procesarImagen($file)
    {
        $rutaFoto = "NULL";
        if ($file && $file['tmp_name']) {
            $nombreArchivo = uniqid() . "_" . $file['name'];
            $rutaDestino = __DIR__ . "/../public/imagenes/" . $nombreArchivo;

            if (!is_dir(dirname($rutaDestino))) {
                mkdir(dirname($rutaDestino), 0755, true);
            }

            if (move_uploaded_file($file['tmp_name'], $rutaDestino)) {
                $rutaFoto = "'public/imagenes/$nombreArchivo'";
            }
        }
        return $rutaFoto;
    }

    public function queryRegistrar(
        $datos,
        string $rutaFoto,
        $codigoValidacion,
        $cuentaActiva,
        $rol,
        $fechaRegistro)
    {
        $passwordHash = password_hash($datos['password'], PASSWORD_BCRYPT);
        $sql = "INSERT INTO usuario (
                    nombre_completo, anio_nacimiento, sexo, pais, ciudad, 
                    email, password, username, foto_perfil, codigo_validacion, 
                    cuenta_activa, rol, fecha_registro
                ) VALUES (
                    '{$datos['nombre_completo']}',
                    '{$datos['anio_nacimiento']}',
                    '{$datos['sexo']}',
                    '{$datos['pais']}',
                    '{$datos['ciudad']}',
                    '{$datos['email']}',
                    '$passwordHash',
                    '{$datos['username']}',
                    $rutaFoto,
                    '$codigoValidacion',
                    $cuentaActiva,
                    '$rol',
                    '$fechaRegistro'
                )";
        return $sql;
    }

    public function errores($resultado)
    {
        if ($resultado === true) {
            return [
                'ok' => true,
                'errores' => []
                ];
        } else {
            return [
                'ok' => false,
                'errores' => "Error al registrar el usuario en la base de datos."];
        }
    }
}