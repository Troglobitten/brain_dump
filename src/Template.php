<?php

namespace BrainDump;

class Template
{
    protected $templatesPath;

    public function __construct($templatesPath)
    {
        $this->templatesPath = rtrim($templatesPath, '/').'/';
    }

    public function render($templateName, $data = [])
    {
        return $this->renderTemplate($templateName, $data);
    }

    protected function renderTemplate($templateName, $data = [])
    {
        $templateFile = $this->templatesPath . $templateName . '.html';
        if (!file_exists($templateFile)) {
            Logger::log("Template not found: $templateFile");
            return '';
        }

        $template = file_get_contents($templateFile);

        // Handle include tags explicitly
        $template = preg_replace_callback('/{% include \'(.*?)\' %}/', function($match) use ($data) {
            return $this->renderTemplate($match[1], $data);
        }, $template);

        // Handle loops
        $template = preg_replace_callback('/{% for (\w+) in (\w+) %}(.*?){% endfor %}/s', function($match) use ($data) {
            [$full, $varName, $arrayName, $loopTemplate] = $match;
            $html = '';
            if (!empty($data[$arrayName]) && is_array($data[$arrayName])) {
                foreach ($data[$arrayName] as $item) {
                    $itemHtml = $loopTemplate;
                    foreach ($item as $key => $value) {
                        if (!is_array($value))
                            $itemHtml = str_replace('{{ '.$varName.'.'.$key.' }}', $value, $itemHtml);
                    }
                    // Conditionals inside loops
                    $itemHtml = preg_replace_callback('/{% if '.$varName.'\.(\w+) %}(.*?){% endif %}/s', function($ifMatch) use ($item) {
    $condKey = $ifMatch[1];

    // Explicitly check for TRUE
    if (!empty($item[$condKey]) && $item[$condKey] === true) {
        return $ifMatch[2];
    }

    return '';
}, $itemHtml);


                    $html .= $itemHtml;
                }
            }
            return $html;
        }, $template);

                // Handle global conditionals like {% if breadcrumbs %}...{% endif %}
        $template = preg_replace_callback('/{% if (\w+) %}(.*?){% endif %}/s', function ($match) use ($data) {
            $varName = trim($match[1]);

            // Ensure the variable exists and is truthy
            if (isset($data[$varName]) && !empty($data[$varName])) {
                return $match[2]; // ✅ Render the content inside the condition
            }

            return ''; // ✅ Remove the block completely if condition is false
        }, $template);

        // Replace variables
        foreach ($data as $key => $value) {
            if (!is_array($value))
                $template = str_replace('{{ '.$key.' }}', $value, $template);
        }

        return $template;

    }
}
