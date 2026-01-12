<?php get_header(); ?>

<?php while ( have_posts() ) : the_post(); 
    $tag_texto = get_field('etiqueta_texto');
    $tag_color = get_field('etiqueta_color');
    $duracion = get_field('duracion');
    $publico = get_field('publico');
?>

    <div class="container section-pad">
        <p class="breadcrumbs">
            <a href="<?php echo home_url(); ?>">Inicio</a> > 
            <a href="<?php echo home_url('/eventos'); ?>">Eventos</a> > 
            <span><?php the_title(); ?></span>
        </p>

        <div class="product-detail-layout">
            
            <div>
                <?php the_post_thumbnail('large', array('class' => 'gallery-main')); ?>
            </div>

            <div>
                <h1 style="color: var(--c-purple); margin-bottom: 10px;"><?php the_title(); ?></h1>
                <span class="card-tag <?php echo $tag_color; ?>"><?php echo $tag_texto; ?></span>
                
                <div class="product-desc" style="margin-top: 20px;">
                    <?php the_content(); ?>
                </div>

                <div class="spec-box">
                    <h3>Detalles del Evento</h3>
                    <div class="spec-row">
                        <span><i class="fas fa-clock"></i> Duración</span>
                        <strong><?php echo $duracion; ?></strong>
                    </div>
                    <div class="spec-row" style="border:none;">
                        <span><i class="fas fa-users"></i> Público Ideal</span>
                        <strong><?php echo $publico; ?></strong>
                    </div>
                </div>

                <div class="cta-box" style="background: var(--c-green);">
                    <p style="margin-bottom: 10px; font-weight: bold;">¿Quieres organizar esto?</p>
                    <a href="<?php echo home_url('/contacto'); ?>" class="btn" style="background: white; color: var(--c-green); width: 100%;">Pedir Presupuesto</a>
                </div>
            </div>
        </div>
    </div>

<?php endwhile; ?>

<?php get_footer(); ?>