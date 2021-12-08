<?php

require './vendor/autoload.php';

use Balsama\Helpers;
use Balsama\OrgCredits;

/**
* @see https://docs.google.com/spreadsheets/d/19rro3gjUCaDvgMXW8be2UksBSnItBP7mi6Ts032yKr8/edit#gid=2989097
*
* @usage
* 1. Set a unique $identifier to be used when naming the output files.
* 2. Set the date range by editing the before and after date strings passed to the OrgCredits constructor.
* 3. Set which projects should be counted using the `toggleCoreOnly` method on OrgCredits.
* CSVs will be generated for each organization in the $orgs array.
*/

$orgs = [
    'acquia',
    'third & grove',
    'opensense',
    'ci&t',
    'previousnext',
    'qed42',
    'lullabot',
    'mediacurrent',
    'acro media',
    'tag1',
];

$identifier = 'octo-2021-to-now--core-only';
$generated = date('Y-m-d G:i:s');
foreach ($orgs as $org) {
    $credits = new OrgCredits($org, '1 day ago', '25 october 2021');
    $credits->toggleCoreOnly(false);
    $credits->run();
    $issueCounts = $credits->getIssueCountsByDay();
    $issues = $credits->getIssues();
    Helpers::csv(
        ['date', $org],
        $issueCounts,
        "$org--counts--$identifier--generated-$generated.csv"
    );
    Helpers::csv(
        ['issue - ' . $org, 'timestamp', 'date'],
        $issues,
        "$org--issues--$identifier--generated-$generated.csv"
    );
    echo "Done with $org\n";
}
