<?php

namespace Balsama;

use GuzzleHttp\Client;
use PHPHtmlParser\Dom;

class OrgCredits
{

    private $beforeTimestamp;
    private $afterTimestamp;
    private $issues = [];
    private $issueCounts;
    private string $orgString;
    private int $orgId;
    private string $ulSelector = '.view-id-issue_credit .view-content ul li';
    private int $page = 0;

    public function __construct(string $org, $beforeDate = '1 day ago', $afterDate = '8 days ago')
    {
        $this->orgString = $org;
        $this->orgId = $this->getOrgId($org);
        $this->setTimestamps($beforeDate, $afterDate);
        $this->buildCountsArray();
    }

    public function run()
    {
        $orgDomSinglePage = $this->getPageDom();
        $this->parsePageIssues($orgDomSinglePage);
    }

    public function getIssues()
    {
        return $this->issues;
    }

    public function getIssueCountsByDay()
    {
        return Helpers::includeArrayKeysInArray($this->issueCounts);
    }

    private function parsePageIssues($orgDomSinglePage, $noContinue = false)
    {
        $issuesUl = $this->findIssuesUl($orgDomSinglePage);

        foreach ($issuesUl as $issueListItem) {
            $issueNumber = $this->getIssueNumberFromLi($issueListItem);
            $issueDom = CreditTimestampFinder::getIssueDom($issueNumber);
            $creditTimestamp = CreditTimestampFinder::getCreditTimestamp($issueDom);
            if (!$creditTimestamp) {
                $creditTimestamp = $this->getIssueTimestampFromLi($issueListItem);
            }
            $listingTimestamp = $this->getIssueTimestampFromLi($issueListItem);

            if ($this->isTimestampBeforeWindow($listingTimestamp)) {
                // Exit out completely.
                return;
            }
            if ($this->isTimestampBeforeWindow($creditTimestamp)) {
                // Break out of this list item, but don't stop processing as it's possible this issue just has a recent
                // comment on it.
                continue;
            }
            if ($this->isTimestampAfterWindow($creditTimestamp)) {
                // Break out of this list item, see if the next one is inside.
                continue;
            }

            $date = date('Y-m-d', $creditTimestamp);
            $this->issues[$issueNumber] = [
                'issueNumber' => $issueNumber,
                'timestamp' => $creditTimestamp,
                'date' => $date,
            ];

            $this->issueCounts[$date]++;
        }

        if ($noContinue) {
            return;
        }
        $this->increment();
    }

    private function increment()
    {
        $this->page++;
        $orgDomSignglePage = $this->getPageDom();
        $this->parsePageIssues($orgDomSignglePage);
    }

    /**
     * @throws \Exception
     */
    public function getPageDom(): Dom
    {
        $orgDomSinglePage = new Dom();
        $client = new Client();

        echo "Getting page $this->page for $this->orgString \n";
        $url = "https://www.drupal.org/node/$this->orgId/issue-credits/3060?page=$this->page";
        $request = \Balsama\Request::getRequest($url);
        $orgDomSinglePage->loadFromUrl($url, null, $client, $request);

        $innerHtml = $orgDomSinglePage->innerHtml;
        $errorString = 'You have been blocked because we believe you are using automation tools to browse the website.';
        if (strpos($innerHtml, $errorString)  !== false) {
            throw new \Exception('Blocked!');
        }

        return $orgDomSinglePage;
    }

    private function setTimestamps($beforeDate, $afterDate)
    {
        $this->beforeTimestamp = strtotime($beforeDate);
        $this->afterTimestamp = strtotime($afterDate);
    }

    private function getOrgId($orgName)
    {
        $orgs = [
            'acquia' => 1204416,
            'third & grove' => 2373279,
            'opensense' => 2300801,
            'ci&t' => 1530378,
            'previousnext' => 1758226,
            'qed42' => 2149203,
            'lullabot' => 1124040,
            'mediacurrent' => 1125004,
            'acro media' => 1912292,
            'tag1' => 1762646,

        ];
        return $orgs[$orgName];
    }

    public function getIssueNumberFromLi(Dom\Node\HtmlNode $issueListItem)
    {
        $issueLink = $issueListItem->find('.views-field-title .field-content a');
        $issueHrefParts = explode('/', $issueLink->getAttribute('href'));

        return end($issueHrefParts);
    }

    /**
     * Gets the date of a credit from the list page. Note that this date is inaccurate because it's based on the most
     * recent comment on an issue. So we only use this method when `getCreditTimestamp()` fails to find a date and for
     * figuring out when we're at the end of the list.
     */
    private function getIssueTimestampFromLi(Dom\Node\HtmlNode $issueListItem)
    {
        $changed = $issueListItem->find('.views-field-changed .field-content');
        $changedAgo = $changed->find('.placeholder');
        if ($changedAgo->count()) {
            // If the issue was credited < 24 hours ago, the output is slightly different.
            $changedAgoText = $changedAgo->innerHtml();
            $timestamp = strtotime($changedAgoText . ' ago');
        } else {
            $dateText = substr($changed->innerHtml(), 0, -13);
            $timestamp = strtotime($dateText);
        }

        return $timestamp;
    }

    private function isTimestampAfterWindow($timestamp)
    {
        if ($timestamp >= $this->beforeTimestamp) {
            return true;
        }
        return false;
    }
    private function isTimestampBeforeWindow($timestamp)
    {
        if ($timestamp < $this->afterTimestamp) {
            return true;
        }
        return false;
    }

    private function buildCountsArray()
    {
        $start = new \DateTime();
        $start->setTimestamp($this->afterTimestamp);
        $end = new \DateTime();
        $end->setTimestamp($this->beforeTimestamp);
        $interval = new \DateInterval('P1D');
        $period = new \DatePeriod($start, $interval, $end);
        foreach ($period as $date) {
            $this->issueCounts[$date->format('Y-m-d')] = 0;
        }
    }

    public function findIssuesUl($orgDomSinglePage)
    {
        return $orgDomSinglePage->find($this->ulSelector);
    }
}
