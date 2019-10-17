<?php

namespace Theme\CssVariables\Module\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Utility\Files as Files;

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
     * @param $variables
     * @return false|int
     * @throws \Neos\Utility\Exception\FilesException
     */
    public function writeCssVariables($variables)
    {

        $contents = ':root{' . $this->processVariables($variables) . '}';

        // Setup the Directory structure
        Files::createDirectoryRecursively(Files::concatenatePaths([
            FLOW_PATH_DATA,
            'Persistent',
            'Theme',
            'CssVariables'
        ]));

        // Store in Data/Persistent
        $persistentPathAndFilename = Files::concatenatePaths([
            FLOW_PATH_DATA,
            'Persistent',
            'Theme',
            'CssVariables',
            $this->stylesheetName
        ]);

        file_put_contents($persistentPathAndFilename, $contents);

        // Create a symlink in Public Resources if it doesn't exist
        $publicPathAndFilename = Files::concatenatePaths([
            FLOW_PATH_WEB,
            '_Resources',
            'Static',
            'Packages',
            'Theme.CssVariables',
            $this->stylesheetName
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
     *
     */
    public function cleanCustomCss()
    {
        // Remove the stored variables
        $persistentPathAndFilename = Files::concatenatePaths([
            FLOW_PATH_DATA,
            'Persistent',
            'Theme',
            'CssVariables',
            $this->stylesheetName
        ]);

        Files::unlink($persistentPathAndFilename);

        // Remove the symlink
        $publicPathAndFilename = Files::concatenatePaths([
            FLOW_PATH_WEB,
            '_Resources',
            'Static',
            'Packages',
            'Theme.CssVariables',
            $this->stylesheetName
        ]);

        if (!Files::is_link($publicPathAndFilename)) {
            Files::unlink($publicPathAndFilename);
        }
    }
}
