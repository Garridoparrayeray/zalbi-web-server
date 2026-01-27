<?php
/**
 * zalbi-theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package zalbi-theme
 */

if ( ! defined( '_S_VERSION' ) ) {
    // Replace the version number of the theme on each release.
    define( '_S_VERSION', '1.0.0' );
}

/**
 * Sets up theme defaults and registers support for various WordPress features.
 */
function zalbi_setup() {
    /*
     * Make theme available for translation.
     */
    load_theme_textdomain( 'zalbi', get_template_directory() . '/languages' );

    // Add default posts and comments RSS feed links to head.
    add_theme_support( 'automatic-feed-links' );

    /*
     * Let WordPress manage the document title.
     */
    add_theme_support( 'title-tag' );

    /*
     * Enable support for Post Thumbnails on posts and pages.
     */
    add_theme_support( 'post-thumbnails' );

    // --- AQUÍ ES DONDE SE REGISTRAN LOS MENÚS CORRECTAMENTE ---
    register_nav_menus(
        array(
            'menu-1'     => esc_html__( 'Primary', 'zalbi' ),       // Menú Principal
            'menu-legal' => esc_html__( 'Menú Legal Footer', 'zalbi' ), // Menú del Pie de página
        )
    );

    /*
     * Switch default core markup for search form, comment form, and comments
     * to output valid HTML5.
     */
    add_theme_support(
        'html5',
        array(
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
            'style',
            'script',
        )
    );

    // Set up the WordPress core custom background feature.
    add_theme_support(
        'custom-background',
        apply_filters(
            'zalbi_custom_background_args',
            array(
                'default-color' => 'ffffff',
                'default-image' => '',
            )
        )
    );

    // Add theme support for selective refresh for widgets.
    add_theme_support( 'customize-selective-refresh-widgets' );

    /**
     * Add support for core custom logo.
     */
    add_theme_support(
        'custom-logo',
        array(
            'height'      => 250,
            'width'       => 250,
            'flex-width'  => true,
            'flex-height' => true,
        )
    );
}
add_action( 'after_setup_theme', 'zalbi_setup' );

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 */
function zalbi_content_width() {
    $GLOBALS['content_width'] = apply_filters( 'zalbi_content_width', 640 );
}
add_action( 'after_setup_theme', 'zalbi_content_width', 0 );

/**
 * Register widget area.
 */
function zalbi_widgets_init() {
    register_sidebar(
        array(
            'name'          => esc_html__( 'Sidebar', 'zalbi' ),
            'id'            => 'sidebar-1',
            'description'   => esc_html__( 'Add widgets here.', 'zalbi' ),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h2 class="widget-title">',
            'after_title'   => '</h2>',
        )
    );
}
add_action( 'widgets_init', 'zalbi_widgets_init' );

/**
 * Enqueue scripts and styles.
 */
function zalbi_scripts() {
    wp_enqueue_style( 'zalbi-style', get_stylesheet_uri(), array(), _S_VERSION );
    wp_style_add_data( 'zalbi-style', 'rtl', 'replace' );

    wp_enqueue_script( 'zalbi-navigation', get_template_directory_uri() . '/js/navigation.js', array(), _S_VERSION, true );
    
    // Fuentes y Iconos
    wp_enqueue_style( 'zalbi-fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css' );
    wp_enqueue_style( 'zalbi-google-fonts', 'https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&family=Open+Sans:wght@400;600&display=swap' );
    
    if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
        wp_enqueue_script( 'comment-reply' );
    }
}
add_action( 'wp_enqueue_scripts', 'zalbi_scripts' );

// 1. Registrar el Post Type "Hinchable"
function zalbi_register_hinchables() {
    $args = array(
        'labels' => array( 'name' => 'Hinchables', 'singular_name' => 'Hinchable' ),
        'public' => true,
        'has_archive' => false,
        'menu_icon' => 'dashicons-smiley',
        'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'rewrite' => array( 'slug' => 'hinchable' ), 
    );
    register_post_type( 'hinchable', $args );
}
add_action( 'init', 'zalbi_register_hinchables' );

// 2. Registrar la Taxonomía para el Filtro (Grandes, Medianos...)
function zalbi_register_taxonomies() {
    register_taxonomy( 'tipo_hinchable', 'hinchable', array(
        'labels' => array( 'name' => 'Tipos de Hinchable' ),
        'hierarchical' => true, 
        'show_admin_column' => true,
        'rewrite' => array( 'slug' => 'tipo' ),
    ) );
}
add_action( 'init', 'zalbi_register_taxonomies' );

// 3. Registrar el Post Type "Evento"
function zalbi_register_eventos() {
    $args = array(
        'labels' => array( 'name' => 'Eventos', 'singular_name' => 'Evento' ),
        'public' => true,
        'has_archive' => false,
        'menu_icon' => 'dashicons-calendar-alt', 
        'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'rewrite' => array( 'slug' => 'evento' ), 
    );
    register_post_type( 'evento', $args );
}
add_action( 'init', 'zalbi_register_eventos' );
/* --- Añadir campo de WhatsApp en Ajustes > Generales --- */
function zalbi_register_whatsapp_setting() {
    register_setting('general', 'zalbi_whatsapp_number', 'esc_attr');
    
    add_settings_field(
        'zalbi_whatsapp_number', 
        '<label for="zalbi_whatsapp_number">' . __('Número de WhatsApp', 'zalbi_whatsapp_number') . '</label>', 
        'zalbi_whatsapp_number_html', 
        'general'
    );
}

function zalbi_whatsapp_number_html() {
    $value = get_option('zalbi_whatsapp_number', '');
    echo '<input type="text" id="zalbi_whatsapp_number" name="zalbi_whatsapp_number" value="' . $value . '" class="regular-text" placeholder="Ej: 34600123456" />';
    echo '<p class="description">Introduce el número con el prefijo del país (34 para España) y sin espacios ni símbolos.</p>';
}

add_action('admin_init', 'zalbi_register_whatsapp_setting');

/**
 * Implement the Custom Header feature.
 */
require get_template_directory() . '/inc/custom-header.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Functions which enhance the theme by hooking into WordPress.
 */
require get_template_directory() . '/inc/template-functions.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

/**
 * Load Jetpack compatibility file.
 */
if ( defined( 'JETPACK__VERSION' ) ) {
    require get_template_directory() . '/inc/jetpack.php';
}
?>