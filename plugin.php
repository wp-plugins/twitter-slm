<?php
/*
* Plugin Name:   Social Link Machine - Twitter Edition
* Version:       0.1.0
* Plugin URI:    http://www.maxvim.com/private/tools/slm.php?who=twitterslm
* Description:   Share your posts and pages on Twitter.
* Author:        Dr. Max V
* Author URI:    http://www.maxvim.com/private/tools/slm.php?who=twitterslm
*/

require_once(dirname(__FILE__).'/SLMTwitterPlugin.php');
if (class_exists('SLMTwitterPlugin')){
	$p = new SLMTwitterPlugin();
	register_activation_hook(__FILE__,array('SLMTwitterPlugin', 'activate'));
	register_deactivation_hook(__FILE__,array('SLMTwitterPlugin', 'deactivate'));
	register_uninstall_hook(__FILE__, array('SLMTwitterPlugin', 'uninstall'));
}

?>