# Project Rules

IBKR App is a local PHP app for viewing Interactive Brokers account and market data through the IBKR Client Portal Gateway API.

- The `www` folder is where all web accessible files live. Only endpoints and static files should exist in the web accessible folder.
- Use `localconfig.php` in the project root for looking up database credentials.
- Use `maint/schema_dump_latest.sql` to determine database structure. Do not update this file manually after schema migrations because the database deploy script does that for us after applying the migrations to the database.
- Pending database schema changes (not yet applied to the production database) are written to `maint/golive_plan.sql`. When making schema changes, check if the table/column in question already exists by examining `schema_dump_latest.sql`. If the table or column already exists, write the necessary alter/change statements to `golive_plan.sql`. If the table or column doesn't exist, then write SQL to create it in `golive_plan.sql`.
- This project does not deploy PHP code to a remote server. `maint/deploy.php` applies database schema changes only, then clears `golive_plan.sql` and refreshes `schema_dump_latest.sql`.
- When making changes to static CSS or JavaScript files, make sure to increment the cachebuster in `localconfig.php`.
