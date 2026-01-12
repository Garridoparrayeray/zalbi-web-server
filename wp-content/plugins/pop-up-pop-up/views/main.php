<?php defined('ABSPATH') or die('No script kiddies please!'); ?>

<?php

$canCall = get_option('wp_mypopups_connect', false) ? true : false;

$message = false;
if ($canCall) {
	$message = wp_mypopups_file_code();
}

?>
<?php if ($message): ?>
	<script>
		jQuery(document).ready(function() {
			jQuery.MyPopUps.showMessage("<?php echo esc_html($message); ?>");
		});
	</script>
<?php endif; ?>

<style media="screen">
	#wpfooter { display: none; }
</style>
<div id="wp-mypopups">
	<div id="mpu-translations-showmore" style="display: none; visibility: hidden"><?php _e('Show More Pop-ups', 'pop-up-pop-up'); ?></div>
	<div id="mpu-translations_autherr" style="display: none; visibility: hidden"><?php _e('Autentification Error', 'pop-up-pop-up'); ?></div>
	<div id="mpu-translations_orgerr" style="display: none; visibility: hidden"><?php _e('MyPopUps origin response error', 'pop-up-pop-up'); ?></div>
	<div id="mpu-translations_noresp" style="display: none; visibility: hidden"><?php _e('Server not responding', 'pop-up-pop-up'); ?></div>
	<div id="mpu-translations_enabled" style="display: none; visibility: hidden"><?php _e('Enabled', 'pop-up-pop-up'); ?></div>
	<div id="mpu-translations_disabled" style="display: none; visibility: hidden"><?php _e('Disabled', 'pop-up-pop-up'); ?></div>
	<div id="mpu-translations_cantchanges" style="display: none; visibility: hidden"><?php echo str_replace('%s', MYPOPUPS_URL, __('Pop-up status cannot be changed - Please login to <a href="%s" target="_blank">MyPopUps.com</a> to learn more.', 'pop-up-pop-up')); ?></div>

	<div id="wp-mypopups-welcome">
		<h1><?php _e('Welcome to MyPopUps!', 'pop-up-pop-up'); ?></h1>
		<div id="wp-mypopups-welcome-wrapper">
			<div id="wp-mypopups-welcome-top">
				<?php echo sprintf(__('Did you already create your PopUp(s) on <a target="_blank" href="%s">MyPopUps.com?</a>', 'pop-up-pop-up'), MYPOPUPS_URL); ?>
			</div>
			<div>
				<a class="wp-mypopups-big-buttons wp-mypopups-big-buttons__no" href="<?php echo MYPOPUPS_URL; ?>" target="_blank">
					<?php _e('<h3>No</h3> Let me do that now!', 'pop-up-pop-up'); ?>
					<div class="wp-mypopups-big-buttons__small-text"><?php _e('(takes less then 60 secs)', 'pop-up-pop-up'); ?></div>
				</a>
				<a class="wp-mypopups-big-buttons wp-mypopups-big-buttons__yes js_test_logged">
					<?php _e('<h3>Yes</h3> I already did!', 'pop-up-pop-up'); ?>
					<div class="wp-mypopups-big-buttons__small-text"><?php _e('(connects you to MyPopUps)', 'pop-up-pop-up'); ?></div>
				</a>
			</div>
			<div id="wp-mypopups-welcome-desc">
				<p><?php _e('The first step is that you create your Pop-Ups on MyPopUps.com.', 'pop-up-pop-up'); ?></p>
				<p><?php _e('After you did this, please click on «Yes, I already did!» above.', 'pop-up-pop-up'); ?></p>
			</div>
		</div>
	</div>


	<div id="wp-mypopups-main" style="display:none;">
		<h1><?php _e('MyPopUps', 'pop-up-pop-up'); ?></h1>
		<div id="wp-mypopups-main-control">
			<div class="wp-mypopups-main-refresh js_refresh">
				<?php _e('Refresh', 'pop-up-pop-up'); ?>
			</div>
			<div class="wp-mypopups-main-create js_create_new">
				<?php _e('Create a new pop-up', 'pop-up-pop-up'); ?>
			</div>
		</div>
		<div id="wp-mypopups-main-list"></div>
		<div id="wp-mypopups-main-list-empty" style="display:none;">
			<?php _e("Please now", 'pop-up-pop-up'); ?>
			<b><?php _e("launch your pop-up(s)", 'pop-up-pop-up'); ?></b>
			<?php _e("on MyPopUps so that they get displayed here", 'pop-up-pop-up'); ?><br>
			<?php _e("(and click on “Refresh” above).", 'pop-up-pop-up'); ?> <br><br>

			<?php echo sprintf(__("If you haven’t set up any pop-up yet, <a href='%s' target='_blank'>do it here</a>.", 'pop-up-pop-up'), MYPOPUPS_URL); ?>
		</div>
		<div id="wp-mypopups-visit-btn" style="text-align: center; margin-top: 40px;">
			<a href="<?php echo get_home_url(); ?>" target="_blank" style="display: inline-block;">
				<div class="wp-mypopups-visit-btn">
					<?php _e("Visit your homepage", 'pop-up-pop-up'); ?>
				</div>
			</a>
		</div>
	</div>

	<div id="wp-mypopups-loader" style="display:none;">
		<div class="lds-ring">
			<div></div>
			<div></div>
			<div></div>
			<div></div>
		</div>
	</div>

	<jdiv class="label_e50 _bottom_ea7 notranslate" id="jvlabelWrap-fake" style="background: linear-gradient(95deg, rgb(47, 50, 74) 20%, rgb(66, 72, 103) 80%);right: 30px;bottom: 0px;width: 310px;">
		<jdiv class="hoverl_bc6"></jdiv>
		<jdiv class="text_468 _noAd_b4d contentTransitionWrap_c73" style="font-size: 15px;font-family: Arial, Arial;font-style: normal;color: rgb(240, 241, 241);position: absolute;top: 8px;line-height: 13px;">
			<span><?php _e('Connect with support (click to load)', 'pop-up-pop-up'); ?></span><br>
			<span style="color: #eee;font-size: 10px;">
				<?php _e('This will establish connection to the chat servers', 'pop-up-pop-up'); ?>
			</span>
		</jdiv>
		<jdiv class="leafCont_180">
			<jdiv class="leaf_2cc _bottom_afb">
				<jdiv class="cssLeaf_464"></jdiv>
			</jdiv>
		</jdiv>
	</jdiv>

