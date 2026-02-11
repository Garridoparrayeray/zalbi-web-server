<?php
/**
 * The template for displaying the footer
 */

// --- 1. LÓGICA DE IDIOMA ---
$es_euskera = (function_exists('pll_current_language') && pll_current_language() == 'eu');

if ($es_euskera) {
    // EUSKERA
    $txt_desc       = 'Aisialdian, abenturan eta puzgarrietan adituak. Dibertsioa eramaten dugu Euskadiko ikastetxe, udal eta festa pribatuetara.';
    $txt_contacto   = 'Kontaktua';
    $txt_info       = 'Informazioa';
    $txt_derechos   = 'Eskubide guztiak erreserbatuta.';
    $txt_dev        = 'Yeray Garrido <span style="color:var(--c-pink);">❤</span> maitasunez garatuta '; // "Desarrollado por..."
    
    // Textos enlaces manuales (por si falla el menú)
    $link_aviso     = 'Lege Oharra';
    $link_priv      = 'Pribatutasun Politika';
    $link_cookies   = 'Cookie Politika';
    
    // Prefijo para URLs manuales en euskera (asumiendo configuración estándar)
    $url_prefix     = '/eu'; 
    
    // Mensaje WhatsApp
    $wa_msg         = 'Kaixo, informazioa nahi nuke';

} else {
    // ESPAÑOL
    $txt_desc       = 'Expertos en ocio, aventura e hinchables. Llevamos la diversión segura a colegios, ayuntamientos y fiestas privadas en todo Euskadi.';
    $txt_contacto   = 'Contacto';
    $txt_info       = 'Información';
    $txt_derechos   = 'Todos los derechos reservados.';
    $txt_dev        = 'Desarrollado con <span style="color:var(--c-pink);">❤</span> por';
    
    $link_aviso     = 'Aviso Legal';
    $link_priv      = 'Política de Privacidad';
    $link_cookies   = 'Política de Cookies';
    
    $url_prefix     = ''; // Sin prefijo en español
    
    $wa_msg         = 'Hola, quisiera información';
}
?>

    <footer id="colophon" class="site-footer">
        <div class="container">
            
            <div class="footer-grid">
                
                <div class="footer-col footer-brand">
                    <div class="site-branding">
                        <?php
                        // Si hay logo personalizado en el personalizador, úsalo. Si no, texto.
                        if (has_custom_logo()) {
                            the_custom_logo();
                        } else {
                            ?>
                            <h1 class="site-title"><a href="<?php echo esc_url(home_url('/')); ?>" rel="home"><?php bloginfo('name'); ?></a></h1>
                            <?php
                        }
                        ?>
                    </div>
                    <p>
                        <?php echo $txt_desc; ?>
                    </p>
                </div>

                <div class="footer-col">
                    <h4 class="footer-title"><?php echo $txt_contacto; ?></h4>
                    <ul class="footer-links contact-list">
                        <li>
                            <i class="fas fa-map-marker-alt"></i> 
                            <span>Telleri 11-A, 48600 Sopela</span>
                        </li>
                        <li>
                            <i class="fas fa-phone-alt"></i> 
                            <a href="tel:+34658887358">658 88 73 58</a>
                        </li>
                        <li>
                            <i class="fas fa-envelope"></i> 
                            <a href="mailto:aritzzalbidea@yahoo.es">aritzzalbidea@yahoo.es</a>
                        </li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4 class="footer-title"><?php echo $txt_info; ?></h4>
                    
                    <?php
                    // IMPORTANTE: Polylang detecta automáticamente qué menú cargar aquí 
                    // si tienes creados dos menús (uno ES y otro EU) asignados a 'menu-legal'.
                    if ( has_nav_menu( 'menu-legal' ) ) {
                        wp_nav_menu( array(
                            'theme_location' => 'menu-legal',
                            'menu_class'     => 'footer-links',
                            'container'      => false,
                            'depth'          => 1,
                        ) );
                    } else {
                        // Fallback manual traducido
                        echo '<ul class="footer-links">';
                        echo '<li><a href="' . home_url($url_prefix . '/aviso-legal') . '">' . $link_aviso . '</a></li>';
                        echo '<li><a href="' . home_url($url_prefix . '/politica-privacidad') . '">' . $link_priv . '</a></li>';
                        echo '<li><a href="' . home_url($url_prefix . '/politica-cookies') . '">' . $link_cookies . '</a></li>';
                        echo '</ul>';
                    }
                    ?>
                </div>

            </div>

            <div class="copyright-bar">
                <p>&copy; <?php echo date('Y'); ?> <strong>ZALBI Aisia eta Abentura</strong>. <?php echo $txt_derechos; ?></p>
                <p class="credits">
                    <?php echo $txt_dev; ?> <a href="https://github.com/Garridoparrayeray" target="_blank">Yeray Garrido</a>
                </p>
            </div>
            
        </div>
    </footer>

<?php 
/* --- LÓGICA DEL BOTÓN DE WHATSAPP --- */
$whatsapp_number = get_theme_mod('zalbi_whatsapp_number'); 

if ( ! empty($whatsapp_number) ) : 
    // Codificamos el mensaje para que sea válido en la URL (espacios a %20, etc.)
    $wa_msg_encoded = rawurlencode($wa_msg);
?>

    <a href="https://wa.me/<?php echo esc_attr($whatsapp_number); ?>?text=<?php echo $wa_msg_encoded; ?>" 
       class="whatsapp-btn" 
       target="_blank" 
       rel="noopener noreferrer" 
       aria-label="WhatsApp">
        <i class="fab fa-whatsapp"></i>
    </a>

<?php endif; ?>

</div><?php wp_footer(); ?>


</body>
<script>
// Tu script del menú hamburguesa se mantiene igual
document.addEventListener('DOMContentLoaded', function() {
    const button = document.querySelector('.menu-toggle');
    const nav = document.querySelector('.main-navigation');
    const menu = nav ? nav.querySelector('ul') : null;

    if (button && menu) {
        button.addEventListener('click', function() {            
            if (menu.style.maxHeight) {
                menu.style.maxHeight = null; 
            } else {
                menu.style.maxHeight = menu.scrollHeight + "px"; 
            }
        });
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                nav.classList.remove('toggled');
                menu.removeAttribute('style');
            }
        });
    }
});
</script>
</html>