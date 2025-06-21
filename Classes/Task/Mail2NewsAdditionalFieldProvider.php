<?php
namespace Cylancer\CyMailToNews\Task;


use Cylancer\CyMailToNews\Domain\Model\ConfigurationKey;
use GeorgRinger\News\Domain\Repository\CategoryRepository;

use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\AbstractAdditionalFieldProvider;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Scheduler\Task\AbstractTask; 


/**
 * This file is part of the "cy_mail_to_news" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2025 C. Gogolin <service@cylancer.net>
 *
 */

class Mail2NewsAdditionalFieldProvider extends AbstractAdditionalFieldProvider
{

    private const TRANSLATION_PREFIX = 'LLL:EXT:cy_mail_to_news/Resources/Private/Language/locallang_task_alert2news.xlf:task.alert2news.property.';

    private function setCurrentKey(array &$taskInfo, ?Mail2NewsTask $task, ConfigurationKey $key): void
    {
        if (empty($taskInfo[$key->value])) {
            $taskInfo[$key->value] = $task != null ? $task->get($key) : $key->getDefault();
        }
    }

    private function initIntegerAddtionalField(array &$taskInfo, $task, ConfigurationKey $key, array &$additionalFields)
    {

        $this->setCurrentKey($taskInfo, $task, $key);
        $_key = $key->value;
        // Write the code for the field
        $fieldID = "task_$_key";
        $fieldCode = '<input type="number" min="0" max="99999" class="form-control" name="tx_scheduler[' . $_key . ']" id="' . $fieldID . '" value="' . $taskInfo[$_key] . '" >';
        $additionalFields[$fieldID] = [
            'code' => $fieldCode,
            'label' => Mail2NewsAdditionalFieldProvider::TRANSLATION_PREFIX . $_key,
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => $fieldID
        ];
    }

    private function initStringAddtionalField(array &$taskInfo, $task, ConfigurationKey $key, array &$additionalFields)
    {
        $this->setCurrentKey($taskInfo, $task, $key);
        $_key = $key->value;
        // Write the code for the field
        $fieldID = "task_$_key";
        $fieldCode = '<input type="text" class="form-control" name="tx_scheduler[' . $_key . ']" id="' . $fieldID . '" value="' . $taskInfo[$_key] . '" >';
        $additionalFields[$fieldID] = [
            'code' => $fieldCode,
            'label' => Mail2NewsAdditionalFieldProvider::TRANSLATION_PREFIX . $_key,
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => $fieldID
        ];
    }
    private function initTextAddtionalField(array &$taskInfo, $task, ConfigurationKey $key, array &$additionalFields)
    {
        $this->setCurrentKey($taskInfo, $task, $key);
        $_key = $key->value;
        // Write the code for the field
        $fieldID = "task_$_key";
        $fieldCode = '<textarea  class="form-control" style="font-family: \'DejaVu Sans Mono\', monospace; height: 20em;" name="tx_scheduler[' . $_key . ']" id="' . $fieldID . '">' . $taskInfo[$_key] . '</textarea>';
        $additionalFields[$fieldID] = [
            'code' => $fieldCode,
            'label' => Mail2NewsAdditionalFieldProvider::TRANSLATION_PREFIX . $_key,
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => $fieldID
        ];
    }


