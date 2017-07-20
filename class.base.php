<?php

require_once("lib/simple_html_dom.php");
require_once("lib/php-hooks.php");
require_once("lib/Snoopy.class.php");

class CrawlerBase 

{
	protected static $_hooks = null;
	protected $snoopy = null;
	protected $crawl_messages = array();
	protected $crawl_config = array();

	public function __construct() {
		self::$_hooks = new Hooks();
		$this->snoopy = new Snoopy();
 		mb_internal_encoding('UTF-8');

	}
	
	public function __destruct() {}
	
	/**
	 * 设置抓取配置
	 *
	 * @access public
	 */
	public function setConfig($config) {
		if (!is_array($config) || count($config) == 0) {
			throw new Exception("crawl config required as non-empty array");
		}
		$default = array(
			'page' => 1,
			'debug' => false,
		);
		$this->crawl_config = array_merge($default, $config);
	}

	/**
	 * 预设的抓取流程
	 *
	 * @access public
	 */
	public function prepareCrawl() {
		// 执行网络IO，抓取指定链接的数据
		self::$_hooks->add_action('run_spider', array($this, 'doCrawl'));

		// 筛除关键词设置
		self::$_hooks->add_action('run_spider', array($this, 'doKeywordFilter'));

		// 内容发布时间设置
		self::$_hooks->add_action('run_spider', array($this, 'doPublicTimeLimit'));
	
		// 点赞数限制
		self::$_hooks->add_action('run_spider', array($this, 'doLikeLimit'));

		// 图片个数限制
		self::$_hooks->add_action('run_spider', array($this, 'doImageLimit'));

		// 视频个数限制
		self::$_hooks->add_action('run_spider', array($this, 'doVideoLimit'));

		// 最终输出合法的抓取数据
		self::$_hooks->add_action('run_spider', array($this, 'doMessage'));	
	}

	/**
	 * 添加钩子函数流程
	 *
	 * @access public
	 */
	public function executeCrawl() {
		self::$_hooks->do_action('run_spider');
	}

	/**
	 * 根据主题拼接关键字，并生成抓取链接
	 *
	 * @access protected
	 */
	public function doCrawl() {
		echo __METHOD__ . "\n";
	}

	/**
	 * 根据筛除关键字，过滤抓取数据并标记
	 *
	 * @access protected
	 */
	public function doKeywordFilter() {
		echo __METHOD__ . "\n";
	}

	/**
	 * 根据内容点赞数，过滤抓取数据并标记
	 *
	 * @access protected
	 */
	public function doLikeLimit() {
		echo __METHOD__ . "\n";
	}

	/**
	 * 根据内容发布时间，过滤抓取数据并标记
	 *
	 * @access protected
	 */
	public function doPublicTimeLimit() {
		echo __METHOD__ . "\n";
	}

	/**
	 * 根据图片个数设置，过滤抓取数据并标记
	 *
	 * @access protected
	 */
	public function doImageLimit() {
		echo __METHOD__ . "\n";
	}

	/**
	 * 检测是否有视频
	 *
	 * @access protected
	 */
	public function doVideoLimit() {
		echo __METHOD__ . "\n";
	}

	/**
	 * 展示过滤后的抓取数据
	 *
	 * @access protected
	 */
	public function doMessage() {
		echo __METHOD__ . "\n";
		print_r($this->crawl_messages);
	}

	/**
	 * 打印抓取日志信息
	 *
	 * @access protected
	 */
	public function log($msg) {
		if ($this->crawl_config['debug']) {
			echo sprintf("[%s]:%s\n", date('c'), $msg);
		}
	}
}