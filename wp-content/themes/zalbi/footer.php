<?php
/**
 * The template for displaying the footer
 */
?>

    </div><footer id="colophon" class="site-footer">
        <div class="container">
            
            <div class="footer-grid">
                
                <div class="footer-col footer-brand">
                    <a href="<?php echo home_url(); ?>" class="footer-logo">ZALBI</a>
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
                    <ul class="footer-links">
                        <li><a href="#">Aviso Legal</a></li>
                        <li><a href="#">Política de Privacidad</a></li>
                        <li><a href="#">Política de Cookies</a></li>
                    </ul>
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
</div><?php wp_footer(); ?>

</body>
<script>
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