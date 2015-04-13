<?php
/*
Plugin Name: TickCaptcha
Plugin URI: http://www.boomvideo.com.au/
Description: Adds Video Captcha anti-spam solution to WordPress on the comment form and registration form.
Version: 1.0.1
Author: BOOM Technology
Author URI: http://www.boomvideo.com.au/
License: GNU GPL2
*/
global $wp_version, $tickcaptcha_vars, $tickcaptchalink, $TickCaptcha_path;
$tickcaptchalink = "http://c.bvcau.com.au/alpha/captcha";
$TickCaptcha_path = WP_PLUGIN_DIR.'/TickCaptcha';
$tickcaptcha_vars = get_option('tickcaptcha_vars_db');
register_activation_hook( plugin_basename(__FILE__), 'tickcaptcha_activate' );
function tickcaptcha_activate()
{
	add_option('tickcaptcha_active', true);
}
function printActivateMessage()
{
	echo '<div id="message" class="updated"><p><strong>'._e('Settings saved.', 'tickcaptcha').'</strong></p></div>';
}
function tickCaptchaInit() 
{
	global $tickcaptcha_vars;

	if(get_option('tickcaptcha_active', false)) 
	{
		delete_option('tickcaptcha_active');
		add_action('admin_notices','printActivateMessage');
		wp_redirect(admin_url("plugins.php?page=TickCaptcha&showmessage=1"));
		exit;
    }

	add_action('admin_menu', 'tickCaptcha_add_tabs');
	add_filter('plugin_action_links','tickCaptcha_action_links', 10, 2);
	wp_register_style('tickcaptchaStylesheet', plugins_url('css/min_tickcaptcha_styles.css', __FILE__));
	
	//captcha on comment form
	if($tickcaptcha_vars['tickcaptcha_on_comment_form'] == 'true')
	{
		add_action('comment_form', 'tickcaptcha_comment_form', 1);
		add_filter('preprocess_comment', 'tickcaptcha_comment_form_check', 1);
	}
	
	//captcha on registration form
	if($tickcaptcha_vars['tickcaptcha_on_register'] == 'true')
	{
		add_action('signup_extra_fields', 'tickcaptcha_register_net_form');
		add_filter('wpmu_validate_user_signup', 'tickcaptcha_register_net_form_check');
	}
	
	
	//captcha on registration form
	if($tickcaptcha_vars['tickcaptcha_on_register'] == 'true' ) 
	{
		add_action('register_form', 'tickcaptcha_register_form', 1);
		add_filter('registration_errors', 'tickcaptcha_register_form_check', 1);
		add_action('bp_before_registration_submit_buttons', 'tickcaptcha_register_bp_form');
		add_action('bp_signup_validate', 'tickcaptcha_register_bp_form_check');
	}
	
	
	//captcha on login form
	if ($tickcaptcha_vars['tickcaptcha_on_login'] == 'true')
	{	
		add_action('login_form', 'tickcaptcha_register_form', 1);
		add_filter('wp_authenticate_user','tickcaptcha_auth_check', 40, 3);
	}
	
	
	//captcha on forgot password form
	if($tickcaptcha_vars['tickcaptcha_on_lostpwd'] == 'true')
	{
		add_action('lostpassword_form', 'tickcaptcha_register_form', 1);
		add_filter('allow_password_reset', 'tickcaptcha_lost_password_check', 1);
	}
	
	if($tickcaptcha_vars['tickcaptcha_on_cf7'] == 'true')
	{
		// adding Captcha to CF7
		if (function_exists('wpcf7_add_shortcode'))
		{
			wpcf7_add_shortcode('tickcaptcha', 'tickcaptcha_shortcode', true );
			add_filter( 'wpcf7_validate_tickcaptcha', 'tickcaptcha_process_custom_forms', 10, 2 );
		}
	}
}

