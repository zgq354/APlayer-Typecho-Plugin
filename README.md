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

待完善......

### 清空生成的歌词和api获取的封面图片url的缓存

前往插件设置页面点击红色删除按钮即可

## LICENSE

MIT © [zgq354](https://github.com/zgq354/)
