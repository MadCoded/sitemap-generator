<?php
/**
 * Created by PhpStorm.
 * User: Aktug
 * Date: 6.05.2017
 * Time: 13:34
 */

require "classes\SitemapGenerator.php";


$sitemapGenerator = new SitemapGenerator("http://www.atolye15.com/");

$sitemapGenerator->setTraceLinkLimit(10)//-> Limit for tracing link counter. So there is a limit for 90 sec execution.
                    ->setDeepLimit(4) //-> Limit for recursion
                    ->setLinkLimit(500); //-> Limit for total fetched links.

$sitemapGenerator->generate();

print $sitemapGenerator->toXML();
