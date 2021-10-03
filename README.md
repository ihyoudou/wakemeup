# Wakemeup

Simple application that i wrote to learn a bit more about MongoDB. It is fully compatible with PHP 8.0 and uses Slim Framework 4. Definitely a WIP.   

The idea of the application is simple - it has to visit saved URLs every X time in order to wake up the application hosted on services like repl.it or Heroku. On the other note - please don't abuse free service - nowadays you can get small VPS for less than $10/year, or just pay for they premium account.

## Requriements
* PHP 8.0 webserver (with xml, mongodb, curl modules)
* MongoDB database
* access to cron

## How to install
```
composer install
```
rename .env.example to .env and edit settings, change your webserver settings to use public/index.php for all requests, more information you can find in [slim framework docs](https://www.slimframework.com/docs/v4/start/web-servers.html)

add to crontab
```
*/25 * * * * curl -s -o /dev/null https://yoururl.zzz/api/v1/cron?secret=your_secret > /dev/null 2>&1
```