/*******comment form hook starts********/
function tickcaptcha_comment_form()
{
	global $tickcaptcha_vars;
	if(empty($tickcaptcha_vars['tickcaptcha_GUID']))
	{
		return true;
	}
	echo(tickcaptcha_get_html());
	tickcaptcha_wp_move_subbmit_button();
	return true;
}

function tickcaptcha_comment_form_check($comment)
{		
	global $tickcaptcha_vars;
	if((($comment['comment_type'] != '' ) && ( $comment['comment_type'] != 'comment' )) || empty($tickcaptcha_vars['tickcaptcha_GUID']) ) {
		return $comment;
	}
	if(isset($_POST['usercaptcha']) && trim($_POST['usercaptcha'])=="")
	{
		wp_die('<strong>' . __('ERROR', 'tickcaptcha') . '</strong>: ' . __('Please enter captcha and try again.','tickcaptcha.'));
	}
	if(tickcaptcha_check_result())
	{
		return $comment;
	}
	else 
	{
		wp_die('<strong>' . __('ERROR', 'tickcaptcha') . '</strong>: ' . __('Sorry, Captcha is not correct, please try again.','tickcaptcha.'));
	}
}
/*******comment form hook ends********/

/*******register form hook starts********/
function tickcaptcha_register_net_form($errors)
{
	global $tickcaptcha_vars;
	if(empty($tickcaptcha_vars['tickcaptcha_GUID']))
	{
		return true;
	}
	$e_captcha_wrong = $errors->get_error_message("captcha_wrong");
	echo(tickcaptcha_get_html());
	if( isset($e_captcha_wrong) && $e_captcha_wrong != "")
	{
		echo '<p class="error">' . $e_captcha_wrong . '</p>';
	}
	return true;
}

function tickcaptcha_register_net_form_check($errors)
{
	global $tickcaptcha_vars;
	if(empty($tickcaptcha_vars['tickcaptcha_GUID']))
	{
		return $errors;
	}
	if ($_POST['stage'] == 'validate-user-signup')
	{
		if(isset($_POST['usercaptcha']) && trim($_POST['usercaptcha'])=="")
		{
			$errors['errors']->add('captcha_wrong', '<strong>' . __('ERROR', 'tickcaptcha') . '</strong>: ' . __('Please enter captcha and try again.','tickcaptcha'));
		}
		elseif(!tickcaptcha_check_result())
			{
				$errors['errors']->add('captcha_wrong', '<strong>' . __('ERROR', 'tickcaptcha') . '</strong>: ' . __('Sorry, Captcha is not correct, please try again.','tickcaptcha'));
			}
	}
	return $errors;
}
/*******register form hook ends********/

/*******register form hook starts********/
function tickcaptcha_register_form()
{
	global $tickcaptcha_vars;
	if(empty($tickcaptcha_vars['tickcaptcha_GUID']))
	{
		return true;
	}
	echo(tickcaptcha_get_html());
	echo ('<script language="JavaScript">document.getElementById("login").style.width = "582px";</script><br>');
	return true;
}

function tickcaptcha_register_bp_form()
{
	global $tickcaptcha_vars;
	if(empty($tickcaptcha_vars['tickcaptcha_GUID']))
	{
		return true;
	}
	echo(tickcaptcha_get_html());
	return true;
}

function tickcaptcha_register_bp_form_check()
{
	global $bp, $tickcaptcha_vars;
	if(empty($tickcaptcha_vars['tickcaptcha_GUID']))
	{
		return true;
	}
	if(isset($_POST['usercaptcha']) && trim($_POST['usercaptcha'])=="")
	{
		$bp->signup->errors['signup_username'] = __('Please enter captcha and try again.','tickcaptcha');
	}
	elseif(!tickcaptcha_check_result())
		{
			$bp->signup->errors['signup_username'] = __('Sorry, Captcha is not correct, please try again.','tickcaptcha');
		}
}

