<?php
/**

Different tools to deal with multilinguism

 */

class I18n {
	static function utf8_ent($text) {
		$text = json_encode($text);
		$text = preg_replace('/\\\u([0-9a-z]{4})/', '&#x$1;', $text);
		// reencode some chars like \n
		return json_decode($text);
	}
	/**
	 * load and clean json resources for php, return array()
	 */
	static function json($file) {
		$content=file_get_contents($file);
		$content=substr($content, strpos($content, '{'));
		$content= json_decode($content, true);
		switch (json_last_error()) {
			case JSON_ERROR_NONE:
			break;
			case JSON_ERROR_DEPTH:
				echo "$file — Maximum stack depth exceeded\n";
			break;
			case JSON_ERROR_STATE_MISMATCH:
				echo "$file — Underflow or the modes mismatch\n";
			break;
			case JSON_ERROR_CTRL_CHAR:
				echo "$file — Unexpected control character found\n";
			break;
			case JSON_ERROR_SYNTAX:
				echo "$file — Syntax error, malformed JSON\n";
			break;
			case JSON_ERROR_UTF8:
				echo "$file — Malformed UTF-8 characters, possibly incorrectly encoded\n";
			break;
			default:
				echo "$file — Unknown error\n";
			break;
		}
		return $content;
	}

}
?>
