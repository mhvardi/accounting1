# Cron Jobs

Use the PHP CLI to run recurring maintenance tasks. Examples below assume the project root is `/home/vardi/domains/vardi.ir/public_html/accounting` and PHP is available on the PATH.

## Server connectivity checks
Add a cron entry (for example, every 10 minutes) to re-validate DirectAdmin server connectivity and store the latest status in the `servers` table:

```cron
*/10 * * * * php /home/vardi/domains/vardi.ir/public_html/accounting/scripts/cron_check_servers.php >> /home/vardi/domains/vardi.ir/public_html/accounting/storage/logs/cron_check_servers.log 2>&1
```

The script uses `App\\Service\\ServerHealthService` to probe every server and writes the result into `servers.last_check_status`, `servers.last_check_message`, and `servers.last_checked_at`.

## Database backup (optional)
Schedule nightly backups of the new schema and data (adjust credentials and destination):

```cron
0 3 * * * mysqldump -uUSER -pPASSWORD DB_NAME > /home/vardi/domains/vardi.ir/public_html/accounting/storage/backups/accounting_$(date +\%F).sql
```

Keep the backup directory readable only by trusted users.
