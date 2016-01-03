<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 炒鸡漂亮的html5播放器APlayer插件，支持在文章中插入html5播放器
 * 
 * @package APlayer
 * @author ZGQ
 * @version 1.0.1
 * @dependence 13.12.12-*
 * @link https://github.com/zgq354/APlayer-Typecho-Plugin
 */
class APlayer_Plugin implements Typecho_Plugin_Interface
{
	protected static $playerID = 0;
	/**
	 * 激活插件方法,如果激活失败,直接抛出异常
	 * 
	 * @access public
	 * @return void
	 * @throws Typecho_Plugin_Exception
	 */
	public static function activate()
	{
		Typecho_Plugin::factory('Widget_Abstract_Contents')->filter = array('APlayer_Plugin','playerfilter');
		Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('APlayer_Plugin','playerparse');
		Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx = array('APlayer_Plugin','playerparse');
		Typecho_Plugin::factory('Widget_Archive')->header = array('APlayer_Plugin','playercss');
		Typecho_Plugin::factory('Widget_Archive')->footer = array('APlayer_Plugin','footerjs');
	}
	
	/**
	 * 禁用插件方法,如果禁用失败,直接抛出异常
	 * 
	 * @static
	 * @access public
	 * @return void
	 * @throws Typecho_Plugin_Exception
	 */
	public static function deactivate() {
		$files = glob('usr/plugins/APlayer/cache/*');
		foreach($files as $file){
			if (is_file($file)){
				unlink($file);
			}
		}
	}
	
	/**
	 * 获取插件配置面板
	 * 
	 * @access public
	 * @param Typecho_Widget_Helper_Form $form 配置面板
	 * @return void
	 */
	public static function config(Typecho_Widget_Helper_Form $form)
	{
		if (isset($_GET['action']) && $_GET['action'] == 'deletefile')
		self::deletefile();

		$cache = new Typecho_Widget_Helper_Form_Element_Radio('cache',
			array('false'=>_t('否')),'false',_t('清空缓存'),_t('必要时可以使用'));
		$form->addInput($cache);


		$submit = new Typecho_Widget_Helper_Form_Element_Submit();
		$submit->value(_t('清空歌词，专辑图片链接缓存'));
		$submit->setAttribute('style','position:relative;');
		$submit->input->setAttribute('style','position:absolute;bottom:37px;');
		$submit->input->setAttribute('class','btn btn-s btn-warn btn-operate');
		$submit->input->setAttribute('formaction',Typecho_Common::url('/options-plugin.php?config=APlayer&action=deletefile',Helper::options()->adminUrl));
		$form->addItem($submit);
	}


	/**
	 * 缓存清空
	 *
	 * @access private
	 * @return void
	 */
	private function deletefile()
	{
		$path = __TYPECHO_ROOT_DIR__ .'/usr/plugins/APlayer/cache/';

		foreach (glob($path.'*') as $filename) {
			unlink($filename);
		}

		Typecho_Widget::widget('Widget_Notice')->set(_t('歌词与封面链接缓存已清空!'),NULL,'success');

		Typecho_Response::getInstance()->goBack();
	}


	/**
	 * 个人用户的配置面板
	 * 
	 * @access public
	 * @param Typecho_Widget_Helper_Form $form
	 * @return void
	 */
	public static function personalConfig(Typecho_Widget_Helper_Form $form) {}

	/**
	 * 头部css挂载
	 * 
	 * @return void
	 */
	public static function playercss()
	{
		$playerurl = Helper::options()->pluginUrl.'/APlayer/dist/';
		echo '<link rel="stylesheet" type="text/css" href="'.$playerurl.'APlayer.min.css" />
<script>var aPlayers = [];var aPlayerOptions = [];</script>
';
	}
	/**
	 * 尾部js
	 *
	 *
	 * @return void
	 */
	 public static function footerjs()
	 {
		$playerurl = Helper::options()->pluginUrl.'/APlayer/dist/';
		
		echo <<<EOF
<script type="text/javascript" src="{$playerurl}APlayer.min.js"></script>
<script>
var len = aPlayerOptions.length;
for(var i=0;i<len;i++){
	aPlayers[i] = new APlayer({
		element: document.getElementById('player' + aPlayerOptions[i]['id']),
            narrow: false,
            autoplay: aPlayerOptions[i]['autoplay'],
            showlrc: aPlayerOptions[i]['showlrc'],
            music: aPlayerOptions[i]['music']
	        });
	aPlayers[i].init();
}

</script>
EOF;
	 }
	/**
	 * MD兼容性过滤
	 * 
	 * @param array $value
	 * @return array
	 */
	public static function playerfilter($value)
	{
		//屏蔽自动链接
		if ($value['isMarkdown']) {
			$value['text'] = preg_replace('/(?!<div>)\[(mp3)](.*?)\[\/\\1](?!<\/div>)/is','<div>[mp3]\\2[/mp3]</div>',$value['text']);
			//兼容JWPlayer
			$value['text'] = preg_replace('/(?!<div>)<(jw)>(.*?)<\/\\1>(?!<\/div>)/is','<div><jw>\\2</jw></div>',$value['text']);
		}
		return $value;
	}

	/**
	 * 内容标签替换
	 * 
	 * @param string $content
	 * @return string
	 */
	public static function playerparse($content,$widget,$lastResult)
	{
		$content = empty($lastResult) ? $content : $lastResult;

		if ($widget instanceof Widget_Archive) {
			$content = preg_replace_callback('/\[(mp3)](.*?)\[\/\\1]/si',array('APlayer_Plugin','parseCallback'),$content);
		}

		return $content;
	}

