<?php
declare(strict_types=1);

namespace Theme\CssVariables\Module\Controller;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Mvc\Exception\StopActionException;
use Neos\Flow\Package\PackageManager;
use Neos\Neos\Domain\Exception;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\SiteRepository;
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
    protected ReadCSsVariablesService $readVariablesService;

    /**
     * @Flow\Inject()
     * @var WriteCssVariablesService
     */
    protected WriteCssVariablesService $writeCssVariablesService;

    /**
     * @Flow\Inject
     * @var PackageManager
     */
    protected PackageManager $packageManager;

    /**
     * @var SiteRepository
     * @Flow\Inject
     */
    protected SiteRepository $siteRepository;
    /**
     *
     * @throws Exception
     */
    public function indexAction(): void
    {
        $this->view->assign('header', 'Theme / CSS Variables');
        $this->view->assign('result', $this->readVariablesService->readCssVariables());
        $this->view->assign('sites', $this->siteRepository->findAll());
    }

    /**
     * @param array $variables
     * @param array $default
     * @param string $site
     * @throws FilesException
     * @throws StopActionException
     */
    public function saveAction(array $variables, array $default, string $site): void
    {
        $changedVariables = [];

        // Iterate through the variables and compare them with the default values
        foreach ($variables[$site] as $key => $value) {
            if (isset($default[$site][$key]) && $default[$site][$key] !== $value) {
                $changedVariables[$key] = $value;
            }
        }

        // Save only if there are changed variables
        if (!empty($changedVariables)) {
            $this->writeCssVariablesService->writeCssVariables($changedVariables, $site);
        }

        $this->redirect('index');
    }

    /**
     * @param Site $site
     * @throws StopActionException
     */
    public function restoreAction(Site $site): void
    {
        $this->writeCssVariablesService->cleanCustomCss($site->getNodeName());
        $this->redirect('index');
    }

}


