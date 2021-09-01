<?php

require './vendor/autoload.php';

use Balsama\Helpers;
use Balsama\OrgCredits;

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

$generated = date('Y-m-d G:i:s');
foreach ($orgs as $org) {
    $credits = new OrgCredits($org, '1 day ago', '9 days ago');
    $credits->run();
    $issueCounts = $credits->getIssueCountsByDay();
    $issues = $credits->getIssues();
    Helpers::csv(
        ['date', $org],
        $issueCounts,
        "$org--counts--last-7-days--generated-$generated.csv"
    );
    Helpers::csv(
        ['issue - ' . $org, 'timestamp', 'date'],
        $issues,
        "$org--issues--last-7-days--generated-$generated.csv"
    );
    echo "Done with $org\n";
}
