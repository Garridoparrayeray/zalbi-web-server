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
            <h2 class="text-center section-title">¿Qué estás buscando?</h2>
            
            <div class="options">
                
                <a href="<?php echo home_url('/catalogo/?cat=grandes'); ?>" class="options-box border-pink">
                    <img src="<?php echo get_template_directory_uri(); ?>/img/hinchables-grandes.jpg" alt="Hinchables Grandes">
                    <div class="card-body">
                        <h3 class="text-pink">Hinchables Grandes</h3>
                        <p>Toboganes gigantes y pistas americanas para la máxima diversión.</p>
                        <span class="link-more text-pink">Ver más &rarr;</span>
                    </div>
                </a>

                <a href="<?php echo home_url('/catalogo/?cat=medianos'); ?>" class="options-box border-orange">
                    <img src="<?php echo get_template_directory_uri(); ?>/img/hinchables-medianos.jpg" alt="Hinchables Medianos"> 
                    <div class="card-body">
                        <h3 class="text-orange">Hinchables Medianos</h3>
                        <p>Ideales para cumpleaños y fiestas privadas en espacios reducidos.</p>
                        <span class="link-more text-orange">Ver más &rarr;</span>
                    </div>
                </a>

                <a href="<?php echo home_url('/eventos'); ?>" class="options-box border-green">
                    <img src="<?php echo get_template_directory_uri(); ?>/img/eventos.jpg" alt="Eventos Especiales">
                    <div class="card-body">
                        <h3 class="text-green">Eventos Especiales</h3>
                        <p>Fiestas de la espuma, tirolinas y animación musical completa.</p>
                        <span class="link-more text-green">Ver más &rarr;</span>
                    </div>
                </a>

            </div>
        </div>
    </section>

    <section class="section-pad">
      <div class="container"> <div class="section-events">
          <h2 style="color: var(--c-purple)">Últimos Eventos</h2>
          <a href="#" style="color: var(--c-pink); font-weight: bold">Ver Galería Completa &rarr;</a>
        </div>
        
        <div class="options">
          <a href="#" class="event-card">
            <img src="<?php echo get_template_directory_uri(); ?>/img/evento1.jpg" alt="Fiesta Colegio" />
            <h4 class="event-title">Fiesta Colegio San José</h4>
          </a>

          <a href="#" class="event-card">
            <img src="<?php echo get_template_directory_uri(); ?>/img/evento2.jpg" alt="Fiesta Ayuntamiento" />
            <h4 class="event-title">Ayuntamiento de Bilbao</h4>
          </a>

          <a href="#" class="event-card">
            <img src="<?php echo get_template_directory_uri(); ?>/img/evento3.jpg" alt="Cumpleaños" />
            <h4 class="event-title">Cumpleaños Privado</h4>
          </a>
        </div>
      </div>
    </section>

<?php get_footer(); ?>