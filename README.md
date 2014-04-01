##rhaco3
__2010-11-11__

Web framework (PHP 5 >= 5.3.0)

( no composer version: <https://github.com/tokushima/rhaco3_old> )



## Install

### composer install
	$ curl -s http://getcomposer.org/installer | php

### edit composer.json
	{
    	"require": {
			"tokushima/rhaco3":"master-dev"
    	}
	}

### rhaco install
	$ php composer.phar install

### brev install
	$ curl -LO http://raw.github.com/tokushima/brev/master/brev.php

### create start file
	$ php brev.php org.rhaco.Dt::setup --create

