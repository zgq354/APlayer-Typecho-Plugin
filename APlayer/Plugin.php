<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 炒鸡漂亮的html5播放器APlayer插件，支持在文章中插入html5播放器
 * 
 * @package APlayer
 * @author ZGQ
 * @version 1.2.0
 * @dependence 13.12.12-*
 * @link https://github.com/zgq354/APlayer-Typecho-Plugin
 */

//support the boolval in old php
if (!function_exists('boolval')) {
	function boolval($val) {
		return (bool) $val;
	}
}

class APlayer_Plugin implements Typecho_Plugin_Interface
{
	//此变量用以在文章中插入多个播放器的时候将播放器区分开来
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
	 * 头部css挂载,定义参数的变量
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
	 * 尾部js，解析文章中的播放器参数并生成播放器的html
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
for(var ii=0;ii<len;ii++){
	aPlayers[ii] = new APlayer({
		element: document.getElementById('player' + aPlayerOptions[ii]['id']),
            narrow: false,
            autoplay: aPlayerOptions[ii]['autoplay'],
            showlrc: aPlayerOptions[ii]['showlrc'],
            music: aPlayerOptions[ii]['music'],
            theme: aPlayerOptions[ii]['theme']
	        });
	aPlayers[ii].init();
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
			//$value['text'] = preg_replace('/(?!<div>)\[(mp3)](.*?)\[\/\\1](?!<\/div>)/is','<div>[mp3]\\2[/mp3]</div>',$value['text']);
			//兼容JWPlayer
			//$value['text'] = preg_replace('/(?!<div>)<(jw)>(.*?)<\/\\1>(?!<\/div>)/is','<div><jw>\\2</jw></div>',$value['text']);
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
			//解析列表播放器
			$content = preg_replace_callback('/\[(music)(.*?)](.*?)\[\/\\1]/si',array('APlayer_Plugin','parselistCallback'),$content);
			//解析单曲播放器
			$content = preg_replace_callback('/\[(mp3)(.*?)](.*?)\[\/\\1]/si',array('APlayer_Plugin','parseCallback'),$content);
		}

