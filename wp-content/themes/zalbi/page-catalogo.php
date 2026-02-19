<?php
/* Template Name: Plantilla Catalogo */
get_header(); 

// --- 1. DETECTAR IDIOMA ---
// Verificamos si estamos en Euskera
$es_euskera = (function_exists('pll_current_language') && pll_current_language() == 'eu');

// --- 2. CONFIGURACIÓN: ORDEN (SLUGS) ---
$orden_personalizado = array(
    // 1. PRIMERO: Hinchables / Puzgarriak
    'hinchable', 'hinchables', 'puzgarria', 'puzgarriak', 'puxgarriak', 
    
    // 2. SEGUNDO: Acuáticos / Uretakoak
    'acuatico', 'acuaticos', 'uretakoa', 'uretakoak',
    
    // 3. TERCERO: Deportivos / Kirol atrakzioak
    'atracciones-deportivas', 'deportivos', 'kirola', 'kirol-atrakzioak',

    // 4. CUARTO: Juegos / Jokoak (NUEVO)
    'juegos', 'juego', 'jokoak', 'jokoa'
);
?>

    <section class="hero-catalog">
        <div class="container">
            <h1><?php the_title(); ?></h1>
            
            <p>
                <?php echo $es_euskera ? 'Aukeratu kategoria iragazteko' : 'Selecciona una categoría para filtrar'; ?>
            </p>
        </div>
    </section>

    <div class="container filter-wrapper">
        <div class="filter-box">
            <button class="btn btn-pink filter-btn" data-filter="todos">
                <?php echo $es_euskera ? 'Guztiak' : 'Todos'; ?>
            </button>

            <?php
            // Obtener categorías reales de la base de datos 
            $terms = get_terms( array(
                'taxonomy' => 'tipo_hinchable',
                'hide_empty' => true, 
            ) );

            if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
                
                // ORDENAR LOS BOTONES
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

                // ORDENAR PRODUCTOS
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
                    $filtro_slug = (!empty($terms) && !is_wp_error($terms)) ? $terms[0]->slug : 'sin-categoria';
                    
                    $tag_color = get_field('etiqueta_color', get_the_ID());
                    $medidas = get_field('medidas', get_the_ID());
                    $capacidad = get_field('capacidad', get_the_ID());
                    // --- TRADUCTOR VISUAL ---
                    if ($es_euskera) {
                        // Textos en Euskera
                        $nombres_visuales = array(
                            'tag-orange' => 'Kirola',
                            'tag-pink'   => 'Puzgarria',
                            'tag-blue'   => 'Uretakoa',
                            'tag-green'  => 'Ekitaldia',
                            'tag-purple' => 'Jokoa' // NUEVO
                        );
                        $texto_boton = 'Ikusi Ezaugarriak';
                        $texto_visual_defecto = 'Puzgarria';
                    } else {
                        // Textos en Español
                        $nombres_visuales = array(
                            'tag-orange' => 'Deportivo',
                            'tag-pink'   => 'Hinchable',
                            'tag-blue'   => 'Acuatico',
                            'tag-green'  => 'Evento',
                            'tag-purple' => 'Juego' // NUEVO
                        );
                        $texto_boton = 'Ver Características';
                        $texto_visual_defecto = 'Hinchable';
                    }

                    $nombre_visual = isset($nombres_visuales[$tag_color]) ? $nombres_visuales[$tag_color] : $texto_visual_defecto;
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
                    
                    <a href="<?php the_permalink(); ?>" class="btn btn-outline" style="width: 100%; text-align: center;">
                        <?php echo $texto_boton; ?>
                    </a>
                </div>
            </article>

            <?php 
                endforeach; 
                wp_reset_postdata(); 
            else :
                echo '<p>' . ($es_euskera ? 'Ez dago puzgarririk eskuragarri.' : 'No hay hinchables disponibles.') . '</p>';
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
                // Comparamos con 'todos' (en minúscula y español, porque es el valor técnico data-filter)
                if (btn.getAttribute('data-filter') === category) {
                    // BOTÓN ACTIVO
                    btn.classList.remove('btn-outline');
                    btn.classList.add('btn-pink');
                } else {
                    // BOTÓN INACTIVO
                    btn.classList.add('btn-outline');
                    btn.classList.remove('btn-pink'); 
                    btn.classList.remove('btn-orange', 'btn-blue', 'btn-green', 'btn-lime', 'btn-purple'); 
                }
            });

            products.forEach(product => {
                // LÓGICA DE FILTRADO
                // El JS no cambia porque el data-filter sigue siendo el slug (uretakoa, acuatico, etc.)
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

        // Recuperar filtro desde la URL
        const params = new URLSearchParams(window.location.search);
        const urlCategory = params.get('cat');
        if (urlCategory) {
            setTimeout(() => filterProducts(urlCategory), 50);
        }
    });
    </script>

<?php get_footer(); ?>