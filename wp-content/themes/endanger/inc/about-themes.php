<?php
/**
 *endanger About Theme
 *
 * @package Endanger
 */

//about theme info
add_action( 'admin_menu', 'endanger_abouttheme' );
function endanger_abouttheme() {    	
	add_theme_page( __('About Theme Info', 'endanger'), __('About Theme Info', 'endanger'), 'edit_theme_options', 'endanger_guide', 'endanger_mostrar_guide');   
} 

//guidline for about theme
function endanger_mostrar_guide() { 
?>
<div class="wrap-GT">
	<div class="gt-left">
   		   <div class="heading-gt">
			  <h3><?php _e('About Theme Info', 'endanger'); ?></h3>
		   </div>
          <p><?php _e('Endanger is a Free Adventure WordPress theme. It is Perfect for all Corporate, Professional, personal, sport, news and any type of business. It is user friendly customizer options and Compatible in wordPress Latest Version. also Compatible with WooCommerce, Nextgen gallery ,Contact Form 7 and many WordPress popular plugins.','endanger'); ?></p>
<div class="heading-gt"> <?php _e('Theme Features', 'endanger'); ?></div>
 

<div class="col-2">
  <h4><?php _e('Theme Customizer', 'endanger'); ?></h4>
  <div class="description"><?php _e('The built-in customizer panel quickly change aspects of the design and display changes live before saving them.', 'endanger'); ?></div>
</div>

<div class="col-2">
  <h4><?php _e('Responsive Ready', 'endanger'); ?></h4>
  <div class="description"><?php _e('The themes layout will automatically adjust and fit on any screen resolution and looks great on any device. Fully optimized for iPhone and iPad.', 'endanger'); ?></div>
</div>

<div class="col-2">
<h4><?php _e('Cross Browser Compatible', 'endanger'); ?></h4>
<div class="description"><?php _e('Our themes are tested in all mordern web browsers and compatible with the latest version including Chrome,Firefox, Safari, Opera, IE11 and above.', 'endanger'); ?></div>
</div>

<div class="col-2">
<h4><?php _e('E-commerce', 'endanger'); ?></h4>
<div class="description"><?php _e('Fully compatible with WooCommerce plugin. Just install the plugin and turn your site into a full featured online shop and start selling products.', 'endanger'); ?></div>
</div>

</div><!-- .gt-left -->
	
	<div class="gt-right">			
			<div>
            <hr />  				
				<a href="<?php echo ENDANGER_LIVE_DEMO; ?>" target="_blank"><?php _e('Live Demo', 'endanger'); ?></a> | 
				<a href="<?php echo ENDANGER_PROTHEME_URL; ?>" target="_blank"><?php _e('Purchase Pro', 'endanger'); ?></a> | 
				<a href="<?php echo ENDANGER_THEME_DOC; ?>" target="_blank"><?php _e('Documentation', 'endanger'); ?></a>
              
				<hr />  
                <ul>
                 <li><?php _e('Theme Customizer', 'endanger'); ?></li>
                 <li><?php _e('Responsive Ready', 'endanger'); ?></li>
                 <li><?php _e('Cross Browser Compatible', 'endanger'); ?></li>
                 <li><?php _e('E-commerce', 'endanger'); ?></li>
                 <li><?php _e('Contact Form 7 Plugin Compatible', 'endanger'); ?></li>  
                 <li><?php _e('User Friendly', 'endanger'); ?></li> 
                 <li><?php _e('Translation Ready', 'endanger'); ?></li>
                 <li><?php _e('Many Other Plugins  Compatible', 'endanger'); ?></li>   
                </ul>              
               
			</div>		
	</div><!-- .gt-right-->
    <div class="clear"></div>
</div><!-- .wrap-GT -->
<?php } ?>