?php get_header(); ?>

<?php while ( have_posts() ) : the_post(); 
    // DETECTAR IDIOMA
    $es_euskera = (function_exists('pll_current_language') && pll_current_language() == 'eu');

    // 1. Recuperamos campos ACF (usando get_the_ID() por seguridad)
    $medidas = get_field('medidas', get_the_ID());
    $capacidad = get_field('capacidad', get_the_ID());
    $tag_color = get_field('etiqueta_color', get_the_ID());
    
    // 2. Traductor de Colores
    if ($es_euskera) {
        $nombres_visuales = array(
            'tag-orange' => 'Kirola',
            'tag-pink'   => 'Puzgarria',
            'tag-blue'   => 'Uretakoa',
            'tag-green'  => 'Ekitaldia',
            'tag-purple' => 'Jokoa'
        );
        $texto_defecto = 'Puzgarria';
        
        $txt_inicio = 'Hasiera';
        $txt_catalogo = 'Katalogoa';
        $txt_categoria = 'Kategoria';
        $txt_ficha = 'Fitxa Teknikoa';
        $txt_medidas = 'Neurriak';
        $txt_capacidad = 'Edukiera';
        $txt_interesa = 'Eredu hau interesatzen zaizu?';
        $txt_btn_presu = 'Eskatu Aurrekontua';
        $txt_tambien = 'Interesgarria izan daiteke ere';
        $txt_ver_mas = 'Ikusi gehiago';
        
        $url_catalogo = home_url('/eu/katalogoa');
        $url_contacto = home_url('/eu/kontaktua');

    } else {
        $nombres_visuales = array(
            'tag-orange' => 'Atracción deportiva',
            'tag-pink'   => 'Hinchable',
            'tag-blue'   => 'Acuático',
            'tag-green'  => 'Evento',
            'tag-purple' => 'Juego'
        );
        $texto_defecto = 'Hinchable';

        $txt_inicio = 'Inicio';
        $txt_catalogo = 'Catálogo';
        $txt_categoria = 'Categoría';
        $txt_ficha = 'Ficha Técnica';
        $txt_medidas = 'Medidas';
        $txt_capacidad = 'Capacidad';
        $txt_interesa = '¿Te interesa este modelo?';
        $txt_btn_presu = 'Solicitar Presupuesto';
        $txt_tambien = 'También te puede interesar';
        $txt_ver_mas = 'Ver más';

        $url_catalogo = home_url('/catalogo');
        $url_contacto = home_url('/contacto');
    }
    
    $nombre_categoria_visual = isset($nombres_visuales[$tag_color]) ? $nombres_visuales[$tag_color] : $texto_defecto;
?>

    <style>
        .product-desc {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        /* Los textos y listas ocupan el 100% del ancho */
        .product-desc > *:not(figure):not(img):not(.wp-block-gallery) {
            flex: 1 1 100%;
            margin-bottom: 5px;
        }
        /* Las imágenes sueltas se auto-organizan en 2 columnas */
        .product-desc figure.wp-block-image, .product-desc p > img {
            flex: 1 1 calc(50% - 10px);
            margin: 0;
        }
        /* Todas las fotos a la misma altura, sin deformarse y con bordes redondeados */
        .product-desc figure img, .product-desc img {
            width: 100%;
            height: 180px; 
            object-fit: cover; 
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .product-desc .wp-block-gallery {
            flex: 1 1 100%;
        }
    </style>

    <div class="container section-pad">
        <p class="breadcrumbs">
            <a href="<?php echo home_url(); ?>"><?php echo $txt_inicio; ?></a> > 
            <a href="<?php echo $url_catalogo; ?>"><?php echo $txt_catalogo; ?></a> > 
            <span><?php the_title(); ?></span>
        </p>

        <div class="product-detail-layout">
            
            <div>
                <?php if ( has_post_thumbnail() ) { 
                    the_post_thumbnail('large', array('class' => 'gallery-main', 'style' => 'border-radius:10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);')); 
                } else { ?>
                    <img src="<?php echo get_template_directory_uri(); ?>/img/logo.webp" class="gallery-main" style="background:#eee; border-radius:10px;">
                <?php } ?>
            </div>

            <div>
                <h1 style="color: var(--c-purple); margin-bottom: 10px;"><?php the_title(); ?></h1>
                
                <span class="card-tag <?php echo $tag_color; ?>"><?php echo $txt_categoria; ?>: <?php echo $nombre_categoria_visual; ?></span>
                
                <div class="product-desc" style="margin-top: 20px;">
                    <?php the_content(); ?>
                </div>

                <div class="spec-box" style="margin-top: 25px;">
                    <h3><?php echo $txt_ficha; ?></h3>
                    
                    <div class="spec-row">
                        <span><i class="fas fa-ruler-combined"></i> <?php echo $txt_medidas; ?></span>
                        <strong><?php echo $medidas; ?></strong>
                    </div>
                    
                    <div class="spec-row">
                        <span><i class="fas fa-users"></i> <?php echo $txt_capacidad; ?></span>
                        <strong><?php echo $capacidad; ?></strong>
                    </div>
                </div>

                <div class="cta-box">
                    <p style="margin-bottom: 10px; font-weight: bold;"><?php echo $txt_interesa; ?></p>
                    <a href="<?php echo $url_contacto; ?>" class="btn" style="background: var(--c-lime); color: var(--c-purple); width: 100%;"><?php echo $txt_btn_presu; ?></a>
                </div>
            </div>
        </div>
    </div>

    <section class="section-pad" style="background: white; border-top: 1px solid #eee;">
        <div class="container">
            <h3 style="margin-bottom: 30px; color: var(--c-purple); text-align: center"><?php echo $txt_tambien; ?></h3>

            <div class="options" style = "justify-content: center;">
                <?php
                $related_args = array(
                    'post_type' => 'hinchable',
                    'posts_per_page' => 3,
                    'post__not_in' => array(get_the_ID()), 
                    'orderby' => 'rand'
                );
                $related = new WP_Query($related_args);

                if( $related->have_posts() ) {
                    while( $related->have_posts() ) {
                        $related->the_post();
                        
                        $r_tag_color = get_field('etiqueta_color', get_the_ID());
                        $r_nombre = isset($nombres_visuales[$r_tag_color]) ? $nombres_visuales[$r_tag_color] : $texto_defecto;
                ?>
                    
                    <a href="<?php the_permalink(); ?>" class="product-card" style="text-decoration: none; color: inherit; max-width: 300px; margin: 0;">
                        <div style="height: 200px; overflow: hidden;">
                            <?php the_post_thumbnail('medium', array('style' => 'width:100%; height:100%; object-fit:contain; background: #f0f0f0;')); ?>
                        </div>
                        <div class="card-content" style="padding: 15px;">
                            <span class="card-tag <?php echo $r_tag_color; ?>" style="font-size: 11px;"><?php echo $r_nombre; ?></span>
                            <h4 style="margin: 10px 0; font-size: 18px; color: var(--text-main);"><?php the_title(); ?></h4>
                            <span class="btn btn-outline" style="font-size: 12px; padding: 5px 15px; width: 100%; display:block; text-align:center;"><?php echo $txt_ver_mas; ?></span>
                        </div>
                    </a>

                <?php
                    }
                    wp_reset_postdata(); 
                }
                ?>
            </div>
        </div>
    </section>

<?php endwhile; ?>

<?php get_footer(); ?>