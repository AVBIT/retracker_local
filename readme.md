## RETRACKER.LOCAL
Retracker - BitTorrent technology to optimize the exchange (bittorrent) traffic inside the local network. 
Retracker allows a direct connection by protocol bittorrent between subscribers of the same internet service provider (or several ISP have an agreement on the exchange of local traffic).

#### THE BEER-WARE LICENSE:
> This project is licensed under the "THE BEER-WARE LICENSE":
> As long as you retain this notice you can do whatever you want with this stuff.
> If we meet some day, and you think this stuff is worth it, you can buy me a beer in return.

##
#### INSTALL:
- Create MySQL database 'retracker' and create tables (sql/schema.sql);
```
# mysql -uroot -p your_password
# CREATE DATABASE `retracker` CHARACTER SET cp1251 COLLATE cp1251_general_ci;
# use retracker;
# \. /path/to/sql/schema.sql
```

- Copy the files to the web server directory (Use Git or checkout with SVN using the web URL: https://github.com/AVBIT/retracker_local.git). Examples:
```
# sudo git clone https://github.com/AVBIT/retracker_local.git/trunk /usr/www/retracker_local
```
or
```
# sudo svn co https://github.com/AVBIT/retracker_local.git/trunk /usr/www/retracker_local
```
- Copy 'app/config.sample.php' to 'app/config.prod.php';
- Configure the connection to the database (app/config.prod.php); 
- Give write permissions for the web server to directories (these directories are not directly accessible via the web):
  /usr/www/retracker_local/var/cache; 
  /usr/www/retracker_local/var/log; 
  /usr/www/retracker_local/var/tmp;
- Configure a virtual host of web server to the directory "web"; 
- Create DNS name "retracker.local" (subscribers will be use URL: http://retracker.local/announce).
- Create cron job for run garbage collector. (add line in crontab file, example):
```
*/5    *       *       *       *       root    cd /usr/www/retracker_local/app/bin/ && php cron.php > /dev/null 2>&1
```
- Install 'python' and 'libtorrent-rasterbar-python' (it should install the packages:  /usr/ports/lang/python, /usr/ports/net-p2p/libtorrent, /usr/ports/net-p2p/libtorrent-rasterbar, /usr/ports/net-p2p/libtorrent-rasterbar-python). It is not necessary, but desirable, because in practice many network announcements do not have the name and size and they will not be displayed on the web site. (Example for FreeBSD):
```
# cd /usr/ports/lang/python 
# make install clean
...
# cd /usr/ports/net-p2p/libtorrent-rasterbar-python
# make install clean
```


#### UPDATE:
- Update all files. Examples:
```
# cd /usr/www/retracker_local
# sudo git pull
```
or
```
# cd /usr/www/retracker_local
# sudo svn up
```
- Update dependencies. Examples:
```
# cd /usr/www/retracker_local
# composer update
# composer make
```
- When changes affecting the structure of the database, you may need to re-create the database tables. (sql/schema.sql);
```
# mysql -uroot -p your_password
# use retracker;
# \. /path/to/sql/schema.sql
```


##
#### Release notes
- **05.09.2018** - Changed engine from MyISAM to InnoDB for the tables `announce_unresolved` and `history`. This allows you to run mysqldump using the --single-transaction option, because then it dumps the consistent state of the database at the time when START TRANSACTION was issued without blocking any applications. To modify existing tables without losing data, you must execute SQL queries: 
```
# ALTER TABLE announce_unresolved ENGINE=InnoDB;
# ALTER TABLE history ENGINE=InnoDB;
```
- **06.09.2017** - PHP 7.1.x and Nginx ready. Fix error in history.class.php
- **21.06.2017** - Code refactoring.

It may be important - the names of some files are changed: 
rename cron_job.php to cron.php; 
rename config.inc.php to config.prod.php; 
rename config.local.php to config.dev.php; 

Updated version of TWIG (^1.30 to ^2.4).

The directory 'vendor' is excluded from the code (use the 'composer update' command to create it)

The 'composer make' command (script app/bin/make.php) has been added, use it to copy the "twbs/bootstrap" files from the VENDOR to the public WEB/ASSETS directory and to clear the cache.

- **27.10.2016** - test new sql-schema, announce and scrape actions.
- **26.10.2016** - bug fix (MySQL [Err] 1366 - Incorrect string value: '\xEF\xBF\xBD\xEF\xBF\xBD...' for column 'info_hash' at row 1).
- **18.10.2016** - change application structure (renamed some directories and files, usage autoload classes, etc)
- **02.03.2016** - application happy birthday :-) . 
Minimum functionality (processing only 'announce' action).

##
##### Full code: https://github.com/AVBIT/retracker_local