<?php
/* Template Name: Plantilla Eventos */
get_header(); 
?>

    <section class="hero-catalog" style="background: var(--c-green);">
        <div class="container">
            <h1>Eventos y Animación</h1>
            <p>Desde discomóviles y talleres</p>
        </div>
    </section>

    <div class="container filter-wrapper">
        <div class="filter-box">
            <button class="btn" style="background: var(--c-green); color: white;">Todos</button>
            </div>
    </div>

    <section class="section-pad">
        <div class="container grid-3">
            
            <?php
            // CONSULTA: Traer todos los 'evento'
            $args = array(
                'post_type' => 'evento',
                'posts_per_page' => -1,
            );
            $query = new WP_Query($args);

            if ($query->have_posts()) :
                while ($query->have_posts()) : $query->the_post();
                    
                    // Cargar campos ACF
                    $tag_texto = get_field('etiqueta_texto');
                    $tag_color = get_field('etiqueta_color');
                    $duracion = get_field('duracion');
                    $publico = get_field('publico');
            ?>

            <article class="product-card">
                 <div style="height: 300px; overflow: hidden;">
                     <a href="<?php the_permalink(); ?>">
                        <?php the_post_thumbnail('medium_large', array('style' => 'width:100%; height:100%; object-fit:contain; background: #f0f0f0;')); ?>
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
                    <a href="<?php the_permalink(); ?>" class="btn btn-outline" style="width: 100%; text-align: center;">Ver Características</a>
                    <a href="<?php echo home_url('/contacto'); ?>" class="btn btn-outline" style="width: 100%; text-align: center; border-color: var(--c-green); color: var(--c-green);">Consultar Disponibilidad</a>
                </div>
            </article>

            <?php 
                endwhile;
                wp_reset_postdata();
            else :
                echo '<p>No hay eventos disponibles.</p>';
            endif; 
            ?>

        </div>
    </section>

<?php get_footer(); ?>