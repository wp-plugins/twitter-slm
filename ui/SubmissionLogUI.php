<?php
if (!class_exists('SLMTwitterSubmissionLogUI')) {
	
	class SLMTwitterSubmissionLogUI {
		
		public static function getOptionsUI() {
			global $wpdb,$lplats;
			$html = '<h4>Submission Log</h4>';
			$path = plugins_url('', __FILE__);
			$waitImg = '<img src="'.$path.'/../img/snake_transparent.gif"/>';
			$html .= '<table id="maxv-social-acounts-log-table" class="table table-striped" style="width:100%;word-break: break-all;">';
			$html .= '<tr><th style="text-align:center;width:130px;">Time</th><th style="text-align:center;width:70px;">Status</th><th>Message</th><th style="width:60px;">Post ID</th><th>URL</th><th style="width:120px;"><button type="button" id="submission-wait-loading-wait" style="background-color:#FFFFFF;border:none;display:none;">'.$waitImg.'</button></th></tr>';
			$sql = 'SELECT l.id,l.status,l.message,l.post_url,l.postid,l.displaytime from '.$wpdb->prefix.'maxv_social_accounts_log l where time>='.strtotime("now -30 days").' order by time desc';
			$data = $wpdb->get_results($sql);
			foreach ( $data as $nextObj) {
				
					$html .= '<tr class="social-account-type-">';
					$html .= '<td class="logdatelog">'.$nextObj->displaytime.'</td>';
					$html .= '<td style="color:'.(($nextObj->status=='Success')?'green':'red').';">'.$nextObj->status.'</td>';
					$html .= '<td>'.htmlentities($nextObj->message).'</td>';
					
					$html .= '<td>'.'<a href="'.site_url().'?p='.$nextObj->postid.'" target="_blank">'.$nextObj->postid.'</a>'.'</td>';
					$html .= '<td>'.((isset($nextObj->post_url) && $nextObj->post_url)?'<a href="'.$nextObj->post_url.'" target="_blank">'.$nextObj->post_url.'</a>':'').'</td>';
					$html .= '<td><button type="button" class="btn btn-warning" onclick="deleteLogItem(\''.$nextObj->id.'\');">Re-Schedule</button></td>';
					$html .= '</tr>';
				
			}
			$html .= '</table>';
						
			return $html;
		}
		
	}
}
?>