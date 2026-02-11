<?php
/* Template Name: Plantilla Eventos */
get_header();

// --- 1. DETECTAR IDIOMA ---
$es_euskera = (function_exists('pll_current_language') && pll_current_language() == 'eu');

// --- 2. TRADUCCIONES ---
if ($es_euskera) {
    // Euskera
    $t_titulo       = 'Ekitaldiak eta Animazioa';
    $t_subtitulo    = 'Diskomobilak eta tailerrak';
    $t_todos        = 'Guztiak';
    $t_ver_mas      = 'Ikusi Ezaugarriak';
    $t_dispo        = 'Galdetu Eskuragarritasuna';
    $t_no_hay       = 'Ez dago ekitaldirik eskuragarri.';
    $url_contacto   = home_url('/eu/kontaktua'); // Asegúrate que este es tu slug en euskera
} else {
    // Español
    $t_titulo       = 'Eventos y Animación';
    $t_subtitulo    = 'Desde discomóviles y talleres';
    $t_todos        = 'Todos';
    $t_ver_mas      = 'Ver Características';
    $t_dispo        = 'Consultar Disponibilidad';
    $t_no_hay       = 'No hay eventos disponibles.';
    $url_contacto   = home_url('/contacto');
}
?>

    <section class="hero-catalog" style="background: var(--c-green);">
        <div class="container">
            <h1><?php echo $t_titulo; ?></h1>
            <p><?php echo $t_subtitulo; ?></p>
        </div>
    </section>

    <div class="container filter-wrapper">
        <div class="filter-box">
            <button class="btn" style="background: var(--c-green); color: white;"><?php echo $t_todos; ?></button>
        </div>
    </div>

    <section class="section-pad">     
        <div class="container options" id="product-grid">
       
            <?php
            // CONSULTA: Traer todos los 'evento'
            // Polylang filtra automáticamente por idioma aquí
            $args = [
                'post_type' => 'evento',
                'posts_per_page' => -1,
            ];
            $query = new WP_Query($args);

            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();

                    // Cargar campos ACF (Se cargan del idioma correspondiente automáticamente)
                    $tag_texto = get_field('etiqueta_texto');
                    $tag_color = get_field('etiqueta_color');
                    $duracion = get_field('duracion');
                    $publico = get_field('publico');
            ?>

            <article class="product-card">
                 <div style="height: 300px; overflow: hidden;">
                     <a href="<?php the_permalink(); ?>">
                        <?php the_post_thumbnail('medium_large', ['style' => 'width:100%; height:100%; object-fit:contain; background: #f0f0f0;']); ?>
                    </a>
                </div>
                <div class="card-content">
                    <span class="card-tag <?php echo $tag_color; ?>"><?php echo $tag_texto; ?></span>
                    
                    <h3 class="card-title">
                        <?php the_title(); ?>
                    </h3>
         
                    <ul class="card-specs">
                        <li><i class="fas fa-clock" style="color: var(--c-green);"></i> <?php echo $duracion; ?></li>
                        <li><i class="fas fa-users" style="color: var(--c-green);"></i> <?php echo $publico; ?></li>
                    </ul>
                    
                    <a href="<?php the_permalink(); ?>" class="btn btn-outline" style="width: 100%; text-align: center; padding-bottom: 5px;">
                        <?php echo $t_ver_mas; ?>
                    </a>
                    
                    <a href="<?php echo $url_contacto; ?>" class="btn btn-outline" style="width: 100%; text-align: center; border-color: var(--c-green); color: var(--c-green);">
                        <?php echo $t_dispo; ?>
                    </a>
                </div>
            </article>

            <?php
                }
                wp_reset_postdata();
            } else {
                echo '<p>' . $t_no_hay . '</p>';
            }
            ?>
        </div>
    </section>

<?php get_footer(); ?>