<?php
/*
Plugin Name: AuthAnvil WordPress Logon Agent
Plugin URI: http://www.scorpionsoft.com/docs/authanvil/wordpress/
Description: Two-Factor Authentication login security for your WordPress site using AuthAnvil.
Author: Scorpion Software Corp.
Version: 1.2
Author URI: http://www.scorpionsoft.com
Compatibility : WordPress 3.0.3

----------------------------------------------------------------------------
	Based on the Yubikey-Plugin by Henrik Schack (http://henrik.schack.dk/yubikey-plugin/)
----------------------------------------------------------------------------

    Copyright 2015  Scorpion Software Corp.  (http://www.scorpionsoft.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * Add One-time Password field to login form.
 */
function authanvil_loginform() {
  echo "<p>";
  echo "<label>".__('AuthAnvil Passcode','authanvil', 'authanvil-wordpress-logon-agent')."<br />";
  echo "<input type=\"password\" name=\"otp\" id=\"user_email\" class=\"input\" value=\"\" size=\"20\"/></label>";
  echo "</p>";
  echo '<style type="text/css">.forgetmenot { display:none; }</style>' . "\n";
}

/**
 * loginform info used in the case where no SAS URL or Site ID has been setup.
 */
function authanvil_loginform_sasinfomissing() {
  echo "<p style=\"font-size: 12px;width: 97%;padding: 3px;\">";
  echo __('AuthAnvil authentication has been disabled, the AuthAnvil SAS URL or Site ID hasn\'t been set up.','authanvil', 'authanvil-wordpress-logon-agent');
  echo "</p><br/>";
}

/**
 * Options page for editing AuthAnvil global options (SAS URL and Site ID) 
 */
function authanvil_options_page() {	
?>    
<div class="wrap">
	<h2><?php _e('AuthAnvil Plugin Options','authanvil', 'authanvil-wordpress-logon-agent');?></h2>
	<form name="authanvil" method="post" action="options.php">
		<?php wp_nonce_field('update-options'); ?>
		<input type="hidden" name="action" value="update" />
		<input type="hidden" name="page_options" value="authanvil_sas_url,authanvil_site_id" />
	    <table class="form-table">
	    	<?php PHP4_Check();?>
			<tr valign="top">
				<th scope="row"><label for="authanvil_sas_url"><?php _e('AuthAnvil SAS URL','authanvil', 'authanvil-wordpress-logon-agent');?></label></th>
				<td><input name="authanvil_sas_url" type="text" id="authanvil_sas_url" class="code" value="<?php echo get_option('authanvil_sas_url') ?>" size="60" /><br /></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="authanvil_site_id"><?php _e('AuthAnvil Site ID','authanvil', 'authanvil-wordpress-logon-agent');?></label></th>
				<td><input name="authanvil_site_id" type="text" id="authanvil_site_id" class="code" value="<?php echo get_option('authanvil_site_id'); ?>" size="5" /><br /></td>
			</tr>
		</table>
		<p class="submit">
			<input type="submit" name="Submit" value="<?php _e('Save Changes', 'authanvil' , 'authanvil-wordpress-logon-agent') ?>" />
		</p>
	</form>
</div>
<?php
}

/**
 * Display a warning if the PHP installation is too old.
 * To be removed later on when PHP4 is completely dead.
 */
function PHP4_Check($globaloptions=true) {
	if (version_compare(PHP_VERSION, '5.0.0', '<')){
		$errormessage=__('WARNING: You appear to be using PHP4, PHP5 or newer is required for the AuthAnvil plugin to work.','authanvil', 'authanvil-wordpress-logon-agent');
		if ($globaloptions) {
			echo "<tr valign=\"top\">";
			echo "<th scope=\"row\">&nbsp;</th>";
			echo "<td><strong>".$errormessage."</strong></td>";
			echo "</tr>";
		} else {
			echo "<tr>";
			echo "<th>&nbsp;</th>";
			echo "<td><strong>".$errormessage."</strong></td>";
			echo "</tr>";
		}
	}
}

/**
 * Attach a AuthAnvil options page to the settings menu
 */
function authanvil_admin() {
	add_options_page('AuthAnvil', 'AuthAnvil', 8, 'authanvil', 'authanvil_options_page');
}

/**
 * Login form handling.
 * Do OTP check if user has been setup to do so.
 * @param wordpressuser
 * @return loginstatus
 */
function authanvil_check_otp($user) {
	// Get user specific settings
	$authanvilserver	=trim(get_user_option('authanvil_server',$user->ID));
	// Get the global SAS URL/Site ID
	$authanvil_sas_url	=trim(get_option('authanvil_sas_url'));
	$authanvil_site_id	=trim(get_option('authanvil_site_id'));

	if (!empty($authanvilserver) && $authanvilserver!='disabled' && empty($_POST['otp'])) {
		$error=new WP_Error();
		$error->add('empty_authanvilotp', __('<strong>ERROR</strong>: You must enter an AuthAnvil passcode to log in.','authanvil', 'authanvil-wordpress-logon-agent'));
		return $error;
	}

	$otp=trim($_POST['otp']);

	if ($authanvilserver=='enabled') {	
		// is OTP valid ?
		if (!authanvil_verify_otp($user->user_login,$otp,$authanvil_sas_url,$authanvil_site_id)) {
			$error=new WP_Error();
			$error->add('invalid_authanvilotp', __('<strong>ERROR</strong>: Invalid AuthAnvil Passcode.','authanvil', 'authanvil-wordpress-logon-agent'));
			return $error;
		}
	}
	
  	return $user;
}

/**
 * Extend personal profile page with AuthAnvil settings.
 */
