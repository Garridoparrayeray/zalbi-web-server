=== Exportador de mensajes CF7 a CSV + Vista HTML ===
Contributors: Glocalium Developers (Yeray Garrido, Aiza Madrazo)
Tags: contact form 7, exportar, csv, vista previa, mensajes, base de datos
Requires at least: 5.0
Tested up to: 6.5
Stable tag: 1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Plugin para exportar los mensajes almacenados de Contact Form 7 a un archivo CSV y verlos en formato tabla HTML desde el panel de administración de WordPress.

== Descripción ==

Este plugin está diseñado para facilitar la gestión de mensajes de formularios de Contact Form 7 guardados en la base de datos. Añade dos funcionalidades clave al panel de WordPress:

- Exportación directa de todos los mensajes a un archivo `.csv`, compatible con Excel (delimitado por punto y coma).
- Vista previa en formato tabla (`<table>`) de todos los registros directamente desde el área de administración.

Es útil para consultar mensajes de contacto sin necesidad de entrar en la base de datos manualmente o para llevar registros periódicos en hojas de cálculo.

== Características principales ==

- Exportación limpia y segura a CSV desde el menú del administrador.
- Vista previa HTML con formato de tabla (cabeceras + datos).
- Uso de punto y coma como separador para compatibilidad con Excel en español.
- Sanitización de saltos de línea y comillas para evitar errores en CSV.
- Compatible con WordPress 5.0+ y Contact Form 7.
- Código ligero y sin dependencias externas.

== Instalación ==

1. Sube el archivo del plugin a la carpeta `/wp-content/plugins/`.
2. Activa el plugin desde el panel de administración de WordPress.
3. Asegúrate de que los mensajes estén siendo guardados en la tabla `mensajes_cf7` (ver plugin complementario de guardado si es necesario).
4. Entra al menú “Exportar CF7 CSV” del panel y elige entre:
    - Exportar a CSV
    - Ver vista previa en HTML

== Historial de versiones ==

= 1.1 (2025-05-21) =
- Añadido botón de vista previa HTML de mensajes desde el admin.
- Mejora del sistema de exportación a CSV (ahora con limpieza de caracteres y delimitador compatible con Excel).
- Mejoras visuales y explicativas en el panel admin (instrucciones incluidas).
- Añadido control de permisos y protección contra errores de cabecera.

= 1.0 (2025-05-21) =
- Versión inicial funcional para exportar mensajes a CSV desde la tabla `mensajes_cf7`.

== Licencia ==

Este plugin está licenciado bajo los términos de la GPLv2 o posterior.

(c) 2025 Yeray Garrido - Todos los derechos reservados.

== Créditos ==

Desarrollado por Glocalium Developers:
- Yeray Garrido
- Aiza Madrazo

== Uso recomendado ==

Usa este plugin junto al plugin “Guardar mensajes de CF7” para almacenar los datos correctamente antes de exportarlos.