</div>
<input type="hidden" id="MYPOPUPS_URL" value="<?php echo MYPOPUPS_URL; ?>">
<input type="hidden" id="MYPOPUPS_CAN_CALL" value="<?php echo $canCall ? 'true' : 'false'; ?>">

<script type="text/template" id="wp-mypopup-template">
	<div class="wp-mypopup-item wp-mypopup-item-<%= slug %>">
		<div class="wp-mypopup-item-title">
			<%= name %>
		</div>
		<div class="wp-mypopup-item-desc">
			<a href="<%= url %>" target="_blank">
				<?php _e('Configure on MyPopUps', 'pop-up-pop-up'); ?>
			</a>
		</div>
		<div class="wp-mypopup-button <% if (status == 'Enabled') print ('wp-mypopup-button__enabled') %>" data-slug="<%=slug%>">
			<span class="wp-mypopup-button-text__enabled">
				<?php _e('Enabled', 'pop-up-pop-up'); ?>
			</span>
			<span class="wp-mypopup-button-text__disabled">
				<?php _e('Disabled', 'pop-up-pop-up'); ?>
			</span>
		</div>
	</div>
</script>

<script type="text/template" id="wp-mypopup-message-template">
	<div class="wp-mypopup-message <%= type %>">
		<% if (message == 'can_not_enable') { %>
			<?php echo sprintf(__('Pop-up cannot be enabled - please go to your <a href="%s">Dashboard on Mypopups</a> to see why and fix the issues', 'pop-up-pop-up'), MYPOPUPS_URL . "/dashboard/main"); ?>
		<% } else { %>
			<%= message %>
		<% } %>
		<div class="wp-mypopup-message-close" title="close"
			onclick="jQuery(this).parent().remove()"></div>
	</div>
</script>

<div id="wp-mypopups-carrousel" style="display: none; margin-top: 20px;">
	<?php do_action('ins_global_print_carrousel'); ?>
</div>