function authanvil_profile_personal_options() {
	global $user_id;
	$authanvilserver=get_user_option('authanvil_server',$user_id);
	
	// Only allow the user to edit their own AuthAnvil settings if they have permissions to manage users
	if (current_user_can( 'edit_users' )) {
		echo "<h3>".__('AuthAnvil Settings','authanvil', 'authanvil-wordpress-logon-agent')."</h3>";

		echo '<table class="form-table">';
		echo '<tbody>';
		PHP4_Check(false);
		echo '<tr>';
		echo '<th scope="row">'.__('Require Strong Authentication','authanvil', 'authanvil-wordpress-logon-agent').'</th>';
		echo '<td>';

		echo '<div><input name="authanvil_server" id="authanvilserver_enabled" value="enabled" type="radio"';
		if ($authanvilserver=='enabled'){
			echo ' checked="checked"';
		}
		echo '/>';
		echo '<label for="authanvilserver_enabled"> '.__('Yes','authanvil', 'authanvil-wordpress-logon-agent').'</label>&nbsp;&nbsp;&nbsp;';
		
		echo '<input name="authanvil_server" id="authanvilserver_disabled" value="disabled" type="radio"';
		if ($authanvilserver == 'disabled' || $authanvilserver=='') {
			echo ' checked="checked"';
		}
		echo '/>';
		echo '<label for="authanvilserver_disabled"> '.__('No','authanvil', 'authanvil-wordpress-logon-agent').'</label>';
		echo '</div>';
		
		echo '</td>';
		echo '</tr>';
		echo '</tbody></table>';
	}
}

/**
 * Extend profile page with ability to enable/disable AuthAnvil authentication requirement.
 */
function authanvil_edit_user_profile() {
	global $user_id;
	$authanvilserver=get_user_option('authanvil_server',$user_id);

	echo "<h3>".__('AuthAnvil Settings','authanvil', 'authanvil-wordpress-logon-agent')."</h3>";

	echo '<table class="form-table">';
	echo '<tbody>';
	PHP4_Check(false);
	echo '<tr>';
	echo '<th scope="row">'.__('Require Strong Authentication','authanvil', 'authanvil-wordpress-logon-agent').'</th>';
	echo '<td>';

	echo '<div><input name="authanvil_server" id="authanvilserver_enabled" value="enabled" type="radio"';
	if ($authanvilserver=='enabled'){
		echo ' checked="checked"';
	}
	echo '/>';
	echo '<label for="authanvilserver_enabled"> '.__('Yes','authanvil', 'authanvil-wordpress-logon-agent').'</label>&nbsp;&nbsp;&nbsp;';
	
	echo '<input name="authanvil_server" id="authanvilserver_disabled" value="disabled" type="radio"';
	if ($authanvilserver == 'disabled' || $authanvilserver=='') {
		echo ' checked="checked"';
	}
	echo '/>';
	echo '<label for="authanvilserver_disabled"> '.__('No','authanvil', 'authanvil-wordpress-logon-agent').'</label>';
	echo '</div>';
	
	echo '</td>';
	echo '</tr>';
	echo '</tbody></table>';
}

/**
 * Form handling of AuthAnvil options added to personal profile page (user editing own profile)
 */
function authanvil_personal_options_update() {
	global $user_id;
	// Only allow the user to edit their own AuthAnvil settings if they have permissions to manage users
	if (current_user_can( 'edit_users' )) {
		$authanvilserver	=trim($_POST['authanvil_server']);
		update_user_option($user_id,'authanvil_server',$authanvilserver,true);
	}
}

/**
 * Form handling of AuthAnvil options on edit profile page (admin user editing other user)
 */
function authanvil_edit_user_profile_update() {
	global $user_id;
	$authanvilserver	=trim($_POST['authanvil_server']);
	update_user_option($user_id,'authanvil_server',$authanvilserver,true);
}

/**
 * Call Authenticate at the AuthAnvil server
 *
 * @param String $user Wordpress username entered by user
 * @param String $otp One-time Password entered by user
 * @param String $authanvil_sas_url SAS URL of AuthAnvil server
 * @param String $authanvil_site_id Site ID of AuthAnvil server
 * @return Boolean Is the password OK ?
 */
function authanvil_verify_otp($user,$otp,$authanvil_sas_url,$authanvil_site_id){
	//First, check for passcode length
	if (strlen($otp) < 12 || strlen($otp) > 16){
		return false;
	}
	//Then, try and authenticate the user. If the authentication attempt throws an exception, then bail.
	try {
		$client = new SoapClient($authanvil_sas_url . '?wsdl');
		$response = $client->Authenticate(array('Username'=> $user, 'Passcode'=> $otp, 'Tokentype'=> 1, 'SiteID'=> $authanvil_site_id));
		return $response->AuthenticateResult;
	} catch (Exception $e){
		return false;
	}
}

// Initialization and Hooks
add_action('personal_options_update','authanvil_personal_options_update');
add_action('profile_personal_options','authanvil_profile_personal_options');

add_action('edit_user_profile','authanvil_edit_user_profile');
add_action('edit_user_profile_update','authanvil_edit_user_profile_update');

add_action('admin_menu','authanvil_admin');

// If the SAS URL & Site ID haven't been setup we don't enable the wp_authenticate_user filter.
if (strlen(get_option('authanvil_sas_url')) && intval(trim(get_option('authanvil_site_id')))) {
	add_action('login_form', 'authanvil_loginform');
	add_filter('wp_authenticate_user','authanvil_check_otp');	
} else {
	add_action('login_form', 'authanvil_loginform_sasinfomissing');
}
?>
