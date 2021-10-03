# Wakemeup

Simple application that i wrote to learn a bit more about MongoDB. It is fully compatible with PHP 8.0 and uses Slim Framework 4.

## Requriements
* PHP 8.0 webserver 
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
