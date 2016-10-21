Retreker - BitTorrent technology to optimize the exchange (bittorrent) traffic inside the local network. 
Retreker allows a direct connection by protocol bittorrent between subscribers of the same operator (or several operators have an agreement on the exchange of local traffic).

USAGE:
1) Create MySQL database (sql/schema.sql);
2) Copy 'app/config.sample.php' to 'app/config.inc.php'
3) Configure the connection to the database (app/config.inc.php); 
4) Configure a virtual host of web server to the directory "web"; 
5) Create DNS name "retracker.local" (subscribers will be use URL: http://retracker.local/announce).