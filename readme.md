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
1. Copy the files to the web server directory (Use Git or checkout with SVN using the web URL: https://github.com/AVBIT/retracker_local.git);
1. Copy 'app/config.sample.php' to 'app/config.inc.php';
1. Configure the connection to the database (app/config.inc.php); 
1. Configure a virtual host of web server to the directory "web"; 
1. Create DNS name "retracker.local" (subscribers will be use URL: http://retracker.local/announce).

#### UPDATE:
1. Update all files.
1. When changes affecting the structure of the database, you may need to re-create the database tables. (sql/schema.sql);
```
# mysql -uroot -p your_password
# use retracker;
# \. /path/to/sql/schema.sql
```


##
#### Release notes
>Legend:
>+ '|?' - It may be implemented in the next versions
>+ '|!' - IMPORTANT (can lead to errors and incompatibilities)
>+ '|+' - New features
>+ '|-' - Bug fixes
>+ '|^' - Known bug
>+ '|*' - For the end user does not matter.

## -
+ |? statistics on the use (GUI)
+ |? implementation 'scrape' action
## -
+ |! - **18.10.2016** - change application structure (renamed some directories and files, usage autoload classes, etc)
+ |* - **02.03.2016** - application happy birthday :-) . 
Minimum functionality (processing only 'announce' action).

##
##### Full code: https://github.com/AVBIT/retracker_local