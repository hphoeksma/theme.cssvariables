<?php

namespace Theme\CssVariables\Command;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Theme\CssVariables\Service\ReadCSsVariablesService;
use Theme\CssVariables\Service\WriteCssVariablesService;

class ThemeCommandController extends CommandController
{

    /**
     * @Flow\Inject()
     * @var ReadCSsVariablesService
     */
    protected $readCssVariableService;

    /**
     * @Flow\Inject()
     * @var WriteCssVariablesService
     */
    protected $writeCssVariableService;

    /**
     * Replace color variables in preprocessed css that has not been using
     * the css custom properties for variables (i.e. with scss).
     *
     * @param string $cssFile The full path to the css file you want to change
     */
    public function replaceColorVariablesCommand($cssFile)
    {
        $finder = new Finder();
        $finder->files()->in(FLOW_PATH_ROOT)->path($cssFile);

        $continue = $this->output->askConfirmation('This feature is expirimental, are you sure you wish to continue? ');

        if ($continue) {
            // We have to loop through but only change one file!
            if ($finder->hasResults()) {
                /** @var SplFileInfo $file */
                foreach ($finder->files() as $file) {
                    $fileContent = $file->getContents();
                    $fileContentWithoutVariables = preg_replace('/(:root\s?\{(\s*?.*?)*?\})/', '', $fileContent);
                    $newFileContent = $fileContentWithoutVariables;
                    $variables = $this->readCssVariableService->readVariables($file);

                    // What to do?
                    // 1. Match the content on the variable value
                    // 2. Update the content with the variable name
                    // 3. Store the updated content in the file

                    foreach ($variables as $variable) {
                        if ($variable['type'] === 'color') {
                            preg_match('/(' . $variable['value'] . ')/i', $fileContent, $variableMatches);
                            if (!empty($variableMatches)) {
                                foreach ($variableMatches as $variableMatch) {
                                    if ($variableMatch !== '0') {
                                        $newFileContent = preg_replace('/' . $variableMatch . '/i', 'var(' . $variable['name'] . ')', $newFileContent);
                                    }
                                }
                            }
                        }
                    }
                    $fileContentToWrite = ':root{' . $this->writeCssVariableService->processVariables($this->readCssVariableService->readSimplifiedVariables($file)) . '}' . $newFileContent;

                    // Copy original
                    file_put_contents($cssFile . '_bup', $fileContent);
                    file_put_contents($cssFile, $fileContentToWrite);
                    $this->output->outputLine('All done! Make sure to check if the result is according to your needs!');
                    $this->outputLine('A backup file has been created for you.');
                }
            }
        } else {
            $this->outputLine('Okay, aborting...');
        }
    }
}
