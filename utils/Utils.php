<?php
if (!function_exists('cleanHTMLSpecialChars')) {
	function getDataFromWPPost($post, $excerptLength=300, $accType='') {
		$result = array();
		$tags = get_the_tags ( $post->ID );
		$tags_list = '';
		$tags_list_ar = array ();
		if (is_array ( $tags )) {
			$i = 0;
			foreach ( $tags as $next ) {
				if ($i == 0)
					$tags_list .= $next->name;
				else
					$tags_list .= ',' . $next->name;
				$tags_list_ar [$i] = $next->name;
				$i ++;
			}
		} else {
			$tags = array ();
		}
	
		$result['tags_list'] = $tags_list;
		$result['tags_list_ar'] = $tags_list_ar;
	
		$categories = get_the_category ( $post->ID );
		$cat_list = '';
		if (is_array ( $categories )) {
			$i = 0;
			foreach ( $categories as $next ) {
				if ($i == 0)
					$cat_list .= $next->name;
				else
					$cat_list .= ',' . $next->name;
				$i ++;
			}
		} else {
			$categories = array ();
		}
		$result['cat_list'] = $cat_list;
	
		global $wp_rewrite;
		$wp_rewrite = new WP_Rewrite ();
		$url = get_permalink ( $post->ID );
		$title = $post->post_title;
		$content = $post->post_content;
		$content = trim(do_shortcode($content));
		if (!$content) {
			$meta = get_post_meta($post->ID,'_pros_EditorialReviews');
			if (isset($meta) && is_array($meta)) {
				$meta = unserialize($meta[0]);
				if (isset($meta->EditorialReview->Content) && $meta->EditorialReview->Content) {
					$content = $meta->EditorialReview->Content;
				}
			}
		}
	
		$str_content = strip_shortcodes($content);
		$content = $str_content;
		if (!$content) $content = $title;
	
		$excerpt = getExcerpt(slm_strip_html_tags($content), $excerptLength, $accType );
		$wp_excerpt = apply_filters ( 'the_excerpt', get_post_field ( 'post_excerpt', $post->ID ) );
	
		$imageURL = '';
		$get_featured_image_first = true;
		if ($get_featured_image_first) {
			if (has_post_thumbnail ( $post->ID ))
				$images = wp_get_attachment_image_src ( get_post_thumbnail_id ( $post->ID ), 'single-post-thumbnail' );
			if (isset($images) && count($images) > 0)
				$imageURL = $images [0];
		}
		if (!$imageURL) {
			$images = getImagesFromText ($content);
			if (count($images) > 0) {
				shuffle($images);
				$imageURL = $images[0];
			}
		}
			
		$result['url'] = $url;
		$result['title'] = $title;
		$result['content'] = $content;
		$result['excerpt'] = $excerpt;
		$result['wp_excerpt'] = $wp_excerpt;
		$result['html_excerpt'] = getExcerptHTML($content,$excerptLength,$accType,false);
		$result['imageURL'] = $imageURL;

		return $result;
	}
}

if (!function_exists('cleanHTMLSpecialChars')) {
	function cleanHTMLSpecialChars($text) {
		$savText = $text;
		$text = trim(htmlentities($text, ENT_COMPAT, 'UTF-8'));
		if (!$text) $text = $savText;
		$text = html_entity_decode($text);
		$text = str_ireplace("“", '"', $text);
		$text = str_ireplace("”", '"', $text);
		$text = str_ireplace("’", "'", $text);
		$text = str_ireplace("—", "-", $text);
		$text = str_ireplace('&#039;',"'", $text);
		$text = str_ireplace('&#39;',"'", $text);
		$text = str_ireplace('&#039;',"'", $text);
		$text = str_ireplace('&#39;',"'", $text);
		$text = preg_replace("/>\s*\t*/iUs",">",$text);
		$text = preg_replace("/\s*\t*</iUs","<",$text);
		$text = preg_replace("/<p[^\/<>]*>\s*\t*<\/p[^\/<>]*>/iUs","",$text);
		$text = preg_replace("/<div[^\/<>]*>\s*\t*<\/div[^\/<>]*>/iUs","",$text);
		$text = preg_replace("/[\r\n](\s*[\r\n])+/", " \n", $text);
		return $text;
	}
}

