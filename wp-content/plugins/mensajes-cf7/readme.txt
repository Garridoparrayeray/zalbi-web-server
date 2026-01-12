=== Guardar mensajes de CF7 ===
Contributors: Glocalium Developers (Yeray Garrido, Aiza Madrazo)
Tags: contact form 7, guardar mensajes, base de datos, exportar, csv, google sheets
Requires at least: 5.0
Tested up to: 6.5
Stable tag: 1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Guarda los mensajes enviados desde Contact Form 7 en una tabla personalizada de la base de datos y permite su exportación a CSV. Pensado para integraciones con Google Sheets o análisis posterior.

== Descripción ==

Este plugin permite almacenar automáticamente los mensajes enviados desde formularios de Contact Form 7 en una tabla personalizada de tu base de datos de WordPress (`wp_mensajes_cf7` o similar). Ideal para conservar registros, realizar exportaciones masivas o integrarlo posteriormente con hojas de cálculo como Google Sheets.

Opcionalmente, puedes implementar un segundo plugin que añade:

- Vista previa en HTML desde el panel de administración.
- Botón para exportar directamente los mensajes a un archivo `.csv`.

== Características principales ==

- Guardado automático de los campos: nombre, email, teléfono y mensaje.
- Almacenamiento en tabla personalizada.
- Código optimizado con sanitización segura.
- Compatible con WP 5.0+ y Contact Form 7.
- Exportación de datos a CSV con formato legible para Excel (delimitado por `;`).
- Vista previa de mensajes en tabla HTML dentro del admin.

== Instalación ==

1. Copia los archivos del plugin en la carpeta `/wp-content/plugins/guardar-mensajes-cf7/`.
2. Activa el plugin desde el panel de administración de WordPress.
3. Asegúrate de tener Contact Form 7 instalado y configurado con los campos:
    - `your-name`
    - `your-email`
    - `tel-34`
    - `your-message`

4. Los mensajes se almacenarán automáticamente al enviar un formulario.

== Historial de versiones ==

= 1.0 (2025-05-21) =
- Versión inicial del plugin que guarda los mensajes de CF7 en la base de datos.
- Campos guardados: nombre, email, teléfono y mensaje.
- Creación automática de la tabla (recomendado implementarla aparte si no existe).

= 1.1 (2025-05-21) =
- Añadido soporte para exportación de mensajes a CSV desde el panel de administración.
- Añadida vista previa en HTML de los mensajes (estilo tabla).
- Mejorada la limpieza de datos para el CSV: eliminación de saltos de línea, comillas y formato delimitado por punto y coma.
- Incluido soporte de licencia GPLv2 o posterior y encabezado de copyright.
- Preparado para ser distribuido o utilizado internamente en entornos profesionales.
- Adaptado a estructura WordPress Admin Menu y uso seguro de `admin-post.php`.

== Licencia ==

Este plugin está licenciado bajo los términos de la GPLv2 o posterior.

(c) 2025 Yeray Garrido - Todos los derechos reservados.

== Créditos ==

Desarrollado por Glocalium Developers:
- Yeray Garrido
- Aiza Madrazo

== Uso recomendado ==

Puedes combinar este plugin con herramientas externas como Google Apps Script o Zapier para automatizar el volcado de datos desde la base de datos a una hoja de Google Sheets.
