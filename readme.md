## RETRACKER.LOCAL
Retracker - BitTorrent technology to optimize the exchange (bittorrent) traffic inside the local network. 
Retracker allows a direct connection by protocol bittorrent between subscribers of the same internet service provider (or several ISP have an agreement on the exchange of local traffic).

#### THE BEER-WARE LICENSE:
> This project is licensed under the "THE BEER-WARE LICENSE":
> As long as you retain this notice you can do whatever you want with this stuff.
> If we meet some day, and you think this stuff is worth it, you can buy me a beer in return.

##
#### INSTALL:
1. Create MySQL database 'retracker' and create tables (sql/schema.sql);
```
# mysql -uroot -p your_password
# CREATE DATABASE `retracker` CHARACTER SET utf8 COLLATE utf8_general_ci;
# use retracker;
# \. /path/to/sql/schema.sql
```
2. Copy the files to the web server directory (Use Git or checkout with SVN using the web URL: https://github.com/AVBIT/retracker_local.git). Example for SVN:
```
# sudo svn co https://github.com/AVBIT/retracker_local.git/trunk /usr/www/retracker_local
```
3. Copy 'app/config.sample.php' to 'app/config.inc.php';
4. Configure the connection to the database (app/config.inc.php); 
5. Configure a virtual host of web server to the directory "web"; 
6. Create DNS name "retracker.local" (subscribers will be use URL: http://retracker.local/announce).
7. Create cron job for run garbage collector. (add line in crontab file, example):
```
*/5    *       *       *       *       root    cd /usr/www/retracker_local/app/ && php cron_job.php > /dev/null 2>&1
```


#### UPDATE:
1. Update all files. Example for SVN:
```
# cd /usr/www/retracker_local
# sudo svn up
```
2. When changes affecting the structure of the database, you may need to re-create the database tables. (sql/schema.sql);
```
# mysql -uroot -p your_password
# use retracker;
# \. /path/to/sql/schema.sql
```


##
#### Release notes
- **27.10.2016** - test new sql-schema, announce and scrape actions.
- **26.10.2016** - bug fix (MySQL [Err] 1366 - Incorrect string value: '\xEF\xBF\xBD\xEF\xBF\xBD...' for column 'info_hash' at row 1).
- **18.10.2016** - change application structure (renamed some directories and files, usage autoload classes, etc)
- **02.03.2016** - application happy birthday :-) . 
Minimum functionality (processing only 'announce' action).

##
##### Full code: https://github.com/AVBIT/retracker_local