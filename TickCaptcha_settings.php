<?php
/*
Version 1.0.1
Author BOOM Technology
Author URI http://boom-tick.com/
License GNU GPL2
*/
$myErrors = "";
if (isset($_POST['submit']))
{
	$boomguid = isset($_POST['tickcaptcha_GUID']) ? $_POST['tickcaptcha_GUID'] : '';
	if($_POST['tickcaptcha_GUID'] != "")
	{
		$checkguidurl = "http://boombox.boomvideo.com.au/MasterPublisher/ValidatePublisher?GUID=".$_POST['tickcaptcha_GUID']."&type=";
		$result = file_get_contents($checkguidurl);
		if($result=="False")
		{
			$myErrors=new WP_Error('required', __('Invalid Tick Captcha Unique Key.Please use the BOOMGUID which is provided by BOOM for your website. If you haven\'t got your Tick Captcha Unique Key, You can contact BOOM Team at info@boomvideo.com.au!'));
			$boomguid = "";
		}
	}
	$optionarray_update = array('tickcaptcha_GUID' 	=> $boomguid,
								'tickcaptcha_width' 	=> (isset($_POST['tickcaptcha_width']) && $_POST['tickcaptcha_width'] > 0) ? $_POST['tickcaptcha_width'] : '450',
								'tickcaptcha_height' 	=> (isset($_POST['tickcaptcha_height']) && $_POST['tickcaptcha_height'] > 0) ? $_POST['tickcaptcha_height'] : '400',
								'tickcaptcha_on_comment_form' 	=> (isset($_POST['tickcaptcha_on_comment_form'])) ? 'true' : 'false',
								'tickcaptcha_on_register' 	=> (isset($_POST['tickcaptcha_on_register'])) ? 'true' : 'false',
								'tickcaptcha_on_login'		=> (isset($_POST['tickcaptcha_on_login'])) ? 'true' : 'false',
								'tickcaptcha_on_lostpwd' 	=> (isset($_POST['tickcaptcha_on_lostpwd'])) ? 'true' : 'false',
								'tickcaptcha_on_cf7' 		=> (isset($_POST['tickcaptcha_on_cf7'])) ? 'true' : 'false',
								'tickcaptcha_custom_text' 	=> (isset($_POST['tickcaptcha_custom_text'])) ? $_POST['tickcaptcha_custom_text'] : '');
	foreach ($optionarray_update as $key => $val)
	{
		$optionarray_update[$key] = str_replace('&quot;','"',trim($val));
	}

	if($boomguid != ""){
		//pass data to CRM
		$parm=$optionarray_update;
		$parm['host']=$_SERVER['SERVER_NAME'];
		pass_date($parm);	

	}

	update_option('tickcaptcha_vars_db', $optionarray_update);


}

function pass_date($parm){
	$postdata = http_build_query($parm);
	

	$options = array( 'http' => array('header'  => "Content-type: application/x-www-form-urlencoded\r\n",
									  'method'  => 'POST',
									  'content' => http_build_query($parm)));
	

	$link="http://boom.boomvideo.tv/alpha/test/formsubmit.php";
	$context  = stream_context_create($options);
	

}