    private function initPasswordAddtionalField(array &$taskInfo, $task, ConfigurationKey $key, array &$additionalFields)
    {
        $this->setCurrentKey($taskInfo, $task, $key);
        $_key = $key->value;
        // Write the code for the field
        $fieldID = "task_$_key";
        $fieldCode = '<input type="password" class="form-control" name="tx_scheduler[' . $_key . ']" id="' . $fieldID . '" value="' . $taskInfo[$_key] . '" >';
        $additionalFields[$fieldID] = [
            'code' => $fieldCode,
            'label' => Mail2NewsAdditionalFieldProvider::TRANSLATION_PREFIX . $_key,
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => $fieldID
        ];
    }


    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule)
    {
        $additionalFields = [];
        $this->initIntegerAddtionalField($taskInfo, $task, ConfigurationKey::NEWS_STORAGE_PAGE_ID, $additionalFields);
        $this->initTextAddtionalField($taskInfo, $task, ConfigurationKey::BODY_TEXT_TEMPLATE, $additionalFields);
        $this->initTextAddtionalField($taskInfo, $task, ConfigurationKey::CATEGORY_RULES, $additionalFields);
        $this->initStringAddtionalField($taskInfo, $task, ConfigurationKey::IMAP_SERVER, $additionalFields);
        $this->initIntegerAddtionalField($taskInfo, $task, ConfigurationKey::IMAP_PORT, $additionalFields);
        $this->initStringAddtionalField($taskInfo, $task, ConfigurationKey::IMAP_ACCOUNT, $additionalFields);
        $this->initPasswordAddtionalField($taskInfo, $task, ConfigurationKey::IMAP_PASSWORD, $additionalFields);

        return $additionalFields;
    }

    private function addErrorMessage(string $msgType, string $key, mixed ...$params): bool
    {
        $this->addMessage(
            sprintf(
                $this->getLanguageService()->sL(Mail2NewsAdditionalFieldProvider::TRANSLATION_PREFIX . 'error.' . $msgType . '.' . $key),
                $params
            ),
            ContextualFeedbackSeverity::ERROR
        );
        return false;
    }

    private function validateIntegerAdditionalField(array &$submittedData, ConfigurationKey $key)
    {
        $_key = $key->value;
        $submittedData[$_key] = (int) $submittedData[$_key];
        if ($submittedData[$_key] < 0) {
            return $this->addErrorMessage('invalid', $_key);
        }

        return true;
    }

    private function validatePageUidAdditionalField(array &$submittedData, ConfigurationKey $key)
    {
        $_key = $key->value;
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        if (count($pageRepository->getPage($submittedData[$_key], true)) === 0) {
            return $this->addErrorMessage('invalid', $_key);
        }

        return true;
    }

    private function validateRequiredField(array &$submittedData, ConfigurationKey $key)
    {
        $_key = $key->value;
        if (empty($submittedData[$_key])) {
            return $this->addErrorMessage('required', $_key);
        }
        return true;
    }

    private function validateSitedField(array &$submittedData, ConfigurationKey $key)
    {
        $_key = $key->value;
        try {
            GeneralUtility::makeInstance(SiteFinder::class)->getSiteByIdentifier($submittedData[$_key]);
            return true;
        } catch (\Exception $e) {
            return $this->addErrorMessage('siteNotFound', $_key);
        }
    }

    private function validateCategoryRulesField(array &$submittedData, ConfigurationKey $key)
    {
        $_key = $key->value;
        $categoryRepository = GeneralUtility::makeInstance(CategoryRepository::class);
        try {
            $decoded = json_decode($submittedData[$_key], true);
            if ($decoded == null) {
                return $this->addErrorMessage('jsonInvalid', $_key);
            }
            foreach ($decoded as $categoryUid => $rules) {
                if ($categoryRepository->findByUid($categoryUid) == null) {
                    return $this->addErrorMessage('categoryUidNotFound', $_key, $categoryUid);
                }
                if (!is_array($rules)) {
                    return $this->addErrorMessage('wrongRuleDeclaration', $_key, $categoryUid);
                } else {
                    foreach ($rules as $source => $regEx) {
                        if ($source != 'subject' && $source != 'content') {
                            return $this->addErrorMessage('wrongSourceKey', $_key, $source);
                        }
                        if (empty($regEx) || !is_string($regEx)) {
                            return $this->addErrorMessage('invalidRule', $_key, $regEx);
                        } else {
                            try {
                                if (preg_match("/$regEx/", null) === false) {
                                    return $this->addErrorMessage('invalidRule', $_key, $regEx);
                                }
                            } catch (\Exception $exception) {
                                return $this->addErrorMessage('invalidRule', $_key, $regEx);
                            }
                        }
                    }
                }
            }
            return true;
        } catch (\Exception $e) {
            debug($e);
            return false;
        }
    }


    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $schedulerModule)
    {
        $result = true;

        $result &= $this->validateIntegerAdditionalField($submittedData, ConfigurationKey::NEWS_STORAGE_PAGE_ID)
            && $this->validatePageUidAdditionalField($submittedData, ConfigurationKey::NEWS_STORAGE_PAGE_ID);
        $result &= $this->validateRequiredField($submittedData, ConfigurationKey::BODY_TEXT_TEMPLATE);
        $result &= $this->validateRequiredField($submittedData, ConfigurationKey::CATEGORY_RULES)
            && $this->validateCategoryRulesField($submittedData, ConfigurationKey::CATEGORY_RULES);
        $result &= $this->validateRequiredField($submittedData, ConfigurationKey::IMAP_SERVER);
        $result &= $this->validateIntegerAdditionalField($submittedData, ConfigurationKey::IMAP_PORT);
        $result &= $this->validateRequiredField($submittedData, ConfigurationKey::IMAP_ACCOUNT);
        $result &= $this->validateRequiredField($submittedData, ConfigurationKey::IMAP_PASSWORD);

        return $result;
    }

    public function saveAdditionalFields(array $submittedData, AbstractTask $task)
    {
        $task->set($submittedData);
    }

    protected function getLanguageService(): ?LanguageService
    {
        return $GLOBALS['LANG'] ?? null;
    }
}