	/**
	 * 参数回调解析，自动下载歌词
	 * 
	 * @param array $matches
	 * @return string
	 */
	public static function parseCallback($matches)
	{
		$all = $matches[2];
		$lyric = false;
		if( preg_match('/\[(lrc)](.*?)\[\/\\1]/si', $all ,$lyrics) ){
			$lyric = $lyrics[2];
		}

		$all = preg_replace('/\[(lrc)](.*?)\[\/\\1]/si', '', $all);

		$atts = explode('|',$all);
		
		//mp3链接
		$files = array_shift($atts);
		
		//其他参数
		$data = array();

		foreach ($atts as $att) {
			$pair = explode('=',$att);
			$data[trim($pair[0])] = trim($pair[1]);
		}

		//解析歌词，如果没有[lrc][/lrc]文本歌词但是有lrc的url的话直接从url中读取并缓存
		if(isset($data['lrc']) && !$lyric){
			if($c = self::getlrc($data['lrc']))
				$lyric = $c;
		}

		$data['lyric'] = $lyric;

		//解析封面
		if(!isset($data['cover'])){
			$title = isset($data['title']) ? $data['title'] : '';
			$artist = isset($data['artist']) ? $data['artist'] : '';
			$words = $title.' '.$artist;
			if ($title || $artist) {
				if ($p = self::getcover($words)) {
					$data['cover'] = $p;
				}elseif ($artist){
					if ($p = self::getcover($artist)){
						$data['cover'] = $p;
					}
				}
			}
		}

		return self::getPlayer($files,$data);
	}

	/**
	 * 输出播放器实例
	 * 
	 * @param string $source
	 * @param array $playerOptions
	 * @return string
	 */
	public static function getPlayer($source,$playerOptions = array())
	{
		//播放器id
		$id = self::$playerID;
		self::$playerID++;
		//参数设置
		$options = array(
			'id' => $id ,
			'autoplay' => false,
			'showlrc' => false,
			);
		//判断是否有歌词
		if ($playerOptions['lyric']) {
			$options['showlrc'] = true;
		}

		if (isset($playerOptions['autoplay'])) {
			if ($playerOptions['autoplay']) {
				$options['autoplay'] = true;
			}
		}
		
		//假如不要自动查找封面的话
		if (isset($playerOptions['cover']))
			if ($playerOptions['cover'] == 'false')
				unset($playerOptions['cover']);

		$options['music']['title'] = isset($playerOptions['title']) ? $playerOptions['title'] : '未知';
		$options['music']['author'] = isset($playerOptions['artist']) ? $playerOptions['artist'] : '未知';
		$options['music']['url'] = $source;
		$options['music']['pic'] = isset($playerOptions['cover']) ? $playerOptions['cover'] : '';
		
		//输出代码
		$playerCode =  '<div id="player'.$id.'" class="aplayer">
		';
		if ($playerOptions['lyric']) {
			$playerCode .= '<pre class="aplayer-lrc-content">'."\n".$playerOptions['lyric']."\n</pre>\n";
		}
		$playerCode .= "</div>\n";
		//加入头部数组
		$js = json_encode($options);
		$playerCode .= <<<EOF
		<script>aPlayerOptions.push({$js});</script>
EOF;
		
		return $playerCode;

	}

	private static function getcover($words){
		//缓存文件夹
		$cachedir = dirname(__FILE__)."/cache";
		$key = 'cover_'.md5($words);

		if($g = self::cache_get($key)){
			if(!isset($g[0])) return false;
			return $g[0];
		}else{
			//缓存不存在时用豆瓣获取并存入缓存
			$arg = http_build_query(array('q' => $words,'count'=> 1 ));
			$url = false;
			$g = self::fetch_url('https://api.douban.com/v2/music/search?'.$arg);
			if ($g){
				$g = json_decode($g,true);
				if($g['count']){
					$url = $g['musics'][0]['image'];
				}
			}
			//用array包裹这个变量就不会判断错误啦
			self::cache_set($key,array($url));
			return $url;
			
		}

	}

	//获取歌词函数
	private static function getlrc($url){
		//存放歌词缓存文件夹
		$cachedir = dirname(__FILE__)."/cache";
		$key = 'lrc_'.md5($url);
		if($g = self::cache_get($key)){
			if(!isset($g[0])) return false;
			return $g[0];
		}else{
			//缓存不存在时用url获取并存入缓存
			$lyric = self::fetch_url($url);
			//用array包裹这个变量就不会判断错误啦
			self::cache_set($key,array($lyric));
			return $lyric;
		}
		
	}

	//简单的文件缓存
	private static function cache_set($key, $value){
		$cachedir = dirname(__FILE__)."/cache";

		$fp = fopen($cachedir.'/'.$key,"w+");
		$status = fwrite($fp,serialize($value));
		fclose($fp);
		return $status;
	}

	//简单的文件缓存
	private static function cache_get($key){
		$cachedir = dirname(__FILE__)."/cache";

		//找到缓存直接读取缓存目录的文件
		if(file_exists($cachedir.'/'.$key)){
			return unserialize(file_get_contents($cachedir.'/'.$key));
		}else{
			return false;
		}
	}

	//加载文件
	private static function fetch_url($url){

		if(function_exists('curl_init')){
			$ch = curl_init($url); 
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true) ; 
			curl_setopt($ch, CURLOPT_BINARYTRANSFER, true) ; 
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			$output = curl_exec($ch);
			$httpCode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
			if ($httpCode != 200) return false;
			return $output;
		}else{
			return false;
		}
	}


}
