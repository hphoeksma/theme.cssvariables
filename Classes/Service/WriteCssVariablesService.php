<?php

namespace Theme\CssVariables\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Utility\Exception\FilesException;
use Neos\Utility\Files;

/**
 * Class WriteCssVariablesService
 * @package Theme\CssVariables\Module\Service
 */
class WriteCssVariablesService
{

    /**
     * @Flow\InjectConfiguration("stylesheetName")
     * @var string
     */
    protected $stylesheetName;

    /**
     * @param array $variables
     * @param string $site
     * @return bool|int
     * @throws FilesException
     */
    public function writeCssVariables(array $variables, string $site): bool|int
    {

        $contents = ':root{' . $this->processVariables($variables) . '}';

        // Setup the Directory structure
        Files::createDirectoryRecursively(Files::concatenatePaths([
            FLOW_PATH_DATA,
            'Persistent',
            'Theme',
            'CssVariables',
            $site
        ]));

        // Store in Data/Persistent
        $persistentPathAndFilename = Files::concatenatePaths([
            FLOW_PATH_ROOT,
            'Data',
            'Persistent',
            'Theme',
            'CssVariables',
            $site,
            $this->stylesheetName
        ]);

        file_put_contents($persistentPathAndFilename, $contents);

        // Create a symlink in Public Resources if it doesn't exist
        $publicPathAndFilename = Files::concatenatePaths([
            FLOW_PATH_ROOT,
            'Web',
            '_Resources',
            'Static',
            'Packages',
            'Theme.CssVariables',
            $site . '_' . $this->stylesheetName
        ]);

        if (!Files::is_link($publicPathAndFilename)) {
            Files::createRelativeSymlink($persistentPathAndFilename, $publicPathAndFilename);
        }

        return true;
    }

    /**
     * @param array $variables
     * @return string
     */
    public function processVariables(array $variables)
    {
        $processedVariables = [];
        foreach ($variables as $variableName => $variableValue) {
            $processedVariables[] = $variableName . ':' . $variableValue;
        }

        return implode(';', $processedVariables);
    }

    /**
     * @param string $site
     */
    public function cleanCustomCss(string $site): void
    {
        // Remove the symlink
        $publicPathAndFilename = Files::concatenatePaths([
            FLOW_PATH_WEB,
            '_Resources',
            'Static',
            'Packages',
            'Theme.CssVariables',
            $site . '_' . $this->stylesheetName
        ]);

        Files::unlink($publicPathAndFilename);

        // Remove the stored variables
        $persistentPathAndFilename = Files::concatenatePaths([
            FLOW_PATH_DATA,
            'Persistent',
            'Theme',
            'CssVariables',
            $site,
            $this->stylesheetName
        ]);

        Files::unlink($persistentPathAndFilename);
    }
}