function tickcaptcha_register_form_check($errors)
{
	global $tickcaptcha_vars;
	if(empty($tickcaptcha_vars['tickcaptcha_GUID']))
	{
		return $errors;
	}
	if(isset($_POST['usercaptcha']) && trim($_POST['usercaptcha'])=="")
	{
		$errors->add('captcha_wrong', '<strong>' . __('ERROR', 'tickcaptcha') . '</strong>: ' . __('Please enter captcha and try again.','tickcaptcha'));
	}
	elseif(!tickcaptcha_check_result())
		{
			$errors->add('captcha_wrong', '<strong>' . __('ERROR', 'tickcaptcha') . '</strong>: ' . __('Sorry, Captcha is not correct, please try again.','tickcaptcha'));			
		}
	return $errors;
}


function tickcaptcha_auth_check($user)
{	
	global $tickcaptcha_vars;
	if((!isset( $_POST['log']))	|| empty($tickcaptcha_vars['tickcaptcha_GUID']))
	{
		return $user;
	}
	if(isset($_POST['usercaptcha']) && trim($_POST['usercaptcha'])=="")
	{
		return new WP_Error( 'captcha_wrong', '<strong>' . __('ERROR', 'tickcaptcha') . '</strong>: ' . __('Please enter captcha and try again.','tickcaptcha') );
	}
	if(!tickcaptcha_check_result())
	{
		return new WP_Error( 'captcha_wrong', '<strong>' . __('ERROR', 'tickcaptcha') . '</strong>: ' . __('Sorry, Captcha is not correct, please try again.','tickcaptcha') );
	}
	return $user;
}

function tickcaptcha_lost_password_check($errors)
{
	global $tickcaptcha_vars;
	if(empty($tickcaptcha_vars['tickcaptcha_GUID']))
	{
		return true;
	}
	if(isset($_POST['usercaptcha']) && trim($_POST['usercaptcha'])=="")
	{
		return new WP_Error( 'captcha_wrong', '<strong>' . __('ERROR', 'tickcaptcha') . '</strong>: ' . __('Please enter captcha and try again.','tickcaptcha') );
	}
	if(!tickcaptcha_check_result())
	{
		return new WP_Error( 'captcha_wrong', '<strong>' . __('ERROR', 'tickcaptcha') . '</strong>: ' . __('Sorry, Captcha is not correct, please try again.','tickcaptcha') );
	}
	return true;
}
/*******register form hook ends********/

/********custom contact form 7 hook*******/
function tickcaptcha_shortcode($atts)
{
	global $tickcaptcha_vars;
	if(empty($tickcaptcha_vars['tickcaptcha_GUID']))
	{
		return true;
	}
	return tickcaptcha_get_html();
}

function tickcaptcha_process_custom_forms($errors, $tag)
{	
	if(isset($_POST['your-message']) && $errors['valid'] ==1 )
	{
		if(isset($_POST['usercaptcha']) && trim($_POST['usercaptcha'])=="")
		{
			if ( $tag != '' ) 
			{
				$errors['valid'] = false;
				$errors['reason']['tickerrorcontent'] = __('Please enter captcha and try again.','tickcaptcha');
			} 
			else
			{
				$errors['errors']->add('captcha_wrong', '<strong>' . __('ERROR', 'tickcaptcha') . '</strong>: ' . __('Please enter captcha and try again.','tickcaptcha'));
			}
		}
		elseif (!tickcaptcha_check_result())
			{
				if ( $tag != '' ) 
				{
					$errors['valid'] = false;
					$errors['reason']['tickerrorcontent'] = __('Sorry, Captcha is not correct, please try again.','tickcaptcha');
				} 
				else
				{
					$errors['errors']->add('captcha_wrong', '<strong>' . __('ERROR', 'tickcaptcha') . '</strong>: ' . __('Sorry, Captcha is not correct, please try again.','tickcaptcha'));
				}
			}
	}
	return $errors;
}
/*******custom contact form 7 hook ends********/


