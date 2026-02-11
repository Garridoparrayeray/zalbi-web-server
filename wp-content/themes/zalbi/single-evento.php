<?php get_header(); ?>

<?php 
// --- 1. LÓGICA DE TRADUCCIÓN ---
$es_euskera = (function_exists('pll_current_language') && pll_current_language() == 'eu');

if ($es_euskera) {
    // === TEXTOS EN EUSKERA ===
    $txt_inicio       = 'Hasiera';
    $txt_eventos      = 'Ekitaldiak';
    $url_eventos      = home_url('/eu/ekitaldiak'); 
    $url_contacto     = home_url('/eu/kontaktua');  
    
    $txt_detalles     = 'Ekitaldiaren Xehetasunak';
    $txt_duracion     = 'Iraupena';
    $txt_publico      = 'Publiko aproposa';
    
    $txt_cta_preg     = 'Hau antolatu nahi duzu?';
    $txt_cta_btn      = 'Eskatu Aurrekontua';

} else {
    // === TEXTOS EN ESPAÑOL ===
    $txt_inicio       = 'Inicio';
    $txt_eventos      = 'Eventos';
    $url_eventos      = home_url('/eventos');
    $url_contacto     = home_url('/contacto');
    
    $txt_detalles     = 'Detalles del Evento';
    $txt_duracion     = 'Duración';
    $txt_publico      = 'Público Ideal';
    
    $txt_cta_preg     = '¿Quieres organizar esto?';
    $txt_cta_btn      = 'Pedir Presupuesto';
}

while ( have_posts() ) : the_post(); 
    $tag_texto = get_field('etiqueta_texto');
    $tag_color = get_field('etiqueta_color');
    $duracion  = get_field('duracion');
    $publico   = get_field('publico');
?>

    <div class="container section-pad">
        <p class="breadcrumbs">
            <a href="<?php echo home_url(); ?>"><?php echo $txt_inicio; ?></a> > 
            <a href="<?php echo $url_eventos; ?>"><?php echo $txt_eventos; ?></a> > 
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
                    <h3><?php echo $txt_detalles; ?></h3>
                    
                    <?php if($duracion): ?>
                    <div class="spec-row">
                        <span><i class="fas fa-clock"></i> <?php echo $txt_duracion; ?></span>
                        <strong><?php echo $duracion; ?></strong>
                    </div>
                    <?php endif; ?>

                    <?php if($publico): ?>
                    <div class="spec-row" style="border:none;">
                        <span><i class="fas fa-users"></i> <?php echo $txt_publico; ?></span>
                        <strong><?php echo $publico; ?></strong>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="cta-box" style="background: var(--c-green);">
                    <p style="margin-bottom: 10px; font-weight: bold;"><?php echo $txt_cta_preg; ?></p>
                    <a href="<?php echo $url_contacto; ?>" class="btn" style="background: white; color: var(--c-green); width: 100%;"><?php echo $txt_cta_btn; ?></a>
                </div>
            </div>
        </div>
    </div>

<?php endwhile; ?>

<?php get_footer(); ?>