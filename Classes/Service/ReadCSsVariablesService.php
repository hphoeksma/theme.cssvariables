<?php

namespace Theme\CssVariables\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Package;
use Neos\Flow\Package\PackageManager;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Utility\Arrays;
use Neos\Utility\Exception\FilesException;
use Neos\Utility\Files;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class ReadCSsVariablesService
 * @package Theme\CssVariables\Module\Service
 */
class ReadCSsVariablesService
{
    /**
     * @Flow\InjectConfiguration("stylesheetName")
     * @var string
     */
    protected $stylesheetName;

    /**
     * @Flow\InjectConfiguration("excludedPackages")
     * @var array
     */
    protected $excludedPackages;

    /**
     * @Flow\InjectConfiguration("excludedPaths")
     * @var array
     */
    protected $excludedPaths;

    /**
     * @Flow\Inject
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * @var SiteRepository
     * @Flow\Inject
     */
    protected SiteRepository $siteRepository;

    /**
     * @return array
     */
    public function readCssVariables()
    {
        $result = [];



        /** @var Site $site */
        foreach ($this->siteRepository->findAll() as $site) {
            // Load the override values
            $files = $this->getCustomCssVariables($site->getNodeName());
            if ($this->getCssFiles()) {
                foreach ($this->getCssFiles() as $file) {
                    if (!empty($this->readVariables($file)) && !str_starts_with($file->getRelativePathname(), 'Private')) {
                        $files[] = [
                            'name' => $file->getFilename(),
                            'variables' => $this->readVariables($file)
                        ];
                    }
                }
            }

            $result[$site->getNodeName()]['cssfile'] = [];

            foreach (array_reverse($files) as $file) {
                $result[$site->getNodeName()]['cssfile'] = Arrays::arrayMergeRecursiveOverrule($result[$site->getNodeName()]['cssfile'], $file);
            }

            if (empty($result[$site->getNodeName()]['cssfile'])) $result[$site->getNodeName()]['error'] = 'No css variables found';

        }

        return $result;
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

        preg_match('/tw-/', $variable[0], $matches);
        if (!empty($matches)) return 'TailWind';

        // Else put it in the other group
        return 'other';
    }

    /**
     * @param SplFileInfo $file
     * @return array
     */
    public function readVariables(SplFileInfo $file): array
    {
        $variables = [];
        $content = $file->getContents();

        // Match all standalone custom variables formatted as `--variable-name: value;`
        preg_match_all('/^\s*--([\w-]+):\s*([^;]+);/m', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            if (isset($match[1]) && isset($match[2])) {
                $variableName = '--' . trim($match[1]); // Re-add prefix "--"
                $variableValue = trim($match[2]);

                // Avoid duplicates by checking if the variable is already in the array
                if (!isset($variables[$variableName])) {
                    $variables[$variableName] = [
                        'name' => $variableName,
                        'simple_name' => str_replace('tw-', '', trim($match[1])),
                        'value' => $variableValue,
                        'type' => $this->getVariableType([$variableName, $variableValue]),
                        'packageKey' => $this->getPackageKeyByFile($file),
                        'fileName' => $file->getFilename()
                    ];
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
     * @param Site|null $site
     * @return array
     * @throws FilesException
     */
    private function getCustomCssVariables(string $siteNodeName): array
    {
        $files = [];


        /** @var Site $site */
        $customCssDirectory = FLOW_PATH_DATA . 'Persistent/Theme/CssVariables/' . $siteNodeName;
        if (!is_dir($customCssDirectory)) {
            Files::createDirectoryRecursively($customCssDirectory);
        }
        $customCss = new Finder();
        $customCss->files()->in($customCssDirectory)->name($this->stylesheetName)->followLinks();
        if ($customCss->hasResults()) {
            foreach ($customCss as $file) {
                if (!empty($this->readVariables($file))) {
                    $files[$siteNodeName] = [
                        'name' => $file->getRelativePathname(),
                        'variables' => $this->readVariables($file)
                    ];
                }
            }
        }

        return $files;
    }

    /**
     * Get all Site or Theme packages
     *
     * @return array
     */
    private function filteredPackages(): array
    {
        $availablePackages = $this->packageManager->getFlowPackages();
        $filteredPackages = [];
        foreach ($availablePackages as $package) {
            if ($package->getPackageKey() === 'Theme.CssVariables') {
                continue;
            }
            if (!array_filter($this->excludedPackages, fn($prefix) => str_starts_with($package->getPackageKey(), $prefix))) {
                $filteredPackages[] = $package;
            }
        }

        return $filteredPackages;
    }

    private function getPackageKeyByFile(SplFileInfo $file): ?string
    {
        $packageKey = null;

        if (preg_match('#/Packages/Application/([^/]+)/#', $file->getRealPath(), $matches)) {
            $packageKey = $matches[1];
        }

        return $packageKey ?: $this->siteRepository->findFirstOnline()->getSiteResourcesPackageKey();
    }

    /**
     * @return array|Finder|null
     */
    private function getCssFiles(): array|Finder|null
    {
        $finder = new Finder();
        $packages = $this->filteredPackages();

        if (empty($packages)) {
            $result['error'] = 'No packages found to read variables from.';
            return $result;
        }

        $finder->files()
            ->name('*.css')
            ->contains('--')
            ->followLinks();

        /** @var Package $package */
        foreach ($packages as $package) {
            if (is_dir($package->getResourcesPath())) {
                $finder->in($package->getResourcesPath());
            }
        }

        // Manually filter out files from the excluded paths
        $finder->filter(function (\SplFileInfo $file) {
            foreach ($this->excludedPaths as $excludedPath) {
                if (str_contains($file->getRealPath(), $excludedPath)) {
                    return false; // Exclude this file
                }
            }
            return true; // Keep this file
        });

        return $finder->hasResults() ? $finder : null;
    }
}
