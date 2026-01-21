<?php
/**
 * Template Name: Portada ZALBI
 * Pagina principal de la web.
 */
get_header();
?>
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
      <div class="container text-center">
        <h1>¿QUIÉNES SOMOS?</h1>
        <div class="about-text">
          <p>
            <b>Somos Zalbi Aisia eta Abentura</b>, una empresa dedicada al ocio y entretenimiento infantil con más de <b>15 años</b> de experiencia trabajando en Bizkaia y Euskadi (también nos trasladamos fuera). Haremos de tu celebración un evento increíble. Siempre con la atenta mirada de nuestros monitores/as.
          </p>
        </div>
      </div>
    </section>

   <section class="section-pad bg-light"> 
        <div class="container">
            <h2 class="text-center section-title">¿Qué estás buscando?</h2>
            
            <div class="options">
                
                <a href="<?php echo home_url('/catalogo/?cat=hinchables'); ?>" class="options-box border-pink">
                    <img src="https://dev-zalbi-aisia-eta-abentura.pantheonsite.io/wp-content/uploads/2026/01/WhatsApp-Image-2026-01-19-at-10.40.18.jpeg" alt="Hinchables">
                    
                    <div class="card-body">
                        <h3 class="text-pink">HINCHABLES</h3>
                        <p>Disponemos de una gran variedad de castillos hinchables de distintos tamaños y para diferentes edades.</p>
                        <span class="link-more text-pink">Ver más &rarr;</span>
                    </div>
                </a>

                <a href="<?php echo home_url('/catalogo/?cat=acuaticos'); ?>" class="options-box border-blue">
                    <img src="PEGAR_AQUI_LA_URL_DE_ACUATICOS" alt="Acuáticos"> 
                    
                    <div class="card-body">
                        <h3 class="text-blue">ACUÁTICOS</h3> 
                        <p>Para cuando el calor aprieta la mejor espuma e hinchables de agua.</p>
                        <span class="link-more text-blue">Ver más &rarr;</span>
                    </div>
                </a>

                <a href="<?php echo home_url('/catalogo/?cat=deportivos'); ?>" class="options-box border-orange">
                     <img src="PEGAR_AQUI_LA_URL_DE_DEPORTIVOS" alt="Atracciones deportivas">
                    
                    <div class="card-body">
                        <h3 class="text-orange">ATRACCIONES DEPORTIVAS</h3>
                        <p>Hinchables deportivos para pasar un buen rato, trabajar la motricidad y risas garantizadas.</p>
                        <span class="link-more text-orange">Ver más &rarr;</span>
                    </div>
                </a>
                
                <a href="<?php echo home_url('/eventos') ?>" class="options-box border-green">
                  <img src="PEGAR_AQUI_LA_URL_DE_EVENTOS" alt="Eventos">
                  
                  <div class="card-body">
                        <h3 class="text-green">EVENTOS</h3>
                        <p>Talleres y animación musical completa.</p>
                        <span class="link-more text-green">Ver más &rarr;</span>
                    </div>
                </a>
            </div>
        </div>
    </section>

    <section class="section-pad">
        <div class="container">
            <div class="text-center mb-30">
                <h2 class="text-purple">Nos adaptamos a tu espacio y presupuesto, os damos un presupuesto sin ningún compromiso</h2>
                <p>Organizamos eventos a medida para todo tipo de instituciones</p>
            </div>
            
            <div class="clients-grid">
                
                <div class="client-card bg-green-light">
                    <h3 class="text-green">AYUNTAMIENTOS Y COMISIONES</h3>
                    <p>Organizamos fiestas y eventos patronales para vuestro pueblo. Gestión integral para ayuntamientos y comisiones de fiestas.</p>
                </div>

                <div class="client-card bg-pink-light">
                    <h3 class="text-pink">COLEGIOS Y ASOCIACIONES</h3>
                    <p>Celebramos fiestas de fin de curso, semanas culturales o cualquier evento asociativo, garantizando profesionalidad y diversión.</p>
                </div>

            </div>
        </div>
    </section>

<?php get_footer(); ?>