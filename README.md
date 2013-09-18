##rhaco3
__2010-11-11__

PHP framework (PHP 5 >= 5.3.0)

( no composer version: <https://github.com/tokushima/rhaco3_old> )


## Install

### composer install
	curl -s http://getcomposer.org/installer | php

### edit composer.json
	{
    	"require": {
			"tokushima/rhaco3":"master-dev"
    	},
    	"autoload":{
    		"psr-0": {
    	    	"local": "lib/"
		    }
    	}
	}

### rhaco3 install
	php composer.phar install


### edit bootstrap.php
	<?php
	include_once('vendor/autoload.php');

### edit \_\_settings\_\_.php
	<?php
	define('APPMODE','local');
	define('COMMONDIR',__DIR__.'/commons');

### edit index.php
	<?php
	include_once('bootstrap.php');
	
	$flow = new \org\rhaco\Flow();
	$flow->out(array(
		'patterns'=>array(
			'dev'=>array('action'=>'org.rhaco.Dt')
		)
	));

### kate install
	curl -LO https://raw.github.com/tokushima/kate/master/kate.php

### create .htaccess
	php kate.php org.rhaco.Dt::htaccess

### view
	http://〜/〜/dev




## はじめに
MAMP( http://www.mamp.info/ )上で確認し PDT( http://www.eclipse.org/pdt/downloads/ )で開発が行われています。

		Apacheのポート: 80
		MySQLのポート: 3306
		PHPのバージョン: 5.4.10
		Apache Docuemnt Root: /Users/tokushima/Documents/workspace
	
アプリケーションはそれぞれドキュメントルート以下にそれぞれアプリケーション毎のフォルダをつくれるように最適化されています。 そのため、バーチャルホスト等の設定は不要です。もちろんバーチャルホストでの利用も可能ですので環境にあわせて設定してください。

エントリポイントへはApacheのモジュールmod_rewriteを利用し.htaccessで行っています。そのためのコマンドも用意されています(kate利用)。

標準のファイル構成は通常のMAMPでの利用を前提としている為、ファイルやフォルダの書き込み権限はある前提となっています。 通常のMAMP以外での利用の場合は適宜パーミッションの設定を行ってください。

xampp( http://www.apachefriends.org/ ) や nginx + php-fpm 、OSXに入っているApache等でも動作しますが個々の設定によるので、それぞれ環境にあわせて適宜読み替えてください。


## 開発環境
Eclispe: http://www.eclipse.org

### (Eclipse) - Help - Install new Software
PDT: http://download.eclipse.org/tools/pdt/updates/release
( http://www.eclipse.org/pdt/downloads/ ) 

EGIT: http://download.eclipse.org/egit/updates-3.0 
( http://marketplace.eclipse.org/content/egit-git-team-provider#.UdTS1hbuSJQ )

Subclipse: http://subclipse.tigris.org/update_1.8.x
( http://marketplace.eclipse.org/node/979#.UdTUCRbuSJQ )

