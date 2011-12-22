<?php

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Ecodev Sàrl
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 * ************************************************************* */

/**
 * Plugin 'RSS Feed' for the 'rss_output' extension.
 *
 * @author	Fabien Udriot <fabien.udriot@ecodev.ch>

 */
class tx_rssoutput_pi1 extends tslib_pibase {

	public $prefixId = 'tx_rssoutput_pi1'; // Same as class name
	public $scriptRelPath = 'pi1/class.tx_rssoutput_pi1.php'; // Path to this script relative to the extension dir.
	public $extKey = 'rss_output'; // The extension key.
	public $pi_checkCHash = TRUE;

	/**
	 * @var t3lib_DB
	 */
	protected $db;

	/**
	 * @var t3lib_cache_frontend_AbstractFrontend
	 */
	protected $cacheInstance;

	public function __construct() {
		$this->db = $GLOBALS['TYPO3_DB'];
	}
	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	public function main($content, $conf) {

		// Initialize plugin
		$this->conf = $conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->pi_USER_INT_obj = 1; // Configuring so caching is not expected. This value means that no cHash params are ever set. We do this, because it's a USER_INT object!

		t3lib_utility_Debug::debug($conf, "debug");

		$uid = (int)t3lib_div::_GET('uid');

		// possibly overrides value
		if (!empty($conf['uid'])) {
			$uid = (int)$conf['uid'];
		}

		if ($uid < 1) {
			throw new Exception('Exception 1323921433: no uid parameter given in the URL.', 1323921433);
		}


		$record = $this->db->exec_SELECTgetSingleRow('*', 'tx_rssoutput_feed', 'deleted = 0 AND hidden = 0 AND uid = ' . $uid);

		// Makes sure the record exists
		if (empty($record)) {
			throw new Exception("Exception 1323921662: no record found for '$uid'. Record exists? Hidden or deleted? ", 1323921662);
		}

		$repository = t3lib_div::makeInstance('Tx_RssOutput_Domain_Repository_RecordRepository');
		t3lib_utility_Debug::debug($record, "debug");
		#$repository->find($record);
		return '123';

		$this->initializeCache();

		// Get values from the User Interface
		$quantity = $this->cObj->data["tx_rssoutput_quantity"];
		$crop = $this->cObj->data["tx_rssoutput_descriptionlength"];
		$separator = $this->cObj->data["tx_rssoutput_separator"];
		$url = $this->cObj->data["tx_rssoutput_feed"];
		$descriptionDisplay = $this->cObj->data["tx_rssoutput_descriptiondisplay"];

		if (strlen($url) <= 3) {
			// if no url is found throw an error
			$result = "<strong style=\"color:red\">No URL feed defined in plugin. Change this in plugin configuration.</strong>";
			return $result;
			#throw new Exception('Exception 1320651278: no URL feed defined in plugin "' . $this->extKey . '". Change this in the plugin configuration.', 1320651278);
		}

		// Try to get from the cache the content
		$cacheIdentifier = md5($url);
		$result = $this->cacheInstance->get($cacheIdentifier);

		// Makes sure "no_cache" flag is not detected
		if (!$result || $GLOBALS['TSFE']->no_cache) {
			$lifetime = $conf['cacheDuration'];

			//t3lib_div.debug($lifetime);
			//exit();

			$content = implode("", file($url));
			$content = $this->sanitizeContent($content);

			// you will probably want "content:encoded" as one of the tags defined
			$tags = explode(",", $conf['tags']);

			//array with the title and the titleLink
			$titleInfo = $this->getTitleInfo($content);

			$templateFile = t3lib_div::getFileAbsFileName($conf['templateFile']);
			$view = t3lib_div::makeInstance('Tx_Fluid_View_StandaloneView');
			$view->setTemplatePathAndFilename($templateFile);
			$view->assign('title', $titleInfo['title']);
			$view->assign('titleLink', $titleInfo['titleLink']);
			$view->assign('max', $crop);

			preg_match_all("|<item>(.*)</item>|Uism", $content, $rawItems, PREG_PATTERN_ORDER);

			for ($i = 0; $i < $quantity && !empty($rawItems[1][$i]); $i++) {
				$items[] = $this->getTagValues($tags, $rawItems[1][$i]);
				//$items[] = $this->getTagValues($tags, $rawItems[1][$i], $crop);
			}

			$view->assign('descriptionDisplay', $descriptionDisplay);
			$view->assign('items', $items);

			$result = $view->render();

			// set in cache for next use
			$this->cacheInstance->set($cacheIdentifier, $result, array(1), $lifetime);
		}

		// Use to flush in Development Context
		//$this->cacheInstance->flush();
		return $result;
	}

