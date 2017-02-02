<?php
namespace KoninklijkeCollective\KoningSecuredFiles\Service;

use KoninklijkeCollective\KoningSecuredFiles\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Exception as FrontendException;
use TYPO3\CMS\Frontend\Page\PageGenerator;
use TYPO3\CMS\Frontend\Page\PageRepository;

class RedirectService
{
    /**
     * Redirect for each given type
     *
     * @param string $type
     * @return void
     * @throws \TYPO3\CMS\Frontend\Exception
     */
    public function redirect($type)
    {
        $target = null;
        $code = HttpUtility::HTTP_STATUS_307;
        $pageTarget = ConfigurationUtility::getSystemPage($type);

        if ($pageTarget) {
            if (MathUtility::canBeInterpretedAsInteger($pageTarget)) {
                $target = $this->getContentObjectRenderer()->typoLink_URL(['parameter' => $pageTarget]);
            } else {
                $target = $pageTarget;
            }

            if ($type === ConfigurationUtility::PAGE_LOGIN) {
                $urlParts = parse_url($target);
                $urlParts['query'] .= '&' . ConfigurationUtility::getLoginPageQuery(GeneralUtility::getIndpEnv('REQUEST_URI'));
                $urlParts['query'] = trim($urlParts['query'], '&');

                $target = HttpUtility::buildUrl($urlParts);
            }

            HttpUtility::redirect($target, $code);
        }
        throw new FrontendException('Please add redirects! (type: ' . ($type ?: 'unknown') . ')', 1448453425);
    }

    /**
     * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected function getTypoScriptFrontEndController()
    {
        if ($GLOBALS['TSFE'] === null) {
            /** @var TypoScriptFrontendController $controller */
            $GLOBALS['TSFE'] = $controller = GeneralUtility::makeInstance(
                TypoScriptFrontendController::class,
                $GLOBALS['TYPO3_CONF_VARS'],
                ConfigurationUtility::getSystemPage(ConfigurationUtility::PAGE_LOGIN),
                0
            );
            if (!($controller->fe_user instanceof FrontendUserAuthentication)) {
                $controller->initFEuser();
            }
            if (!($controller->sys_page instanceof PageRepository)) {
                $controller->determineId();
            }
            if (!($controller->tmpl instanceof TemplateService)) {
                $controller->initTemplate();
            }
            if (!is_array($controller->config)) {
                $controller->getConfigArray();
            }
            if (empty($controller->indexedDocTitle)) {
                PageGenerator::pagegenInit();
            }
            if (!($controller->cObj instanceof ContentObjectRenderer)) {
                $controller->newCObj();
            }
        }
        return $GLOBALS['TSFE'];
    }

    /**
     * @return \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
     */
    protected function getContentObjectRenderer()
    {
        return $this->getTypoScriptFrontEndController()->cObj;
    }

}
