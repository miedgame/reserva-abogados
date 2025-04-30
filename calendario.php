<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Reserva - Estudio de Abogados</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Estudio Jurídico - Sistema de Reserva de Citas</h1>
        </header>
        <main>
            <section id="instrucciones">
                <h2>Bienvenido al sistema de reserva de citas</h2>
                <p>En esta lista podras visualizar los horarios que han sido reservados por los usuarios en el formulario.</p>
            </section>
            <section id="reservas">
                <h2>Reservas Realizadas</h2>
                <div id="reservas-container">
                    <!-- El calendario se cargará dinámicamente con JavaScript -->
                    <p>Cargando reservas realizadas...</p>
                </div>
            </section>
    </div>
    <script src="js/reservas.js"></script>
</body>
</html>