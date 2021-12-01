<?php

class BfoxIframe {

	/**
	 * @var BfoxRef
	 */
	private $ref;

	private $url;

	private $lang;

	public function __construct(BfoxRef $ref, string $ln = 'en') {
		$this->ref = $ref;
		$this->lang = $ln;
		$translations = BfoxTranslations::replace_vars(BfoxTranslations::translations(true, $this->lang), $this->ref);

		// Get the previously used Bible translation from cookies
		foreach ($translations as $id => $trans) {
			$trans->url = str_replace(array('+', '%3A'), '/', $trans->url);
			if (empty($this->url) ) { //|| $id == $_COOKIE['bfox-blog-iframe-select']
				$this->url = $trans->url;
			}
		};

		$this->domains = BfoxTranslations::group_by_domain($translations);
	}

	/**
	 * Returns the currently selected URL
	 * @return string
	 */
	public function url() {
		return $this->url;
	}

	/**
	 * Returns a string of HTML option elements for these translations
	 * @return string
	 */
	public function select_options() {
		$content = '';

		foreach ($this->domains as $domain => $translations) {
			$content .= "<option value=''>From http://$domain</option>";
			foreach ($translations as $id => $trans) {
				if ($this->url == $trans->url) $selected = "selected='selected'";
				else $selected = '';

				$content .= "<option name='$id' value='$trans->url'$selected> - $trans->long_name</option>";
			}
		}

		return $content;
	}
}

?>