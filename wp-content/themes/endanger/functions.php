<?php 
/**
 *Endanger functions and definitions 
 *
 * @package Endanger
 */

/**
 * Set the content width based on the theme's design and stylesheet.
 */

if ( ! function_exists( 'endanger_setup' ) ) : 
/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which runs
 * before the init hook. The init hook is too late for some features, such as indicating
 * support post thumbnails.  
 */
function endanger_setup() { 
	global $content_width; 
	if ( ! isset( $content_width ) )
		$content_width = 640; /* pixels */

	load_theme_textdomain( 'endanger', get_template_directory() . '/languages' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support('woocommerce');
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'custom-header', array( 
		'default-text-color' => false,
		'header-text' => false,
	) );	
	add_theme_support( 'title-tag' );	
	add_theme_support( 'custom-logo', array(
		'height'      => 100,
		'width'       => 250,
		'flex-height' => true,
	) );
	register_nav_menus( array(
		'primary' => __( 'Primary Menu', 'endanger' ),
		'footer' => __( 'Footer Menu', 'endanger' ),
	) );
	add_theme_support( 'custom-background', array(
		'default-color' => 'ffffff'
	) );
	add_editor_style( 'editor-style.css' );
} 
endif; // endanger_setup
add_action( 'after_setup_theme', 'endanger_setup' );

function endanger_widgets_init() { 	
	register_sidebar( array(
		'name'          => __( 'Blog Sidebar', 'endanger' ),
		'description'   => __( 'Appears on blog page sidebar', 'endanger' ),
		'id'            => 'sidebar-1',
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	) );
	
	register_sidebar( array(
		'name'          => __( 'Footer Widget 1', 'endanger' ),
		'description'   => __( 'Appears on footer', 'endanger' ),
		'id'            => 'footer-1',
		'before_widget' => '<aside id="%1$s" class="cols-4 widget-column-1 %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h5>',
		'after_title'   => '</h5>',
	) );
	
	register_sidebar( array(
		'name'          => __( 'Footer Widget 2', 'endanger' ),
		'description'   => __( 'Appears on footer', 'endanger' ),
		'id'            => 'footer-2',
		'before_widget' => '<aside id="%1$s" class="cols-4 widget-column-2 %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h5>',
		'after_title'   => '</h5>',
	) );
	
	register_sidebar( array(
		'name'          => __( 'Footer Widget 3', 'endanger' ),
		'description'   => __( 'Appears on footer', 'endanger' ),
		'id'            => 'footer-3',
		'before_widget' => '<aside id="%1$s" class="cols-4 widget-column-3 %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h5>',
		'after_title'   => '</h5>',
	) );
	
	register_sidebar( array(
		'name'          => __( 'Footer Widget 4', 'endanger' ),
		'description'   => __( 'Appears on footer', 'endanger' ),
		'id'            => 'footer-4',
		'before_widget' => '<aside id="%1$s" class="cols-4 widget-column-4 %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h5>',
		'after_title'   => '</h5>',
	) );	
	
}
add_action( 'widgets_init', 'endanger_widgets_init' );


function endanger_font_url(){
		$font_url = '';		
		
		/* Translators: If there are any character that are not
		* supported by Montserrat, trsnalate this to off, do not
		* translate into your own language.
		*/
		$montserrat = _x('on','montserrat:on or off','endanger');		
		
		if('off' !== $montserrat ){
			$font_family = array();
			
			if('off' !== $montserrat){
				$font_family[] = 'Montserrat:300,400,600,700,800,900';
			}
					
						
			$query_args = array(
				'family'	=> urlencode(implode('|',$font_family)),
			);
			
			$font_url = add_query_arg($query_args,'//fonts.googleapis.com/css');
		}
		
	return $font_url;
	}


function endanger_scripts() {
	wp_enqueue_style('endanger-font', endanger_font_url(), array());
	wp_enqueue_style( 'endanger-basic-style', get_stylesheet_uri() );	
	wp_enqueue_style( 'nivo-slider', get_template_directory_uri()."/css/nivo-slider.css" );
	wp_enqueue_style( 'endanger-responsive', get_template_directory_uri()."/css/responsive.css" );		
	wp_enqueue_style( 'endanger-default', get_template_directory_uri()."/css/default.css" );
	wp_enqueue_script( 'jquery-nivo-slider', get_template_directory_uri() . '/js/jquery.nivo.slider.js', array('jquery') );
	wp_enqueue_script( 'endanger-custom', get_template_directory_uri() . '/js/custom.js' );
	wp_enqueue_style( 'font-awesome', get_template_directory_uri()."/css/font-awesome.css" );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'endanger_scripts' );

function endanger_ie_stylesheet(){
	// Load the Internet Explorer specific stylesheet.
	wp_enqueue_style('endanger-ie', get_template_directory_uri().'/css/ie.css', array( 'endanger-style' ), '20160928' );
	wp_style_add_data('endanger-ie','conditional','lt IE 10');
	
	// Load the Internet Explorer 8 specific stylesheet.
	wp_enqueue_style( 'endanger-ie8', get_template_directory_uri() . '/css/ie8.css', array( 'endanger-style' ), '20160928' );
	wp_style_add_data( 'endanger-ie8', 'conditional', 'lt IE 9' );

	// Load the Internet Explorer 7 specific stylesheet.
	wp_enqueue_style( 'endanger-ie7', get_template_directory_uri() . '/css/ie7.css', array( 'endanger-style' ), '20160928' );
	wp_style_add_data( 'endanger-ie7', 'conditional', 'lt IE 8' );	
	}
add_action('wp_enqueue_scripts','endanger_ie_stylesheet');

define('ENDANGER_THEME_DOC','https://gracethemes.com/documentation/endanger-doc/#homepage-lite','endanger');
define('ENDANGER_PROTHEME_URL','https://gracethemes.com/themes/adventure-tour-wordpress-theme/','endanger');
define('ENDANGER_LIVE_DEMO','https://gracethemes.com/demo/endanger/','endanger');


/**
 * Load Jetpack compatibility file.
 */
require get_template_directory() . '/inc/jetpack.php';

if ( ! function_exists( 'endanger_the_custom_logo' ) ) :
/**
 * Displays the optional custom logo.
 *
 * Does nothing if the custom logo is not available.
 *
 */
function endanger_the_custom_logo() {
	if ( function_exists( 'the_custom_logo' ) ) {
		the_custom_logo();
	}
}
endif;


/**
 * Implement the Custom Header feature.
 */
require get_template_directory() . '/inc/custom-header.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Custom template for about theme.
 */
require get_template_directory() . '/inc/about-themes.php';

/**
 * Custom functions that act independently of the theme templates.
 */
require get_template_directory() . '/inc/extras.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