$tickcaptcha_vars = get_option('tickcaptcha_vars_db');
if(is_array($tickcaptcha_vars))
{
	foreach($tickcaptcha_vars as $key => $val)
	{
		$tickcaptcha_vars[$key] = str_replace('\\','',$val);
	}
}
if (function_exists('wp_cache_flush'))
{
	wp_cache_flush();
}
?>
<div class="div_settings">
	<div class="bvc_pluginsettings bvcleft">
		<h2 class="bvc_heading">
			<?php _e('TICK CAPTCHA SETTINGS', 'tickcaptcha'); ?>
			<img src='<?php echo plugins_url('images/bvclogo.png', plugin_basename(__FILE__)); ?>' class="boomlogo" alt='Boom Video CRM'>
		</h2>
	</div>
	<div class="bvcleft">
		
		<form name="formsettings" action="<?php echo admin_url( 'plugins.php?page=TickCaptcha' );?>" method="post">
		

			<div class="bvc_form_settings1">
				<?php if(!empty($_GET['showmessage'])) : ?>
					<div id="message" class="updated fade"><p><strong><?php _e('To set up Tick Captcha and protect your WordPress forms, Please update the following settings.', 'tickcaptcha'); ?></strong></p></div>
				<?php endif; ?>
				<?php if(!empty($_POST )) : ?>
					<div id="message" class="updated fade"><p><strong><?php _e('Settings saved.', 'tickcaptcha'); ?></strong></p></div>
				<?php endif; ?>
				<?php if(!empty($myErrors )) : ?>
					<div id="message" class="error fade"><p><strong><?php echo $myErrors->get_error_message(); ?></strong></p></div>
				<?php endif; ?>
				<h3 class="generalsettings" ><?php _e('General Settings', 'tickcaptcha'); ?></h3>
				<?php
					if(empty($tickcaptcha_vars['tickcaptcha_GUID']))
					{
				?>
							<div class="regsteps">
								<p>
									Please follow the steps below to start using the Tick Captcha in your WordPress forms.
								</p>
								<ol>
									<li>
										<a target="_blank" href="http://www.boom-tick.com/register?pl=wp"><button type="button" class="button-primary">Register at Boom-tick.com</button></a> for Tick Captcha Unique Key for plugin
									</li>
									<li>
										Get your Tick Captcha Unique Key in your email account fromTick.
									</li>
									<li>
										Enter your Tick Captcha Unique Key in setting page of Tick Captcha plugin and choose Tick Captcha setting, save it(Minimum size is 450X400).
									</li>
									<li>
										Tick Captcha is ready to use.
									</li>
								</ol>
								<p>
									Note : If you're having trouble getting Tick Captcha to work on your WordPress site, you can contact BOOM Team at support@boomvideo.com.au and we'll get back to you.
								</p>
							</div>
						
				<?php
					}
				?>
				<table style="width: 54%;" cellspacing="2" cellpadding="5" class="form-table">
					<tr>
						<th scope="row" colspan="2">
							<span class="bvc_required">*</span> <?php _e('Tick Captcha Unique Key :', 'tickcaptcha'); ?>
							<input name="tickcaptcha_GUID" id="tickcaptcha_GUID" type="text" value="<?php echo($tickcaptcha_vars['tickcaptcha_GUID']); ?>" style="width: 100%; margin-top: 10px;" placeholder="Tick Captcha Unique Key" />
						</th>
					</tr>
					<tr>
						<th scope="row">
							<?php _e('Tick Captcha Width', 'tickcaptcha'); ?><br />
							<input name="tickcaptcha_width" id="tickcaptcha_width" type="text" value="<?php echo($tickcaptcha_vars['tickcaptcha_width']); ?>" style="width: 100%; margin-top: 10px;" placeholder="Captcha Width Minimum 450px" />
						</th>
						<th scope="row">
							<?php _e('Tick Captcha Height', 'tickcaptcha'); ?><br />
							<input name="tickcaptcha_height" id="tickcaptcha_height" type="text" value="<?php echo($tickcaptcha_vars['tickcaptcha_height']); ?>" style="width: 100%; margin-top: 10px;" placeholder="Captcha Height Minimum 400px" />
						</th>
					</tr>
				</table>
			</div>
			<div class="bvc_form_settings2">
				<h3 class="generalsettings" ><?php _e('Tick Captcha Settings', 'tickcaptcha'); ?></h3>
				<table width="100%" cellspacing="2" cellpadding="5" class="form-table">
					<tr>
						<td class="bvc_checkboxtd">
							<input name="tickcaptcha_on_comment_form" id="tickcaptcha_on_comment_form" type="checkbox" <?php if ( $tickcaptcha_vars['tickcaptcha_on_comment_form'] == 'true' ) echo ' checked="checked" '; ?> />
						</td>
						<td class="bvc_texttd" scope="row">
							<?php _e('On Comment Form', 'tickcaptcha'); ?>
							<br />
							<span class="bvc_comment">
								<?php _e('Check this box to enable Tick Captcha protection on the comment form.', 'tickcaptcha'); ?>
							</span>
						</td>
					</tr>
					<tr>
						<td class="bvc_checkboxtd">
							<input name="tickcaptcha_on_register" id="tickcaptcha_on_register" type="checkbox" <?php if ( $tickcaptcha_vars['tickcaptcha_on_register'] == 'true' ) echo ' checked="checked" '; ?> />
						</td>
						<td class="bvc_texttd" scope="row">
							<?php _e('On Registration Form', 'tickcaptcha'); ?>
							<br />
							<span class="bvc_comment">
								<?php _e('Check this box to enable Tick Captcha protection on the register form.', 'tickcaptcha'); ?>
							</span>
						</td>
					</tr>
					<tr>
						<td class="bvc_checkboxtd">
							<input name="tickcaptcha_on_login" id="tickcaptcha_on_login" type="checkbox" <?php if ( $tickcaptcha_vars['tickcaptcha_on_login'] == 'true' ) echo ' checked="checked" '; ?> />
						</td>
						<td class="bvc_texttd" scope="row">
							<?php _e('On Login Form', 'tickcaptcha'); ?>
							<br />
							<span class="bvc_comment">
								<?php _e('Check this box to enable Tick Captcha protection on the login form. This option is supported by WordPress ver. 2.8 or above.', 'tickcaptcha'); ?>
							</span>
						</td>
					</tr>
					<tr>
						<td class="bvc_checkboxtd">
							<input name="tickcaptcha_on_lostpwd" id="tickcaptcha_on_lostpwd" type="checkbox" <?php if ( $tickcaptcha_vars['tickcaptcha_on_lostpwd'] == 'true' ) echo ' checked="checked" '; ?> />
						</td>
						<td class="bvc_texttd" scope="row">
							<?php _e('On Lost Password Form', 'tickcaptcha'); ?>
							<br />
							<span class="bvc_comment">
								<?php _e('Check this box to enable Tick Captcha protection on "Lost your password" form. This option is supported by WordPress ver. 2.7 or above.', 'tickcaptcha'); ?>
							</span>
						</td>
					</tr>
					<tr>
						<td class="bvc_checkboxtd">
							<input name="tickcaptcha_on_cf7" id="tickcaptcha_on_cf7" type="checkbox" <?php if ( $tickcaptcha_vars['tickcaptcha_on_cf7'] == 'true' ) echo ' checked="checked" '; ?> />
						</td>
						<td class="bvc_texttd" scope="row">
							<?php _e('On Contact Form 7', 'tickcaptcha'); ?>
							<br />
							<span class="bvc_comment">
								<?php _e('Check this box to enable Tick Captcha protection on Contact Form 7.
										<br>
										<div>
										<span>To integarte Tick Captcha with <b>Contact Form 7</b> please use the following instuctions:</span>
										<ol>
											<li>Copy the following tag with square brackets <b>[tickcaptcha]</b></li>
											<li>Open the page with settings of Contact Form 7</li>
											<li>Paste the copied tag into "Form" section above the line which contains "&lt;p&gt;[submit "Send"]&lt;/p&gt;"</li>
										</ol></div>', 'tickcaptcha'); ?>
							</span>
						</td>
					</tr>
					<tr>
						<td colspan="2" class="bvc_checkboxtd">
							<input class="bvc_submit" type="submit" name="submit" value="<?php _e('Save Tick Captcha Settings', 'tickcaptcha'); ?>" />
						</td>
					</tr>
				</table>
			</div>
		</form>
	</div>
</div>
