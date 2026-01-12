<?php
/*
Plugin Name: Guardar mensajes de CF7
Description: Guarda los datos del formulario de Contact Form 7 en una tabla personalizada en la base de datos del servidor.
Posteriormente, usado para guardarlo en un google Sheet.
Version: 1.0
Author: Glocalium Developers (Yeray Garrido, Aiza Madrazo)
Copyright: (c) 2025 Yeray Garrido - Todos los derechos reservados.
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

add_action('wpcf7_mail_sent', 'guardar_mensaje_cf7');

function guardar_mensaje_cf7($contact_form) {
    global $wpdb;

    $submission = WPCF7_Submission::get_instance();
    if (!$submission) return;

    $data = $submission->get_posted_data();

    $nombre  = sanitize_text_field($data['your-name']);
    $email   = sanitize_email($data['your-email']);
    $telefono = sanitize_text_field($data['tel-34']);
    $mensaje = sanitize_textarea_field($data['your-message']);

    $tabla = $wpdb->prefix . 'mensajes_cf7';

    $wpdb->insert($tabla, array(
        'nombre'  => $nombre,
        'email'   => $email,
        'telefono' => $telefono,
        'mensaje' => $mensaje,
        'fecha'   => current_time('mysql')
    ));
}
