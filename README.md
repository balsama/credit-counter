# Credit Counter
Counts the number of credits an org earned over a given time period.

## Usage
1. Clone this repo
2. run `composer install`
3. See `/scripts` for examples. E.g., run:
  ```bash
  $ php ./scripts/get-orgs-credits.php
  ```

**Note:**  
You'll need the `CREDIT_COUNTER_USER_AGENT` env variable set to prevent the DA's bot detector from blocking these requests.

**More info:**
```php
<?php
$credits = new Balsama\OrgCredits('acquia', '1 july 2021', '30 june 2020');

// Get an array of counts by day, structured like this:
// [date => [date, count], date => [date, count], ...]
$credits->getIssueCountsByDay();

// Get an array of issues structured like this:
// [issue_number => [issueNumber => issue_number, timestamp => timestamp, date => date(Y-M-d)], ...]
$credits->getIssues();

// Write the results of OrgCredits::getIssueCountsByDay into a CSV in the /data directory:
\Balsama\Helpers::csv(['date', 'value'], $credits->getIssueCountsByDay(), 'filename.csv');
```
