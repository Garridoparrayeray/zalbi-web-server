<?php

// Namespace
namespace BMI\Plugin;

use BMI\Plugin\Dashboard as Dashboard;
use Couchbase\ValueRecorder;
use Exception;

// Exit on direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'bmi_pro_ftp_template', function () {

	$host    = get_option('bmi_pro_ftp_host');
	$port    = get_option('bmi_pro_ftp_port');

	// Get userName And Password
	$username = get_option( 'bmi_pro_ftp_username', false );
	$password = get_option( 'bmi_pro_ftp_password', false );

	$shouldBeConnected = true;

	try {
        if ($host === false || $username === false || $password === false ){
            throw new Exception( 'Unable to log in' );
        }

        $ftp = ftp_connect($host, $port);

        if (!$ftp){
            throw new Exception( 'Unable to log in' );
        }

        if (!@ftp_login($ftp, $username, $password)){
            throw new Exception( 'Unable to log in' );
        }

        ftp_pasv($ftp, true);

	} catch ( Exception $e ) {
		$shouldBeConnected = false;
	}

	if ( $shouldBeConnected ) {
		ftp_close( $ftp );
	}


	$isEnabled = Dashboard\bmi_get_config( 'STORAGE::EXTERNAL::FTP' );
	if ( $isEnabled === true || $isEnabled === 'true' ) {
		$isEnabled = ' checked';
	} else {
		$isEnabled = '';
	}

	?>
    <!-- External: FTP -->
    <div class="tab2-item d-flex jst-sb ia-center<?php echo( ( $isEnabled == ' checked' ) ? ' activeList' : '' ); ?>">

        <div class="d-flex ia-center">
            <svg fill="#000000" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                 width="45px" height="45px" viewBox="0 0 98.385 98.385"
                 xml:space="preserve">
<g>
    <g>
        <path d="M61.838,54.803c-0.793,0-1.33,0.053-1.611,0.129v5.085c0.332,0.075,0.742,0.103,1.304,0.103
			c2.069,0,3.349-1.047,3.349-2.785C64.879,55.75,63.805,54.803,61.838,54.803z"/>
        <path d="M53.155,31.677c-2.188-2.187-5.734-2.187-7.922,0L20.356,56.555c-2.188,2.188-2.188,5.734,0,7.923l24.876,24.875
			c2.188,2.188,5.734,2.188,7.922,0l24.877-24.877c1.051-1.05,1.641-2.476,1.641-3.961s-0.59-2.91-1.641-3.962L53.155,31.677z
			 M40.153,55.161h-6.618v3.937h6.184v3.168h-6.184v6.925h-3.884V51.967h10.502V55.161z M55.026,55.238h-4.703v13.951H46.44V55.238
			h-4.65v-3.271h13.236V55.238z M67.178,61.293c-1.33,1.229-3.322,1.815-5.621,1.815c-0.512,0-0.971-0.024-1.33-0.103v6.184h-3.857
			V52.198c1.201-0.205,2.889-0.358,5.264-0.358c2.401,0,4.139,0.461,5.289,1.405c1.1,0.845,1.814,2.274,1.814,3.962
			C68.736,58.918,68.2,60.349,67.178,61.293z"/>
        <path d="M78.445,22.433c-0.545-0.039-1.046-0.318-1.366-0.762c-3.998-5.545-10.51-8.976-17.444-8.976
			c-0.502,0-1.004,0.018-1.506,0.053c-0.451,0.032-0.896-0.103-1.255-0.378c-4.198-3.229-9.314-4.979-14.675-4.979
			c-9.579,0-18.069,5.614-21.936,14.088c-0.266,0.583-0.816,0.985-1.452,1.065C8.221,23.867,0,32.926,0,43.869
			c0,9.697,6.46,17.908,15.301,20.574c-0.534-1.225-0.82-2.553-0.82-3.928c0-1.766,0.472-3.455,1.338-4.94
			c-4.343-2.114-7.351-6.559-7.351-11.706c0-7.182,5.843-13.024,13.025-13.024c0.363,0,0.719,0.029,1.074,0.059
			c2.069,0.159,3.943-1.183,4.447-3.19c1.752-6.979,7.996-11.854,15.184-11.854c4.107,0,7.994,1.586,10.944,4.466
			c1.009,0.984,2.439,1.401,3.82,1.114c0.879-0.182,1.777-0.275,2.672-0.275c5.027,0,9.519,2.826,11.719,7.377
			c0.772,1.6,2.464,2.553,4.232,2.371c0.44-0.045,0.879-0.066,1.307-0.066c7.183,0,13.025,5.843,13.025,13.024
			c0,5.147-3.008,9.591-7.351,11.706c0.866,1.484,1.338,3.173,1.338,4.938c0,1.376-0.287,2.705-0.821,3.931
			c8.842-2.666,15.301-10.877,15.301-20.575C98.387,32.542,89.575,23.229,78.445,22.433z"/>
    </g>
