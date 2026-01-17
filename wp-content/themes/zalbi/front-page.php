<?php
/**
 * Template Name: Portada ZALBI
 * Pagina principal de la web. 
 */
get_header(); ?>

    <section class="hero-home">
      <div class="container">
        <h1>Tu Mundo de Diversión</h1>
        <p>
          Especialistas en alquiler de hinchables y organización de eventos para
          todos los públicos.
        </p>
        <a href="<?php echo home_url('/catalogo'); ?>" class="btn">Ver Catálogo Completo</a>
      </div>
    </section>
  <section class="section-pad">
      <div class="container">
        <h1>¿QUIENES SOMOS?</h1>
        <p>
          <b>Somos Zalbi aisia eta abentura</b> una empresa dedicada al ocio y entretenimiento infantil con mas de <b>15 años</b> de experiencia trabajando en bizkaia y euskadi (también nos trasladamos fuera) que haremos tu celebración un evento increible. Siempre con la atenta mirada de nuestros monitore/as
        </p>
      </div>
    </section>
    <section class="section-pad">
        <div class="container">
            <h2 class="text-center section-title">¿Qué estás buscando?</h2>
            
            <div class="options">
                
                <a href="<?php echo home_url('/catalogo/?cat=hinchables'); ?>" class="options-box border-pink">
                    <img src="<?php echo get_template_directory_uri(); ?>/img/hinchables-grandes.jpg" alt="Hinchables Grandes">
                    <div class="card-body">
                        <h3 class="text-pink">Hinchables</h3>
                        <p>Disponemos de una gran variedad de castillos hinchables de distintos tamaños y para diferentes edades. Siempre bajo la supervisión de un monitor/a</p>
                        <span class="link-more text-pink">Ver más &rarr;</span>
                    </div>
                </a>

                <a href="<?php echo home_url('/catalogo/?cat=acuaticos'); ?>" class="options-box border-orange">
                    <img src="<?php echo get_template_directory_uri(); ?>/img/hinchables-medianos.jpg" alt="Hinchables Medianos"> 
                    <div class="card-body">
                        <h3 class="text-orange">Fiesta de espuma e hinchables acuáticos</h3>
                        <p>Para cuando el calor aprieta la mejor espuma e hinchables de agua</p>
                        <span class="link-more text-orange">Ver más &rarr;</span>
                    </div>
                </a>

                <a href="<?php echo home_url('/catalogo/?=deportivos'); ?>" class="options-box border-green">
                    <img src="<?php echo get_template_directory_uri(); ?>/img/eventos.jpg" alt="Eventos Especiales">
                    <div class="card-body">
                        <h3 class="text-green">ATRACCIONES DEPORTIVAS</h3>
                        <p>Atracciones deportivas e hinchables para pasar un buen rato, para la motricidad y como no, risas garantizadas.</p>
                        <span class="link-more text-green">Ver más &rarr;</span>
                    </div>
                </a>
                <a href="<?php echo home_url('/eventos')?>" class = "options-box border-green">
                  <img src="" alt="<?php echo get_template_directory_uri(); ?>/img/eventos.jpg" alt="Eventos Especiales">
                  <div class="card-body">
                        <h3 class="text-green">EVENTOS</h3>
                        <p>Fiestas de la espuma, tirolinas y animación musical completa.</p>
                        <span class="link-more text-green">Ver más &rarr;</span>
                    </div>
                </a>
            </div>
        </div>
    </section>

    <section class="section-pad">
        <p>Te organizamos vuestro evento adaptandonos a vuestro espacio y presupuesto

        *AYUNTAMIENTOS Y COMISION DE FIESTAS*

        Organizamos fiestas y eventos para vuestro pueblo bien a ayuntamientos o bien comisiones de fiesta.

        *COLEGIOS Y CUALQUIER ASOCIACIÓN*
        Celebramos fiestas para vuestra fiesta de fin de curso o cualqui3er evento que tengas, garantizando profesionalidad y diversión.
        </p>
    </section>

<?php get_footer(); ?>