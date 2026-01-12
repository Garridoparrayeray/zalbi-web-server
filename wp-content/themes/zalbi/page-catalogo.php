<?php
/* Template Name: Plantilla Catalogo */
get_header(); 

// --- 1. CONFIGURACIÓN: ORDEN (SLUGS) ---
// IMPORTANTE: Aquí NO uses tildes ni ñ. Usa los slugs de WordPress.
// Si en el admin se llama "Mecánicos", aquí pon 'mecanicos'.
// Si se llama "Pequeños", aquí pon 'pequenos'.
$orden_personalizado = array(
    'pequenos',   
    'medianos',   
    'grandes',    
    'deportivos', 
    'mecanicos',
    'eventos'
);
?>

    <section class="hero-catalog">
        <div class="container">
            <h1>Nuestros Hinchables</h1>
            <p>Selecciona una categoría para filtrar</p>
        </div>
    </section>

    <div class="container filter-wrapper">
        <div class="filter-box">
            <button class="btn btn-pink filter-btn" data-filter="todos">Todos</button>

            <?php
            // Obtener categorías reales de la base de datos
            $terms = get_terms( array(
                'taxonomy' => 'tipo_hinchable',
                'hide_empty' => true, 
            ) );

            if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
                
                // ORDENAR LOS BOTONES SEGÚN TU LISTA PERSONALIZADA
                usort($terms, function($a, $b) use ($orden_personalizado) {
                    $pos_a = array_search($a->slug, $orden_personalizado);
                    $pos_b = array_search($b->slug, $orden_personalizado);
                    // Si una categoría no está en tu lista, la manda al final
                    return (($pos_a === false ? 999 : $pos_a) - ($pos_b === false ? 999 : $pos_b));
                });

                // PINTAR BOTONES
                foreach ( $terms as $term ) {
                    echo '<button class="btn btn-outline filter-btn" data-filter="' . esc_attr( $term->slug ) . '">' . esc_html( $term->name ) . '</button>';
                }
            }
            ?>
        </div>
    </div>

    <section class="section-pad">
        <div class="container options" id="product-grid">
            
            <?php
            // Consulta de productos
            $args = array(
                'post_type' => 'hinchable',
                'posts_per_page' => -1,
            );
            $query = new WP_Query($args);

            if ($query->have_posts()) :
                
                $posts_array = $query->posts;

                // ORDENAR PRODUCTOS (Para que salgan ordenados al dar a "Todos")
                usort($posts_array, function($a, $b) use ($orden_personalizado) {
                    $terms_a = get_the_terms($a->ID, 'tipo_hinchable');
                    $slug_a = (!empty($terms_a) && !is_wp_error($terms_a)) ? $terms_a[0]->slug : '';

                    $terms_b = get_the_terms($b->ID, 'tipo_hinchable');
                    $slug_b = (!empty($terms_b) && !is_wp_error($terms_b)) ? $terms_b[0]->slug : '';

                    $pos_a = array_search($slug_a, $orden_personalizado);
                    $pos_b = array_search($slug_b, $orden_personalizado);

                    return (($pos_a === false ? 999 : $pos_a) - ($pos_b === false ? 999 : $pos_b));
                });

                foreach ($posts_array as $post) : 
                    setup_postdata($post); 
                    
                    // Datos del producto
                    $terms = get_the_terms( get_the_ID(), 'tipo_hinchable' );
                    // Fallback 'sin-categoria' por si se te olvidó marcar el check
                    $filtro_slug = (!empty($terms) && !is_wp_error($terms)) ? $terms[0]->slug : 'sin-categoria';
                    
                    $tag_color = get_field('etiqueta_color');
                    $medidas = get_field('medidas');
                    $capacidad = get_field('capacidad');

                    // --- 2. TRADUCTOR VISUAL (Lo que ve el cliente) ---
                    $nombres_visuales = array(
                        'tag-purple' => 'Pequeño',
                        'tag-orange' => 'Mediano',
                        'tag-pink'   => 'Grande',
                        'tag-blue'   => 'Deportivo',
                        'tag-lime'   => 'Mecánicos', 
                        'tag-green'  => 'Evento'
                    );
                    $nombre_visual = isset($nombres_visuales[$tag_color]) ? $nombres_visuales[$tag_color] : 'Hinchable';
            ?>

            <article class="product-card" data-category="<?php echo esc_attr($filtro_slug); ?>"> 
                
                <div style="height: 300px; overflow: hidden;">
                     <a href="<?php the_permalink(); ?>">
                        <?php the_post_thumbnail('medium_large', array('style' => 'width:100%; height:100%; object-fit:contain; background: #f0f0f0;')); ?>
                    </a>
                </div>

                <div class="card-content">
                    <span class="card-tag <?php echo $tag_color; ?>"><?php echo $nombre_visual; ?></span>
                    
                    <h3 class="card-title"><?php the_title(); ?></h3>
                    
                    <ul class="card-specs">
                        <li><i class="fas fa-ruler-combined"></i> <?php echo $medidas; ?></li>
                        <li><i class="fas fa-users"></i> <?php echo $capacidad; ?></li>
                    </ul>
                    
                    <a href="<?php the_permalink(); ?>" class="btn btn-outline" style="width: 100%; text-align: center;">Ver Características</a>
                </div>
            </article>

            <?php 
                endforeach; 
                wp_reset_postdata(); 
            else :
                echo '<p>No hay hinchables disponibles.</p>';
            endif; 
            ?>

        </div>
    </section>

    <script>
    document.addEventListener("DOMContentLoaded", () => {
        const buttons = document.querySelectorAll('.filter-btn');
        const products = document.querySelectorAll('.product-card');

        function filterProducts(category) {
            buttons.forEach(btn => {
                if (btn.getAttribute('data-filter') === category) {
                    // BOTÓN ACTIVO
                    btn.classList.remove('btn-outline');
                    btn.classList.add('btn-pink');
                } else {
                    // BOTÓN INACTIVO (Limpiamos todos los colores posibles)
                    btn.classList.add('btn-outline');
                    btn.classList.remove('btn-pink'); 
                    // Añadimos btn-lime y btn-purple a la limpieza
                    btn.classList.remove('btn-orange', 'btn-blue', 'btn-green', 'btn-lime', 'btn-purple'); 
                }
            });

            products.forEach(product => {
                // LÓGICA DE FILTRADO
                // Comprobamos si es 'todos' O si la categoría coincide
                if (category === 'todos' || product.getAttribute('data-category') === category) {
                    product.style.display = 'block'; 
                    // Animación suave
                    product.style.opacity = '0';
                    product.style.transform = 'translateY(20px)';
                    setTimeout(() => {
                        product.style.transition = 'all 0.3s ease';
                        product.style.opacity = '1';
                        product.style.transform = 'translateY(0)';
                    }, 50);
                } else {
                    product.style.display = 'none'; 
                }
            });
        }

        buttons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault(); 
                filterProducts(btn.getAttribute('data-filter'));
            });
        });

        // Recuperar filtro desde la URL (si vienes de la home)
        const params = new URLSearchParams(window.location.search);
        const urlCategory = params.get('cat');
        if (urlCategory) {
            setTimeout(() => filterProducts(urlCategory), 50);
        }
    });
    </script>

<?php get_footer(); ?>
