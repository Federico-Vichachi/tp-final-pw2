<?php
class EditorController
{
    private $renderer;
    private $model;

    public function __construct($editorModel, $renderer)
    {
        $this->model = $editorModel;
        $this->renderer = $renderer;
    }

    public function index()
    {
        $this->verificarRolEditor();
        $preguntas = $this->model->getTodasLasPreguntas();

        if (is_array($preguntas)) {
            foreach ($preguntas as $key => $pregunta) {
                $respuestas = $this->model->getRespuestas($pregunta['id']);

                if (is_array($respuestas)) {
                    foreach ($respuestas as $r_key => $respuesta) {
                        $respuestas[$r_key]['es_correcta'] = isset($respuesta['es_correcta']) ? (bool)$respuesta['es_correcta'] : false;
                    }
                }

                $preguntas[$key]['respuestas'] = $respuestas;
            }
        }

        $this->renderer->render("panelEditor", ["preguntas" => $preguntas]);
    }

    public function verReportes()
    {
        $this->verificarRolEditor();
        $reportes = $this->model->getReportesPendientes();

        if (is_array($reportes)) {
            foreach ($reportes as $key => $reporte) {
                $preguntaId = $reporte['pregunta_id'];
                $respuestas = $this->model->getRespuestas($preguntaId);


                if (is_array($respuestas)) {
                    foreach ($respuestas as $r_key => $respuesta) {
                        $respuestas[$r_key]['es_correcta'] = (bool)$respuesta['es_correcta'];
                    }
                }
                $reportes[$key]['respuestas'] = $respuestas;
            }
        }

        $data = ['reportes' => $reportes];
        $this->renderer->render("reportes", $data);
    }


    public function crearPreguntaForm()
    {
        $this->verificarRolEditor();
        $categorias = $this->model->getCategorias();
        $this->renderer->render("crearPregunta", ["categorias" => $categorias]);
    }

    public function procesarCrearPregunta()
    {
        $this->verificarRolEditor();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $preguntaData = [
                'texto' => $_POST['texto'] ?? '',
                'categoria_id' => $_POST['categoria_id'] ?? 0,
                'respuesta_correcta' => $_POST['respuesta_correcta'] ?? '',
                'respuesta_incorrecta1' => $_POST['respuesta_incorrecta1'] ?? '',
                'respuesta_incorrecta2' => $_POST['respuesta_incorrecta2'] ?? ''
            ];

            $this->model->crearPreguntaCompleta($preguntaData);
        }

        header('Location: /editor/index');
        exit();
    }

    public function eliminarPregunta()
    {
        $this->verificarRolEditor();
        $preguntaId = $_GET['id'] ?? null;
        if ($preguntaId) {
            $this->model->eliminarPregunta($preguntaId);
        }

        header('Location: /editor/index');
        exit();
    }


    private function verificarRolEditor()
    {
        if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'editor') {
            header('Location: /user/lobby');
            exit();
        }
    }

    public function marcarReporteRevisado()
    {
        $this->verificarRolEditor();
        $reporteId = $_GET['id'] ?? null;

        if ($reporteId) {
            $this->model->marcarReporteRevisado($reporteId);
        }

        header('Location: /editor/verReportes');
        exit();
    }

    public function historialReportes()
    {
        $this->verificarRolEditor();
        $reportes = $this->model->getReportesRevisados();
        $data = ['reportes' => $reportes];
        $this->renderer->render("historialReportes", $data);
    }

    public function crearCategoriaForm()
    {
        $this->verificarRolEditor();
        $this->renderer->render("crearCategoria");
    }

    public function procesarCrearCategoria()
    {
        $this->verificarRolEditor();
        if ($_SERVER['REQUEST_METHOD'] === 'POST'){
            $categoriaData =[
                'nombre' => $_POST['nombre'] ?? '',
                'color' => $_POST['color'] ?? '',
                'imagen' => $_POST['imagen'] ?? ''
            ];

            $this->model->crearCategoria($categoriaData);
        }
        header('Location: /editor/index');
        exit();
    }

    public function verSugerencias()
    {
        $this->verificarRolEditor();
        $sugerencias = $this->model->getSugerenciasPendientes();
        $this->renderer->render("sugerenciasPendientes", ["sugerencias" => $sugerencias]);
    }

    public function aprobarSugerencia()
    {
        $this->verificarRolEditor();
        $sugerenciaId = $_GET['id'] ?? null;
        if ($sugerenciaId) {
            $this->model->aprobarSugerencia($sugerenciaId);
        }
        header('Location: /editor/verSugerencias');
        exit();
    }

    public function rechazarSugerencia()
    {
        $this->verificarRolEditor();
        $sugerenciaId = $_GET['id'] ?? null;
        if ($sugerenciaId) {
            $this->model->rechazarSugerencia($sugerenciaId);
        }
        header('Location: /editor/verSugerencias');
        exit();
    }

}