# APlayer-Typecho-Plugin
A typecho plugin for the beautiful html5 music player https://github.com/DIYgod/APlayer 

[Demo](http://blog.izgq.net/archives/456/)

## Introduction
1. 通过简短的代码在文章或页面中插入漂亮的Html5播放器
2. 自动解析lrc链接，可根据歌曲名和歌手名自动查找封面并生成缓存
3. 与APlayer保持同步更新

## Install
安装前请确保插件中的cache目录可写（保存缓存用，否则会让博客加载缓慢）
主机需支持curl扩展，否则将不能自动查找封面和从url中获取歌词

Download ZIP, 解压，将其中的 APlayer 文件夹放入你博客中的 /usr/plugins 目录，在后台启用即可

## Usage
在文章编辑页面中，在要插入播放器的部分输入以下代码：
```
[mp3]mp3文件地址|title=标题|artist=艺术家[/mp3]
```
如果需要歌词，有两种方式：

1. 直接粘贴歌词的链接
```
[mp3]mp3文件地址|lrc=lrc文件地址|title=标题|artist=艺术家[/mp3]
```
例如：
```
[mp3]http://m2.music.126.net/slObLwCVixCI89eQTERv3A==/1994514092802879.mp3|lrc=http://music.baidu.com/data2/lrc/114769747/114769747.lrc|title=Boulevard Of Broken Dreams|artist=Green Day[/mp3]
```

2 . 手动粘贴lrc歌词
```
[mp3]mp3文件地址|title=标题|artist=艺术家[lrc]歌词文本[/lrc][/mp3]
```
例如：
```
[mp3]http://m2.music.126.net/slObLwCVixCI89eQTERv3A==/1994514092802879.mp3|title=Boulevard Of Broken Dreams|artist=Green Day[lrc][ti:boulevard of broken dreams]
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
[mp3]mp3文件地址|lrc=lrc文件地址|title=标题|artist=艺术家|cover=封面图片链接[/mp3]
```
例如
```
[mp3]http://m2.music.126.net/slObLwCVixCI89eQTERv3A==/1994514092802879.mp3|title=Boulevard Of Broken Dreams|artist=Green Day|lrc=http://music.baidu.com/data2/lrc/114769747/114769747.lrc|cover=https://img3.doubanio.com/spic/s27047281.jpg[/mp3]
```
不想要封面？
```
[mp3]mp3文件地址|lrc=lrc文件地址|title=标题|artist=艺术家|cover=false[/mp3]
```
这时候出现的就是默认的封面图片了~~~


清空生成的歌词和api获取的图片链接的缓存

前往插件设置页面点击红色按钮即可

## LICENSE

MIT © [zgq354](https://github.com/zgq354/)
