<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IP Monitor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 50;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.75);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            width: 25%;
            max-width: 400px;
            margin: auto;
        }

        /* Sidebar styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: -320px;
            width: 320px;
            height: 100vh;
            background-color: #1f2937;
            color: white;
            transition: left 0.3s ease;
            z-index: 1000;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.3);
        }

        .sidebar.open {
            left: 0;
        }

        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
        }

        .sidebar-overlay.show {
            display: block;
        }

        .main-content {
            transition: margin-left 0.3s ease;
        }

        .sidebar-toggle {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background-color: #3b82f6;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .sidebar-toggle:hover {
            background-color: #2563eb;
            transform: scale(1.05);
        }
    </style>
    <script>
        let pingInterval = <?php echo $ping_interval; ?>;
        let countdown = pingInterval;

        function reloadPage() {
            updateCards();
        }

        function updateCards() {
            // Mostrar indicador de carga
            const cardsContainer = document.querySelector('#cards-container');
            if (cardsContainer) {
                cardsContainer.style.opacity = '0.7';
            }

            // Actualizar solo las cards via AJAX
            fetch(window.location.pathname + '?ajax=1')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    
                    // Actualizar cards
                    const newCardsContainer = doc.querySelector('#cards-container');
                    const currentCardsContainer = document.querySelector('#cards-container');
                    
                    if (newCardsContainer && currentCardsContainer) {
                        currentCardsContainer.innerHTML = newCardsContainer.innerHTML;
                        currentCardsContainer.style.opacity = '1';
                    }
                    
                    // Actualizar también el resumen del sidebar si está abierto
                    const newSummary = doc.querySelector('#system-summary');
                    const currentSummary = document.querySelector('#system-summary');
                    
                    if (newSummary && currentSummary) {
                        currentSummary.innerHTML = newSummary.innerHTML;
                    }
                })
                .catch(error => {
                    console.error('Error updating cards:', error);
                    // Restaurar opacidad en caso de error
                    if (cardsContainer) {
                        cardsContainer.style.opacity = '1';
                    }
                    // Opcionalmente mostrar una notificación al usuario
                    // alert('Error al actualizar los datos. Intentando nuevamente...');
                });
        }

        // Temporizador para recargar la página a intervalos regulares
        setInterval(updateCountdown, 1000);

        function updateTimer(event) {
            event.preventDefault(); // Evitar el envío del formulario
            const newTimerValue = parseInt(document.getElementById('new_timer_value').value, 10);
            if (newTimerValue > 0) {
                pingInterval = newTimerValue; // Actualizar el valor global del temporizador
                countdown = pingInterval; // Reiniciar el contador con el nuevo valor
                hideChangeTimerForm(); // Cerrar la ventana modal
            } else {
                alert("Please enter a valid number greater than 0.");
            }
        }

        // Funciones para el sidebar
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            sidebar.classList.toggle('open');
            overlay.classList.toggle('show');
        }

        function closeSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            sidebar.classList.remove('open');
            overlay.classList.remove('show');
        }

        // Variable para controlar si las actualizaciones están pausadas
        let updatesPaused = false;

        // Función para pausar actualizaciones
        function pauseUpdates() {
            updatesPaused = true;
        }

        // Función para reanudar actualizaciones
        function resumeUpdates() {
            updatesPaused = false;
        }

        // Modificar la función updateCountdown para respetar la pausa
        function updateCountdown() {
            document.getElementById('countdown').innerText = countdown;
            countdown--;
            if (countdown < 0) {
                countdown = pingInterval;
                if (!updatesPaused) {
                    updateCards(); // Solo actualizar si no está pausado
                }
            }
        }

        // Funciones modificadas para modales que pausan actualizaciones
        function showAddIpForm() {
            pauseUpdates();
            document.getElementById('addIpForm').style.display = 'flex';
        }

        function hideAddIpForm() {
            resumeUpdates();
            document.getElementById('addIpForm').style.display = 'none';
        }

        function showAddServiceForm() {
            pauseUpdates();
            document.getElementById('addServiceForm').style.display = 'flex';
        }

        function hideAddServiceForm() {
            resumeUpdates();
            document.getElementById('addServiceForm').style.display = 'none';
        }

        function showChangeTimerForm() {
            pauseUpdates();
            document.getElementById('changeTimerForm').style.display = 'flex';
        }

        function hideChangeTimerForm() {
            resumeUpdates();
            document.getElementById('changeTimerForm').style.display = 'none';
        }

        function showChangePingAttemptsForm() {
            pauseUpdates();
            document.getElementById('changePingAttemptsForm').style.display = 'flex';
        }

        function hideChangePingAttemptsForm() {
            resumeUpdates();
            document.getElementById('changePingAttemptsForm').style.display = 'none';
        }

        function showClearDataConfirmation() {
            pauseUpdates();
            document.getElementById('clearDataConfirmation').style.display = 'flex';
        }

        function hideClearDataConfirmation() {
            resumeUpdates();
            document.getElementById('clearDataConfirmation').style.display = 'none';
        }

        function confirmDelete(ip) {
            pauseUpdates();
            document.getElementById('deleteIpForm').style.display = 'flex';
            document.getElementById('delete_ip').value = ip;
        }

        function hideDeleteIpForm() {
            resumeUpdates();
            document.getElementById('deleteIpForm').style.display = 'none';
        }

    </script>
</head>

<body class="bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200">
    <!-- Botón para abrir sidebar -->
    <button class="sidebar-toggle" onclick="toggleSidebar()">
        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
    </button>

    <!-- Overlay para cerrar sidebar -->
    <div id="sidebarOverlay" class="sidebar-overlay" onclick="closeSidebar()"></div>

    <!-- Sidebar -->
    <div id="sidebar" class="sidebar">
        <div class="p-6">
            <!-- Header del sidebar -->
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold">IP Monitor Panel</h2>
                <button onclick="closeSidebar()" class="text-gray-400 hover:text-white">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Resumen del sistema -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold mb-4 text-blue-300">System Summary</h3>
                <div id="system-summary" class="bg-gray-800 rounded-lg p-4 space-y-3">
                    <?php
                    // Variables para el resumen
                    $total_ips = count($ips_to_monitor);
                    $ips_up = 0;
                    $ips_down = 0;
                    $total_ping = 0;
                    $ping_count = 0;

                    foreach ($ips_to_monitor as $ip => $service) {
                        $result = analyze_ip($ip);
                        if ($result['status'] === "UP") {
                            $ips_up++;
                        } else {
                            $ips_down++;
                        }

                        if ($result['average_response_time'] !== 'N/A') {
                            $total_ping += $result['average_response_time'];
                            $ping_count++;
                        }
                    }

                    $average_ping = $ping_count > 0 ? round($total_ping / $ping_count, 2) : 'N/A';
                    $system_status = $ips_down === 0 ? "Healthy" : ($ips_up > $ips_down ? "Degraded" : "Critical");
                    $system_status_color = $system_status === "Healthy" ? "text-green-400" : ($system_status === "Degraded" ? "text-yellow-400" : "text-red-400");
                    ?>
                    <div class="flex justify-between">
                        <span class="text-gray-300">Total IPs:</span>
                        <span class="font-bold"><?php echo $total_ips; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-300">UP:</span>
                        <span class="font-bold text-green-400"><?php echo $ips_up; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-300">DOWN:</span>
                        <span class="font-bold text-red-400"><?php echo $ips_down; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-300">Avg Ping:</span>
                        <span class="font-bold"><?php echo $average_ping !== 'N/A' ? $average_ping . " ms" : "N/A"; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-300">Status:</span>
                        <span class="font-bold <?php echo $system_status_color; ?>"><?php echo $system_status; ?></span>
                    </div>
                </div>
            </div>

            <!-- Contador de próximo ping -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold mb-4 text-blue-300">Next Check</h3>
                <div class="bg-gray-800 rounded-lg p-4 text-center">
                    <p class="text-gray-300 mb-2">Next ping in</p>
                    <span id="countdown" class="text-2xl font-bold text-yellow-400"><?php echo $ping_interval; ?></span>
                    <p class="text-gray-300 text-sm">seconds</p>
                    <button onclick="reloadPage();" class="mt-3 w-full bg-orange-500 text-white px-4 py-2 rounded-lg hover:bg-orange-600 transition duration-300">
                        Check Now
                    </button>
                </div>
            </div>

            <!-- Botones de acción -->
            <div class="space-y-4">
                <h3 class="text-lg font-semibold mb-4 text-blue-300">Actions</h3>
                
                <button onclick="showAddServiceForm(); closeSidebar();" class="w-full bg-blue-500 text-white px-4 py-3 rounded-lg hover:bg-blue-600 transition duration-300 font-medium">
                    Add Service
                </button>
                
                <button onclick="showAddIpForm(); closeSidebar();" class="w-full bg-blue-500 text-white px-4 py-3 rounded-lg hover:bg-blue-600 transition duration-300 font-medium">
                    Add IP
                </button>
                
                <button onclick="showChangeTimerForm(); closeSidebar();" class="w-full bg-teal-500 text-white px-4 py-3 rounded-lg hover:bg-teal-600 transition duration-300 font-medium">
                    Change Timer
                </button>
                
                <button onclick="showChangePingAttemptsForm(); closeSidebar();" class="w-full bg-teal-500 text-white px-4 py-3 rounded-lg hover:bg-teal-600 transition duration-300 font-medium">
                    Change Ping History
                </button>
                
                <button onclick="showClearDataConfirmation(); closeSidebar();" class="w-full bg-red-500 text-white px-4 py-3 rounded-lg hover:bg-red-600 transition duration-300 font-medium">
                    Clear Data
                </button>
            </div>
        </div>
    </div>

    <!-- Contenido principal -->
    <div class="main-content">
        <div class="container mx-auto p-4 pt-16">
            <h1 class="text-4xl font-bold text-center mb-8">Monitoreo IP - Soporte Atika</h1>
            
            <!-- Solo las cards de IPs -->
            <div id="cards-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-6">
                <?php foreach ($ips_to_monitor as $ip => $service): ?>
                    <?php
                    $result = analyze_ip($ip);
                    $status = $result['status'];
                    $percentage = $result['percentage'];
                    $label = $result['label'];
                    $ping_results = $result['ping_results'];
                    $average_response_time = $result['average_response_time'];

                    $status_color = $status === "UP" ? "bg-green-500" : "bg-red-500";
                    $label_color = $label === "Good" ? "bg-green-400" : ($label === "Stable" ? "bg-yellow-400" : "bg-red-400");
                    $service_color = $services[$service] ?? $services["DEFAULT"];

                    // Determinar el color del percentage
                    if ($percentage !== 'N/A') {
                        $percentage = round($percentage, 1);
                        if ($percentage > 80) {
                            $response_time_percentage = "text-green-500";
                        } elseif ($percentage > 60) {
                            $response_time_percentage = "text-yellow-500";
                        } else {
                            $response_time_percentage = "text-red-500";
                        }
                    } else {
                        $response_time_percentage = "text-gray-500";
                    }

                    // Determinar el color del tiempo de respuesta
                    if ($average_response_time !== 'N/A') {
                        $average_response_time = round($average_response_time, 1);
                        if ($average_response_time < 50) {
                            $response_time_color = "text-green-500";
                        } elseif ($average_response_time < 100) {
                            $response_time_color = "text-yellow-500";
                        } else {
                            $response_time_color = "text-red-500";
                        }
                    } else {
                        $response_time_color = "text-gray-500";
                    }
                    ?>
                    
                    <!-- Card individual -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg hover:shadow-xl transition-shadow duration-300 p-6 border border-gray-200 dark:border-gray-700">
                        <!-- Header del servicio -->
                        <div class="mb-4">
                            <div class="inline-block px-3 py-1 rounded-full text-white font-semibold text-sm mb-2"
                                 style="background-color: <?php echo $service_color; ?>">
                                <?php echo $service; ?>
                            </div>
                            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200"><?php echo $ip; ?></h3>
                        </div>
                        
                        <!-- Status principal -->
                        <div class="flex justify-between items-center mb-4">
                            <button type="button"
                                    class="bg-red-500 text-white px-3 py-1 rounded-full hover:bg-red-600 transition duration-300 text-sm font-medium"
                                    onclick="confirmDelete('<?php echo $ip; ?>')">
                                Delete IP
                            </button>
                            <span class="<?php echo $label_color; ?> text-white px-3 py-1 rounded-full font-bold text-sm">
                                <?php echo $label; ?>
                            </span>
                        </div>
                        
                        <!-- Métricas -->
                        <div class="space-y-3 mb-4">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Uptime:</span>
                                <span class="font-bold <?php echo $response_time_percentage; ?>">
                                    <?php echo $percentage; ?>%
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Avg Ping:</span>
                                <span class="font-bold <?php echo $response_time_color; ?>">
                                    <?php echo $average_response_time . " ms"; ?>
                                </span>
                            </div>
                        </div>
                        
                        <!-- Historial de pings -->
                        <div class="mb-4">
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">Ping History:</div>
                            <div class="flex justify-center space-x-1">
                                <?php foreach ($ping_results as $ping): ?>
                                    <div class="w-3 h-3 rounded-full <?php echo ($ping['status'] ?? "-") === "UP" ? "bg-green-500" : "bg-red-500"; ?>"
                                         title="<?php echo $ping['timestamp'] ?? "-"; ?>">
                                    </div>
                                <?php endforeach; ?>
                                <?php for ($i = count($ping_results); $i < $ping_attempts; $i++): ?>
                                    <div class="w-3 h-3 rounded-full bg-gray-300" title="No data"></div>
                                <?php endfor; ?>
                            </div>
                        </div>
                        
                        <!-- Botón de estado prominente -->
                        <div class="text-center">
                            <span class="<?php echo $status_color; ?> text-white px-4 py-2 rounded-lg font-bold text-sm w-full inline-block">
                                <?php echo $status; ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Modales y formularios -->

            <div id="addIpForm" class="modal">
                <div class="modal-content">
                    <h2 class="text-2xl font-bold mb-4">Add New IP</h2>
                    <form method="POST" action="">
                        <div class="mb-4">
                            <label for="new_service" class="block text-gray-700">Service</label>
                            <select id="new_service" name="new_service"
                                class="w-full p-2 border border-gray-300 rounded mt-1" required>
                                <option value="" disabled selected>Select a service</option>
                                <?php foreach ($services as $service_name => $color): ?>
                                    <?php if ($service_name !== "DEFAULT"): ?>
                                        <option value="<?php echo htmlspecialchars($service_name, ENT_QUOTES, 'UTF-8'); ?>"
                                            style="background-color: <?php echo htmlspecialchars($color, ENT_QUOTES, 'UTF-8'); ?>; color: #fff;">
                                            <?php echo htmlspecialchars($service_name, ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="new_ip" class="block text-gray-700">IP Address</label>
                            <input type="text" id="new_ip" name="new_ip"
                                class="w-full p-2 border border-gray-300 rounded mt-1" required>
                        </div>
                        <div class="flex justify-end">
                            <button type="button" onclick="hideAddIpForm();"
                                class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-700 transition duration-300 mr-2">Cancel</button>
                            <button type="submit" name="add_ip"
                                class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-700 transition duration-300">Add</button>
                        </div>
                    </form>
                </div>
            </div>
            <div id="addServiceForm" class="modal">
                <div class="modal-content">
                    <h2 class="text-2xl font-bold mb-4">Add New Service</h2>
                    <form method="POST" action="">
                        <div class="mb-4">
                            <label for="new_service_name" class="block text-gray-700">Service Name</label>
                            <input type="text" id="new_service_name" name="new_service_name"
                                class="w-full p-2 border border-gray-300 rounded mt-1" required>
                        </div>
                        <div class="mb-4">
                            <label for="new_service_color" class="block text-gray-700">Service Color</label>
                            <input type="color" id="new_service_color" name="new_service_color"
                                class="w-full p-2 border border-gray-300 rounded mt-1" required>
                        </div>
                        <div class="flex justify-end">
                            <button type="button" onclick="hideAddServiceForm();"
                                class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-700 transition duration-300 mr-2">Cancel</button>
                            <button type="submit" name="add_service"
                                class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-700 transition duration-300">Add</button>
                        </div>
                    </form>
                </div>
            </div>
            <div id="deleteIpForm" class="modal">
                <div class="modal-content">
                    <h2 class="text-2xl font-bold mb-4">Delete IP</h2>
                    <form method="POST" action="">
                        <input type="hidden" id="delete_ip" name="delete_ip">
                        <p>Are you sure you want to delete this IP?</p>
                        <div class="flex justify-end mt-4">
                            <button type="button" onclick="hideDeleteIpForm();"
                                class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-700 transition duration-300 mr-2">Cancel</button>
                            <button type="submit"
                                class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-700 transition duration-300">Delete</button>
                        </div>
                    </form>
                </div>
            </div>
            <div id="changeTimerForm" class="modal">
                <div class="modal-content">
                    <h2 class="text-2xl font-bold mb-4">Change Timer Interval</h2>
                    <form method="POST" action="">
                        <div class="mb-4">
                            <label for="new_timer_value" class="block text-gray-700">New Timer Value (seconds):</label>
                            <input type="number" id="new_timer_value" name="new_timer_value"
                                class="w-full p-2 border border-gray-300 rounded mt-1"
                                value="<?php echo $ping_interval; ?>" min="1" required>
                        </div>
                        <div class="flex justify-end">
                            <button type="button" onclick="hideChangeTimerForm();"
                                class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-700 transition duration-300 mr-2">Cancel</button>
                            <button type="submit" name="change_timer"
                                class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-700 transition duration-300">Update</button>
                        </div>
                    </form>
                </div>
            </div>
            <div id="changePingAttemptsForm" class="modal">
                <div class="modal-content">
                    <h2 class="text-2xl font-bold mb-4">Change Ping History</h2>
                    <form method="POST" action="">
                        <div class="mb-4">
                            <label for="new_ping_attempts" class="block text-gray-700">New Ping History:</label>
                            <input type="number" id="new_ping_attempts" name="new_ping_attempts"
                                class="w-full p-2 border border-gray-300 rounded mt-1"
                                value="<?php echo $ping_attempts; ?>" min="1" required>
                        </div>
                        <div class="flex justify-end">
                            <button type="button" onclick="hideChangePingAttemptsForm();"
                                class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-700 transition duration-300 mr-2">Cancel</button>
                            <button type="submit" name="change_ping_attempts"
                                class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-700 transition duration-300">Update</button>
                        </div>
                    </form>
                </div>
            </div>

            <div id="clearDataConfirmation" class="modal">
                <div class="modal-content">
                    <h2 class="text-2xl font-bold mb-4">Confirm Clear Data</h2>
                    <p class="mb-4">Are you sure you want to delete the ping history? IPs and services will be
                        maintained.</p>
                    <form method="POST" action="">
                        <div class="flex justify-end">
                            <button type="button" onclick="hideClearDataConfirmation();"
                                class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-700 transition duration-300 mr-2">Cancel</button>
                            <button type="submit" name="clear_data"
                                class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-700 transition duration-300">Confirm</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</body>

</html>