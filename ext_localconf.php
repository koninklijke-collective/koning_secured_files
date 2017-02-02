<?php
defined('TYPO3_MODE') or die ('Access denied.');

call_user_func(function ($extension) {
    $GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include'][$extension] = \KoninklijkeCollective\KoningSecuredFiles\Controller\FileEidController::class .'::processSecuredFile';
}, $_EXTKEY);
