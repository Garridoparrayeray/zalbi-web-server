<?php
/**
 * Template Name: Portada ZALBI
 * Pagina principal de la web.
 */
get_header();

// --- 1. LÓGICA DE TRADUCCIÓN ---
$es_euskera = (function_exists('pll_current_language') && pll_current_language() == 'eu');

// Función auxiliar para crear URLs a prueba de móviles y caché
function zalbi_url_filtro($url_base, $slug) {
    // 1. Aseguramos que la URL base termine en '/' (Esto es el arreglo real)
    $url_base = trailingslashit($url_base);
    
    // 2. Añadimos el filtro de categoría limpiamente
    return add_query_arg(array(
        'cat' => $slug
    ), $url_base);
}

if ($es_euskera) {
    // === TEXTOS EN EUSKERA ===
    $t = array(
        'hero_titulo' => 'Zure Dibertsio Mundua',
        'hero_desc'   => 'Puzgarrien alokairuan eta mota guztietako ekitaldien antolakuntzan espezialistak.',
        'btn_catalogo'=> 'Ikusi Katalogo Osoa',
        
        'quienes_tit' => 'NORTZUK GARA?',
        'quienes_txt' => '<b>Zalbi Aisia eta Abentura gara</b>, haurren aisialdi eta entretenimendura dedikatutako enpresa, <b>15 urte</b> baino gehiagoko esperientziarekin Bizkaian eta Euskadin (kanpora ere mugitzen gara). Zure ospakizuna ekitaldi ahaztezina bihurtuko dugu. Beti gure begiraleen begiradapean.',
        
        'buscando_tit'=> 'Zer bilatzen ari zara?',
        'ver_mas'     => 'Ikusi gehiago &rarr;',
        
        // Cajas Categorías
        'cat1_tit'    => 'PUZGARRIAK',
        'cat1_desc'   => 'Tamaina eta adin ezberdinetarako puzgarri mota asko ditugu.',
        'cat2_tit'    => 'URETAKOAK',
        'cat2_desc'   => 'Beroa egiten duenerako, aparrik onena eta uretako puzgarriak.',
        'cat3_tit'    => 'KIROL ATRAKZIOAK',
        'cat3_desc'   => 'Kirol-puzgarriak ondo pasatzeko, motrizitatea lantzeko eta barreak bermatzeko.',
        'cat4_tit'    => 'EKITALDIAK',
        'cat4_desc'   => 'Tailerrak eta animazio musikal osoa.',
        'cat5_tit'    => 'JOKOAK',
        'cat5_desc'   => 'Haurrek amek eta aitek gozatu genituen jokoekin gozatu dezaten.',
        
        // Sección Presupuesto
        'presu_tit'   => 'Zure espazio eta aurrekontura egokitzen gara, konpromisorik gabe',
        'presu_sub'   => 'Erakunde mota guztientzako neurrira egindako ekitaldiak antolatzen ditugu',
        
        'cli1_tit'    => 'UDALAK ETA BATZORDEAK',
        'cli1_desc'   => 'Zure herriko jaiak eta ekitaldiak antolatzen ditugu. Kudeaketa integrala udal eta jai-batzordeentzat.',
        'cli2_tit'    => 'IKASTETXEAK ETA ELKARTEAK',
        'cli2_desc'   => 'Ikasturte amaierako jaiak, aste kulturalak edo edozein elkarte-ekitaldi ospatzen ditugu, profesionaltasuna eta dibertsioa bermatuz.',
    );

    // --- SLUGS EUSKERA (CONFIRMADOS) ---
    $url_catalogo_base = home_url('/eu/katalogoa');
    $url_eventos       = home_url('/eu/ekitaldiak'); 
    
    $slug_hinchables   = 'puzgarriak';       
    $slug_acuaticos    = 'uretakoak';         
    $slug_deportivos   = 'kirol-atrakzioak'; 
    $slug_juegos       = 'jokoak';

} else {
    // === TEXTOS EN ESPAÑOL ===
    $t = array(
        'hero_titulo' => 'Tu Mundo de Diversión',
        'hero_desc'   => 'Especialistas en alquiler de hinchables y organización de eventos para todos los públicos.',
        'btn_catalogo'=> 'Ver Catálogo Completo',
        
        'quienes_tit' => '¿QUIÉNES SOMOS?',
        'quienes_txt' => '<b>Somos Zalbi Aisia eta Abentura</b>, una empresa dedicada al ocio y entretenimiento infantil con más de <b>15 años</b> de experiencia trabajando en Bizkaia y Euskadi (también nos trasladamos fuera). Haremos de tu celebración un evento increíble. Siempre con la atenta mirada de nuestros monitores/as.',
        
        'buscando_tit'=> '¿Qué estás buscando?',
        'ver_mas'     => 'Ver más &rarr;',
        
        // Cajas Categorías
        'cat1_tit'    => 'HINCHABLES',
        'cat1_desc'   => 'Disponemos de una gran variedad de castillos hinchables de distintos tamaños y para diferentes edades.',
        'cat2_tit'    => 'ACUÁTICOS',
        'cat2_desc'   => 'Para cuando el calor aprieta la mejor espuma e hinchables de agua.',
        'cat3_tit'    => 'ATRACCIONES DEPORTIVAS',
        'cat3_desc'   => 'Hinchables deportivos para pasar un buen rato, trabajar la motricidad y risas garantizadas.',
        'cat4_tit'    => 'EVENTOS',
        'cat4_desc'   => 'Talleres y animación musical completa.',
        'cat5_tit'    => 'JUEGOS',
        'cat5_desc'   => 'Que los niños disfruten de los juegos que disfrutamos las amas y aitas.',
        
        // Sección Presupuesto
        'presu_tit'   => 'Nos adaptamos a tu espacio y presupuesto, os damos un presupuesto sin ningún compromiso',
        'presu_sub'   => 'Organizamos eventos a medida para todo tipo de instituciones',
        
        'cli1_tit'    => 'AYUNTAMIENTOS Y COMISIONES',
        'cli1_desc'   => 'Organizamos fiestas y eventos patronales para vuestro pueblo. Gestión integral para ayuntamientos y comisiones de fiestas.',
        'cli2_tit'    => 'COLEGIOS Y ASOCIACIONES',
        'cli2_desc'   => 'Celebramos fiestas de fin de curso, semanas culturales o cualquier evento asociativo, garantizando profesionalidad y diversión.',
    );

    // --- SLUGS ESPAÑOL (CONFIRMADOS) ---
    $url_catalogo_base = home_url('/catalogo');
    $url_eventos       = home_url('/eventos');
    
    $slug_hinchables   = 'hinchables';              
    $slug_acuaticos    = 'acuaticos';               
    $slug_deportivos   = 'deportivos'; 
    $slug_juegos       = 'juegos';
}
?>

    <section class="hero-home">
      <div class="container">
        <h1><?php echo $t['hero_titulo']; ?></h1>
        <p><?php echo $t['hero_desc']; ?></p>
        <a href="<?php echo $url_catalogo_base; ?>" class="btn"><?php echo $t['btn_catalogo']; ?></a>
      </div>
    </section>

    <section class="section-pad">
      <div class="container text-center">
        <h1><?php echo $t['quienes_tit']; ?></h1>
        <div class="about-text">
          <p><?php echo $t['quienes_txt']; ?></p>
        </div>
      </div>
    </section>

    <section class="section-pad bg-light"> 
        <div class="container">
            <h2 class="text-center section-title"><?php echo $t['buscando_tit']; ?></h2>
            
            <div class="options">
                
                <a href="<?php echo zalbi_url_filtro($url_catalogo_base, $slug_hinchables); ?>" class="options-box border-pink">
                    <img src="https://dev-zalbi-aisia-eta-abentura.pantheonsite.io/wp-content/uploads/2026/01/WhatsApp-Image-2026-01-19-at-10.40.18.jpeg" alt="<?php echo $t['cat1_tit']; ?>">
                    <div class="card-body">
                        <h3 class="text-pink"><?php echo $t['cat1_tit']; ?></h3>
                        <p><?php echo $t['cat1_desc']; ?></p>
                        <span class="link-more text-pink"><?php echo $t['ver_mas']; ?></span>
                    </div>
                </a>

                <a href="<?php echo zalbi_url_filtro($url_catalogo_base, $slug_acuaticos); ?>" class="options-box border-blue">
                    <img src="https://dev-zalbi-aisia-eta-abentura.pantheonsite.io/wp-content/uploads/2026/01/espuma1.jpeg" alt="<?php echo $t['cat2_tit']; ?>"> 
                    <div class="card-body">
                        <h3 class="text-blue"><?php echo $t['cat2_tit']; ?></h3> 
                        <p><?php echo $t['cat2_desc']; ?></p>
                        <span class="link-more text-blue"><?php echo $t['ver_mas']; ?></span>
                    </div>
                </a>

                <a href="<?php echo zalbi_url_filtro($url_catalogo_base, $slug_deportivos); ?>" class="options-box border-orange">
                     <img src="https://dev-zalbi-aisia-eta-abentura.pantheonsite.io/wp-content/uploads/2026/01/WhatsApp-Image-2026-01-19-at-14.48.29.jpeg" alt="<?php echo $t['cat3_tit']; ?>">
                    <div class="card-body">
                        <h3 class="text-orange"><?php echo $t['cat3_tit']; ?></h3>
                        <p><?php echo $t['cat3_desc']; ?></p>
                        <span class="link-more text-orange"><?php echo $t['ver_mas']; ?></span>
                    </div>
                </a>
                
                <a href="<?php echo zalbi_url_filtro($url_catalogo_base, $slug_juegos); ?>" class="options-box border-purple">
                    <img src="https://dev-zalbi-aisia-eta-abentura.pantheonsite.io/wp-content/uploads/2026/02/juegos_variados_puestos.jpeg" alt="<?php echo $t['cat5_tit']; ?>">
                    <div class="card-body">
                        <h3 class="text-purple"><?php echo $t['cat5_tit']; ?></h3>
                        <p><?php echo $t['cat5_desc']; ?></p>
                        <span class="link-more text-purple"><?php echo $t['ver_mas']; ?></span>
                    </div>
                </a>

                <a href="<?php echo $url_eventos; ?>" class="options-box border-green">
                  <img src="https://dev-zalbi-aisia-eta-abentura.pantheonsite.io/wp-content/uploads/2026/01/eventos_foto.jpeg" alt="<?php echo $t['cat4_tit']; ?>">
                  <div class="card-body">
                        <h3 class="text-green"><?php echo $t['cat4_tit']; ?></h3>
                        <p><?php echo $t['cat4_desc']; ?></p>
                        <span class="link-more text-green"><?php echo $t['ver_mas']; ?></span>
                    </div>
                </a>

            </div>
        </div>
    </section>

    <section class="section-pad">
        <div class="container">
            <div class="text-center mb-30">
                <h2 class="text-purple"><?php echo $t['presu_tit']; ?></h2>
                <p><?php echo $t['presu_sub']; ?></p>
            </div>
            
            <div class="clients-grid">
                
                <div class="client-card bg-green-light">
                    <h3 class="text-green"><?php echo $t['cli1_tit']; ?></h3>
                    <p><?php echo $t['cli1_desc']; ?></p>
                </div>

                <div class="client-card bg-pink-light">
                    <h3 class="text-pink"><?php echo $t['cli2_tit']; ?></h3>
                    <p><?php echo $t['cli2_desc']; ?></p>
                </div>

            </div>
        </div>
    </section>

<?php get_footer(); ?>