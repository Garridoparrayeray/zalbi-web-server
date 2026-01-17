<?php get_header(); ?>

<?php while ( have_posts() ) : the_post(); 
    // 1. Recuperamos los campos de ACF del producto actual
    $medidas = get_field('medidas');
    $capacidad = get_field('capacidad');
    $potencia = get_field('potencia');
    $certificacion = get_field('certificacion');
    $tag_color = get_field('etiqueta_color');
    
    // 2. Definimos el Traductor de Colores (Lo definimos aquí para usarlo arriba y abajo)
    $nombres_visuales = array(
        //'tag-purple' => 'Pequeño',
        'tag-orange' => 'Atraccion deportiva',
        'tag-pink'   => 'Hinchable',
        'tag-blue'   => 'Acuático',
        //'tag-lime'   => 'Mecánicos', 
        'tag-green'  => 'Evento'
    );
    
    // Nombre visual del producto actual
    $nombre_categoria_visual = isset($nombres_visuales[$tag_color]) ? $nombres_visuales[$tag_color] : 'Hinchable';
?>

    <div class="container section-pad">
        <p class="breadcrumbs">
            <a href="<?php echo home_url(); ?>">Inicio</a> > 
            <a href="<?php echo home_url('/catalogo'); ?>">Catálogo</a> > 
            <span><?php the_title(); ?></span>
        </p>

        <div class="product-detail-layout">
            
            <div>
                <?php if ( has_post_thumbnail() ) { 
                    the_post_thumbnail('large', array('class' => 'gallery-main')); 
                } else { ?>
                    <img src="<?php echo get_template_directory_uri(); ?>/img/logo.webp" class="gallery-main" style="background:#eee;">
                <?php } ?>
            </div>

            <div>
                <h1 style="color: var(--c-purple); margin-bottom: 10px;"><?php the_title(); ?></h1>
                
                <span class="card-tag <?php echo $tag_color; ?>">Categoría: <?php echo $nombre_categoria_visual; ?></span>
                
                <div class="product-desc" style="margin-top: 20px;">
                    <?php the_content(); ?>
                </div>

                <div class="spec-box">
                    <h3>Ficha Técnica</h3>
                    
                    <div class="spec-row">
                        <span><i class="fas fa-ruler-combined"></i> Medidas</span>
                        <strong><?php echo $medidas; ?></strong>
                    </div>
                    
                    <div class="spec-row">
                        <span><i class="fas fa-users"></i> Capacidad</span>
                        <strong><?php echo $capacidad; ?></strong>
                    </div>
                </div>

                <div class="cta-box">
                    <p style="margin-bottom: 10px; font-weight: bold;">¿Te interesa este modelo?</p>
                    <a href="<?php echo home_url('/contacto'); ?>" class="btn" style="background: var(--c-lime); color: var(--c-purple); width: 100%;">Solicitar Presupuesto</a>
                </div>
            </div>
        </div>
    </div>

    <section class="section-pad" style="background: white; border-top: 1px solid #eee;">
        <div class="container">
            <h3 style="margin-bottom: 30px; color: var(--c-purple); text-align: center">También te puede interesar</h3>

            <div class="options" style = "justify-content: center;">
                <?php
                // Configuración de la consulta de relacionados
                $related_args = array(
                    'post_type' => 'hinchable',
                    'posts_per_page' => 3,             // Mostramos 3
                    'post__not_in' => array(get_the_ID()), // Excluir el que estamos viendo
                    'orderby' => 'rand'                // Aleatorios
                );
                $related = new WP_Query($related_args);

                if( $related->have_posts() ) {
                    while( $related->have_posts() ) {
                        $related->the_post();
                        
                        // Recuperamos datos para la tarjeta mini
                        $r_tag_color = get_field('etiqueta_color');
                        $r_nombre = isset($nombres_visuales[$r_tag_color]) ? $nombres_visuales[$r_tag_color] : 'Hinchable';
                ?>
                    
                    <a href="<?php the_permalink(); ?>" class="product-card" style="text-decoration: none; color: inherit; max-width: 300px; margin: 0;">
                        <div style="height: 200px; overflow: hidden;">
                            <?php the_post_thumbnail('medium', array('style' => 'width:100%; height:100%; object-fit:contain; background: #f0f0f0;')); ?>
                        </div>
                        <div class="card-content" style="padding: 15px;">
                            <span class="card-tag <?php echo $r_tag_color; ?>" style="font-size: 11px;"><?php echo $r_nombre; ?></span>
                            <h4 style="margin: 10px 0; font-size: 18px; color: var(--text-main);"><?php the_title(); ?></h4>
                            <span class="btn btn-outline" style="font-size: 12px; padding: 5px 15px; width: 100%; display:block; text-align:center;">Ver más</span>
                        </div>
                    </a>

                <?php
                    }
                    wp_reset_postdata(); // IMPORTANTE: Reseteamos para no romper el bucle principal
                } else {
                    echo '<p style="text-align:center; width:100%;">Echa un vistazo a nuestro catálogo completo.</p>';
                }
                ?>
            </div>
        </div>
    </section>

<?php endwhile; ?>

<?php get_footer(); ?>