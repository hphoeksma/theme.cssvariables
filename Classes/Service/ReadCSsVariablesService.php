<?php

namespace Theme\CssVariables\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Exception;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Utility\Arrays;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class ReadCSsVariablesService
 * @package Theme\CssVariables\Module\Service
 *
 * Todo: merge files and variables
 */
class ReadCSsVariablesService
{
    /**
     * @Flow\InjectConfiguration("stylesheetName")
     * @var string
     */
    protected $stylesheetName;

    /**
     * @Flow\Inject
     * @var SiteRepository
     */
    protected $siteRepository;

    /**
     * @return array
     * @throws Exception
     */
    public function readCssVariables()
    {
        // Load the override values
        $files = $this->getCustomCssVariables();

        $finder = new Finder();
        $currentSite = $this->siteRepository->findDefault();

        $finder->files()
            ->in(FLOW_PATH_WEB . '_Resources/Static/Packages/' . $currentSite->getSiteResourcesPackageKey() . '/**/*')
            ->name('*.css')
            ->contains('--')
            ->followLinks();
        if ($finder->hasResults()) {
            foreach ($finder as $file) {
                if (!empty($this->readVariables($file))) {
                    $files[] = [
                        'name' => $file->getRelativePathname(),
                        'variables' => $this->readVariables($file)
                    ];
                }

            }
        }

        $returnValues = [];
        foreach (array_reverse($files) as $file) {
            $returnValues = Arrays::arrayMergeRecursiveOverrule($returnValues, $file);
        }

        return $returnValues;

    }

    /**
     * @param array $variable
     * @return string
     */
    private function getVariableType($variable)
    {
        // Check for Colors
        preg_match('/#|rgb*/', $variable[1], $matchesColor);
        if (!empty($matchesColor)) return 'color';

        // Check for Breakpoints
        preg_match('/break/', $variable[0], $matchesBreakpiont);
        if (!empty($matchesBreakpiont)) return 'viewport';

        // Check for fonts
        preg_match('/font/', $variable[0], $matchesFont);
        if (!empty($matchesFont)) return 'font';

        // Else put it in the other group
        return 'other';
    }

    /**
     * @param SplFileInfo $file
     * @return array
     */
    public function readVariables($file)
    {
        $variables = [];

        preg_match('/(:root\s?\{(\s*?.*?)*?\})/', $file->getContents(), $matches);
        if (!empty($matches)) {
            foreach ($matches as $match) {
                $match = preg_replace('/:root{/', '', $match);
                $match = preg_replace('/}/', '', $match);
                foreach (explode(';', $match) as $variable) {
                    $exploded = explode(':', $variable);
                    if (isset($exploded[1])) {
                        $variables[$exploded[0]] = [
                            'name' => $exploded[0],
                            'simple_name' => str_replace('--', '', $exploded[0]),
                            'value' => $exploded[1],
                            'type' => $this->getVariableType($exploded)
                        ];
                    }
                }
            }
        }
        return $variables;
    }

    /**
     * @param SplFileInfo $file
     * @return array
     */
    public function readSimplifiedVariables($file)
    {
        $variables = [];

        preg_match('/(:root\s?\{(\s*?.*?)*?\})/', $file->getContents(), $matches);
        if (!empty($matches)) {
            foreach ($matches as $match) {
                $match = preg_replace('/:root{/', '', $match);
                $match = preg_replace('/}/', '', $match);
                foreach (explode(';', $match) as $variable) {
                    $exploded = explode(':', $variable);
                    if (isset($exploded[1])) {
                        $variables[$exploded[0]] = $exploded[1];
                    }
                }
            }
        }
        return $variables;
    }

    /**
     * @return array
     */
    private function getCustomCssVariables() {
        $files = [];
        $customCss = new Finder();
        $customCss->files()->in(FLOW_PATH_DATA . 'Persistent/Theme/CssVariables')->name($this->stylesheetName)->followLinks();
        if ($customCss->hasResults()) {
            foreach ($customCss as $file) {
                if (!empty($this->readVariables($file))) {
                    $files[] = [
                        'name' => $file->getRelativePathname(),
                        'variables' => $this->readVariables($file)
                    ];
                }
            }
        }

        return $files;
    }
}
