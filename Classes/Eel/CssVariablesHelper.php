<?php
declare(strict_types=1);

namespace Theme\CssVariables\Eel;

use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use Theme\CssVariables\Service\WriteCssVariablesService;

/**
 * Eel helper `CssVariables`: returns the overridden variables of a site for inline rendering.
 */
class CssVariablesHelper implements ProtectedContextAwareInterface
{
    /**
     * @Flow\Inject
     * @var WriteCssVariablesService
     */
    protected $writeCssVariablesService;

    /**
     * @param object|null $siteNode the site node (Neos 8 NodeInterface or Neos 9 Node)
     */
    public function customCss($siteNode): string
    {
        $siteNodeName = $this->getSiteNodeName($siteNode);
        if ($siteNodeName === null) {
            return '';
        }

        $pathAndFilename = $this->writeCssVariablesService->getPathAndFilename($siteNodeName);
        if (!is_file($pathAndFilename)) {
            return '';
        }

        // The file is written by WriteCssVariablesService, but never allow it to close the style tag
        return str_replace('<', '', (string)file_get_contents($pathAndFilename));
    }

    private function getSiteNodeName($siteNode): ?string
    {
        if (!is_object($siteNode)) {
            return null;
        }
        // Neos 8: NodeInterface::getName()
        if (method_exists($siteNode, 'getName')) {
            return (string)$siteNode->getName();
        }
        // Neos 9: Node::$name (NodeName|null), equal to the SiteNodeName
        if (property_exists($siteNode, 'name') && $siteNode->name !== null) {
            return $siteNode->name->value;
        }
        return null;
    }

    public function allowsCallOfMethod($methodName): bool
    {
        return true;
    }
}
