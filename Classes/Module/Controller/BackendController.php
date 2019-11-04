<?php

namespace Theme\CssVariables\Module\Controller;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Mvc\Exception\StopActionException;
use Neos\Flow\Package\PackageManager;
use Neos\Neos\Domain\Exception;
use Neos\Utility\Exception\FilesException;
use Theme\CssVariables\Service\ReadCSsVariablesService;
use Theme\CssVariables\Service\WriteCssVariablesService;


/**
 * Class BackendController
 * @package Theme\CssVariables\Module\Controller
 */
class BackendController extends ActionController
{

    /**
     * @Flow\Inject()
     * @var ReadCSsVariablesService
     */
    protected $readVariablesService;

    /**
     * @Flow\Inject()
     * @var WriteCssVariablesService
     */
    protected $writeCssVariablesService;

    /**
     * @Flow\Inject
     * @var PackageManager
     */
    protected $packageManager;

    /**
     *
     * @throws Exception
     */
    public function indexAction()
    {
        $this->view->assign('header', 'Theme / CSS Variables');
        $this->view->assign('result', $this->readVariablesService->readCssVariables());
    }

    /**
     * @param array $variables
     * @throws StopActionException
     * @throws FilesException
     */
    public function saveAction(array $variables)
    {
        if (!empty($variables)) {
            $this->writeCssVariablesService->writeCssVariables($variables);
        }

        $this->redirect('index');
    }

    /**
     * @throws StopActionException
     */
    public function restoreAction() {
        $this->writeCssVariablesService->cleanCustomCss();

        $this->redirect('index');
    }

}


