<?php

namespace BrainDump;

class Sitemap
{
    public static function generate($pages, $siteUrl, $staticDir, $outputDir)
    {
        $sitemapPath = rtrim($outputDir, '/') . '/sitemap.xml';

        $siteUrl = rtrim($siteUrl, '/');

        $sitemapContent = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $sitemapContent .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

        foreach ($pages as $page) {
            $sitemapContent .= "<url>\n";
            $sitemapContent .= "<loc>{$siteUrl}{$page['url']}</loc>\n";
            $sitemapContent .= "<lastmod>" . date('Y-m-d', strtotime($page['date'])) . "</lastmod>\n";
            $sitemapContent .= "</url>\n";
        }

        $sitemapContent .= "</urlset>\n";

        // Apply checksum logic
        generateFile('sitemap.xml', $sitemapContent, $staticDir, $outputDir);
    }
}