if (!function_exists('changeSpintaxSubstitutions')) {
	function changeSpintaxSubstitutions($text) {
		$regexp = "#\{([^\{\}]*\|[^\{\}]*)\}#";
		if (preg_match_all($regexp, $text, $parts)) {
			for ($i=0; $i<count($parts[0]); $i++) {
				$res = $parts[1][$i];
				$ar = explode("|",$res);
				$rndm = rand(1,sizeof($ar));
				$replacement=$ar[$rndm-1];
				$text = preg_replace($regexp,$replacement,$text,1);
			}
			$text = changeSpintaxSubstitutions($text);
		}
		return $text;
	}
}
if (!function_exists('slm_strip_html_tags')) {
	function slm_strip_html_tags( $text, $allowed_tags='' ) {
		$text = preg_replace(
				array(
						// Remove invisible content
						'/<head[^>]*>.*<\/head>/siU',
						'/<style[^>]*>.*<\/style>/iUs',
						'/<script[^>]*>.*<\/script>/iUs',
						'/<object[^>]*>.*<\/object>/iUs',
						'/<embed[^>]*>.*<\/embed>/iUs',
						'/<applet[^>]*>.*<\/applet>/iUs',
						'/<noframes[^>]*>.*<\/noframes>/iUs',
						'/<noscript[^>]*>.*<\/noscript>/iUs',
						'/<noembed[^>]*>.*<\/noembed>/iUs',
						// Add line breaks before and after blocks
						'/<\/((address)|(blockquote)|(center)|(del))/iU',
						'/<\/((div)|(h[1-9])|(ins)|(isindex)|(p)|(pre))/iU',
						'/<\/((dir)|(dl)|(dt)|(dd)|(li)|(menu)|(ol)|(ul))/iU',
						'/<\/((table)|(th)|(td)|(caption))/iU',
						'/<\/((form)|(button)|(fieldset)|(legend)|(input))/iU',
						'/<\/((label)|(select)|(optgroup)|(option)|(textarea))/iU',
						'/<\/((frameset)|(frame)|(iframe))/iU'
				),
				array(
						' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',
						"\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0",
						"\n\$0", "\n\$0"
				),
				$text );
		return cleanHTMLSpecialChars(html_entity_decode(strip_tags(stripcslashes($text),$allowed_tags)));
	}
}
if (!function_exists('getExcerpt')) {
	function getExcerpt($str, $maxLength=100, $accType = '') {
		$startPos=0;
		if(strlen($str) > $maxLength) {
			$excerpt   = substr($str, $startPos, $maxLength-3);
			$lastSpace = strrpos($excerpt, ' ');
			$excerpt   = substr($excerpt, 0, $lastSpace);
		} else {
			$excerpt = $str;
		}

		return $excerpt;
	}
}
if (!function_exists('getExcerptHTML')) {
	function getExcerptHTML($str, $maxLength=100, $accType = '', $checkHTMLlength = true) {
		if (strpos($str, '</p>')>0) {
			$contentpage = $str;
			$textcontent = '';
			preg_match_all("/\<p.*?\>(.*?)\<\/p\>/is", $contentpage, $paras);
			foreach ($paras[1] as $para) {
				$textcontent .= "<p>".trim($para)."</p>";
				if ($checkHTMLlength && strlen($textcontent) >= $maxLength) break;
				elseif (!$checkHTMLlength && strlen(slm_strip_html_tags($textcontent)) >= $maxLength) break;
			}
			return $textcontent;
		} else {
			return getExcerpt($str, $maxLength, $accType);
		}
	}
}

