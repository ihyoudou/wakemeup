# Wakemeup

Simple application that i wrote to learn a bit more about MongoDB. It is fully compatible with PHP 8.0 and uses Slim Framework 4. Definitely a WIP.   

The idea of the application is simple - it has to visit saved URLs every X time in order to wake up the application hosted on services like repl.it or Heroku. On the other note - please don't abuse free service - nowadays you can get small VPS for less than $10/year, or just pay for their premium account.

## Requriements
* PHP 8.0 webserver (with xml, mongodb, curl modules)
* MongoDB database
* access to cron

## How to install
```
composer install
```
Rename .env.example to .env and enter proper values for database server. Also you can set own user agent, referer and timeout for curl. Change your webserver settings to use public/index.php for all requests, more information you can find in [slim framework docs](https://www.slimframework.com/docs/v4/start/web-servers.html)

To call websites from list you need to add cronjob
```
*/25 * * * * php8.0 /var/www/wakemeup/cron.php > /dev/null 2>&1
```
Change the path to a real one. This cronjob above will be called in every 25 minutes.

## Make modifications
To change homepage, you can edit the templates/index.html file and flush twig cache.
