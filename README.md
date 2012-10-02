##rhaco3

@see [rhaco.org](http://rhaco.org)


### quick start

    mkdir newapp
    cd newapp
    curl -O http://rhaco.org/rhaco3.php
    php rhaco3.php -org.rhaco.Dt new -htaccess
    php rhaco3.php -import


<http://localhost/newapp/>


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
 
 
 
 
 
## phpunit
    /Applications/MAMP/bin/php/php5.4.4/bin/pear install pear.phpunit.de/PHPUnit

/Applications/MAMP/bin/php/php5.4.4/conf/php.ini

    [xdebug]
    zend_extension="/Applications/MAMP/bin/php/php5.4.4/lib/php/extensions/no-debug-non-zts-20100525/xdebug.so"


ドキュメント: <http://www.phpunit.de/manual/3.7/ja/index.html>

実行:

    /Applications/MAMP/bin/php/php5.4.4/bin/phpunit --colors --coverage-html ./unittest/report unittest

phpinit.xml

    <?xml version="1.0" encoding="UTF-8"?>
    <phpunit colors="true" bootstrap="bootstrap.php">
      <testsuite name="Sample">
        <directory>test</directory>
      </testsuite>
    </phpunit>


## composer
<http://packagist.org>

<http://getcomposer.org>



    curl -s https://getcomposer.org/installer | mamp
    sudo mv composer.phar /usr/local/bin/composer


composer.json

    {
        "require": {
             "monolog/monolog": "1.0.*"
        }
    }

install

    mamp composer.phar install


