mamp bin/packaging.php ./lib/ ../rhaco_org/repository/3/lib/
mamp bin/gen.php
mamp bin/rhaco3.php -org.rhaco.Dt export_entry -block content -extends ../../index.html -map_url rhaco3_document -prefix document/libs/ -out ../rhaco_org/resources/templates/rhaco3/document/libs
mamp testman.php
