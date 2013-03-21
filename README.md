##rhaco3

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
    	    	"site": "lib/"
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

### edit index.php
	<?php
	include_once('bootstrap.php');
	
	$flow = new \org\rhaco\Flow();
	$flow->out(array(
		'patterns'=>array(
			'dev'=>array('action'=>'org.rhaco.Dt')
		)
	));

### cmdman install
	curl -O https://raw.github.com/tokushima/cmdman/master/cmdman.php

### create .htaccess
	php cmdman.php org.rhaco.Dt::htaccess

### view
	http://〜/〜/dev



## IDE
 <http://www.eclipse.org/downloads/download.php?file=/eclipse/downloads/drops4/R-4.2-201206081400/eclipse-SDK-4.2-macosx-cocoa-x86_64.tar.gz>

### Install new Software
 <http://download.eclipse.org/releases/juno>
 
     Collaboration - Eclipse EGit
     Web,XML,Java EE and OSGi Enterprise Development - PHP Development Tools
 
<http://subclipse.tigris.org/update_1.8.x>


    Subclipse - Subclipse (Required)
              - Subversion Client Adapter (Required) 
    
    SVNKit - JNA Library
           - SVNKit Client Adapter (Not required)
           - SVNKit Library
 	