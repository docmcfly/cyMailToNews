<?php


defined('TYPO3') || die();


/**
 *
 * This file is part of the "cy_mail_to_news" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2025 C. Gogolin <service@cylancer.net>
 *
 */


use Cylancer\CyMailToNews\Task\Mail2NewsAdditionalFieldProvider;
use Cylancer\CyMailToNews\Task\Mail2NewsTask;

$translationPath = 'LLL:EXT:cy_mail_to_news/Resources/Private/Language/locallang_task_alert2news.xlf';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][Mail2NewsTask::class] = [
    'extension' => 'cy_mail_to_news',
    'title' => "$translationPath:task.alert2news.title",
    'description' => "$translationPath:task.alert2news.description",
    'additionalFields' => Mail2NewsAdditionalFieldProvider::class
];

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
    'cy_mail_to_news',
    'setup',
    "@import 'EXT:cy_mail_to_news/Configuration/TypoScript/setup.typoscript'"
);


