<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2">Panel de Administrador (Google Charts)</h1>
        <div>
            <a href="/admin/generarReportePDF" class="btn btn-success">Descargar Reporte en PDF</a>
            <a href="/user/lobby?action=salir" class="btn btn-outline-danger">Cerrar Sesión</a>
        </div>
    </div>

    <div class="row mb-4">
    </div>

    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header">Nuevos Registros (Últimos 7 días)</div>
                <div class="card-body">
                    <div id="registros_chart_div" style="width: 100%; height: 300px;"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header">Preguntas por Categoría</div>
                <div class="card-body">
                    <div id="categorias_chart_div" style="width: 100%; height: 300px;"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12 mb-4">
            <div class="card shadow">
                <div class="card-header">Top 10 Países con más Usuarios</div>
                <div class="card-body">
                    <div id="paises_chart_div" style="width: 100%; height: 400px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

<script type="text/javascript">
    google.charts.load('current', {'packages':['corechart']});

    google.charts.setOnLoadCallback(drawAllCharts);

    function drawAllCharts() {
        drawCategoriasChart();
        drawPaisesChart();
        drawRegistrosChart();
    }

    function drawCategoriasChart() {
        const jsonData = JSON.parse(`{{{jsonDataCategorias}}}`);
        const data = google.visualization.arrayToDataTable(jsonData);

        const options = {
            title: 'Distribución de Preguntas',
            pieHole: 0.4,
        };

        const chart = new google.visualization.PieChart(document.getElementById('categorias_chart_div'));
        chart.draw(data, options);
    }

    function drawPaisesChart() {
        const jsonData = JSON.parse(`{{{jsonDataPaises}}}`);
        const data = google.visualization.arrayToDataTable(jsonData);

        const options = {
            title: 'Usuarios por País',
            legend: { position: 'none' },
            chartArea: {width: '50%'},
            hAxis: {
                title: 'Total Usuarios',
                minValue: 0
            },
            vAxis: {
                title: 'País'
            }
        };
        const chart = new google.visualization.BarChart(document.getElementById('paises_chart_div'));
        chart.draw(data, options);
    }

    function drawRegistrosChart() {
        const jsonData = JSON.parse(`{{{jsonDataRegistros}}}`);
        const data = google.visualization.arrayToDataTable(jsonData);

        const options = {
            title: 'Evolución de Registros',
            curveType: 'function',
            legend: { position: 'bottom' }
        };

        const chart = new google.visualization.LineChart(document.getElementById('registros_chart_div'));
        chart.draw(data, options);
    }
</script>