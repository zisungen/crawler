<?php

require_once('class.base.php');

class CrawlerWeiboSearch extends CrawlerBase {

	public function __construct() {
		parent::__construct();
	}

	/**
	 * 网络IO，执行抓取
	 */
	public function doCrawl() {
		if (!isset($this->crawl_config['keywords']) || empty($this->crawl_config['keywords'])) {
			throw new Exception("keywords required for weibo search");
		}

		$page = $this->crawl_config['page'];
		if ($page <= 0) {
			throw new Exception("invalid page setting for weibo search");
		}

		$this->snoopy->agent = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36";

		foreach ($this->crawl_config['keywords'] as $kw) {
			for ($i=1; $i <= $page; $i++) { 
				$weibo_url = 'https://m.weibo.cn/container/getIndex?type=all&containerid=100103type%3D1%26q%3D'.rawurlencode($kw).'&page='.$i;

				$this->log("开始请求地址:$weibo_url");

				$this->snoopy->fetch($weibo_url);
				
				if ($this->snoopy->results === null) {
					continue;
				}

				$weibo_result = $this->snoopy->results;

				$this->log("请求返回结果:$weibo_result");

				$result = json_decode($weibo_result, true);

				if (!is_array($result)) {
					continue;
				}

				if (isset($result['cards'])) {
					foreach ($result['cards'] as $rc) {
						if (isset($rc['card_group'])) {
							foreach ($rc['card_group'] as $rcc) {
								if ($rcc['card_type'] == 9) {
									$rcc['mblog']['created_at_time'] = date('Y-m-d H:i', $this->getWeiboMblogTime($rcc['mblog']['created_at']));
									$this->crawl_messages[] = $rcc['mblog'];
								}
							}
						}
					}
				}
			}
		}
	}

	public function doKeywordFilter() {
		if (isset($this->crawl_config['keyword_filter'])) {
			$keyword_filter = $this->crawl_config['keyword_filter'];
			if (!empty($keyword_filter)) {
				foreach ($this->crawl_messages as $ssk => $ssc) {
					foreach ($keyword_filter as $kf) {
						if (mb_strpos($ssc['text'], $kf) !== false) {
							$this->log('微博正文筛出关键字匹配成功，删除数据:' . print_r($ssc, true));
							unset($this->crawl_messages[$ssk]);
						}
					}
				}
			}
		}
	}

	public function doPublicTimeLimit() {
		if (isset($this->crawl_config['public_time_limit'])) {
			foreach ($this->crawl_messages as $ssk => $ssc) {
				if ($ssc['created_at_time'] < $this->crawl_messages['public_time_limit']) {
					$this->log('不满足发布时间限制，删除数据:' . print_r($ssc, true));
					unset($this->crawl_messages[$ssk]);
				}
			}
		}
	}

	public function doImageLimit() {
		if (isset($this->crawl_config['image_limit'])) {
			foreach ($this->crawl_messages as $ssk => $ssc) {
				//如果是视频，不检测图片限制
				if (isset($ssc['page_info']) && $ssc['page_info']['type'] == 'video') {
					continue;
				}
				if (count($ssc['pics']) < $this->crawl_config['image_limit']) {
					$this->log('不满足图片设置，删除数据:' . print_r($ssc, true));
					unset($this->crawl_messages[$ssk]);
				}
			}
		}
	}

	public function doVideoLimit() {
		if (isset($this->crawl_config['video_limit'])) {
			foreach ($this->crawl_messages as $ssk => $ssc) {
				if (!isset($ssc['page_info']) || $ssc['page_info']['type'] != 'video') {
					$this->log('不满足视频设置，删除数据:' . print_r($ssc, true));
					unset($this->crawl_messages[$ssk]);
				}
			}
		}
	}

	public function doMessage() {
		// print_r($this->crawl_messages);
	}

	public function getMessage() {
		return $this->crawl_messages;
	}

	//================= Spider END =======================	

	//通用
	//------------------------通用错误码 BEGIN----------------------------------

	private function getWeiboMblogTime($str) {
		$real_time = null;
		$time_now = time();
		$date_today = date('Y-m-d');
		$year_now = date('Y');
		if (mb_strpos($str, '分钟') !== false) {
			$real_time = $time_now - intval($str) * 60;
		} else if (mb_strpos($str, '今天') !== false) {
			$real_time = strtotime($date_today . ' ' . trim(str_replace('今天', '', $str)));
		} else if (strpos($str, '-') !== false) {
			$real_time = strtotime($year_now . '-' . $str);
		} else {
			$real_time = strtotime($str);
		}
		return $real_time;
	}
	//------------------------通用错误码 END----------------------------------

}
?>