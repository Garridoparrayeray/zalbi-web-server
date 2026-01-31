<?php
/**
 * The template for displaying the footer
 */
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
                        Expertos en ocio, aventura e hinchables. Llevamos la diversión segura a colegios, ayuntamientos y fiestas privadas en todo Euskadi.
                    </p>
                </div>

                <div class="footer-col">
                    <h4 class="footer-title">Contacto</h4>
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
                    <h4 class="footer-title">Información</h4>
                    
                    <?php
                    if ( has_nav_menu( 'menu-legal' ) ) {
                        wp_nav_menu( array(
                            'theme_location' => 'menu-legal',
                            'menu_class'     => 'footer-links',
                            'container'      => false,
                            'depth'          => 1,
                        ) );
                    } else {
                        echo '<ul class="footer-links">';
                        echo '<li><a href="' . home_url('/aviso-legal') . '">Aviso Legal</a></li>';
                        echo '<li><a href="' . home_url('/politica-privacidad') . '">Política de Privacidad</a></li>';
                        echo '<li><a href="' . home_url('/politica-cookies') . '">Política de Cookies</a></li>';
                        echo '</ul>';
                    }
                    ?>
                </div>

            </div>

            <div class="copyright-bar">
                <p>&copy; <?php echo date('Y'); ?> <strong>ZALBI Aisia eta Abentura</strong>. Todos los derechos reservados.</p>
                <p class="credits">
                    Desarrollado con <span style="color:var(--c-pink);">❤</span> por <a href="https://github.com/Garridoparrayeray" target="_blank">Yeray Garrido</a>
                </p>
            </div>
            
        </div>
    </footer>

<?php 
/* --- LÓGICA DEL BOTÓN DE WHATSAPP --- */
// Usamos get_theme_mod porque ahora está en el Personalizador
$whatsapp_number = get_theme_mod('zalbi_whatsapp_number'); 

if ( ! empty($whatsapp_number) ) : ?>

    <a href="https://wa.me/<?php echo esc_attr($whatsapp_number); ?>?text=Hola,%20quisiera%20informaci%C3%B3n" 
       class="whatsapp-btn" 
       target="_blank" 
       rel="noopener noreferrer" 
       aria-label="Contactar por WhatsApp">
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