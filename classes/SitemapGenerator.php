<?php


/**
 * Created by PhpStorm.
 * User: Aktug
 * Date: 6.05.2017
 * Time: 13:25
 */
require '.\promises\Printable.php';

class SitemapGenerator implements Printable
{
    protected $siteURL = "";
    protected $domain = "";
    public $urlArr = [];

    private $previousURL = "";
    private $fetchedHTML = null;


    private $traceLinkLimit = -1; // Limitless = -1
    private $traceLinkCounter = 0;

    private $deepLimit = 2; // Limitless = -1
    private $linkLimit = 500; // Limitless = -1


    /**
     * SitemapGenerator constructor.
     * @param string $siteURL
     */
    public function __construct($siteURL)
    {
        $this->siteURL = $siteURL;
        $this->domain = $this->getDomain($this->siteURL);
    }

    /**
     * Set the total visiting links counter.
     * @param limit
     * @return SitemapGenerator
     */
    public function setTraceLinkLimit($limit)
    {
        $this->traceLinkLimit = $limit;
        return $this;
    }

    /**
     * Set the max limit for deep linking (recursion).
     * @return SitemapGenerator
     */
    public function setDeepLimit($limit)
    {
        $this->deepLimit = $limit;
        return $this;
    }

    /**
     * Set the max link fetching.
     * @return SitemapGenerator
     */
    public function setLinkLimit($limit)
    {
        $this->linkLimit = $limit;
        return $this;
    }

    /**
     * SitemapGenerator destructor.
     */
    public function __destruct()
    {
    }

    /**
     * Returns URL counter.
     * @return Integer counter
     */
    public function getCounter()
    {
        return count($this->urlArr);
    }

    /**
     * getDomain("http://www.atolye15.com");
     * @param URL ( for parsing )
     * @return String DomainName
     */
    private function getDomain($url)
    {
        $urlParts = parse_url($url);
        if (!isset($urlParts["host"])) { // sadece path ile link verilmiş ise
            return ""; // host'tan emin olamam.
        }
        $domainParts = explode('.', $urlParts['host']);
        if (strlen(end($domainParts)) == 2) { // www.atolye15.com gibi
            $topDomainParts = array_slice($domainParts, -3);
        } else { // subdomain olan urller
            $topDomainParts = array_slice($domainParts, -2);
        }
        $topDomain = implode('.', $topDomainParts);
        return $topDomain;
    }

    /**
     * checkURLisOnlyPath("deneme.php");
     * @param URL
     * @return Bool true/false
     */
    private function checkURLisOnlyPath($url)
    {
        $urlParts = parse_url($url);
        if (!isset($urlParts["host"])) { // sadece path ile link verilmiş ise
            return true;
        } else {
            return false;
        }
    }

    /**
     * readHTML("http://www.atolye15.com/otherBranch.html");
     * @param URL
     * @return Void
     */
    private function readHTML($url)
    {
        // CURL kullanarak daha çok kontrol elde edebilecektik !
        // 200=OK , 301=MovedPermanently , 302=Moved
        // atolye15.com'da 302 dönmekte
        if (in_array(substr(get_headers($url)[0], 9, 3), ["200", "301", "302"])) {
            $this->fetchedHTML = @file_get_contents($url);

            $this->previousURL = $url;
        } else {
            //die(get_headers($url)[0]);
            // URL was not found
            $this->fetchedHTML = "";
        }
    }


    /**
     * Parse all URLs in given URL
     * @param RefArr
     * @return void
     */
    private function parse()
    {
        $doc = new DOMDocument();
        @$doc->loadHTML($this->fetchedHTML);
        $xpath = new DOMXPath($doc);
        $nodeList = $xpath->query('//a/@href');
        for ($i = 0; $i < $nodeList->length; $i++) {
            $URL = $nodeList->item($i)->value;
            if ($URL == "#") {
                continue;
            }
            if ($this->checkURLisOnlyPath($URL)) {
                $URL = $this->domain . "/" . $URL;
            }
            if (!in_array($URL, $this->urlArr)) {
                array_push($this->urlArr, $URL);
            }
        }
    }

    /**
     * Generate is a main method of this class
     * @return Void
     */
    public function generate()
    {
        $this->generateRecursivePart($this->siteURL);
    }

    private function generateRecursivePart($url, $deep = 0)
    {
        if ($this->linkLimit != -1 && $this->getCounter() >= 500) return;
        if ($this->traceLinkLimit != -1 && $this->traceLinkCounter >= $this->traceLinkLimit) return;
        if ($this->deepLimit != -1 && $deep >= $this->deepLimit) return;

        $this->traceLinkCounter++;

        if ($this->checkURLisOnlyPath($url)) {
            $url = "http://" . $this->domain . "/" . $url;
        }

        $this->readHTML($url);
        if ($this->fetchedHTML != "") {
            $this->parse();
            foreach ($this->urlArr as &$url) {
                $this->generateRecursivePart($url, $deep + 1);
            }
        }
    }


    public function toString()
    {
        $response = "";

        foreach ($this->urlArr as &$url) {
            $response .= "<url><loc><![CDATA[" . $url . "]]></loc></url>";
        }

        return $response;
    }

    public function toXML()
    {
        header("Content-type: text/xml; charset=utf-8");

        $response = "";
        $response .= '<?xml version="1.0" encoding="utf-8"?>';
        $response .= '<response>';
        $response .= $this->toString();
        $response .= "</response>";

        return $response;
    }
}