function tickcaptcha_wp_move_subbmit_button()
{
	echo( '<div id="tickcaptcha-submit-button-area" style="margin-top:0px;" ><br /></div>
		<script type="text/javascript">
			var tick_subs = document.getElementsByClassName( "form-submit" );
			var fmoved = false;
			for(var i in tick_subs) {
				var sub = tick_subs[i];
				if ( sub.parentNode != undefined ) {
					sub.parentNode.removeChild(tick_subs[i]);
					document.getElementById("tickcaptcha-submit-button-area").appendChild(sub);
					fmoved = true;
				}
			}
			if (!fmoved) {
				var tick_subs = document.getElementById("submit");
				if (tick_subs!=undefined) {
					tick_subs.parentNode.removeChild(tick_subs);
					document.getElementById("tickcaptcha-submit-button-area").appendChild(tick_subs);
					tick_subs.tabIndex = 6;
				}
			}
		</script>' );
}

function tickcaptcha_get_html()
{
	global $tickcaptcha_vars, $tickcaptchalink;
	$tickcaptcha_text = '
						<div id="content" class="tickvideocontent">
							 <iframe  id="boomvideoplayer" src="'.$tickcaptchalink.'/captcha_image.php?w='.$tickcaptcha_vars['tickcaptcha_width'].'&h='.$tickcaptcha_vars['tickcaptcha_height'].'&captcha='.$tickcaptcha_vars['tickcaptcha_GUID'].'&bgimg=Y" width="'.$tickcaptcha_vars['tickcaptcha_width'].'" height="'.$tickcaptcha_vars['tickcaptcha_height'].'" frameborder="0">
							 </iframe> 
						</div>
						<input type="hidden" value="" id="codeid" name="codeid">
						<input type="hidden" value="" id="usercaptcha" name="usercaptcha">
						<script type="text/javascript" src="'.$tickcaptchalink.'/js/captchaboom_1.js"></script>
						<span class="wpcf7-form-control-wrap boomerrorcontent">
						</span>';
	return $tickcaptcha_text;
}

function tickcaptcha_check_result()
{
	global $tickcaptchalink;
	$url=$tickcaptchalink."/proc/valcode.php";
	$codeid=$_POST['codeid'];
	$usercaptcha=$_POST['usercaptcha'];
	$data = array('codeid' => $codeid,'usercaptcha'=>$usercaptcha);
	$options = array( 'http' => array('header'  => "Content-type: application/x-www-form-urlencoded\r\n",
									  'method'  => 'POST',
									  'content' => http_build_query($data)));
	//remove proxy from production code
	$context  = stream_context_create($options);
	$result = file_get_contents($url, false, $context);
	if($result)
	{
		return true;
	}
	return false;
}

//create sub menu
function tickCaptcha_add_tabs ()
{		
	add_submenu_page('plugins.php', 'Tick Captcha', 'Tick Captcha', 'manage_options', "TickCaptcha",'tickCaptcha_settings');
	add_submenu_page('options-general.php', 'Tick Captcha Settings', 'Tick Captcha Settings', 'manage_options', "TickCaptcha",'tickCaptcha_settings');
}

//add link in menu
function tickCaptcha_action_links($links, $file)
{
	static $this_plugin;
	if (!$this_plugin)
	{
		$this_plugin = plugin_basename(__FILE__);
	}
	if ($file == $this_plugin)
	{
		$settings_link = '<a href="plugins.php?page=TickCaptcha">'. __('Settings', 'TickCaptcha') . '</a>';
		array_unshift( $links, $settings_link );
	}
	return $links;
}

//plugin settings page call
function tickCaptcha_settings()
{
	global $TickCaptcha_path;
	wp_enqueue_style('tickcaptchaStylesheet');
	require_once('TickCaptcha_settings.php');
}

//uninstall plugin function
function tickCaptcha_Uninstall()
{
   if (basename(dirname(__FILE__)) != "mu-plugins")
      delete_option('tickcaptcha_vars_db');
}

//initiate plugin
add_action( 'init', 'tickCaptchaInit' );

//uninstall plugin
if(function_exists('register_uninstall_hook'))
{
	register_uninstall_hook(__FILE__, 'tickCaptcha_Uninstall');
}
?>