if (!function_exists('replaceSubstitutionsV2')) {
	function replaceSubstitutionsV2($data, $acc = '') {
		if ($acc->settings->title_format)
			$title = replaceSubstitutions ( $data['content'], $data['title'], $data['url'], $data['excerpt'], $acc->settings->title_format, $data['imageURL'], $data['wp_excerpt'], $acc );
		else
			$title = strip_tags ( stripcslashes ( $data['title'] ) );
		if ($acc->settings->content_format)
			$content = stripcslashes (replaceSubstitutions ( $data['content'], $data['title'], $data['url'], $data['excerpt'], $acc->settings->content_format, $data['imageURL'], $data['wp_excerpt'], $acc ) );
		else
			$content = stripcslashes ($data['content']);
		$content = str_replace('%html_excerpt%', $data['html_excerpt'], $content);
		$data['title'] = $title;
		$data['content'] = $content;
		return $data;
	}
}
if (!function_exists('replaceSubstitutions')) {
	function replaceSubstitutions($content, $title, $url, $excerpt, $format, $image='',$wp_excerpt = '', $acc = '') {
		$result = $format;
		$result = changeSpintaxSubstitutions($result);
		$content_txt = strip_tags(stripcslashes($content));

		$result = str_replace('%title%', $title, $result);
		$result = str_replace('%content%', $content, $result);
		$result = str_replace('%contenttext%', $content_txt, $result);
		$result = str_replace('%image%', $image, $result);
		if (isset($image) && $image) $result = str_replace('%img_tag%', '<img src="'.$image.'"/>', $result);
		$result = str_replace('%img_tag%', '', $result);
		$result = str_replace('%excerpt%', $excerpt, $result);
		$result = str_replace('%wp_excerpt%', $wp_excerpt, $result);
		$result_no_url = $result;
		$result = str_replace('%url%', $url, $result);

		if (is_object($acc)) {
			switch ($acc->type) {
				case 'twitter' :
					if (strlen($result)>120) {
						$urlLen = strlen($url);
						if ($urlLen>120) $result = $url;
						else $result = getExcerpt($title, (120-$urlLen)).' - '.$url;
					}
					break;
			}
		}
		$result = trim(str_replace('%site_url%', site_url(), $result));
		$result = trim(str_replace('%home_url%', get_home_url(), $result));

		return $result;
	}
}
if (!function_exists('getOptionsForPostingV2')) {
	function getOptionsForPostingV2($data, $acc) {
		$data['title'] = cleanHTMLSpecialChars($data['title']);
		$data['content'] = cleanHTMLSpecialChars($data['content']);
		return getOptionsForPosting ($acc, $data['title'], $data['content'], $data['url'], $data['tags_list'], $data['tags_list_ar'], $data['imageURL'], $data['cat_list'] );
	}
}
if (!function_exists('getOptionsForPosting')) {
	function getOptionsForPosting($nextAcc,$title,$content,$url,$tags_list,$tags_list_ar,$imageURL='', $cats_list='') {
		$options = new stdClass();
		$options->social_account_type = $nextAcc->type;
		$options->accountId = $nextAcc->id;
		switch ($options->social_account_type) {
			case 'twitter':
				$options->title = $title;
				$options->content = $content;
				$options->consumer_key = $nextAcc->settings->consumer_key;
				$options->consumer_secret = $nextAcc->settings->consumer_secret;
				$options->image = $imageURL;
				$options->token = $nextAcc->settings->token;
				$options->token_secret = $nextAcc->settings->token_secret;
				$options->url = trim(str_ireplace('http://', '', $nextAcc->settings->url));
				$options->url = trim(str_ireplace('https://', '', $options->url));
				$options->url = 'https://'.$options->url;
				break;
		}

		$options->image = $imageURL;
		if (isset($nextAcc->settings->attach_image)) $options->attach_image = $nextAcc->settings->attach_image;

		$options->title = preg_replace('/%[^%]*%/iUs', '', $options->title);
		$options->content = preg_replace('/%[^%]*%/iUs', '', $options->content);
		$options->content = str_replace('%read_more%', '', $options->content);

		return $options;

	}
}
if (!function_exists('getImagesFromText')) {
	function getImagesFromText($text) {
		$site_url = site_url('/');
		$images = array();
		$pattern = "/<img[\s]+[^<>]*src[\s]*=[\s]*['\"](.*)['\"][^<>]*>/iUs";
		if (preg_match_all($pattern,$text,$matches)) {
			for($i = 0; $i < count($matches[0]); $i ++) {
				$imgSRC = trim($matches[1][$i]);
				if ($imgSRC) {
					if (!preg_match ('/^https?:\/\//', $imgSRC)) {
						$imgSRC = $site_url.ltrim ($imgSRC, '/');
						array_push($images, $imgSRC);
					} else {
						$site_url = str_replace('http://www.', 'http://', $site_url);
						$site_url = str_replace('https://www.', 'https://', $site_url);
						$ar = parse_url($site_url);
						array_push($images, $imgSRC);
					}
				}
			}
		}
		return $images;
	}
}

?>