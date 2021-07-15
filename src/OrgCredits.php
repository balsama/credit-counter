<?php

namespace Balsama;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use PHPHtmlParser\Dom;

class OrgCredits
{

    private Dom $orgDomSinglePage;
    private $beforeTimestamp;
    private $afterTimestamp;
    private $issues;
    private $issueCounts;
    private $orgId;
    private string $ulSelector = '.view-id-issue_credit .view-content ul li';
    private int $page = 0;

    public function __construct($org, $beforeDate = 'now', $afterDate = '2 days ago')
    {
        $this->orgId = $this->getOrgId($org);
        $this->setTimestamps($beforeDate, $afterDate);
        $this->buildCountsArray();
        $this->getPageDom(false);
        $this->parsePageIssues();
    }

    public function getIssues()
    {
        return $this->issues;
    }

    public function getIssueCountsByDay()
    {
        return Helpers::includeArrayKeysInArray($this->issueCounts);
    }

    private function parsePageIssues()
    {
        $issuesUl = $this->orgDomSinglePage->find($this->ulSelector);

        foreach ($issuesUl as $issueListItem) {
            $issueNumber = $this->getIssueNumberFromLi($issueListItem);
            $creditTimestamp = $this->getCreditTimestamp($issueNumber, false);
            if (!$creditTimestamp) {
                $creditTimestamp = $this->getIssueTimestampFromLi($issueListItem);
            }
            $listingTimestamp =$this->getIssueTimestampFromLi($issueListItem);

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

        $this->page++;
        $this->getPageDom();
        $this->parsePageIssues();
    }

    public function getPageDom($mock = false)
    {
        $this->orgDomSinglePage = new Dom();
        if ($mock) {
            $this->orgDomSinglePage->loadFromFile('./tests/data/example-response.html');
        }
        else {
            $client = new Client();
            echo "Getting page $this->page \n";
            $url = "https://www.drupal.org/node/$this->orgId/issue-credits/3060?page=$this->page";
            // The `creditCounter/v1.0` user agent is allow-listed by the DA so we don't get blocked by their bot
            // detector.
            $requestHeaders = ['User-Agent' => 'creditCounter/v1.0'];
            $request = new Request('GET', $url, $requestHeaders);
            $this->orgDomSinglePage->loadFromUrl($url, null, $client, $request);
        }

        $innerHtml = $this->orgDomSinglePage->innerHtml;
        if (strpos($innerHtml, 'You have been blocked because we believe you are using automation tools to browse the website.')  !== false) {
            throw new \Exception('Blocked!');
        }
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

    private function getIssueNumberFromLi(Dom\Node\HtmlNode $issueListItem)
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
        }
        else {
            $dateText = substr($changed->innerHtml(), 0, -13);
            $timestamp = strtotime($dateText);
        }

        return $timestamp;
    }

    /**
     * Tries to determine when credit was given based on:
     *   1. The date of the last commit comment on the issue
     *   2. When an issue was marked fixed (this case is used when no commit comment found. E.g. for meeting credits)
     */
    private function getCreditTimestamp($issueNumber, $mock = false)
    {
        $client = new Client();
        $url = 'https://www.drupal.org/node/' . $issueNumber;
        $requestHeaders = ['User-Agent' => 'creditCounter/v1.0'];
        $request = new Request('GET', $url, $requestHeaders);

        $issue = new Dom();
        if ($mock) {
            $issue->loadFromFile('./tests/data/example-issue-commit.html');
        }
        else {
            $issue->loadFromUrl($url, null, $client, $request);
        }

        // First, look for a commit message.
        $commitMessages = $issue->find('.comment.system-message.committed');
        if ($commitMessages->count()) {
            $commitMessagesArray = $commitMessages->toArray();
            $lastCommitMessage = end($commitMessagesArray);
            /* @var $lastCommitMessage \PHPHtmlParser\Dom\Node\HtmlNode */
            $timestamp = strtotime($lastCommitMessage->find('time')->getAttribute('datetime'));
            return $timestamp;
        }

        // If we don't find a commit message, just look for when it was marked fixed.
        $nodeChanges = $issue->find('.nodechanges-new');
        foreach ($nodeChanges as $nodeChange) {
            /* @var $nodeChange \PHPHtmlParser\Dom\Node\HtmlNode */
            if ($nodeChange->innerHtml == '» Fixed') {
                $timestamp = strtotime($nodeChange->getParent()->getParent()->getParent()->getParent()->getParent()->getParent()->getParent()->getParent()->find('time')->getAttribute('datetime'));
                return $timestamp;
            }
        }

        return 0;
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
        $period = new \DatePeriod(
            $start,
            new \DateInterval('P1D'),
            $end
        );
        foreach ($period as $date) {
            $this->issueCounts[$date->format('Y-m-d')] = 0;
        }

    }

}