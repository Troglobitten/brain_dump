<?php

namespace BrainDump;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\Extension\FrontMatter\Output\RenderedContentWithFrontMatter;
use League\CommonMark\MarkdownConverter;
use Spatie\CommonMarkHighlighter\FencedCodeRenderer;

class Markdown
{
    public static function parseFile($filepath)
    {
        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new FrontMatterExtension());
        $environment->addRenderer(
            \League\CommonMark\Extension\CommonMark\Node\Block\FencedCode::class,
            new FencedCodeRenderer(['php', 'javascript', 'bash'])
        );

        $converter = new MarkdownConverter($environment);
        $markdown = file_get_contents($filepath);
        $result = $converter->convert($markdown);

        $frontMatter = ($result instanceof \League\CommonMark\Extension\FrontMatter\Output\RenderedContentWithFrontMatter)
            ? $result->getFrontMatter()
            : [];

        return [
            'content' => $result->getContent(),
            'frontMatter' => $frontMatter,
        ];
    }
}
