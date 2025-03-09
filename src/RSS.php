<?php

namespace BrainDump;

class RSS
{
    public static function generateFeed($blogs, $config, $staticDir, $outputDir)
    {
        $rssPath = rtrim($outputDir, '/') . '/rss.xml';

        $siteUrl = rtrim($config['site']['url'], '/');
        $rssTitle = htmlspecialchars($config['site']['title']);
        $rssDescription = htmlspecialchars($config['site']['description']);

        $rssContent = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $rssContent .= "<rss version=\"2.0\">\n";
        $rssContent .= "<channel>\n";
        $rssContent .= "<title>{$rssTitle}</title>\n";
        $rssContent .= "<link>{$siteUrl}/</link>\n";
        $rssContent .= "<description>{$rssDescription}</description>\n";

        foreach ($blogs as $blog) {
            $rssContent .= "<item>\n";
            $rssContent .= "<title>" . htmlspecialchars($blog['title']) . "</title>\n";
            $rssContent .= "<link>{$siteUrl}{$blog['url']}</link>\n";
            $rssContent .= "<pubDate>" . date(DATE_RSS, strtotime($blog['date'])) . "</pubDate>\n";
            $rssContent .= "</item>\n";
        }

        $rssContent .= "</channel>\n";
        $rssContent .= "</rss>\n";

        // Apply checksum logic
        generateFile('rss.xml', $rssContent, $staticDir, $outputDir);
    }
}