	/**
	 * Returns the title info of the RSS feed
	 *
	 * @param string $content Content of the feed
	 * @return array $titleInfo Title and titleLink of the RSS feed
	 */
	private function getTitleInfo($content) {
		// Initialize empty value
		$titleInfo['title'] = $titleInfo['titleLink'] = '';

		preg_match("|<title>(.*)</title>.*<link>(.*)</link>|isU", $content, $matches);
		if (!empty($matches[1])) {

			// Extract title
			$title = $matches[1];
			$searches[] = '<![CDATA[';
			$searches[] = ']]>';
			$titleInfo['title'] = str_replace($searches, '', $title);

		}

		if (!empty($matches[2])) {
			// Extract title link
			$titleInfo['titleLink'] = $matches[2];
		}

		return $titleInfo;
	}

	/**
	 * Analyse the item and extract the value corresponding to the tag name
	 *
	 * @param string $tag: the tag to be analyzed
	 * @param string $item: the item of the feed
	 * @return string
	 */
	private function getTagValue($tag, $item) {
		preg_match_all("|<" . $tag . ">(.*)</" . $tag . ">(.*)|Uism", $item, $register, PREG_PATTERN_ORDER);
		return $register[1][0];
	}

	/**
	 * Anaylze the array of tag and fetch the corresponding value from the feed.
	 * The value corresponds to the string displayed on the HTML page.
	 *
	 * @param array $tags: the array of tagnames
	 * @param array $items: array with rss data
	 * @param int $crop: the trim value
	 * @return array
	 */
	private function getTagValues($tags, $items) {
		foreach ($tags as $tag) {
			if ($tag == 'description') {
				$value = strtr($this->getTagValue($tag, $items), array('<![CDATA[' => '', ']]>' => ''));
				$values[$tag] = strip_tags(html_entity_decode($value)); // remove potential html tag
			} else {
				$values[$tag] = strtr($this->getTagValue($tag, $items), array('<![CDATA[' => '', ']]>' => ''));
			}
		}
		return $values;
	}

	/**
	 * Clean the RSS feed from possible flaws such as JavaScript tags
	 *
	 * @param string $content
	 * @return string the cleaned content
	 */
	private function sanitizeContent($content) {
		$pattern = '/<( *)script([^>]*)type( *)=( *)([^>]*)>(.*)<\/( *)script( *)>/isU';
		$replace = '';

		$content = preg_replace($pattern, $replace, $content);

		return $content;
	}

	/**
	 * Initialize cache instance to be ready to use
	 *
	 * @return void
	 */
	protected function initializeCache() {
		t3lib_cache::initializeCachingFramework();
		try {
			$this->cacheInstance = $GLOBALS['typo3CacheManager']->getCache('rssoutput_cache');
		} catch (t3lib_cache_exception_NoSuchCache $e) {
			$this->cacheInstance = $GLOBALS['typo3CacheFactory']->create(
				'rssoutput_cache',
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['rssoutput_cache']['frontend'],
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['rssoutput_cache']['backend'],
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['rssoutput_cache']['options']
			);
		}
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rss_output/pi1/class.tx_rssoutput_pi1.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rss_output/pi1/class.tx_rssoutput_pi1.php']);
}
?>