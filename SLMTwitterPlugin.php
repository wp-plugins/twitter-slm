<?php

if (!class_exists('SLMTwitterPlugin')) {
	class SLMTwitterPlugin {
		const slm_twitter_hook = 'slm_twitter_schedule_action'; 
		private $load_handle_prefix = 'slm-twitter';
		private $plugin_name = 'Twitter Social Link Machine';
		private $plugin_name_short = 'Twitter SLM';
		
		public function __construct() {
			if (is_admin()) {
				add_action('init', array($this,'loadPluginResources'));
				add_action('wp_ajax_ajax_delete_log_item', array($this, 'ajaxDeleteLogItem'));
			}
			require_once dirname(__FILE__).'/SLMTwitterScheduler.php';
			$scheduler = new SLMTwitterScheduler();
			$scheduler->scheduleTasks();
				
		}

		public function loadPluginResources() {
			add_action('admin_menu',array($this,'addSettingsMenu'));
			$this->loadResources();
		}
		
		public function addSettingsMenu() {
			add_submenu_page( 'options-general.php', __($this->plugin_name_short, $this->load_handle_prefix), __($this->plugin_name_short, $this->load_handle_prefix ), 'manage_options', $this->load_handle_prefix, array(&$this, 'getCronTasksUI' ));
		}
		
		public function getCronTasksUI() {
			
			require_once dirname(__FILE__).'/ui/SLMTwitterOptionsUI.php';
			require_once dirname(__FILE__).'/ui/SubmissionLogUI.php';
			
			$settingsTab = new SLMTwitterOptionsUI();
			
			$html = '<h2>'.$this->plugin_name.'</h2>';
			
			$html .= '<div style="width:75%;float:left;" class="postbox-container">';
			$html .= '<ul id="tabs" class="nav nav-tabs">';
			$active_class='active';
			$html .= '<li class="active"><a href="#slm-twitter-options-tab" data-toggle="tab">Settings</a></li>';
			$html .= '<li><a href="#slm-twitter-log-tab" data-toggle="tab">Submissions Log</a></li>';
			$divsHTML = '<div class="tab-content tabs-container" style="display:block;">';
			$divsHTML .= '<div id="slm-twitter-options-tab" class="tab-pane active">'.$settingsTab->getOptionsTab().'</div>';
			$divsHTML .= '<div id="slm-twitter-log-tab" class="tab-pane">'.SLMTwitterSubmissionLogUI::getOptionsUI().'</div>';				
			$divsHTML .= '</div>';
				
			$html .= '</ul>';
			$html .= $divsHTML;
			
			$html .= '</div>';
				
			$html .= $this->getSidebar();
			echo $html;
		}
		
		private function getSidebar() {
			$html = '';
			$url = 'http://www.maxvim.com/private/getad.php?who=slmtwitter';
			$ch = curl_init ( $url );
			curl_setopt ( $ch, CURLOPT_HEADER, 0 );
			curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
			$content = trim(curl_exec($ch));
			curl_close($ch);
			
			if ($content) $html .= $content;
				
			return $html;
		}
		
		

		public function loadResources() {
			if (isset($_GET['page']) && $_GET['page']=='slm-twitter') {
				$path = plugins_url('', __FILE__);
				wp_enqueue_script('jquery');
					
				wp_enqueue_script($this->load_handle_prefix.'-bootstrap-datepicker-js', $path.'/js/bootstrap-datepicker.js');
				wp_enqueue_style($this->load_handle_prefix.'-bootstrap-datepicker-css', $path.'/css/datepicker.css');				
				wp_enqueue_script($this->load_handle_prefix.'-slm-twitter-js', $path.'/js/slm-twitter.js');
				wp_enqueue_script($this->load_handle_prefix.'-twitter_bootstrap-js', $path.'/js/bootstrap.min.js');
				wp_enqueue_style($this->load_handle_prefix.'-twitter-bootstrap-css', $path.'/css/bootstrap.min.css');
			}
		
		}

		public function ajaxDeleteLogItem() {
			$id = trim(urldecode($_POST['id']));
			require_once dirname(__FILE__).'/data/SocialAccountsLog.php';
			$result = new stdClass();
			$result->status = SocialAccountsLog::delete($id);
			echo json_encode($result);
			die();
		}
		
		public static function activate() {
			global $wpdb;
			require_once dirname(__FILE__).'/data/SocialAccountsLog.php';				
			SocialAccountsLog::createTables();
			wp_clear_scheduled_hook( SLMTwitterPlugin::slm_twitter_hook );
		}
		
		public static function deactivate() {
			wp_clear_scheduled_hook( SLMTwitterPlugin::slm_twitter_hook );
		}
		
		public static function uninstall() {
			wp_clear_scheduled_hook( SLMTwitterPlugin::slm_twitter_hook );
		}
		
		
	}
}
?>