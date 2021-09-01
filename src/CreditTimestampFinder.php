<?php

namespace Balsama;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use PHPHtmlParser\Dom;

class CreditTimestampFinder
{

    public static function getIssueDom($issueNumber)
    {
        $client = new Client();
        $url = 'https://www.drupal.org/node/' . $issueNumber;
        $requestHeaders = ['User-Agent' => 'creditCounter/v1.0'];
        $request = new Request('GET', $url, $requestHeaders);

        $issueDom = new Dom();
        $issueDom->loadFromUrl($url, null, $client, $request);

        return $issueDom;
    }

    /**
     * Tries to determine when credit was given based on:
     *   1. The date of the last commit comment on the issue.
     *   2. When an issue was marked fixed (this case is used when no commit comment found. E.g. for meeting credits)
     */
    public static function getCreditTimestamp($issueDom)
    {
        // First, look for a commit message.
        $commitMessages = $issueDom->find('.comment.system-message.committed');
        if ($commitMessages->count()) {
            $commitMessagesArray = $commitMessages->toArray();
            $lastCommitMessage = end($commitMessagesArray);
            /* @var $lastCommitMessage \PHPHtmlParser\Dom\Node\HtmlNode */
            $timestamp = strtotime($lastCommitMessage->find('time')->getAttribute('datetime'));
            return $timestamp;
        }

        // If we don't find a commit message, just look for when it was marked fixed. This is the case for meeting issue
        // credits.
        $nodeChanges = $issueDom->find('.nodechanges-new');
        foreach ($nodeChanges as $nodeChange) {
            /* @var $nodeChange \PHPHtmlParser\Dom\Node\HtmlNode */
            if ($nodeChange->innerHtml == '» Fixed') {
                // AFAIK, there is no way to search "upwards" from a known elemennt - hence this mess.
                $timestamp = strtotime(
                    $nodeChange
                        ->getParent()
                        ->getParent()
                        ->getParent()
                        ->getParent()
                        ->getParent()
                        ->getParent()
                        ->getParent()
                        ->getParent()
                        ->find('time')
                        ->getAttribute('datetime')
                );
                return $timestamp;
            }
        }

        return 0;
    }
}
