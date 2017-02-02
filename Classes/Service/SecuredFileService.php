<?php
namespace KoninklijkeCollective\KoningSecuredFiles\Service;

use KoninklijkeCollective\KoningSecuredFiles\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Exception as FrontendException;

/**
 * Service: Secured File
 *
 * @package KoninklijkeCollective\KoningSecuredFiles\Service
 */
class SecuredFileService
{

    /**
     * @var \TYPO3\CMS\Core\Resource\ResourceFactory
     */
    protected $resourceFactory;

    /**
     * Try to retrieve file object based on given file path!
     *
     * @param string $filePath
     * @return \TYPO3\CMS\Core\Resource\FileInterface
     * @throws \TYPO3\CMS\Frontend\Exception
     */
    public function getFileObject($filePath)
    {
        $file = null;
        if ($this->validate($filePath)) {
            $_file = $this->getResourceFactory()->retrieveFileOrFolderObject($filePath);

            if ($_file instanceof File) {
                if ($this->userLoggedIn()) {
                    if (in_array($_file->getExtension(), ConfigurationUtility::getAllowedExtensions())) {
                        throw new FrontendException('Invalid file extension, are you trying something dirty?', 1486041176623);
                    } elseif ($this->userHasAccess($_file)) {
                        $file = $_file;
                    } else {
                        $this->getRedirectService()->redirect(ConfigurationUtility::PAGE_ERROR);
                    }
                } else {
                    $this->getRedirectService()->redirect(ConfigurationUtility::PAGE_LOGIN);
                }
            } else {
                throw new FrontendException('No file object found, are you trying something dirty?', 1407146005);
            }
        }
        return $file;
    }

    /**
     * Check if current fe user has access to given File Object
     *
     * @param \TYPO3\CMS\Core\Resource\File $file
     * @return boolean
     */
    protected function userHasAccess(File $file)
    {
        $access = false;
        if ($this->userLoggedIn()) {
            $groups = GeneralUtility::intExplode(',', $this->getFrontendUserAuthentication()->user['usergroup']);
            if (!empty($groups)) {
                if (method_exists($file, 'getParentFolder')) {
                    $folder = $file->getParentFolder();
                } else {
                    $directory = dirname($file->getPublicUrl());
                    $folder = $this->getResourceFactory()->retrieveFileOrFolderObject($directory);
                }

                $fileMount = $this->getFileMount($folder->getIdentifier(), $folder->getStorage()->getUid());
                if ($fileMount) {
                    if (!empty($fileMount['fe_group'])) {
                        $allowedGroups = GeneralUtility::intExplode(',', $fileMount['fe_group']);
                        if (in_array(-2, $allowedGroups)) {
                            $access = true;
                        }

                        if ($access === false) {
                            $matches = array_intersect($groups, $allowedGroups);
                            if (!empty($matches)) {
                                $access = true;
                            } else {
                                $this->getRedirectService()->redirect(ConfigurationUtility::PAGE_FORBIDDEN);
                            }
                        }
                    } else {
                        $access = true;
                    }
                }
            }
        }

        return $access;
    }

    /**
     * Check whether user has a correct login in session
     *
     * @return boolean
     */
    protected function userLoggedIn()
    {
        return $this->getFrontendUserAuthentication()->user !== null && $this->getFrontendUserAuthentication()->user['username'];
    }

    /**
     * Validate class and given file for existence!
     *
     * @param string $file
     * @return boolean
     * @throws \TYPO3\CMS\Frontend\Exception
     */
    protected function validate($file)
    {
        $allowedExtensions = ConfigurationUtility::getAllowedExtensions();
        if (empty($allowedExtensions)) {
            throw new FrontendException('No extensions allowed, did you configure the extension?', 1407146001);
        }
        if (empty($file)) {
            throw new FrontendException('No correct file given', 1407146008);
        } else {
            $file = GeneralUtility::getFileAbsFileName(ltrim($file, '/'));
            if (!file_exists($file)) {
                throw new FrontendException('No file found.', 1407146010);
            }
        }

        return true;
    }

    /**
     * Find file mount data in database
     *
     * @param string $identifier
     * @param integer $storageId
     * @return array
     */
    protected function getFileMount($identifier, $storageId)
    {
        $directories = GeneralUtility::trimExplode('/', $identifier, true);
        $possiblePaths = [];
        $lookUpPath = '/';
        foreach ($directories as $directory) {
            $lookUpPath .= $directory . '/';
            $possiblePaths[] = 'sys_filemounts.path = "' . $lookUpPath . '"';
        }

        $fileMount = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            '*',
            'sys_filemounts',
            '(' . implode(' OR ', $possiblePaths) . ')'
            . ' AND sys_filemounts.base = ' . (int)$storageId
            . \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields('sys_filemounts')
            . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('sys_filemounts'),
            '',
            'sys_filemounts.path DESC'
        );

        if ($fileMount) {
            // add sub fe_groups if available
            $groups = GeneralUtility::intExplode(',', $fileMount['fe_group'], true);

            $subGroups = [];
            foreach ($groups as $groupId) {
                $subGroups = $this->findUserSubGroups($groupId);
            }
            $groups = array_merge($groups, array_keys($subGroups));

            $fileMount['fe_group'] = implode(',', $groups);
        }
        return $fileMount;
    }

    /**
     * Find recursive attached usergroups
     *
     * @param integer $identifier
     * @param array $results
     * @return array
     */
    protected function findUserSubGroups($identifier = 0, &$results = [])
    {
        $res = $this->getDatabaseConnection()->exec_SELECTquery(
            '*',
            'fe_groups',
            ' FIND_IN_SET(' . (int)$identifier . ', fe_groups.subgroup) '
            . \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields('fe_groups')
            . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('fe_groups')
        );
        while ($row = $this->getDatabaseConnection()->sql_fetch_assoc($res)) {
            $results[$row['uid']] = $row;
        }

        return $results;
    }

    /**
     * @return \TYPO3\CMS\Core\Resource\ResourceFactory
     */
    protected function getResourceFactory()
    {
        if ($this->resourceFactory === null) {
            $this->resourceFactory = $this->getObjectManager()->get(ResourceFactory::class);
        }
        return $this->resourceFactory;
    }

    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * @return \TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication
     */
    protected function getFrontendUserAuthentication()
    {
        if ($GLOBALS['TSFE'] instanceof TypoScriptFrontendController) {
            return $GLOBALS['TSFE']->fe_user;
        }
        return \TYPO3\CMS\Frontend\Utility\EidUtility::initFeUser();
    }

    /**
     * @return \KoninklijkeCollective\KoningSecuredFiles\Service\RedirectService
     */
    protected function getRedirectService()
    {
        return $this->getObjectManager()->get(RedirectService::class);
    }

    /**
     * @return \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected function getObjectManager()
    {
        return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ObjectManager::class);
    }
}
