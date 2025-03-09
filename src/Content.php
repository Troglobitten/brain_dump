<?php

namespace BrainDump;

use BrainDump\Logger;

class Content
{
    public static function scanContent($contentDir, $markdownParser)
    {
        $pages = [];
        $basePath = realpath($contentDir);

        if (!is_dir($contentDir) || !is_readable($contentDir)) {
            Logger::log("Content directory missing or unreadable: $contentDir");
            return $pages;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($contentDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getFilename()) === 'content.md') {
                $filePath = $file->getPathname();

                // Check if the file is readable
                if (!is_readable($filePath)) {
                    Logger::log("Cannot read file: $filePath");
                    continue;
                }

                // Attempt to parse Markdown file
                try {
                    $parsed = $markdownParser::parseFile($filePath);
                } catch (\Exception $e) {
                    Logger::log("Markdown parsing failed for: $filePath - " . $e->getMessage());
                    continue;
                }

                $relativePath = trim(str_replace($basePath, '', realpath(dirname($file))), '/');

                // Explicitly handle root-level markdown
                if ($relativePath === '') {
                    $url = '/';
                    $path = '';
                } else {
                    $url = '/' . $relativePath . '/';
                    $path = $relativePath;
                }

                // Parse tags properly
                $tags = $parsed['frontMatter']['tags'] ?? '';

                if (!is_array($tags)) {
                    $tags = array_map('trim', explode(',', $tags));
                }

                // Validate required fields
                if (empty($parsed['frontMatter']['title'])) {
                    Logger::log("Missing title in frontmatter: $filePath");
                }

                if (empty($parsed['content'])) {
                    Logger::log("No content found in: $filePath");
                }

                $pages[] = [
                    'title' => $parsed['frontMatter']['title'] ?? 'No title',
                    'date' => $parsed['frontMatter']['date'] ?? '',
                    'pagetype' => $parsed['frontMatter']['pagetype'] ?? 'page',
                    'content' => $parsed['content'],
                    'tags' => $tags,
                    'url' => $url,
                    'path' => $path,
                ];
            }
        }

        return $pages;
    }
}
