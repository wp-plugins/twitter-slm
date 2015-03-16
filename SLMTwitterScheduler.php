<?php
if (!class_exists('SLMTwitterScheduler')) {
	class SLMTwitterScheduler {
		
		private $hook_name;
		private $settings;

		public function __construct() {
			$this->hook_name = str_replace('_action','',SLMTwitterPlugin::slm_twitter_hook);
			add_filter('cron_schedules', array (
			$this,
			'scheduleCronJobManager'
					));
		}
		public function scheduleCronJobManager($schedules) {
			$schedules [$this->hook_name] = array (
					'interval' => 90,
					'display' => __( 'Twitter SLM')
			);
				
			return $schedules;
		}
		public function scheduleTasks() {
			add_action(SLMTwitterPlugin::slm_twitter_hook, array (
			$this,
			'executeSocialTriggerTask'
					));
				
			if (!wp_next_scheduled(SLMTwitterPlugin::slm_twitter_hook)) {
				wp_schedule_event(time(), $this->hook_name, SLMTwitterPlugin::slm_twitter_hook);
			}
		}
		
		public function executeSocialTriggerTask() {
			$this->settings = get_option('wp_maxv_st_settings');
			echo "executeSocialTriggerTask 1 <br/>";
			if (isset($this->settings ['share_auto_share_enabled']) && $this->settings ['share_auto_share_enabled'] == 'false') {
			} else {
				if (isset($this->settings['slm_twitter_url']) && $this->settings['slm_twitter_url'] &&
					isset($this->settings['slm_twitter_consumer_key']) && $this->settings['slm_twitter_consumer_key'] &&
					isset($this->settings['slm_twitter_consumer_secret']) && $this->settings['slm_twitter_consumer_secret'] &&
					isset($this->settings['slm_twitter_token']) && $this->settings['slm_twitter_token'] &&
					isset($this->settings['slm_twitter_token_secret']) && $this->settings['slm_twitter_token_secret'] &&
					isset($this->settings['slm_twitter_content_format']) && $this->settings['slm_twitter_content_format']) {
					$time = time() - (int)substr(date('O'),0,3)*60*60;
					echo "executeSocialTriggerTask 2 - ".($this->settings['slm_twitter_next_schedule'] < $time)." <br/>";
					if (!isset($this->settings['slm_twitter_next_schedule']) || (isset($this->settings['slm_twitter_next_schedule']) && $this->settings['slm_twitter_next_schedule']<$time)) {
						echo "executeSocialTriggerTask 3 <br/>";
						$this->sharePosts();
					}
				}
			}
		}
		
		public function sharePosts() {
			if (!isset($this->settings))
				$this->settings = get_option('wp_maxv_st_settings');
			
			echo 'Settings: <pre>';
			print_r($this->settings);
			echo '</pre>';
				
			$xPosts = (isset($settings['slm_twitter_max_posts']))?$settings['slm_twitter_max_posts']:'1';
			$perXHours = (isset($settings['slm_twitter_max_posts_hours']))?$settings['slm_twitter_max_posts_hours']:'24';
			$nextTime = time() - (int)substr(date('O'),0,3)*60*60;
			$this->settings['slm_twitter_next_schedule'] = $this->getNextTime($xPosts, $perXHours, $nextTime); // $log->time);
			update_option('wp_maxv_st_settings',$this->settings);
			$nextAcc = new stdClass();
			$nextAcc->settings = new stdClass();
			$nextAcc->type = 'twitter';
			$nextAcc->id = $this->settings['slm_twitter_acc_id'];
			$nextAcc->settings->consumer_key = $this->settings['slm_twitter_consumer_key'];
			$nextAcc->settings->consumer_secret = $this->settings['slm_twitter_consumer_secret'];
			$nextAcc->settings->token = $this->settings['slm_twitter_token'];
			$nextAcc->settings->token_secret = $this->settings['slm_twitter_token_secret'];
			$nextAcc->settings->url = $this->settings['slm_twitter_url'];
			$nextAcc->settings->title_format = '';
			$nextAcc->settings->content_format = $this->settings['slm_twitter_content_format'];
			$nextAcc->settings->attach_image = $this->settings['slm-twitter-attach-image-enable'];
			
			$next_post = $this->getNewPostForSharing($nextAcc);
				
			if (isset($next_post->ID) && $next_post->ID) {
				require_once dirname(__FILE__) . '/utils/Utils.php';
				$data = getDataFromWPPost($next_post, $this->settings['share_excerpt_length'], 'twitter');
				$data = replaceSubstitutionsV2($data, $nextAcc);
				$options = getOptionsForPostingV2($data, $nextAcc);

				echo '<pre>';
				print_r($options);
				echo '</pre>';
				$postResponse = $this->createTwitterPost($options);
				if (isset($postResponse->errors)) {
					$postResponse->status = 'Error';
					$postResponse->content = 'HTTP Code: '.$postResponse->http_code.' Error code: '.$postResponse->errors[0]->code.' Error message: '.$postResponse->errors[0]->message;
				} elseif (isset($postResponse->error)) {
					$postResponse->status = 'Error';
					$postResponse->content = 'HTTP Code: '.$postResponse->http_code.' Error message: '.$postResponse->error;
				} elseif ($postResponse->http_code=='200') {
					$postResponse->status = 'Success';
					$postResponse->url = $options->url.'/status/'.$postResponse->id_str;
					$postResponse->content = 'Success! Post ID: '.$postResponse->id_str.' Post URL: '.$postResponse->url;
				} else {
					$postResponse->status = 'Error';
					$postResponse->content = 'HTTP Code: '.$postResponse->http_code;
				}

				if (isset($postResponse->url) && $postResponse->url) {
					$settings = $this->settings;
				
					if (isset($settings['share_use_backlinksindexer']) && $settings['share_use_backlinksindexer'] &&
						isset($settings['share_use_backlinksindexer_key']) && $settings['share_use_backlinksindexer_key']) {
						
						$params = 'key='.urlencode($settings['share_use_backlinksindexer_key']);
						$params .= '&urls='.urlencode($postResponse->url);
						$this->get_page_now('http://backlinksindexer.com/api.php', $params);
						
						
					}
				}
				$this->saveLogRecord ( $next_post->ID, $nextAcc->id, $postResponse->status, $postResponse->url, $postResponse->content );
				echo 'Response<pre>';
				print_r($postResponse);
				echo '</pre>';
				
			}
					
		}
		
		private static function get_page_now($url,$params='') {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL,$url);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array("User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.15) Gecko/20080623 Firefox/2.0.0.15") );
			curl_setopt($ch, CURLOPT_NOBODY, false);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_AUTOREFERER, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 20);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			if ($params == '') {
				curl_setopt ($ch, CURLOPT_POST, false);
				curl_setopt ($ch, CURLOPT_POSTFIELDS, '');
				curl_setopt ($ch, CURLOPT_HTTPGET, true);
			} else {
				curl_setopt ($ch, CURLOPT_POST, true);
				curl_setopt ($ch, CURLOPT_POSTFIELDS, $params);
			}
				
			$result= curl_exec ($ch);
			curl_close ($ch);
			return $result;
		}
		
		private static function getNextTime($xPosts, $perXHours, $currentTime = 0) {
			$postPerseconds = round(($perXHours * 60 * 60) / $xPosts);
			if ($currentTime) {
				$h = date('G', $currentTime);
				$m = date('i', $currentTime);
				$s = date('s', $currentTime);
			} else {
				$h = date('G');
				$m = date('i');
				$s = date('s');
			}
			$hourInSec = ($h * 60 + $m + 1) * 60;
			$nextTime = 0;
			while($nextTime < $hourInSec) {
				$nextTime = $nextTime + $postPerseconds;
			}
			$nextSec = $nextTime + mt_rand(round($postPerseconds / 3), $postPerseconds);
			if ($currentTime)
				$nextTimeResult = strtotime(date('Y-m-d', $currentTime)) + $nextSec;
			else
				$nextTimeResult = strtotime(date('Y-m-d') . ' 00:00:00') + $nextSec;
			return $nextTimeResult;
		}
		
		private function getNewPostForSharing($socialAccount) {
			global $wpdb;
			$socialAccId = $socialAccount->id;
				
			$result = '';
				
			if (isset($this->settings['share_min_content_length']) && intval($this->settings['share_min_content_length'])>0) $content_length = intval($this->settings['share_min_content_length']);
			else $content_length = 50;
		
			$where = "where LENGTH(post_content)>={$content_length} and post_status='publish'";
				
			if (isset($this->settings ['wp_maxv_st_share_post_types']) && is_array($this->settings ['wp_maxv_st_share_post_types']) && count($this->settings ['wp_maxv_st_share_post_types']) > 0) {
				$post_types = get_post_types(array (
						"public" => true
				));
				$post_type_where = '';
				$pt = 1;
				foreach($post_types as $post_type) {
					if ($post_type != 'attachment') {
						$tmp = (((isset($this->settings ['wp_maxv_st_share_post_types'] [$post_type]) && $this->settings ['wp_maxv_st_share_post_types'] [$post_type]) || ! isset($this->settings ['wp_maxv_st_share_post_types'])) ? "post_type='{$post_type}'" : "");
						if ($post_type_where && $tmp) {
							$post_type_where .= ' or ' . $tmp;
						} elseif ($tmp) {
							$post_type_where = $tmp;
						}
					}
				}
		
				if ($post_type_where) {
					$where .= " and ({$post_type_where})";
				}
			} else {
		
				$post_type = '';
		
				if (! isset($this->settings ['share_posts_or_pages']) || $this->settings ['share_posts_or_pages'] == '' || $this->settings ['share_posts_or_pages'] == 'all') {
					$post_type = '';
				} elseif (isset($this->settings ['share_posts_or_pages'])) {
					$post_type = $this->settings ['share_posts_or_pages'];
				}
		
				if ($post_type) {
					$where .= " and post_type='" . $post_type . "'";
				} else {
					$where .= " and (post_type='post' or post_type='page')";
				}
			}
			//check categories
			if (isset($this->settings['slm_postcats'])) {
				$args = array(
						'orderby' => 'name',
						'hide_empty'=> 0 //,
				);
					
				$cats = get_categories($args);
				$post_cats_where = '';
				foreach ($cats as $nextCat) {
					if (isset($this->settings['slm_postcats'][$nextCat->term_taxonomy_id]) && $this->settings['slm_postcats'][$nextCat->term_taxonomy_id]) {
						if ($post_cats_where) {
							$post_cats_where .= ' or term_taxonomy_id='.$nextCat->term_taxonomy_id;
						} elseif ($tmp) {
							$post_cats_where = 'term_taxonomy_id='.$nextCat->term_taxonomy_id;
						}
					}
				}
				if ($post_cats_where) {
					$where .= ' and (exists (select object_id from '.$wpdb->prefix.'term_relationships where object_id=p.ID and ((p.post_type=\'post\' and ('.$post_cats_where.')) or p.post_type!=\'post\') limit 1) or not exists (select object_id from '.$wpdb->prefix.'term_relationships where object_id=p.ID and p.post_type!=\'post\' limit 1)) ';
				}
		
			}
			if (isset($this->settings ['share_old_posts_date']) && $this->settings ['share_old_posts_date']) {
				$where .= " and post_date>='" . $this->settings ['share_old_posts_date'] . " 00:00:00'";
			}
		
			$where .= " and not exists (select postid from " . $wpdb->prefix . "maxv_social_accounts_log where accountid='" . $socialAccId . "' and postid=p.ID limit 1)";
			$where .= " and not exists (select post_id from " . $wpdb->prefix . "postmeta where meta_key='social-acc-auto-share-post' and meta_value='false' and post_id=p.ID limit 1)";
		
			$sql = 'SELECT ID,post_content from ' . $wpdb->posts . ' p ' . $where;
				
			if ($this->settings ['share_posts_order'] == 'oldest') {
				$sql .= ' order by post_date ASC';
			} elseif ($this->settings ['share_posts_order'] == 'random') {
				$sql .= ' order by RAND()';
			} else {
				$sql .= ' order by post_date DESC';
			}
			$sql .= ' limit 1';
				
			$result = '';
			$data = $wpdb->get_results($sql);
			if (count($data) > 0) {
				$result = get_post($data [0]->ID);
			}
			return $result;
		}

		private function createTwitterPost($options) {
			$postinfo = '';
			if ($options->content && $options->consumer_key &&
			$options->consumer_secret &&
			$options->token &&
			$options->token_secret) {
				$options->content = html_entity_decode($options->content,ENT_COMPAT,'UTF-8');
				require_once dirname(__FILE__).'/OAuth/TwitterOAuth.php';
				$tmblr = new TwitterOAuth($options->consumer_key, $options->consumer_secret,
						$options->token, $options->token_secret);
		
				$postArr = array();
				$postArr['status'] = $options->content;
				$media_ids = '';
				if (isset($options->image) && trim($options->image) && isset($options->attach_image) && $options->attach_image=='true') {//sset($options['post_media']) && $options['post_media']) {
					$postMediaArr = array();
					$postMediaArr['media'] = $this->get_page_now($options->image);
		
					$post_media_info = $tmblr->post("https://upload.twitter.com/1.1/media/upload.json", $postMediaArr, true);
					if (isset($post_media_info->media_id_string) && $post_media_info->media_id_string) {
						$media_ids = $post_media_info->media_id_string;
					}
				}
				if ($media_ids) $postArr['media_ids'] = $media_ids;
				$postinfo = $tmblr->post("https://api.twitter.com/1.1/statuses/update.json", $postArr);
				$postinfo->http_code = $tmblr->http_code;
			} else {
				$postinfo = new stdClass();
				$postinfo->errors = array();
				$postinfo->http_code = 'N/A';
		
				$msg = '';
				if (!isset($options->content) || !$options->content) $msg=' (content is missing)';
				elseif (!isset($options->consumer_key) || !$options->consumer_key) $msg=' (Consumer Key is missing)';
				elseif (!isset($options->consumer_secret) || !$options->consumer_secret) $msg=' (Consumer Secret is missing)';
				elseif (!isset($options->token) || !$options->token) $msg=' (Token is missing. You need to authorize your application.)';
				elseif (!isset($options->token_secret) || !$options->token_secret) $msg=' (Token Secret is missing. You need to authorize your application.)';
				$postinfo->errors[0]->message = 'Please provide all necessary data.'.$msg;
				$postinfo->errors[0]->code = '000';
		
			}
			return $postinfo;
		}
		
		public static function saveLogRecord($postId, $accountId, $status, $post_url, $message, $second_tire_id=0, $log_type='post', $original_url_or_id='') {
			require_once dirname ( __FILE__ ) . '/data/SocialAccountsLog.php';
			$log = new SocialAccountsLog ();
			$log->postid = $postId;
			$log->accountid = $accountId;
			$settings = get_option('wp_maxv_st_settings');
			$time = time() - (int)substr(date('O'),0,3)*60*60;
			if (isset($settings['share_timezone']) && $settings['share_timezone']) {
				$dtz = new DateTimeZone($settings['share_timezone']);
				$dt = new DateTime('now', $dtz);
				$time = $time + $dt->getOffset();
			}
			$log->time = $time;
			$log->status = $status;
			$log->post_url = $post_url;
			$log->message = $message;
			$log->second_tire_id = $second_tire_id;
			$log->type = $log_type;
			$log->original_url_or_id = $original_url_or_id;
			$log->save();
		}
		
	}
}
?>