<?php
/**
 *endanger Theme Customizer
 *
 * @package Endanger
 */

/**
 * Add postMessage support for site title and description for the Theme Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function endanger_customize_register( $wp_customize ) {
	
	//Add a class for titles
    class endanger_Info extends WP_Customize_Control {
        public $type = 'info';
        public $label = '';
        public function render_content() {
        ?>
			<h3><?php echo esc_html( $this->label ); ?></h3>
        <?php
        }
    }
	
	function endanger_sanitize_checkbox( $checked ) {
		// Boolean check.
		return ( ( isset( $checked ) && true == $checked ) ? true : false );
	}
	
	
	$wp_customize->get_setting( 'blogname' )->transport         = 'postMessage';
	$wp_customize->get_setting( 'blogdescription' )->transport  = 'postMessage';	
	
	$wp_customize->add_setting('color_scheme',array(
			'default'	=> '#1d9d74',
			'sanitize_callback'	=> 'sanitize_hex_color'
	));
	
	$wp_customize->add_control(
		new WP_Customize_Color_Control($wp_customize,'color_scheme',array(
			'label' => __('Color Scheme','endanger'),			
			 'description'	=> __('More color options in PRO Version','endanger'),
			'section' => 'colors',
			'settings' => 'color_scheme'
		))
	);
	
	// Slider Section		
	$wp_customize->add_section(
        'slider_section',
        array(
            'title' => __('Slider Settings', 'endanger'),
            'priority' => null,
			'description'	=> __('Featured Image Size Should be same ( 1400x600 ) More slider settings available in PRO Version.','endanger'),            			
        )
    );	
	$wp_customize->add_setting('page-setting7',array(
			'default'	=> '0',			
			'capability' => 'edit_theme_options',
			'sanitize_callback'	=> 'absint'
	));
	
	$wp_customize->add_control('page-setting7',array(
			'type'	=> 'dropdown-pages',
			'label'	=> __('Select page for slide one:','endanger'),
			'section'	=> 'slider_section'
	));	
	
	$wp_customize->add_setting('page-setting8',array(
			'default'	=> '0',			
			'capability' => 'edit_theme_options',
			'sanitize_callback'	=> 'absint'
	));
	
	$wp_customize->add_control('page-setting8',array(
			'type'	=> 'dropdown-pages',
			'label'	=> __('Select page for slide two:','endanger'),
			'section'	=> 'slider_section'
	));	
	
	$wp_customize->add_setting('page-setting9',array(
			'default'	=> '0',			
			'capability' => 'edit_theme_options',
			'sanitize_callback'	=> 'absint'
	));
	
	$wp_customize->add_control('page-setting9',array(
			'type'	=> 'dropdown-pages',
			'label'	=> __('Select page for slide three:','endanger'),
			'section'	=> 'slider_section'
	));	// Slider Section
	
	$wp_customize->add_setting('slider_readmore',array(
	 		'default'	=> null,
			'sanitize_callback'	=> 'sanitize_text_field'
	 ));
	 
	 $wp_customize->add_control('slider_readmore',array(
	 		'settings'	=> 'slider_readmore',
			'section'	=> 'slider_section',
			'label'		=> __('Add text for slide read more button','endanger'),
			'type'		=> 'text'
	 ));// Slider Read more	
	
	$wp_customize->add_setting('disabled_slides',array(
				'default' => true,
				'sanitize_callback' => 'endanger_sanitize_checkbox',
				'capability' => 'edit_theme_options',
	));	 
	
	$wp_customize->add_control( 'disabled_slides', array(
			   'settings' => 'disabled_slides',
			   'section'   => 'slider_section',
			   'label'     => __('Uncheck To Enable This Section','endanger'),
			   'type'      => 'checkbox'
	 ));//Disable Slider Section	
	
	
	
	// Homepage About UsSection 	
	$wp_customize->add_section('section_first',array(
		'title'	=> __('Homepage About Us Section','endanger'),
		'description'	=> __('Select Page from the dropdown for about us section','endanger'),
		'priority'	=> null
	));
	
	$wp_customize->add_setting('page-setting1',	array(
			'default'	=> '0',			
			'capability' => 'edit_theme_options',
			'sanitize_callback'	=> 'absint'
		));
 
	$wp_customize->add_control(	'page-setting1',array('type' => 'dropdown-pages',			
			'section' => 'section_first',
	));
	
	$wp_customize->add_setting('disabled_aboutpg',array(
			'default' => true,
			'sanitize_callback' => 'endanger_sanitize_checkbox',
			'capability' => 'edit_theme_options',
	));	 
	
	$wp_customize->add_control( 'disabled_aboutpg', array(
			   'settings' => 'disabled_aboutpg',
			   'section'   => 'section_first',
			   'label'     => __('Uncheck To Enable This Section','endanger'),
			   'type'      => 'checkbox'
	 ));//Home Welcome Section 	
	
	// Home Three Boxes Section 	
	$wp_customize->add_section('section_second', array(
		'title'	=> __('Homepage Four Boxes Section','endanger'),
		'description'	=> __('Select Pages from the dropdown for homepage four boxes section','endanger'),
		'priority'	=> null
	));		
	
	$wp_customize->add_setting('page-column1',	array(
			'default'	=> '0',			
			'capability' => 'edit_theme_options',
			'sanitize_callback'	=> 'absint'
		));
 
	$wp_customize->add_control(	'page-column1',array('type' => 'dropdown-pages',			
			'section' => 'section_second',
	));		
	
	$wp_customize->add_setting('page-column2',	array(
			'default'	=> '0',			
			'capability' => 'edit_theme_options',
			'sanitize_callback'	=> 'absint'
		));
 
	$wp_customize->add_control(	'page-column2',array('type' => 'dropdown-pages',			
			'section' => 'section_second',
	));
	
	$wp_customize->add_setting('page-column3',	array(
			'default'	=> '0',			
			'capability' => 'edit_theme_options',
			'sanitize_callback'	=> 'absint'
		));
 
	$wp_customize->add_control(	'page-column3',array('type' => 'dropdown-pages',			
			'section' => 'section_second',
	));
	
	$wp_customize->add_setting('page-column4',	array(
			'default'	=> '0',			
			'capability' => 'edit_theme_options',
			'sanitize_callback'	=> 'absint'
		));
 
	$wp_customize->add_control(	'page-column4',array('type' => 'dropdown-pages',			
			'section' => 'section_second',
	));//end four column page boxes
	
	$wp_customize->add_setting('disabled_pgboxes',array(
			'default' => true,
			'sanitize_callback' => 'endanger_sanitize_checkbox',
			'capability' => 'edit_theme_options',
	));	 
	
	$wp_customize->add_control( 'disabled_pgboxes', array(
			   'settings' => 'disabled_pgboxes',
			   'section'   => 'section_second',
			   'label'     => __('Uncheck To Enable This Section','endanger'),
			   'type'      => 'checkbox'
	 ));//Disable page boxes Section	
		
}
add_action( 'customize_register', 'endanger_customize_register' );

function endanger_custom_css(){
		?>
        	<style type="text/css"> 
					
					a, .blog_lists h2 a:hover,
					#sidebar ul li a:hover,									
					.blog_lists h3 a:hover,
					.cols-4 ul li a:hover, .cols-4 ul li.current_page_item a,
					.recent-post h6:hover,					
					.fourbox:hover h3,
					.footer-icons a:hover,
					.sitenav ul li a:hover, .sitenav ul li.current_page_item a, 
					.postmeta a:hover
					{ color:<?php echo esc_html( get_theme_mod('color_scheme','#1d9d74')); ?>;}
					 
					
					.pagination ul li .current, .pagination ul li a:hover, 
					#commentform input#submit:hover,					
					.nivo-controlNav a.active,
					.ReadMore,
					.slide_info .slide_more,
					.appbutton:hover,					
					h3.widget-title,									
					#sidebar .search-form input.search-submit,				
					.wpcf7 input[type='submit']					
					{ background-color:<?php echo esc_html( get_theme_mod('color_scheme','#1d9d74')); ?>;}
					
					
					.footer-icons a:hover							
					{ border-color:<?php echo esc_html( get_theme_mod('color_scheme','#1d9d74')); ?>;}					
					
					
			</style> 
<?php   
}
         
add_action('wp_head','endanger_custom_css');	 

/**
 * Binds JS handlers to make Theme Customizer preview reload changes asynchronously.
 */
function endanger_customize_preview_js() {
	wp_enqueue_script( 'endanger_customizer', get_template_directory_uri() . '/js/customize-preview.js', array( 'customize-preview' ), '20130508', true );
}
add_action( 'customize_preview_init', 'endanger_customize_preview_js' );