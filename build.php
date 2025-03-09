<?php

require 'vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;
use BrainDump\Markdown;
use BrainDump\Template;
use BrainDump\Content;
use BrainDump\FileManager;
use BrainDump\Navigation;
use BrainDump\Sitemap;
use BrainDump\RSS;
use BrainDump\Logger;

spl_autoload_register(function ($class) {
    $file = __DIR__ . '/src/' . str_replace('BrainDump\\', '', $class) . '.php';
    if (file_exists($file)) require $file;
});

$config = Yaml::parseFile('config.yaml');
$templateEngine = new Template('templates');
$contentDir = 'content';
$staticDir = 'static';
$updateDir = 'updates';
$siteUrl = rtrim($config['site']['url'], '/');

$pages = Content::scanContent($contentDir, Markdown::class);
$menuItems = Navigation::scanMenuItems($contentDir, $siteUrl);

$blogs = array_filter($pages, fn($p) => $p['pagetype'] === 'blog');

// First clear updates folder completely
if (is_dir($updateDir)) exec("rm -rf " . escapeshellarg($updateDir));
mkdir($updateDir, 0755, true);

// Function to generate and conditionally write files based on hash
function generateFile($path, $content, $staticDir, $updateDir) {
    // Normalize the path by removing any leading slash
    $path = ltrim($path, '/');

    $staticPath = "{$staticDir}/{$path}";
    $updatePath = "{$updateDir}/{$path}";

    // Ensure destination directory exists
    if (!is_dir(dirname($updatePath))) {
        mkdir(dirname($updatePath), 0755, true);
    }

    // Normalize content: collapse multiple whitespace characters and trim
    $normalizedContent = preg_replace('/\s+/', ' ', trim($content));
    $newHash = md5($normalizedContent);

    $oldContent = file_exists($staticPath) ? file_get_contents($staticPath) : '';
    $normalizedOld = preg_replace('/\s+/', ' ', trim($oldContent));
    $oldHash = md5($normalizedOld);

    if ($newHash !== $oldHash) {
        file_put_contents($updatePath, $normalizedContent);
        echo "Updated: {$updatePath}\n";
    } else {
        echo "Unchanged: {$staticPath}\n";
    }
}


// Generate normal pages and blogs
foreach ($pages as $page) {
    if ($page['pagetype'] === 'bloglist') continue;


    // clearly example inside build.php when rendering:
    $html = $templateEngine->render($page['pagetype'], [
        'site.title' => $config['site']['title'],
        'page.title' => $page['title'],
        'page.date' => $page['date'],
        'page.content' => $page['content'],
        'page.tags' => implode(', ', $page['tags']),
        'breadcrumbs' => Navigation::breadcrumbsArray($page['url'], $siteUrl),
        'menu' => Navigation::menuArray($menuItems, $siteUrl . $page['url']),
        'page.url' => $page['url'],
]);


    $filePath = "{$page['path']}/index.html";
    generateFile($filePath, $html, $staticDir, $updateDir);
}

// Generate bloglist pages (using generateFile for checksum checking)
foreach ($pages as $page) {
    if ($page['pagetype'] !== 'bloglist') continue;

    // For root-level bloglist, blogPath will be empty
    $blogPath = trim($page['path'], '/');

    // Filter blogs belonging to this bloglist (by path matching)
    $filteredBlogs = array_filter($blogs, fn($blog) => strpos($blog['path'], $blogPath) === 0);
    usort($filteredBlogs, fn($a, $b) => strtotime($b['date']) <=> strtotime($a['date']));

    $maxPerPage = $config['blog']['max_posts_per_page'];
    $chunks = array_chunk($filteredBlogs, $maxPerPage);
    $totalPages = count($chunks);

    for ($i = 0; $i < $totalPages; $i++) {
        // Prepare structured data for each blog in this page
        $currentBlogs = array_map(function ($blog) use ($siteUrl) {
            return [
                'title' => $blog['title'],
                'url'   => $siteUrl . $blog['url'],
                'date'  => $blog['date'],
                'tags'  => implode(', ', $blog['tags']),
            ];
        }, $chunks[$i]);

        // Build pagination structure
        $pagination = [];
        for ($p = 1; $p <= $totalPages; $p++) {
            $pagination[] = [
                'number' => $p,
                'url'    => ($p === 1) ? 'index.html' : "page-{$p}.html",
                'active' => ($p === $i + 1)
            ];
        }

        // Render the bloglist template, passing structured arrays
        $html = $templateEngine->render('bloglist', [
            'site.title'   => $config['site']['title'],
            'page.title'   => $page['title'],
            'blogs'        => $currentBlogs,
            'pagination'   => $pagination,
            'menu'         => Navigation::menuArray($menuItems, $siteUrl . $page['url']),
            'breadcrumbs'  => Navigation::breadcrumbsArray($page['url'], $siteUrl),
            'page.url'     => $page['url'],
        ]);

        // Determine output file path:
        if (empty($blogPath)) {
            $outputFile = ($i === 0) ? 'index.html' : "page-" . ($i + 1) . ".html";
        } else {
            $outputFile = ($i === 0) ? $blogPath . '/index.html' : $blogPath . "/page-" . ($i + 1) . ".html";
        }

        // Use generateFile function to write the file conditionally
        generateFile($outputFile, $html, $staticDir, $updateDir);
    }
}

// Generate RSS & Sitemap with checksum checking
RSS::generateFeed($blogs, $config, $staticDir, $updateDir);
Sitemap::generate($pages, $siteUrl, $staticDir, $updateDir);

// Copy new/updated files to static
exec("cp -r {$updateDir}/. {$staticDir}/");

// Handle static assets and images
FileManager::copyStaticAssets($contentDir, $staticDir);
FileManager::processImages($contentDir, $config, $staticDir);