		return $content;
	}
	
	/**
	 * 解析列表播放器
	 * @param unknown $matches
	 * @return string
	 */
	public static function parselistCallback($matches)
	{
		//播放器id
		$id = self::getUniqueId();
		
		$result = array();
		if (preg_match_all('/\[(mp3)(.*?)](.*?)\[\/\\1]/si', $matches[3] , $all)){	
			foreach ($all[3] as $k=>$v){
				//获取所有music信息
				$result[$k] = self::parse(trim($all[3][$k]),trim($all[2][$k]));
			}
		}
		
		$data = array(
			'id' => $id ,
			'autoplay' => false,
			'theme' => '#e6d0b2'
		);
		
		
		//获取播放器属性
		$atts = explode('|',trim($matches[2]));
		foreach ($atts as $att) {
			if (empty($att)) continue;
			$pair = explode('=',$att);
			$data[trim($pair[0])] = trim($pair[1]);
		}
		
		if(isset($data['netease'])){
			$type = isset($data['type']) ? $data['type'] : 'song';
			$r = self::parse_netease($data['netease'],$type);
			if ($r) $result = array_merge($result, $r);
				
		}
		
		//默认有歌词就显示
		if (!isset($data['showlrc'])){
			foreach ($result as $v){
				if ($v['lyric']) {
					$data['showlrc'] = true;
					break;
				}
			}
		}
				
		//自动播放
		$data['autoplay'] = boolval($data['autoplay']) && $data['autoplay'] !== 'false';
		//歌词
		$data['showlrc'] = isset($data['showlrc']) && boolval($data['showlrc']) && $data['showlrc'] !== 'false';

		//输出代码，先输出歌词
		$playerCode =  '<div id="player'.$id.'" class="aplayer">
		';
		
		$lrcCode = '';
		if (!empty($result)){
			foreach ($result as $k=>$v){
				//歌词不存在的时候输出
				$lrc = $v['lyric'] ? $v['lyric'] : '[00:00.00]no lyric';
				$lrcCode .= '<pre class="aplayer-lrc-content">'."\n".$lrc."\n</pre>\n";	
				//清理多余参数, 确保lrc内容不输出到json里面
				unset($result[$k]['lrc']);
				unset($result[$k]['cover']);
				unset($result[$k]['lyric']);
				unset($result[$k]['artist']);
			}
		}
		
		
		if ($data['showlrc']) {
			$playerCode .= $lrcCode;
		}
		$playerCode .= "</div>\n";
		
		//开始添加歌曲列表
		$data['music'] = $result;
		
		//加入头部数组
		$js = json_encode($data);
		$playerCode .= <<<EOF
		<script>aPlayerOptions.push({$js});</script>
EOF;
		
		return $playerCode;
	}

	/**
	 * 解析单曲播放器
	 * 
	 * @param array $matches
	 * @return string
	 */
	public static function parseCallback($matches)
	{		
		$data = self::parse($matches[3],$matches[2]);
		
		//播放器id
		$id = self::getUniqueId();
		
		//参数设置
		$options = array(
			'id' => $id ,
			'autoplay' => false,
			'showlrc' => false,
			'theme' => '#e6d0b2'
			);
		
		//判断是否有歌词
		if ($data['lyric']) {
			$options['showlrc'] = true;
		}

		if (isset($data['autoplay'])) {
			$options['autoplay'] = boolval($data['autoplay']) && $data['autoplay'] != 'false';
		}
		
		//主题颜色
		$options['theme'] = isset($data['theme'])?$data['theme']:$options['theme'];
		
		//添加音乐
		$options['music']['title'] = $data['title'];
		$options['music']['author'] = $data['author'];
		$options['music']['url'] = isset($data['url']) ? $data['url'] : '';
		$options['music']['pic'] = isset($data['pic']) ? $data['pic'] : '';
		
		//输出代码
		$playerCode =  '<div id="player'.$id.'" class="aplayer">
		';
		if ($data['lyric']) {
			$playerCode .= '<pre class="aplayer-lrc-content">'."\n".$data['lyric']."\n</pre>\n";
		}
		$playerCode .= "</div>\n";
		//加入头部数组
		$js = json_encode($options);
		$playerCode .= <<<EOF
		<script>aPlayerOptions.push({$js});</script>
EOF;
		
		return $playerCode;
	}

	/**
	 * 获取一个唯一的id
	 * @return number
	 */
	public static function getUniqueId()
	{
		self::$playerID++;
		return self::$playerID;
	}
	
	/**
	 * 解析一首歌
	 * 
	 * @param string $line [mp3]标签内的内容
	 * @return multitype: data
	 */
	private static function parse($content = '',$attr = '')
	{
		//过滤html标签避免出错
		$content = strip_tags($content);
		$attr = strip_tags($attr);
		
		//取出[lrc]
		$lyric = false;
		if( preg_match('/\[(lrc)](.*?)\[\/\\1]/si', $content ,$lyrics) ){
			$lyric = $lyrics[2];
		}
		
		$data = array();
		
		//mp3链接
		$data['url'] = preg_replace('/\[(lrc)](.*?)\[\/\\1]/si', '', $content);;
		
		$atts = explode('|',trim($attr));
		foreach ($atts as $att) {
			if (empty($att)) continue;
			$pair = explode('=',$att);
			$data[trim($pair[0])] = trim($pair[1]);
		}

		//解析歌词，如果没有[lrc][/lrc]文本歌词但是有lrc的url的话直接从url中读取并缓存
		if(isset($data['lrc']) && !$lyric){
			if($c = self::getlrc($data['lrc']))
				$lyric = $c;
		}

		$data['lyric'] = $lyric;
		
		if(isset($data['netease'])){
			$result = self::parse_netease($data['netease'],'song');
			if ($result) $data = array_merge($data, $result[0]);
			
		}

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
		
		$data['author'] = isset($data['artist']) ? $data['artist']:'Unknown';
		$data['title'] = isset($data['title']) ? $data['title'] : 'Unknown';

		//假如不要自动查找封面的话
		if (isset($data['cover'])){
			if ($data['cover'] == 'false' || !boolval($data['cover']))
				unset($data['cover']);
			else 
				$data['pic'] = $data['cover'];
		}
		
		return $data;
	}
	
	private static function parse_netease($id, $type){
		$key = 'netease_'.md5($id.$type);
		$result = self::cache_get($key);
		//缓存过期或者找不到的时候则重新请求服务器（例如歌单发生了改变），否则返回缓存
		if ($result && isset($result['data']) && ($type == "songs" || (isset($result['time']) && (time() - $result['time']) < 43200))){
			$data = $result['data'];
		}else{
			$data = self::get_netease_music($id, $type);
			self::cache_set($key, array('time' => time(),'data' => $data));
		}
		if (empty($data['trackList'])) return false;
		
		$return = array();
		
		foreach ($data['trackList'] as $v){
			$return[] = array(
					'author' => $v['artist'],
					'artist' => $v['artist'],
					'title' => $v['title'],
					'pic' => $v['pic'],
					'url' => $v['location'],
					'lyric' => $v['lyric'],
			);
		}
		
		return $return;		
		
	}
	
	/**
	 * 
	 * @link https://github.com/webjyh/WP-Player/blob/master/include/player.php
	 * @param unknown $id
	 * @param unknown $type
	 */
	private static function get_netease_music($id, $type){
		$return = false;
		switch ( $type ) {
			case 'song': $url = "http://music.163.com/api/song/detail/?ids=[$id]"; $key = 'songs'; break;
			case 'album': $url = "http://music.163.com/api/album/$id?id=$id"; $key = 'album'; break;
			case 'artist': $url = "http://music.163.com/api/artist/$id?id=$id"; $key = 'artist'; break;
			case 'collect': $url = "http://music.163.com/api/playlist/detail?id=$id"; $key = 'result'; break;
			default: $url = "http://music.163.com/api/song/detail/?ids=[$id]"; $key = 'songs';
		}
		
		if (!function_exists('curl_init')) return false;
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array( 'Cookie: appver=2.0.2' ));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
		curl_setopt($ch, CURLOPT_REFERER, 'http://music.163.com/;');
		$cexecute = curl_exec($ch);
		curl_close($ch);
		
		if ( $cexecute ) {
			$result = json_decode($cexecute, true);
			if ( $result['code'] == 200 && $result[$key] ){
				$return['status'] = true;
				$return['message'] = "";
		
				switch ( $key ){
					case 'songs' : $data = $result[$key]; break;
					case 'album' : $data = $result[$key]['songs']; break;
					case 'artist' : $data = $result['hotSongs']; break;
					case 'result' : $data = $result[$key]['tracks']; break;
					default : $data = $result[$key]; break;
				}
		
				foreach ( $data as $keys => $data ){
					//获取歌词
					$lyric = self::get_netease_lyric($data['id']);
					if ($lyric) $lyric = $lyric['lyric'];
					
					$return['trackList'][] = array(
							'song_id' => $data['id'],
							'title' => $data['name'],
							'album_name' => $data['album']['name'],
							'artist' => $data['artists'][0]['name'],
							'location' => $data['mp3Url'],
							'pic' => $data['album']['blurPicUrl'].'?param=90x90',
							'lyric' => $lyric
					);
					
				}
			}
		} else {
			$return = array('status' =>  false, 'message' =>  '非法请求');
		}
		return $return;
	}
	
	private static function get_netease_lyric($id){
		$url = 'http://music.163.com/api/song/media?id='.$id;
		$refere = 'http://music.163.com;';
		if (!function_exists('curl_init') ) {
			return false;
		} else {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array( 'Cookie: appver=2.0.2' ));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
			curl_setopt($ch, CURLOPT_REFERER, $refere);
			$cexecute = curl_exec($ch);
			curl_close($ch);
			$JSON = false;
			if ( $cexecute ) {
				$result = json_decode($cexecute, true);
				if ( $result['code'] == 200 && isset($result['lyric']) && $result['lyric'] ){
					$JSON = array('status' => true, 'lyric' => $result['lyric']);
				}
			
			} else {
				$JSON = array('status' => true, 'lyric' => null);
			}
			return $JSON;
		}
	}
	
	/**
	 * 通过关键词从豆瓣获取专辑封面链接，当缓存存在时则直接读取缓存
	 * 
	 * @param string $words
	 * @return boolean|string
	 */
	private static function getcover($words){
		
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

	/**
	 * 通过url获取歌词内容，若缓存存在就直接读取缓存
	 * 
	 * @param string $url
	 * @return boolean|string
	 */
	private static function getlrc($url){
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

	/**
	 * 缓存写入
	 * 
	 * @param unknown $key
	 * @param unknown $value
	 * @return number
	 */
	private static function cache_set($key, $value){
		$cachedir = dirname(__FILE__)."/cache";

		$fp = fopen($cachedir.'/'.$key,"w+");
		$status = fwrite($fp,serialize($value));
		fclose($fp);
		return $status;
	}

	/**
	 * 缓存读取
	 * 
	 * @param unknown $key
	 * @return mixed|boolean
	 */
	private static function cache_get($key){
		$cachedir = dirname(__FILE__)."/cache";

		//找到缓存直接读取缓存目录的文件
		if(file_exists($cachedir.'/'.$key)){
			return unserialize(file_get_contents($cachedir.'/'.$key));
		}else{
			return false;
		}
	}
	

	/**
	 * url抓取,两种方式,优先用curl,当主机不支持curl时候采用file_get_contents
	 * 
	 * @param unknown $url
	 * @return boolean|mixed
	 */
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
			//若主机不支持openssl则file_get_contents不能打开https的url
			if($result = @file_get_contents($url)){
				if (strpos($http_response_header[0],'200')){
					return $result;
				}
			}
			return false;
		}
	}


}
