# APlayer-Typecho-Plugin
A typecho plugin for the beautiful html5 music player https://github.com/DIYgod/APlayer 

[Demo](http://blog.izgq.net/archives/456/)

## Introduction
1. 通过简短的代码在文章或页面中插入漂亮的Html5播放器
2. 自动解析lrc链接，可根据歌曲名和歌手名自动查找封面并生成缓存
3. 与APlayer保持同步更新

## Install
安装前请确保插件中的cache目录可写（保存缓存用，否则会让博客加载缓慢）

主机需支持curl扩展，否则将可能不能自动查找封面或从https的url中获取歌词(file_get_contents在不支持openssl的主机中不能打开https链接)

Download ZIP, 解压，将其中的 APlayer 文件夹放入你博客中的 /usr/plugins 目录，在后台启用即可

## Usage

基本格式：

单曲播放器:
```
[mp3 属性1=值1|属性2=值2|属性3=值3]mp3文件地址[/mp3]
```
播放列表:

```
[music 属性1=值1|属性2=值2|属性3=值3]
[mp3 属性1=值1|属性2=值2|属性3=值3]mp3文件地址[/mp3]
[mp3 属性1=值1|属性2=值2|属性3=值3]mp3文件地址[/mp3]
[mp3 属性1=值1|属性2=值2|属性3=值3]mp3文件地址[/mp3]
[mp3 属性1=值1|属性2=值2|属性3=值3]mp3文件地址[/mp3]
......
[/music]
```
可以设置的属性：

autoplay(是否一载入页面就自动播放),theme(主题颜色),showlrc(是否显示歌词，本属性只有播放列表模式才起作用)

在文章编辑页面中，在要插入单曲播放器的部分输入以下代码：
```
[mp3 title=标题|artist=艺术家]mp3文件地址[/mp3]
```
如果需要歌词，有两种方式：

1. 直接粘贴歌词的链接
```
[mp3 lrc=lrc文件地址|title=标题|artist=艺术家]mp3文件地址[/mp3]
```
例如：
```
[mp3 lrc=http://music.baidu.com/data2/lrc/114769747/114769747.lrc|title=Boulevard Of Broken Dreams|artist=Green Day]http://m2.music.126.net/slObLwCVixCI89eQTERv3A==/1994514092802879.mp3[/mp3]
```

2 . 手动粘贴lrc歌词
```
[mp3 title=标题|artist=艺术家]mp3文件地址[lrc]歌词文本[/lrc][/mp3]
```
例如：
```
[mp3 title=Boulevard Of Broken Dreams|artist=Green Day]http://m2.music.126.net/slObLwCVixCI89eQTERv3A==/1994514092802879.mp3[lrc][ti:boulevard of broken dreams]
[ar:green day]
[al:260923]
[offset:0]

[00:01.44]Green Day-boulevard of broken dreams
[00:11.47]I walk a lonely road
[00:14.15]The only one that I have ever known
[00:17.96]Don't know were it goes
[00:20.12]But it's home to me and I walk alone
[00:26.52]
[00:29.04]I walk this empty street
[00:31.53]On the Boulevard of broken dreams
[00:35.08]Were the city sleeps
[00:37.22]And I'm the only one and I walk alone
[00:40.28]
[00:43.45]I walk alone I walk alone
[00:49.13]I walk alone and I walk a-
[00:52.33]My shadows the only one that walks beside me
[00:58.09]My shallow hearts the only thing that's beating
[01:03.90]Sometimes I wish someone out there will find me
[01:09.55]'Till then I'll walk alone
[01:15.65]Ah ah
[01:26.44]I'm walking down the line
[01:29.04]That divides me somewhere in my mind
[01:32.73]On the border line of the edge
[01:35.97]And where I walk alone
[01:41.27]
[01:44.15]Read between the lines
[01:46.27]What's fucked up and everything's alright
[01:49.87]Check my vital signs to know I'm still alive
[01:53.32]And I walk alone
[01:58.70]I walk alone I walk alone
[02:00.65]I walk alone and I walk a-
[02:07.07]My shadows the only one that walks beside me
[02:12.84]My shallow hearts the only thing that's beating
[02:18.55]Sometimes I wish someone out there will find me
[02:24.25]'Till then I'll walk alone
[02:30.13]Ah ah
[02:38.76]I walk alone and I walk a-
[03:07.22]I walk this empty street
[03:09.64]On the Boulevard of broken dreams
[03:13.21]Were the city sleeps
[03:15.39]And I'm the only one and I walk a-
[03:19.05]My shadows the only one that walks beside me
[03:24.70]My shallow hearts the only thing that's beating
[03:30.49]Sometimes I wish someone out there will find me
[03:36.22]'Till then I'll walk alone
[/lrc][/mp3]
```
若需要自定义封面图片的话：
```
[mp3 lrc=lrc文件地址|title=标题|artist=艺术家|cover=封面图片链接]mp3文件地址[/mp3]
```
例如
```
[mp3 title=Boulevard Of Broken Dreams|artist=Green Day|lrc=http://music.baidu.com/data2/lrc/114769747/114769747.lrc|cover=https://img3.doubanio.com/spic/s27047281.jpg]http://m2.music.126.net/slObLwCVixCI89eQTERv3A==/1994514092802879.mp3[/mp3]
```
不想要封面？
```
[mp3 lrc=lrc文件地址|title=标题|artist=艺术家|cover=false]mp3文件地址[/mp3]
```
这时候出现的就是默认的封面图片了~~~

插入列表播放器：

只要把单曲的[mp3][/mp3]包裹在[music][/music]标签下就可以形成列表了

example:
```
[music autoplay=false|showlrc=1|theme=#e6d0b2]
[mp3 lrc=http://music.baidu.com/data2/lrc/114769747/114769747.lrc|title=Boulevard Of Broken Dreams|artist=Green Day]http://m2.music.126.net/slObLwCVixCI89eQTERv3A==/1994514092802879.mp3[/mp3]
[mp3 title=Seven|artist=Tobu]http://m2.music.126.net/r3EAfh3jsRDffsSAcbR6eg==/6636652185818738.mp3[/mp3]
[mp3 title=Animals|artist=Maroon 5]http://m2.music.126.net/NYq7If0Alf1wH0b81vrEpw==/6668538022514769.mp3[lrc]
[00:00.570]Baby I'm preying on you tonight
[00:03.140]Hunt you down eat you alive
[00:05.560]Just like animals
[00:06.980]Animals
[00:08.500]Like animals
[00:10.800]Maybe you think that you can hide
[00:13.080]I can smell your scent for miles
[00:15.590]Just like animals
[00:17.250]Animals
[00:18.520]Like animals
[00:20.820]Baby I'm
[00:22.110]So what you trying to do to me
[00:24.400]It's like we can't stop we're enemies
[00:26.680]But we get along when I'm inside you
[00:32.300]You're like a drug that's killing me
[00:34.380]I cut you out entirely
[00:36.880]But I get so high when I'm inside you
[00:40.680]Yeah you can start over you can run free
[00:43.640]You can find other fish in the sea
[00:45.970]You can pretend it's meant to be
[00:48.550]But you can't stay away from me
[/lrc][/mp3]
[/music]
```

### 清空生成的歌词和api获取的封面图片url的缓存

前往插件设置页面点击红色删除按钮即可

## LICENSE

MIT © [zgq354](https://github.com/zgq354/)
