<?php
// Cargar configuración desde config.ini
$config = parse_ini_file('config.ini', true);
$ips_to_monitor = $config['ips'];
$services = $config['services'];
$ping_attempts = $config['settings']['ping_attempts'];
$ping_interval = $config['settings']['ping_interval'];

$ping_file = 'ping_results.json';

// Cargar resultados previos si existen
if (file_exists($ping_file)) {
    $ping_data = json_decode(file_get_contents($ping_file), true);
} else {
    $ping_data = [];
}

// Cargar funciones
require_once 'functions.php';

// Manejar la adición de IP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_ip'])) {
    $new_ip = filter_var($_POST['new_ip'], FILTER_VALIDATE_IP);
    $new_service = htmlspecialchars($_POST['new_service'], ENT_QUOTES, 'UTF-8');
    add_ip_to_config($new_ip, $new_service);
    header("Location: " . $_SERVER['PHP_SELF'] . "?action=added");
    exit;
}

// Manejar la eliminación de IP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_ip'])) {
    $ip_to_delete = filter_var($_POST['delete_ip'], FILTER_VALIDATE_IP);
    delete_ip_from_config($ip_to_delete);
    header("Location: " . $_SERVER['PHP_SELF'] . "?action=deleted");
    exit;
}
//Manejar la insercion de un servicio
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service'])) {
    $new_service_name = htmlspecialchars($_POST['new_service_name'], ENT_QUOTES, 'UTF-8');
    $new_service_color = htmlspecialchars($_POST['new_service_color'], ENT_QUOTES, 'UTF-8');

    // Cargar la configuración actual
    $config = parse_ini_file('config.ini', true);

    // Añadir el nuevo servicio
    $config['services'][$new_service_name] = $new_service_color;

    // Guardar los cambios en config.ini
    $new_content = '';
    foreach ($config as $section => $values) {
        $new_content .= "[$section]\n";
        foreach ($values as $key => $value) {
            $new_content .= "$key = \"$value\"\n";
        }
    }
    file_put_contents('config.ini', $new_content);

    // Redirigir para evitar reenvío del formulario
    header("Location: " . $_SERVER['PHP_SELF'] . "?action=service_added");
    exit;
}

// Manejar el cambio del temporizador
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_timer'])) {
    $new_timer_value = intval($_POST['new_timer_value']);

    if ($new_timer_value > 0) {
        // Cargar la configuración actual
        $config = parse_ini_file('config.ini', true);

        // Actualizar el valor del temporizador
        $config['settings']['ping_interval'] = $new_timer_value;

        // Guardar los cambios en config.ini
        $new_content = '';
        foreach ($config as $section => $values) {
            $new_content .= "[$section]\n";
            foreach ($values as $key => $value) {
                $new_content .= "$key = \"$value\"\n";
            }
        }
        file_put_contents('config.ini', $new_content);

        // Redirigir para evitar reenvío del formulario
        header("Location: " . $_SERVER['PHP_SELF'] . "?action=timer_updated");
        exit;
    } else {
        echo "<script>alert('Please enter a valid number greater than 0.');</script>";
    }
}

// Manejar el cambio de intentos de ping
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_ping_attempts'])) {
    $new_ping_attempts = intval($_POST['new_ping_attempts']);

    if ($new_ping_attempts > 0) {
        // Cargar la configuración actual
        $config = parse_ini_file('config.ini', true);

        // Actualizar el valor de ping_attempts
        $config['settings']['ping_attempts'] = $new_ping_attempts;

        // Guardar los cambios en config.ini
        $new_content = '';
        foreach ($config as $section => $values) {
            $new_content .= "[$section]\n";
            foreach ($values as $key => $value) {
                $new_content .= "$key = \"$value\"\n";
            }
        }
        file_put_contents('config.ini', $new_content);

        // Redirigir para evitar reenvío del formulario
        header("Location: " . $_SERVER['PHP_SELF'] . "?action=ping_attempts_updated");
        exit;
    } else {
        echo "<script>alert('Please enter a valid number greater than 0.');</script>";
    }
}
//Manejar la eliminación de datos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_data'])) {
    // Limpiar los datos de ping
    if (file_exists($ping_file)) {
        file_put_contents($ping_file, json_encode([])); // Vaciar el archivo
    }

    // Redirigir para evitar reenvío del formulario
    header("Location: " . $_SERVER['PHP_SELF'] . "?action=data_cleared");
    exit;
}

// Ejecutar un solo ping por ciclo solo si no se está eliminando una IP o añadiendo una nueva
if (!isset($_GET['action'])) {
    foreach ($ips_to_monitor as $ip => $service) {
        update_ping_results($ip);
    }

    // Guardar resultados actualizados en JSON
    file_put_contents($ping_file, json_encode($ping_data));
}

// Manejar peticiones AJAX para actualizar solo las cards
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    // Solo devolver el HTML de las cards y el resumen del sistema
    header('Content-Type: text/html; charset=utf-8');
    ?>
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
    <?php
    exit; // No cargar la vista completa
}

// Incluir la vista
require_once 'views.php';
?>