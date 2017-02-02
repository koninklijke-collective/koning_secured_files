<?php
defined('TYPO3_MODE') or die ('Access denied.');

call_user_func(function ($extension, $table) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns($table, [
        'fe_group' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extension . '/Resources/Private/Language/locallang_be.xlf:sys_filemount.fe_group',
            'config' => [
                'type' => 'select',
                'size' => 7,
                'maxitems' => 20,
                'items' => [
                    [
                        'LLL:EXT:lang/locallang_general.xlf:LGL.any_login',
                        -2
                    ],
                    [
                        'LLL:EXT:lang/locallang_general.xlf:LGL.usergroups',
                        '--div--'
                    ]
                ],
                'exclusiveKeys' => '-1,-2',
                'foreign_table' => 'fe_groups',
                'foreign_table_where' => 'ORDER BY fe_groups.title'
            ],
        ],
    ]);
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes($table, 'fe_group');
}, 'koning_secured_files', 'sys_filemounts');
