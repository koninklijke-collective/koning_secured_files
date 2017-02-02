<?php
namespace KoninklijkeCollective\KoningSecuredFiles\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Utility: Configuration
 *
 * @package KoninklijkeCollective\KoningSecuredFiles\Utility
 */
class ConfigurationUtility
{
    const PAGE_LOGIN = 'login';
    const PAGE_FORBIDDEN = 'forbidden';
    const PAGE_ERROR = 'error';

    /**
     * Default file extensions allowed
     *
     * @var array
     */
    static $defaultAllowedExtensions = [
        'pdf', 'zip', 'jpg', 'jpeg', 'png', 'gif', 'xls', 'xlsx', 'doc', 'docx', 'rar', '7z', 'tar', 'gz',
        'exe', 'bmp', 'txt', 'odt', 'odf', 'rtf', 'htm', 'html', 'csv', 'pps', 'ppt', 'pptx', 'xml', 'wav', 'mp3',
        'wma', 'avi', 'wmv', 'swf', 'flv', 'mp4', 'mpg', 'mov', 'tif', 'psd', 'eps', 'bin', 'iso', 'dmg', 'msi',
    ];

    /**
     * Configured system pages (403, 404 and login)
     *
     * @param string $type "error", "forbidden" or "login"
     * @return array
     */
    public static function getSystemPage($type)
    {
        $systemPages = static::getSystemPages();
        return ($systemPages[$type] ? $systemPages[$type] : null);
    }

    /**
     * Configured system pages (403, 404 and login)
     *
     * @return array
     */
    public static function getSystemPages()
    {
        $configuration = static::getConfiguration();
        return [
            self::PAGE_ERROR => $configuration['notFoundPage'],
            self::PAGE_FORBIDDEN => $configuration['forbiddenPage'],
            self::PAGE_LOGIN => $configuration['loginPage'],
        ];
    }

    /**
     * Get login page query for changing target
     *
     * @return string
     */
    public static function getLoginPageQuery($target)
    {
        $configuration = static::getConfiguration();
        $query = (!empty($configuration['loginPageQuery']) ? $configuration['loginPageQuery'] : 'redirect_url={target}');
        return str_replace('{target}', $target, $query);
    }

    /**
     * Get global configuration
     *
     * @return array
     */
    public static function getConfiguration()
    {
        static $configuration;
        if ($configuration === null) {
            $data = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['koning_secured_files'];
            if (!is_array($data)) {
                $configuration = (array) unserialize($data);
            } else {
                $configuration = $data;
            }
        }

        return $configuration;
    }

    /**
     * List of file extensions allowed to be served by this extension. (comma separated)
     *
     * @return array
     */
    public static function getAllowedExtensions()
    {
        $configuration = static::getConfiguration();
        if ($results = $configuration['allowedFileExtensions']) {
            if (!is_array($results)) {
                $results = GeneralUtility::trimExplode(',', $results, true);
            }
            return $results;
        }
        return static::$defaultAllowedExtensions;
    }
}
