# mamp packager.php ../rhaco2/libs/ ../rhaco_org/repository/2/lib/
mamp packager.php ./libs/ ../rhaco_org/repository/3/lib/

mamp rhaco3.php -org.rhaco.flow.parts.Developer export_entry -block content -extends ../../index.html -map_url rhaco3_document -prefix document/libs/ -out ../rhaco_org/resources/templates/rhaco3/document/libs