</g>
</svg>
            <span class="ml25 d-flex ia-center">
            <span class="title_whereStored"><?php _e( "FTP", 'backup-backup' ); ?></span>
          </span>
        </div>

        <div class="ia-center">
            <div class="b2 bmi-switch"><input type="checkbox" class="checkbox storage-checkbox"<?php echo $isEnabled; ?>
                                              data-toggle="storage-ftp-row" id="bmi-pro-storage-ftp-toggle">
                <div class="bmi-knobs"><span></span></div>
                <div class="bmi-layer_str"></div>
            </div>
        </div>

    </div>

    <div class="bg_grey storage_target"
         id="storage-ftp-row"<?php echo( ( $isEnabled == ' checked' ) ? '' : ' style="display: none;"' ); ?>>
		<?php
		$disabled_functions = explode( ',', ini_get( 'disable_functions' ) );
		$vA                 = ! in_array( 'curl_exec', $disabled_functions );
		$vB                 = ! in_array( 'curl_init', $disabled_functions );

		if ( function_exists( 'curl_version' ) && function_exists( 'curl_exec' ) && function_exists( 'curl_init' ) && $vA && $vB ) {
			?>

            <div class="container-40 lh30 pt30">

                <div class="d-flex">
                    <div class="w270" style="margin-top: 23px;"><span>Backup directory path:</span></div>

                    <div class="w100">
                        <div class="w100 pos-r">
                            <input id="bmip-ftp-backup-dir" class="input-ftpdrive_storage" type="text" autocomplete="off"
                                   value="<?php echo sanitize_text_field(get_option('bmi_pro_ftp_backup_dir')); ?>"
                                   placeholder="Directory_Name_Of_My_Backups_In_FTP" 
                                   <?php echo( ( $shouldBeConnected ) ? 'disabled' : '' ); ?>>
                        </div>
                        <div class="mt10"><span></span></div>
                    </div>
                </div>

                <div class="d-flex">
                    <div class="w270" style="margin-top: 23px;"><span>Host IP Address:</span></div>
                    <div>
                        <div class="w100 pos-r">
                            <input id="bmip-ftp-host-ip" class="input-ftpdrive_storage" type="text" autocomplete="off"
                                   value="<?php echo sanitize_text_field(get_option('bmi_pro_ftp_host')); ?>"
                                   placeholder="192.168.100.100" required
                                <?php echo( ( $shouldBeConnected ) ? 'disabled' : '' ); ?>>
                            <span style="color: red" id="host-required-error" hidden="hidden">Please enter the Host IP.</span>
                        </div>
                        <div class="mt10">
                  <span>

                  </span>
                        </div>
                    </div>
                </div>

                <div class="d-flex">
                    <div class="w270" style="margin-top: 23px;"><span>Host Port:</span></div>
                    <div>
                        <div class="w100 pos-r">
                            <input id="bmip-ftp-host-port" class="input-ftpdrive_storage" type="text" autocomplete="off"
                                   value="<?php echo sanitize_text_field(get_option('bmi_pro_ftp_port', "21")); ?>"
                                   placeholder="21" required
                                   <?php echo( ( $shouldBeConnected ) ? 'disabled' : '' ); ?>>
                            <span style="color: red" id="host-port-required-error" hidden="hidden">Please enter the Host Port.</span>
                        </div>
                        <div class="mt10">
                  <span>

                  </span>
                        </div>
                    </div>
                </div>

                <div class="d-flex">
                    <div class="w270" style="margin-top: 23px;"><span>User Name:</span></div>
                    <div>
                        <div class="w100 pos-r">
                            <input id="bmip-ftp-user-name" class="input-ftpdrive_storage" type="text" autocomplete="off"
                                   value="<?php echo $username; ?>"
                                   placeholder="User Name" required
                                   <?php echo( ( $shouldBeConnected ) ? 'disabled' : '' ); ?>>
                            <span style="color: red" id="username-required-error" hidden="hidden">Username is required.</span>
                        </div>
                        <div class="mt10">
                  <span>

                  </span>
                        </div>
                    </div>
                </div>

                <?php if (!$shouldBeConnected):?>
                <div class="d-flex">
                    <div class="w270" style="margin-top: 23px;"><span>Password:</span></div>
                    <div>
                        <div class="w100 pos-r">
                            <input id="bmip-ftp-password" name="bmip_ftp_password" class="input-ftpdrive_storage" type="password"  autocomplete="off"
                                   placeholder="Password" required>
                            <span style="color: red" id="password-required-error" hidden="hidden">Password is required.</span>
                        </div>
                        <div class="mt10">
                  <span>

                  </span>
                        </div>
                    </div>
                </div>
            <?php endif;?>
            </div>

            <div id="ftpdrive-unauthenticated-box"
                 class="container-40 lh30 pt30 pb30" <?php echo( (  $shouldBeConnected ) ? 'style="display: none;"' : '' ); ?>>

                <div class="d-flex">
                    <div class="w270" style="margin-top: 11px;"><span id="ftpdrive-not-authed-content-box">Current status:&nbsp;<b>Inactive</b></span>
                    </div>
                    <div>
                        <div class="w100 pos-r">
                            <a href="#" id="ftp-connect-btn"
                               class="btn external-storage-btn-connection"><?php _e( "Connect", 'backup-backup' ); ?></a>
                        </div>
                    </div>
                </div>

                <div class="d-flex">
                    <blockquote class="bmi-ftpdrive-info">
                        FTP external storage allows automated transfer of  backup files to a remote FTP server. This enhances your backup safety by storing backups off-site. Configure FTP server details and set backup schedules.
                    </blockquote>
                </div>
            </div>

            <div id="ftpdrive-authenticated-box"
                 class="container-40 lh30 pt30 pb30" <?php echo( ( ! $shouldBeConnected ) ? 'style="display: none;"' : '' ); ?>>

                <div class="d-flex">
                    <div class="w270" style="margin-top: 11px;"><span id="ftpdrive-authed-content-box">Current status:&nbsp;<b>Active</b></span>
                    </div>
                    <div>
                        <div class="w100 pos-r">
                            <a href="#" id="ftp-disconnect-btn"
                               class="btn"><?php _e( "Disconnect", 'backup-backup' ); ?></a>
                        </div>
                    </div>
                </div>
            </div>
			<?php
		} else {
			?>
            <div class="container-40 lh30 pt30 pb30">
                <div class="center">
                    It seem like you don't have cURL extension (PHP module) installed on your server.<br/>
                    Without this module it's impossible to upload backups to FTP Server.<br/>
                    If you wish to use this feature, please enable cURL module.
                </div>
            </div>
			<?php
		}
		?>
    </div>
    <script>
        document.getElementById('ftp-connect-btn').addEventListener('click', function (e) {
            var host = document.getElementById('bmip-ftp-host-ip').value;
            var port = document.getElementById('bmip-ftp-host-port').value;
            var username = document.getElementById('bmip-ftp-user-name').value;
            var password = document.getElementById('bmip-ftp-password').value;

            var hostError = document.getElementById('host-required-error');
            var portError = document.getElementById('host-port-required-error');
            var usernameError = document.getElementById('username-required-error');
            var passwordError = document.getElementById('password-required-error');

            function toggleErrorDisplay(element, condition) {
                element.style.display = condition ? "block" : "none";
            }

            function validateHost(host) {
                return host.trim() !== '';
            }

            function validatePort(port) {
                return port.trim() !== '' && !isNaN(port) && Number(port) >= 0 && Number(port) <= 65535;
            }

            function validateUsername(username) {
                return username.trim() !== '';
            }

            function validatePassword(password) {
                return password.trim() !== '';
            }

            toggleErrorDisplay(hostError, !validateHost(host));
            toggleErrorDisplay(portError, !validatePort(port));
            toggleErrorDisplay(usernameError, !validateUsername(username));
            toggleErrorDisplay(passwordError, !validatePassword(password));
        });
    </script>
  <?php

} );