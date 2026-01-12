<?php
/*
Plugin Name: Exportador de mensajes CF7 a CSV + Vista HTML
Description: Exporta los mensajes de la tabla mensajes_cf7 a un CSV y muestra una vista previa en formato tabla HTML.
Version: 1.1
Lo nuevo de la última version:
*Botón de vista previa
*Mejor exportacion
Author: Glocalium Developers: Yeray Garrido, Aiza Madrazo
Copyright: (c) 2025 Yeray Garrido - Todos los derechos reservados.

*/

add_action('admin_menu', 'cf7_csv_export_menu');

function cf7_csv_export_menu() {
    add_menu_page(
        'Exportar Mensajes CF7',
        'Exportar CF7 CSV',
        'manage_options',
        'cf7-export-csv',
        'cf7_csv_export_page'
    );
}

function cf7_csv_export_page() {
    echo '<div class="wrap">';
    echo '<h1>Exportar Mensajes de Contacto</h1>';
    echo '<p> Cuando quieras exportar todas las personas, clicka en el botón y te descargará el documento (en un CSV) con toda la gente que te ha contactado</p>';
    echo '<form method="post" action="' . admin_url('admin-post.php') . '">';
    echo '<input type="hidden" name="action" value="cf7_exportar_csv">';
    echo '<br>';
    echo '<input type="submit" class="button button-primary" value="Exportar a CSV">';
    echo '</form>';
    echo '</div>';
    echo '<br>';
    echo '<br>';
    // Botón de vista previa
    echo '<form method="post">';
    echo '<input type="submit" name="ver_vista_previa" class="button" value="Ver vista previa">';
    echo '</form>';
    echo '</div>';

    // Si se solicita la vista previa
    if (isset($_POST['ver_vista_previa'])) {
        cf7_mostrar_tabla_html();
    }
}

add_action('admin_post_cf7_exportar_csv', 'cf7_exportar_mensajes_csv');

function cf7_exportar_mensajes_csv() {
    if (!current_user_can('manage_options')) {
        wp_die('No tienes permisos suficientes para acceder a esta página. Contacte con el administrador para la correcta exportación del CSV.');
    }

    global $wpdb;
    $tabla = $wpdb->prefix . 'mensajes_cf7';
    $mensajes = $wpdb->get_results("SELECT * FROM $tabla", ARRAY_A);

    if (empty($mensajes)) {
        wp_die('No hay mensajes para exportar.');
    }

    if (ob_get_length()){ //comprueba si hay algo pendiente en el buffer del navegador, lo limpia y lo descarta
        ob_end_clean();
    }     
    header('Content-Type: text/csv; charset=utf-8');//Indica al navegador que el contenido que viene es de tipo text/csv.
    header('Content-Disposition: attachment; filename=mensajes_cf7_' . date('Y-m-d') . '.csv');//Fuerza al navegador a descargar el contenido en lugar de mostrarlo en el navegador.
    $output = fopen('php://output', 'w');
    // Usar punto y coma como delimitador para que se vea bien en Excel en castellano
    $delimitador = ';';

    // Esta línea escribe la fila de cabecera del archivo CSV, que contiene los nombres de las columnas.
    fputcsv($output, array_keys($mensajes[0]), $delimitador);//$mensajes son nombre, email, mensaje, fecha

    // Filas
    foreach ($mensajes as $mensaje) {
        // Elimina saltos de línea y dobles comillas para evitar errores en CSV
        $fila_limpia = array_map(function($campo) {
            return str_replace(["\n", "\r", '"'], [' ', ' ', ''], $campo);
            /*  str_replace(...) elimina:
            -\n → salto de línea
            -\r → retorno de carro
            -" → comillas dobles (que pueden romper el CSV)
            -Los reemplaza por espacios para que el CSV quede limpio y no se rompa el formato al abrirlo en Excel o importar en otro sistema.
            */
        }, $mensaje);

        fputcsv($output, $fila_limpia, $delimitador);
    }

    fclose($output);
    exit;
}

function cf7_mostrar_tabla_html() {
    global $wpdb;
    $tabla = $wpdb->prefix . 'mensajes_cf7';
    $mensajes = $wpdb->get_results("SELECT * FROM $tabla", ARRAY_A);

    if (empty($mensajes)) {
        echo '<p>No hay mensajes para mostrar.</p>';
        return;
    }

    echo '<h2>Vista previa de los mensajes</h2>';
    echo '<table class="widefat fixed striped">';
    echo '<thead><tr>';
    foreach (array_keys($mensajes[0]) as $columna) {
        echo '<th>' . esc_html($columna) . '</th>';
    }
    echo '</tr></thead><tbody>';

    foreach ($mensajes as $fila) {
        echo '<tr>';
        foreach ($fila as $valor) {
            echo '<td>' . esc_html($valor) . '</td>';
        }
        echo '</tr>';
    }

    echo '</tbody></table>';
}
