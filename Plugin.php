<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 炒鸡漂亮的html5播放器APlayer插件，支持在文章中插入html5播放器，启用前请确保插件的cache目录可写
 * 
 * @package APlayer
 * @author ZGQ
 * @version 1.4.11
 * @dependence 13.12.12-*
 * @link https://github.com/zgq354/APlayer-Typecho-Plugin
 */

class APlayer_Plugin implements Typecho_Plugin_Interface
{
    //此变量用以在一个变量中区分多个播放器实例
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
        $info = self::is_really_writable(dirname(__FILE__)."/cache") ? "插件启用成功！！" : "APlayer插件目录的cache目录不可写，可能会导致博客加载缓慢！"; 
        return _t($info);
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
                @unlink($file);
            }
        }
        return _t('APlayer插件禁用成功，所有缓存已清空!');
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

        $listexpire = new Typecho_Widget_Helper_Form_Element_Text(
            'listexpire', null, '43200',
            _t('歌单更新周期'), _t('设置歌单的缓存时间（单位：秒），超过设定时间后歌单将自动更新'));
        $form->addInput($listexpire);

        $maintheme = new Typecho_Widget_Helper_Form_Element_Text(
            'maintheme', null, '#e6d0b2',
            _t('默认主题颜色'), _t('播放器默认的主题颜色，如 #372e21、#75c、red、blue，该设定会被[player]标签中的theme属性覆盖，默认为 #e6d0b2'));
        $form->addInput($maintheme);

        $nolyric = new Typecho_Widget_Helper_Form_Element_Text(
            'nolyric', null, '找不到歌词',
            _t('找不到歌词时显示的文字'), _t('找不到歌词时显示的文字'));
        $form->addInput($nolyric);

        $mutex = new Typecho_Widget_Helper_Form_Element_Radio(
            'mutex', array('false'=>_t('是'),'true'=>_t('否')), 'true',
            _t('是否允许在一个页面中多个播放器同时播放'), _t('若选择否，当页面中存在多个播放器时，点击其中一个播放器的播放按钮，其它播放器将自动暂停'));
        $form->addInput($mutex);


        $preload = new Typecho_Widget_Helper_Form_Element_Radio(
            'preload', array('false'=>_t('自动'),'none'=>_t('none'),'metadata'=>_t('metadata'),'auto'=>_t('auto')), 'false',
            _t('音频预加载(preload)属性'), '自动:移动端为none，桌面端为metadata; none:页面加载后不预加载音频; metadata:当页面加载后仅加载音频的元数据; auto:一旦页面加载，则开始加载音频。');
        $form->addInput($preload);


        $cache = new Typecho_Widget_Helper_Form_Element_Radio('cache',
            array('false'=>_t('否')),'false',_t('清空缓存'),_t('清空插件生成的缓存文件，必要时可以使用'));
        $form->addInput($cache);

        $submit = new Typecho_Widget_Helper_Form_Element_Submit();
        $submit->value(_t('清空歌词，专辑图片链接，在线歌曲缓存'));
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
            @unlink($filename);
        }

        Typecho_Widget::widget('Widget_Notice')->set(_t('歌词与封面链接，在线歌曲缓存已清空!'),NULL,'success');

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
     * 头部css挂载,并定义参数的变量
     * 
     * @return void
     */
    public static function playercss()
    {
        $playerurl = Helper::options()->pluginUrl.'/APlayer/assets/dist/';
        echo '
<!-- APlayer Start -->
<link rel="stylesheet" type="text/css" href="'.$playerurl.'APlayer.min.css" />
<script>var APlayers = [];var APlayerOptions = [];</script>
<!-- APlayer End -->
';
    }


    /**
     * 尾部js，解析文章中给header的播放器变量添加的播放器参数并生成播放器的html
     *
     *
     * @return void
     */
     public static function footerjs()
     {
        $playerurl = Helper::options()->pluginUrl.'/APlayer/assets/dist/';
        
        echo <<<EOF
<!-- APlayer Start -->
<script type="text/javascript" src="{$playerurl}APlayer.min.js"></script>
<script>
var len = APlayerOptions.length;
for(var i=0;i<len;i++){
    APlayers[i] = new APlayer({
        element: document.getElementById('player' + APlayerOptions[i]['id']),
        narrow: false,
        preload: APlayerOptions[i]['preload'],
        mutex: APlayerOptions[i]['mutex'],
        autoplay: APlayerOptions[i]['autoplay'],
        showlrc: APlayerOptions[i]['showlrc'],
        music: APlayerOptions[i]['music'],
        theme: APlayerOptions[i]['theme']
        });
    APlayers[i].init();
}
</script>
<!-- APlayer End -->


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

            //当没有标签时候就直接return提高运行效率
            if ( false === strpos( $content, '[' ) ) {
                return $content;
            }

            $pattern = self::get_shortcode_regex( array('player') );
            $content = preg_replace_callback("/$pattern/",array('APlayer_Plugin','parseCallback'), $content);

        }

        return $content;
    }
    
    /**
     * 回调解析
     * @param unknown $matches
     * @return string
     */
    public static function parseCallback($matches)
    {
        /*
            $mathes array
            * 1 - An extra [ to allow for escaping shortcodes with double [[]]
            * 2 - The shortcode name
            * 3 - The shortcode argument list
            * 4 - The self closing /
            * 5 - The content of a shortcode when it wraps some content.
            * 6 - An extra ] to allow for escaping shortcodes with double [[]]
         */

        // allow [[player]] syntax for escaping the tag
        if ( $matches[1] == '[' && $matches[6] == ']' ) {
            return substr($matches[0], 1, -1);
        }
        //播放器id
        $id = self::getUniqueId();
        //还原转义后的html
        //[player title=&quot;Test Abc&quot; artist=&quot;haha&quot; id=&quot;1234543&quot;/]
        $attr = htmlspecialchars_decode($matches[3]);
        //[player]标签的属性，类型为array
        $atts = self::shortcode_parse_atts($attr);
        //开始解析音乐地址
        $result = array();
        //解析[player]标签内id和url属性
        if (isset($atts['url']) || isset($atts['id'])){
            $r = self::parse($matches[5], $atts);
            if ($r) $result = array_merge($result, $r);
        }
        //解析[player][/player]内部的[mp3]标签
        if ($matches[4] != '/' && $matches[5]){
            //获取正则
            $regex = self::get_shortcode_regex(array('mp3'));
            //过滤html标签并还原转义后的字符
            $content = htmlspecialchars_decode(strip_tags($matches[5]));
            //开始解析
            if ( ( false !== strpos( $content, '[' ) ) && preg_match_all("/$regex/", $content , $all)){
                foreach ($all[0] as $k=>$v){
                    $a = self::shortcode_parse_atts($all[3][$k]);
                    //获取所有music信息
                    $r = self::parse(trim($all[5][$k]), $a);
                    if ($r) $result = array_merge($result, $r);
                }
            }
        }
        //删除id避免与后面的id属性冲突
        if (isset($atts['id'])) unset($atts['id']);
        //没有歌曲时候直接返回空值避免出错
        if (empty($result)) return '';
        //主题颜色
        $theme = Typecho_Widget::widget('Widget_Options')->plugin('APlayer')->maintheme;
        if (!$theme) $theme = '#e6d0b2';
        //只允许一个播放器播放
        $mutex = Typecho_Widget::widget('Widget_Options')->plugin('APlayer')->mutex;
        if ($mutex == "false") $mutex = false;
        if ($mutex == "true") $mutex = true;
        //Audio preload
        $preload = Typecho_Widget::widget('Widget_Options')->plugin('APlayer')->preload;
        if (!$preload || $preload == "false") $preload = false;
        //播放器默认属性
        $data = array(
            'id' => $id ,
            'autoplay' => false,
            'theme' => $theme,
            'mutex' => $mutex,
            'preload' => $preload
        );
        //设置播放器属性
        if(!empty($atts)){
            foreach ($atts as $k => $att) {
                $data[$k] = $atts[$k];
            }
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
        $data['autoplay'] = (bool)$data['autoplay'] && $data['autoplay'] !== 'false';
        //歌词
        $data['showlrc'] = isset($data['showlrc']) && (bool)$data['showlrc'] && $data['showlrc'] !== 'false' ? 1 : 0 ;
        //输出代码
        $playerCode =  '<div id="player'.$id.'" class="aplayer">
        ';

        //nolyric
        $nolyric = Typecho_Widget::widget('Widget_Options')->plugin('APlayer')->nolyric;
        if (!$nolyric) $nolyric = '找不到歌词';

        //歌词
        if (!empty($result)){
            foreach ($result as $k=>$v){
                //歌词不存在的时候输出'no lyric'
                $result[$k]['lrc'] = $v['lyric'] ? $v['lyric'] : "[00:00.00]$nolyric\n[99:00.00] ";
                unset($result[$k]['cover']);
                unset($result[$k]['lyric']);
                unset($result[$k]['artist']);
            }
        }
        $playerCode .= "</div>\n";
        //开始添加歌曲列表，若只有一首歌则解析成单曲播放器，否则解析为列表播放器
        if (count($result) === 1) 
            $result = array_shift($result);
        $data['music'] = $result;
        //加入头部数组
        $js = json_encode($data);
        $playerCode .= <<<EOF
<script>APlayerOptions.push({$js});</script>
EOF;

        return $playerCode;
    }


    /**
     * 获取一个唯一的id以区分各个播放器实例
     * @return number
     */
    public static function getUniqueId()
    {
        self::$playerID++;
        return self::$playerID;
    }

    /**
     * 根据参数进一步解析得到歌曲的信息
     * 
     * @param string $content 标签内的内容，如歌词
     * @param array $atts 歌曲的属性
     * @return array 包含解析结果的数组
     */
    private static function parse($content = '',$atts = array())
    {
        //过滤html标签避免出错
        $content = strip_tags($content);
        //取出[lrc]
        $lyric = false;
        if( preg_match('/\[(lrc)](.*?)\[\/\\1]/si', $content ,$lyrics) ){
            $lyric = $lyrics[2];
        }
        //最终结果
        $return = array();
        //解析歌词，如果没有[lrc][/lrc]文本歌词但是有lrc的url的话直接从url中读取并缓存
        if(isset($atts['lrc']) && !$lyric){
            if($c = self::getlrc($atts['lrc']))
                $lyric = $c;
        }
        $atts['lyric'] = false;
        //解析网易云音乐
        if(isset($atts['id'])){
            $type = isset($atts['type']) ? $atts['type'] : 'song';
            $result = self::parse_netease($atts['id'], $type);
            if ($result)
                $return = array_merge($return, $result);
        }
        //当网易只返回了一首歌或是插入自己上传的音乐才考虑下方情况
        if (isset($atts['url']) || count($return) === 1) {
            //自定义歌词
            if($lyric)
                $atts['lyric'] = $lyric;
            //解析封面
            if(( ! isset($atts['id']) && isset($atts['url']) && !isset($atts['cover'])) || (isset($atts['cover']) && $atts['cover'] == 'search')){
                $title = isset($atts['title']) ? $atts['title'] : '';
                $artist = isset($atts['artist']) ? $atts['artist'] : '';
                $words = $title.' '.$artist;
                if ($title || $artist) {
                    if ($p = self::getcover($words)) {
                        $atts['cover'] = $p;
                    }elseif ($artist){
                        if ($p = self::getcover($artist)){
                            $atts['cover'] = $p;
                        }
                    }
                }
            }
            //标题和艺术家
            if (isset($atts['artist'])) {
                $atts['author'] = $atts['artist'];
            }elseif ( ! isset($atts['id']) && isset($atts['url'])) {
                $atts['author'] = 'Unknown';
            }
            if (! isset($atts['title']) && ! isset($atts['id']) && isset($atts['url'])) {
                $atts['title'] = 'Unknown';
            }
            //假如不要自动查找封面的话
            if (isset($atts['cover'])){
                if ($atts['cover'] == 'false' || !(bool)$atts['cover'])
                    $atts['cover'] = '';
                $atts['pic'] = $atts['cover'];
            }
            //判断是修改网易获取的歌曲属性还是添加自己的歌曲链接
            if ( ! isset($atts['id'])) {
                $return[] = $atts;
            }else{
                //当没有自定义歌词时候删除变量避免覆盖掉原有歌词
                if ( ! $atts['lyric']) {
                    unset($atts['lyric']);
                }
                $return[0] = array_merge($return[0], $atts);
            }
        }
        return $return;
    }


    /**
     * 解析netease信息
     * 
     * @param unknown $id
     * @param unknown $type
     * @return boolean|multitype:multitype:unknown Ambigous <>
     */
    private static function parse_netease($id, $type){
        //当id过长时md5避免缓存出错
        $key = 'netease_'.$type.'_'.(strlen($id) > 20 ? md5($id) : $id);
        $result = self::cache_get($key);
        //列表更新周期
        $listexpire = Typecho_Widget::widget('Widget_Options')->plugin('APlayer')->listexpire;
        if ($listexpire === null) $listexpire = 43200;
        $listexpire = (int)$listexpire;
        //缓存过期或者找不到的时候则重新请求服务器（设置过期时间是因为歌单等信息可能会发生改变），否则返回缓存
        if ($result && isset($result['data']) && ($type == "song" || (isset($result['time']) && (time() - $result['time']) < $listexpire))){
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
     * 从netease中获取歌曲信息
     * 
     * @link https://github.com/webjyh/WP-Player/blob/master/include/player.php
     * @param unknown $id 
     * @param unknown $type 获取的id的类型，song:歌曲,album:专辑,artist:艺人,collect:歌单
     */
    private static function get_netease_music($id, $type = 'song'){
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
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

                //列表
                $list = array();
                foreach ( $data as $keys => $data ){
                    //获取歌词
                    $lyric = self::get_netease_lyric($data['id']);
                    if ($lyric) $lyric = $lyric['lyric'];

                    $list[$data['id']] = array(
                            'song_id' => $data['id'],
                            'title' => $data['name'],
                            'album_name' => $data['album']['name'],
                            'artist' => $data['artists'][0]['name'],
                            'location' => str_replace('http://m', 'http://p', $data['mp3Url']),
                            'pic' => $data['album']['blurPicUrl'].'?param=106x106',
                            'lyric' => $lyric
                    );
                }
                //修复一次添加多个id的乱序问题
                if ($type = 'song' && strpos($id, ',')) {
                    $ids = explode(',', $id);
                    $r = array();
                    foreach ($ids as $v) {
                        if (!empty($list[$v])) {
                            $r[] = $list[$v];
                        }
                    }
                    $list = $r;
                }
                //最终播放列表
                $return['trackList'] = $list;
            }
        } else {
            $return = array('status' =>  false, 'message' =>  '非法请求');
        }
        return $return;
    }


    /**
     * 根据id从netease中获取歌词，带缓存
     */
    private static function get_netease_lyric($id){
        $key = 'netease_lrc_'.$id;
        $result = self::cache_get($key);
        if($result && isset($result[0])){
            return $result[0];
        }else{
            //缓存取不到则重新抓取
            $url = 'http://music.163.com/api/song/media?id='.$id;
            $refere = 'http://music.163.com;';
            if (!function_exists('curl_init') ) {
                return false;
            } else {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array( 'Cookie: appver=2.0.2' ));
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);
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
                //存入缓存
                self::cache_set($key, array($JSON));
                return $JSON;
            }
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
                    //换成大图
                    $url = str_replace("/spic/", "/mpic/", $url);
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
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
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


    /**
     * Retrieve all attributes from the shortcodes tag.
     *
     * The attributes list has the attribute name as the key and the value of the
     * attribute as the value in the key/value pair. This allows for easier
     * retrieval of the attributes, since all attributes have to be known.
     *
     * @link https://github.com/WordPress/WordPress/blob/master/wp-includes/shortcodes.php
     * @since 2.5.0
     *
     * @param string $text
     * @return array|string List of attribute values.
     *                      Returns empty array if trim( $text ) == '""'.
     *                      Returns empty string if trim( $text ) == ''.
     *                      All other matches are checked for not empty().
     */
    private static function shortcode_parse_atts($text) {
        $atts = array();
        $pattern = '/([\w-]+)\s*=\s*"([^"]*)"(?:\s|$)|([\w-]+)\s*=\s*\'([^\']*)\'(?:\s|$)|([\w-]+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/';
        $text = preg_replace("/[\x{00a0}\x{200b}]+/u", " ", $text);
        if ( preg_match_all($pattern, $text, $match, PREG_SET_ORDER) ) {
            foreach ($match as $m) {
                if (!empty($m[1]))
                    $atts[strtolower($m[1])] = stripcslashes($m[2]);
                elseif (!empty($m[3]))
                $atts[strtolower($m[3])] = stripcslashes($m[4]);
                elseif (!empty($m[5]))
                $atts[strtolower($m[5])] = stripcslashes($m[6]);
                elseif (isset($m[7]) && strlen($m[7]))
                $atts[] = stripcslashes($m[7]);
                elseif (isset($m[8]))
                $atts[] = stripcslashes($m[8]);
            }
    
            // Reject any unclosed HTML elements
            foreach( $atts as &$value ) {
                if ( false !== strpos( $value, '<' ) ) {
                    if ( 1 !== preg_match( '/^[^<]*+(?:<[^>]*+>[^<]*+)*+$/', $value ) ) {
                        $value = '';
                    }
                }
            }
        } else {
            $atts = ltrim($text);
        }
        return $atts;
    }

    
    /**
     * Retrieve the shortcode regular expression for searching.
     *
     * The regular expression combines the shortcode tags in the regular expression
     * in a regex class.
     *
     * The regular expression contains 6 different sub matches to help with parsing.
     *
     * 1 - An extra [ to allow for escaping shortcodes with double [[]]
     * 2 - The shortcode name
     * 3 - The shortcode argument list
     * 4 - The self closing /
     * 5 - The content of a shortcode when it wraps some content.
     * 6 - An extra ] to allow for escaping shortcodes with double [[]]
     *
     * @link https://github.com/WordPress/WordPress/blob/master/wp-includes/shortcodes.php
     * @since 2.5.0
     *
     *
     * @param array $tagnames List of shortcodes to find. Optional. Defaults to all registered shortcodes.
     * @return string The shortcode search regular expression
     */
    private static function get_shortcode_regex( $tagnames = null ) {
        $tagregexp = join( '|', array_map('preg_quote', $tagnames) );
    
        // WARNING! Do not change this regex without changing do_shortcode_tag() and strip_shortcode_tag()
        // Also, see shortcode_unautop() and shortcode.js.
        return
        '\\['                              // Opening bracket
        . '(\\[?)'                           // 1: Optional second opening bracket for escaping shortcodes: [[tag]]
        . "($tagregexp)"                     // 2: Shortcode name
        . '(?![\\w-])'                       // Not followed by word character or hyphen
        . '('                                // 3: Unroll the loop: Inside the opening shortcode tag
        .     '[^\\]\\/]*'                   // Not a closing bracket or forward slash
        .     '(?:'
        .         '\\/(?!\\])'               // A forward slash not followed by a closing bracket
        .         '[^\\]\\/]*'               // Not a closing bracket or forward slash
        .     ')*?'
        . ')'
        . '(?:'
        .     '(\\/)'                        // 4: Self closing tag ...
        .     '\\]'                          // ... and closing bracket
        . '|'
        .     '\\]'                          // Closing bracket
        .     '(?:'
        .         '('                        // 5: Unroll the loop: Optionally, anything between the opening and closing shortcode tags
        .             '[^\\[]*+'             // Not an opening bracket
        .             '(?:'
        . '\\[(?!\\/\\2\\])' // An opening bracket not followed by the closing shortcode tag
        .                 '[^\\[]*+'         // Not an opening bracket
        .             ')*+'
        .         ')'
        .         '\\[\\/\\2\\]'             // Closing shortcode tag
        .     ')?'
        . ')'
        . '(\\]?)';                          // 6: Optional second closing brocket for escaping shortcodes: [[tag]]
    }
    /**
     * Tests for file writability
     *
     * is_writable() returns TRUE on Windows servers when you really can't write to
     * the file, based on the read-only attribute. is_writable() is also unreliable
     * on Unix servers if safe_mode is on.
     *
     * @link    https://bugs.php.net/bug.php?id=54709
     * @param   string
     * @return  bool
     */
    private static function is_really_writable($file)
    {
        // Create cache directory if not exists
        if (!file_exists($file))
        {
            mkdir($file, 0755);
        }
        // If we're on a Unix server with safe_mode off we call is_writable
        if (DIRECTORY_SEPARATOR === '/' && (version_compare(PHP_VERSION, '5.4', '>=') OR ! ini_get('safe_mode')))
        {
            return is_writable($file);
        }
        /* For Windows servers and safe_mode "on" installations we'll actually
         * write a file then read it. Bah...
         */
        if (is_dir($file))
        {
            $file = rtrim($file, '/').'/'.md5(mt_rand());
            if (($fp = @fopen($file, 'ab')) === FALSE)
            {
                return FALSE;
            }
            fclose($fp);
            @chmod($file, 0777);
            @unlink($file);
            return TRUE;
        }
        elseif ( ! is_file($file) OR ($fp = @fopen($file, 'ab')) === FALSE)
        {
            return FALSE;
        }
        fclose($fp);
        return TRUE;
    }

}
