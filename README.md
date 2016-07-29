# Server-Monitoring-for-fivereborn

### Setup
 - Upload api and statistic folder to your webserver
 - Apply database structure from database folder to your database
 - Configure MySQL connection in statistics/API.php file (lines 59-62)
 - Create a cronjob that calls statistics/cron_job.php every minute once

Let it run for 24 hours to collect serverdata.

### Usage
``` 
Get playerlist in JSON format:
http://YOUR.DOMAIN/.../api/playerlist.php?ip=xxx.xxx.xxx.xxx:xxxxxx
```
``` 
Get player and ping graph of last 24 hours 
http://YOUR.DOMAIN/.../api/monitor_graphic.php?ip=xxx.xxx.xxx.xxx:xxxxxx
```

> This project is not finnished so some features are not complete or just added for future.
