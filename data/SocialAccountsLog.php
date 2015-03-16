<?php

if (!class_exists('SocialAccountsLog')) {
	class SocialAccountsLog {
		public $id;
		public $accountid;
		public $postid;
		public $time;
		public $displaytime;
		public $status;
		public $post_url;
		public $message;
		public $second_tire_id;
		public $original_url_or_id;
		public $type;
		
		const table_name = 'maxv_social_accounts_log';

		public function load($id) {
			global $wpdb;
			//SocialAccountsLog::changeNecessaryTables();
			$data = $wpdb->get_row('SELECT * FROM '.$wpdb->prefix.SocialAccountsLog::table_name." WHERE id = ".$id);
			if ($data) {
				$this->id = $data->id;
				$this->accountid = $data->accountid;
				$this->postid = $data->postid;
				$this->time = $data->time;
				$this->status = $data->status;
				$this->post_url = $data->post_url;
				$this->message = $data->message;
				$this->displaytime = $data->displaytime;
				$this->second_tire_id = $data->second_tire_id;				
				$this->type = $data->type;				
				$this->original_url_or_id = $data->original_url_or_id;				
				return 1;
			} else {
				return 0;
			}
		}
		
		public function save() {
			global $wpdb;
			//SocialAccountsLog::changeNecessaryTables();
			if ($this->id) {
			} else {
				$this->displaytime = date('Y-m-d H:i:s', $this->time);
				if ($wpdb->insert(
					$wpdb->prefix.SocialAccountsLog::table_name,
					array(
											'postid' => $this->postid,	// string
											'accountid' => $this->accountid,	// string
											'time' => $this->time, 
											'displaytime' => $this->displaytime,
											'status' => $this->status,
											'post_url' => $this->post_url,
											'message' => $this->message,
											'second_tire_id' => $this->second_tire_id, 
											'type' => $this->type,
											'original_url_or_id' => $this->original_url_or_id 
						),
					array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
				)) $this->id = $wpdb->insert_id;
			}
		}
		
		public static function delete($id) {
			global $wpdb;
			if ($id) {
				return $wpdb->query('delete from '.$wpdb->prefix.SocialAccountsLog::table_name." where id='$id'");
			}
			return false;
		}
		
		public static function delete_status($status) {
			global $wpdb;
			return $wpdb->query('delete from '.$wpdb->prefix.SocialAccountsLog::table_name." where status='$status'");
		}
		
		public static function delete_account_logs($accountId) {
			global $wpdb;
			return $wpdb->query('delete from '.$wpdb->prefix.SocialAccountsLog::table_name." where accountid='{$accountId}'");
		}
		
		public static function createTables() {
			global $wpdb;
		
			require_once(ABSPATH.'/wp-admin/includes/upgrade.php');
				
			$collate = ' COLLATE utf8_general_ci';
		
			$sql = 'CREATE TABLE IF NOT EXISTS `'.$wpdb->prefix.SocialAccountsLog::table_name."` (
								  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
								  `time` bigint NOT NULL,
								  `displaytime` datetime NOT NULL,
								  `accountid` varchar(15) NOT NULL,
								  `postid` bigint(20) NOT NULL,
								  `status` enum('Error','Success') NOT NULL,
								  `post_url` varchar(250) NOT NULL,
								  `message` longtext NOT NULL,
								  PRIMARY KEY (`id`)
								) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";
			
			dbDelta($sql.$collate);
			SocialAccountsLog::changeNecessaryTables();
		}

		public static function changeNecessaryTables() {
			global $wpdb;
			$myResult = $wpdb->get_row('SELECT * FROM '.$wpdb->prefix.SocialAccountsLog::table_name);
			
			if(!isset($myResult->second_tire_id)){
				$wpdb->query('ALTER TABLE '.$wpdb->prefix.SocialAccountsLog::table_name.' ADD second_tire_id bigint(20) NOT NULL DEFAULT 0');
			}
			if(!isset($myResult->type)){
				$wpdb->query('ALTER TABLE '.$wpdb->prefix.SocialAccountsLog::table_name." ADD type ENUM( 'post', 'video', 'external', '2ndtier' ) NOT NULL DEFAULT 'post'");
			}
			if(!isset($myResult->original_url_or_id)){
				$wpdb->query('ALTER TABLE '.$wpdb->prefix.SocialAccountsLog::table_name." ADD original_url_or_id VARCHAR( 250 ) NOT NULL DEFAULT ''");
			}
			
		}
	}
}

?>