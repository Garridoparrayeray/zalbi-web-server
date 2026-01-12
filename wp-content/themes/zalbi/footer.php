<?php

/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package zalbi-theme
 */

?>

    </div><footer id="colophon" class="site-footer">
        <div class="container">
            
            <div class="footer-grid">
                
                <div>
                    <a href="<?php echo home_url(); ?>" class="logo" style="color: white; font-size: 2rem; text-decoration: none; font-weight: 800;">ZALBI</a>
                    <p style="margin-top: 15px; opacity: 0.8; font-size: 14px;">
                        Expertos en ocio, aventura e hinchables. Llevamos la diversión segura a colegios, ayuntamientos y fiestas privadas.
                    </p>
                </div>

                <div>
                    <h4>Contacto</h4>
                    <ul>
                        <li><i class="fas fa-map-marker-alt"></i> Polígono ZALBI, Nave 4</li>
                        <li><i class="fas fa-phone"></i> 600 000 000</li>
                        <li><i class="fas fa-envelope"></i> info@zalbi.com</li>
                    </ul>
                </div>

                <div>
                    <h4>Información</h4>
                    <ul>
                        <li><a href="#">Aviso Legal</a></li>
                        <li><a href="#">Política de Privacidad</a></li>
                        <li><a href="#">Certificados de Seguridad</a></li>
                    </ul>
                </div>

            </div><div class="copyright-bar">
                
                <p style="margin-bottom: 10px;">&copy; <?php echo date('Y'); ?> ZALBI. Todos los derechos reservados.</p>
                
                <div class="site-info" style="font-size: 12px; opacity: 0.6;">
                    <a href="<?php echo esc_url(__('https://wordpress.org/', 'zalbi')); ?>" style="color: inherit;">
                        <?php
                        /* translators: %s: CMS name, i.e. WordPress. */
                        printf(esc_html__('Proudly powered by %s', 'zalbi'), 'WordPress');
                        ?>
                    </a>
                    <span class="sep"> | </span>
                    <?php
                    /* translators: 1: Theme name, 2: Theme author. */
                    printf(esc_html__('Theme: %1$s by %2$s.', 'zalbi'), 'zalbi', '<a href="https://github.com/Garridoparrayeray" style="color: inherit; text-decoration: underline;">Yeray Garrido</a>');
                    ?>
                </div></div></div></footer></div><?php wp_footer(); ?>

</body>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Seleccionamos los elementos
    const button = document.querySelector('.menu-toggle');
    const nav = document.querySelector('.main-navigation');
    const menu = nav ? nav.querySelector('ul') : null;

    if (button && menu) {
        
        // --- EVENTO CLICK ---
        button.addEventListener('click', function() {            
            // Animación de altura (Slide Down)
            if (menu.style.maxHeight) {
                menu.style.maxHeight = null; // Cerrar
            } else {
                menu.style.maxHeight = menu.scrollHeight + "px"; // Abrir
            }
        });

        // --- EVENTO RESIZE (Resetear al agrandar pantalla) ---
        window.addEventListener('resize', function() {
            // Si la pantalla es mayor que 768px
            if (window.innerWidth > 768) {
                //Quitar la clase de "abierto"
                nav.classList.remove('toggled');
                menu.removeAttribute('style');
            }
        });
    }
});
</script>
</html>

