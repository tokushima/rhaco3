##rhaco3
__2010-11-11__

Web framework (PHP 5 >= 5.3.0)


## Install

composer.json
```
{
	"require": {
		"tokushima/rhaco3":"0.9.7"
	}
}
```

## get cmdman
```
curl -LO http://git.io/cmdman.phar
```

## setup
```
php cmdman.phar org.rhaco.Dt::setup
```

## entry

index.php
```
<?php
include_once(__DIR__.'/vendor/autoload.php');

\org\rhaco\Flow::out([
	'patterns'=>[
		''=>['action'=>'org.rhaco.Dt']
	]
]);

```





