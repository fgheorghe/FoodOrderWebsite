Food Order Website v0.1.

Do not forget to run (from the php-app folder), when deploying:
php ../composer.phar update

NOTE: If you run this application on FreeBSD, add the following to apache config, file:
/usr/local/etc/apache24/httpd.conf

At the end of file:
Include /usr/local/www/apache24/data/etc/apache2/sites-enabled/site

Change DocumentRoot directive:
DocumentRoot "/usr/local/www/apache24/data/php-app/web"
<Directory "/usr/local/www/apache24/data/php-app/web">

And enable:
LoadModule rewrite_module libexec/apache24/mod_rewrite.so

Configuration for parameters.yml:
    foapi_services_root_url: http://admin.yourapi/api/
    foapi_image_store_url: http://admin.yourapi/api/image/
