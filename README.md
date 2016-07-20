# APlayer-Typecho-Plugin
Typecho plugin for a beautiful html5 music player https://github.com/DIYgod/APlayer 

[Demo](http://blog.izgq.net/archives/456/)

## 介绍
1. 通过简短的代码在文章或页面中插入漂亮的Html5播放器
2. 自动解析lrc链接，可根据歌曲名和歌手名自动查找封面并生成缓存
3. 支持网易云音乐单曲、歌单、专辑、歌手id的解析
4. 与APlayer保持同步更新

## 声明
本插件仅供个人学习研究使用，请勿将其用作商业用途，音乐版权归网易云音乐 music.163.com 所有。

## 安装
安装前请确保插件中的cache目录可写（保存缓存用，否则会让博客加载缓慢）

主机需支持curl扩展，否则将可能不能自动查找封面、解析网易云音乐id、从https的url中获取歌词(file_get_contents在不支持openssl的主机中不能打开https链接)

Download ZIP, 解压，将 APlayer-Typecho-Plugin-master 重命名为 APlayer ，之后上传到你博客中的 /usr/plugins 目录，在后台启用即可

## 使用方法
在文章或页面中加入下方格式的短代码即可

#### 调用格式

##### 单曲播放：
```
[player 属性1="值1" 属性2="值2" 属性3="值3" /]
or
[player 属性1="值1" 属性2="值2" 属性3="值3"][lrc]歌词[/lrc][/player]
```

example:
```
[player url="http://xxx.com/xxx.mp3" artist="Someone" title="Title" showlrc="false"/]

[player url="http://xxx.com/xxx.mp3" artist="Someone" title="Title"][lrc][00:00.00]Test lyrics[/lrc][/player]

网易云音乐：
[player id="29947420"/]

```


##### 多首歌曲：

```
[player 属性1="值1" 属性2="值2" 属性3="值3"]
[mp3 歌曲属性1="值1" 歌曲属性2="值2" 歌曲属性3="值3"/]
[mp3 歌曲属性1="值1" 歌曲属性2="值2" 歌曲属性3="值3"][lrc]歌词[/lrc][/mp3]
[/player]
```

example:
```
[player theme="#e6d0b2" autoplay='1']
[mp3 url="http://xxx.com/xxx.mp3" artist="Someone" title="Title"/]
[mp3 url="http://xxx.com/xxx.mp3" artist="Someone" title="Title"][lrc][00:00.00]Test lyrics[/lrc][/mp3]
[mp3 id="29947420"/] //网易云音乐歌曲id直接解析
[/player]
```

##### 网易云音乐解析示例：
```
[player id='346069,346080,29947420'/] //一次加入三首歌
[player id='11362719' type='collect'/] //歌单
[player id='3684' type='artist'/] //艺人热门五十首
[player id='3084335' type='album'/] //专辑

```

如果要阻止代码解析成为播放器的话，用[]包裹[player]标签即可

```
[[player id='3084335' type='album'/]]

输出：
[player id='3084335' type='album'/]
```

#### 用到的shortcode标签
```
[player] :整个播放器的标签，里面可用下面提到的所有属性
[mp3] :可以用歌曲属性和网易云音乐属性，用于嵌套在[player]标签内部添加音乐
[lrc] :用以添加文本的歌词，可嵌套在[mp3],[player]标签内部；只有当其父标签只定义一首歌的时候才起作用
```

#### 关于各个标签的属性
播放器配置(只能在[player]标签中使用)：
```
showlrc: 当showlrc的值为 0 或 false 时，不显示歌词，否则将按照歌曲有无歌词来判断是否输出歌词
autoplay: 是否自动播放，默认为 false （注：由于移动端浏览器限制，此功能在移动端浏览器将不起作用）
theme: 设置主题颜色(十六进制)，默认为 '#e6d0b2' 
```
歌曲的属性(可在[mp3]或[player]中使用，不能用于修改整个歌单的属性)：
```
url: mp3文件的链接，必需
lrc: 歌词的lrc链接，非必需
lrcoffset: 歌词整体提前时间（ms）若这个值为负数则为歌词整体延后的时间
title: 歌曲的标题，若值为空则显示 Unknown
artist: 歌曲的艺术家，若值为空则显示 Unknown
cover: 封面图片链接，非必需，若该值为图片链接则按照链接加载封面图，若没有此属性则会按照title和artist自动从豆瓣api中查找封面图，若值为 false 则不自动查找封面，显示默认封面图片
```
网易云音乐(与歌曲属性用法一样)
```
id: 歌曲/歌单/专辑/艺人的id ,如果是歌曲的话可用 , 分隔歌曲id一次插入多首歌曲
type: 用以判断id的类型，分为4种：song:歌曲,album:专辑,artist:艺人,collect:歌单
```

### 清空歌词，播放列表、封面图片url的缓存

前往插件设置页面点击红色清空缓存按钮即可

## 支持作者
如果你觉得这个项目对您有所帮助，不妨考虑小额支持我一下
[支持作者](http://blog.izgq.net/donate.html)

## LICENSE

MIT © [zgq354](https://github.com/zgq354/)
