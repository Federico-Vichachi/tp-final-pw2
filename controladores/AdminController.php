<?php

use Dompdf\Dompdf;
use Dompdf\Options;

class AdminController
{
    private $renderer;
    private $model;

    public function __construct($adminModel, $renderer)
    {
        $this->model = $adminModel;
        $this->renderer = $renderer;
    }

    public function panel()
    {
        $this->verificarRolAdmin();

        $preguntasPorCategoria = $this->model->getPreguntasPorCategoria();
        $usuariosPorPais = $this->model->getUsuariosPorPais();
        $registrosNuevos = $this->model->getRegistrosNuevosPorDia();

        $datosParaGraficoCategorias = [['Categoría', 'Cantidad']];
        foreach ($preguntasPorCategoria as $item) {
            $datosParaGraficoCategorias[] = [$item['nombre'], (int)$item['cantidad']];
        }

        $datosParaGraficoPaises = [['País', 'Usuarios']];
        foreach ($usuariosPorPais as $item) {
            $datosParaGraficoPaises[] = [$item['pais'], (int)$item['cantidad']];
        }

        $datosParaGraficoRegistros = [['Fecha', 'Registros']];
        foreach ($registrosNuevos as $item) {
            $datosParaGraficoRegistros[] = [date('d/m', strtotime($item['fecha'])), (int)$item['cantidad']];
        }

        $datos = [
            'jsonDataCategorias' => json_encode($datosParaGraficoCategorias),
            'jsonDataPaises' => json_encode($datosParaGraficoPaises),
            'jsonDataRegistros' => json_encode($datosParaGraficoRegistros),
            'totalUsuarios' => $this->model->getConteoTotalUsuarios(),
            'totalPreguntas' => $this->model->getConteoTotalPreguntas(),
            'partidasHoy' => $this->model->getPartidasJugadasHoy()
        ];

        // Agregar datos del usuario y rol para el header
        if (isset($_SESSION['usuario'])) {
            $datos['usuario'] = $_SESSION['usuario'];
            $datos['es_editor'] = ($_SESSION['usuario']['rol'] === 'editor');
            $datos['es_admin'] = true;
        }

        $this->renderer->render("panelAdmin", $datos);
    }

    private function verificarRolAdmin()
    {
        if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'administrador') {
            header('Location: /');
            exit();
        }
    }

    public function generarReportePDF()
    {
        $this->verificarRolAdmin();

        $datos = [
            'totalUsuarios' => $this->model->getConteoTotalUsuarios(),
            'totalPreguntas' => $this->model->getConteoTotalPreguntas(),
            'partidasHoy' => $this->model->getPartidasJugadasHoy(),
            'fechaGeneracion' => date("d/m/Y H:i")
        ];

        // Agregar datos del usuario y rol para el header
        if (isset($_SESSION['usuario'])) {
            $datos['usuario'] = $_SESSION['usuario'];
            $datos['es_editor'] = ($_SESSION['usuario']['rol'] === 'editor');
            $datos['es_admin'] = true;
        }

        $preguntasPorCategoria = $this->model->getPreguntasPorCategoria();
        $usuariosPorPais = $this->model->getUsuariosPorPais();
        $registrosNuevos = $this->model->getRegistrosNuevosPorDia();

        $datos['urlGraficoCategorias'] = $this->generarUrlGraficoDona($preguntasPorCategoria);
        $datos['urlGraficoPaises'] = $this->generarUrlGraficoBarras($usuariosPorPais);
        $datos['urlGraficoRegistros'] = $this->generarUrlGraficoLineas($registrosNuevos);

        $html = $this->renderer->generateHtml('vistas/reporteAdminPDFVista.mustache', $datos, false);

        $options = new Options();
        $options->set('isRemoteEnabled', true);

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);

        $dompdf = new Dompdf($options);
        $dompdf->setHttpContext($context);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $fecha = date("Y-m-d");
        $dompdf->stream("reporte-preguntados-{$fecha}.pdf", ["Attachment" => true]);
        exit();
    }

    private function generarUrlGraficoDona($data)
    {
        if (empty($data)) return "";

        $labels = [];
        $values = [];
        foreach ($data as $row) {
            $labels[] = "'" . addslashes($row['nombre']) . "'";
            $values[] = $row['cantidad'];
        }

        $chartConfig = "{
        type: 'doughnut',
        data: {
            labels: [" . implode(',', $labels) . "],
            datasets: [{
                data: [" . implode(',', $values) . "],
                backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF']
            }]
        }
    }";

        $encodedConfig = urlencode($chartConfig);
        return "https://quickchart.io/chart?c=" . $encodedConfig;
    }

    private function generarUrlGraficoBarras($data)
    {
        if (empty($data)) return "";

        $labels = [];
        $values = [];
        foreach ($data as $row) {
            $labels[] = "'" . $row['pais'] . "'";
            $values[] = $row['cantidad'];
        }

        $chartConfig = "{
            type: 'horizontalBar',
            data: {
                labels: [" . implode(',', $labels) . "],
                datasets: [{
                    label: 'Usuarios',
                    data: [" . implode(',', $values) . "],
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                legend: { display: false },
                layout: {
                    padding: {
                        left: 100,  
                        right: 30,
                        top: 30,
                        bottom: 30
                    }
                },
                scales: {
                    xAxes: [{
                        ticks: {
                            beginAtZero: true,
                            fontColor: '#000', 
                            fontSize: 12
                        }
                    }],
                    yAxes: [{
                        ticks: {
                            fontColor: '#000', 
                            fontSize: 12,
                            mirror: false 
                        }
                    }]
                }
            }
        }";

        $encodedConfig = urlencode($chartConfig);
        return "https://quickchart.io/chart?c=" . $encodedConfig . "&width=700&height=400";
    }

    private function generarUrlGraficoLineas($data)
    {
        if (empty($data)) return "";

        $labels = [];
        $values = [];
        foreach ($data as $row) {
            $labels[] = "'" . date('d/m', strtotime($row['fecha'])) . "'";
            $values[] = $row['cantidad'];
        }

        $chartConfig = "{
            type: 'line',
            data: {
                labels: [" . implode(',', $labels) . "],
                datasets: [{
                    label: 'Nuevos Registros',
                    data: [" . implode(',', $values) . "],
                    fill: false,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                layout: {
                    padding: {
                        left: 20,
                        right: 20,
                        top: 20,
                        bottom: 20 
                    }
                },
                scales: {
                    xAxes: [{
                        ticks: {
                            fontColor: '#000',
                            fontSize: 12
                        }
                    }],
                    yAxes: [{
                        ticks: {
                            beginAtZero: true,
                            stepSize: 1, 
                            fontColor: '#000'
                        }
                    }]
                }
            }
        }";

        $encodedConfig = urlencode($chartConfig);
        return "https://quickchart.io/chart?c=" . $encodedConfig . "&width=700&height=300";
    }

}