To run this...

Download the ibkr client portal.

Change conf.yaml to use port 5050 instead of the default (5000, i think).

Run the ibkr client portal:

> bin/run.sh root/conf.yaml

Find the download here: https://www.interactivebrokers.com/campus/ibkr-api-page/cpapi-v1/#cpgw

Run php docker instance using the ./launch script

## MySQL cache

This project follows the `../calakes` database pattern:

1. Copy `localconfig.example.php` to `localconfig.php`.
2. Fill in the database host, username, password, and database name.
3. Review pending schema changes in `maint/golive_plan.sql`.
4. Apply database schema changes with `php maint/deploy.php`. This does not
   deploy PHP code; it only applies `golive_plan.sql`, clears it, and refreshes
   `maint/schema_dump_latest.sql`.

`localconfig.php` is ignored by git. When configured, `www/marketdata.php`
will cache historical market data responses in MySQL. Use `force=true` to
bypass cache for a request, or `cacheTtl=SECONDS` to override the default
15-minute freshness window.
