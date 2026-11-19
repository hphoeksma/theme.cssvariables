<?php

namespace Theme\CssVariables\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Utility\Exception\FilesException;
use Neos\Utility\Files;

/**
 * Stores the overridden CSS variables of a site in Data/Persistent.
 *
 * The file is rendered inline by Fusion (see CssVariablesHelper), so nothing is
 * published to Web/_Resources and nothing is written into the package directory.
 *
 * @Flow\Scope("singleton")
 */
class WriteCssVariablesService
{
    /**
     * @Flow\InjectConfiguration("stylesheetName")
     * @var string
     */
    protected $stylesheetName;

    /**
     * @param array<string, string> $variables
     * @throws FilesException
     */
    public function writeCssVariables(array $variables, string $site): bool
    {
        $contents = ':root{' . $this->processVariables($variables) . '}';

        Files::createDirectoryRecursively($this->getDirectory($site));
        file_put_contents($this->getPathAndFilename($site), $contents);

        return true;
    }

    /**
     * @param array<string, string> $variables
     */
    public function processVariables(array $variables): string
    {
        $processedVariables = [];
        foreach ($variables as $variableName => $variableValue) {
            $variableName = (string)$variableName;
            // Only accept custom property names, and keep values from breaking out of the declaration
            if (!preg_match('/^--[\w-]+$/', $variableName)) {
                continue;
            }
            $variableValue = trim(str_replace(['<', '>', '{', '}', ';'], '', (string)$variableValue));
            if ($variableValue === '') {
                continue;
            }
            $processedVariables[] = $variableName . ':' . $variableValue;
        }

        return implode(';', $processedVariables);
    }

    public function cleanCustomCss(string $site): void
    {
        $pathAndFilename = $this->getPathAndFilename($site);
        if (is_file($pathAndFilename)) {
            Files::unlink($pathAndFilename);
        }

        // Versions up to 0.2.x published a symlink next to the package's public resources
        $legacyLink = Files::concatenatePaths([
            FLOW_PATH_WEB, '_Resources', 'Static', 'Packages', 'Theme.CssVariables', $site . '_' . $this->stylesheetName
        ]);
        if (is_link($legacyLink)) {
            unlink($legacyLink);
        }
    }

    public function getPathAndFilename(string $site): string
    {
        return Files::concatenatePaths([$this->getDirectory($site), $this->stylesheetName]);
    }

    private function getDirectory(string $site): string
    {
        return Files::concatenatePaths([FLOW_PATH_DATA, 'Persistent', 'Theme', 'CssVariables', $site]);
    }
}
