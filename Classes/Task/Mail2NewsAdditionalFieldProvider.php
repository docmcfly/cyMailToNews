<?php
namespace Cylancer\CyMailToNews\Task;


use Cylancer\CyMailToNews\Domain\Model\ConfigurationKey;
use GeorgRinger\News\Domain\Repository\CategoryRepository;

use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
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

    private const TRANSLATION_PREFIX = 'LLL:EXT:cy_mail_to_news/Resources/Private/Language/locallang_task_mail2news.xlf:task.mail2news.property.';

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

        $fieldCode = '<input type="number" ' . ($key->isRequired() ? 'min="0" max="99999" ' : '') . ' class="form-control" name="tx_scheduler[' . $_key . ']" id="' . $fieldID . '" value="' . htmlspecialchars( $taskInfo[$_key] ). '" ' . ($key->isRequired() ? 'required ' : '') . '>';
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
        $fieldCode = '<input type="text" class="form-control" name="tx_scheduler[' . $_key . ']" id="' . $fieldID . '" value="' . htmlspecialchars( $taskInfo[$_key] ). '" ' . ($key->isRequired() ? 'required ' : '') . '>';
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
        $fieldCode = '<textarea  class="form-control" style="font-family: \'DejaVu Sans Mono\', monospace; height: 20em;" name="tx_scheduler[' . $_key . ']" id="' . $fieldID . '" ' . ($key->isRequired() ? 'required ' : '') . '>' .  $taskInfo[$_key] . '</textarea>';
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
        $fieldCode = '<input type="password" class="form-control" name="tx_scheduler[' . $_key . ']" id="' . $fieldID . '" value="' . htmlspecialchars( $taskInfo[$_key]) . '" ' . ($key->isRequired() ? 'required ' : '') . '>';
        $additionalFields[$fieldID] = [
            'code' => $fieldCode,
            'label' => Mail2NewsAdditionalFieldProvider::TRANSLATION_PREFIX . $_key,
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => $fieldID
        ];
    }


    private function initBooleanAddtionalField(array &$taskInfo, $task, ConfigurationKey $key, array &$additionalFields)
    {
        $this->setCurrentKey($taskInfo, $task, $key);
        $_key = $key->value;
        // Write the code for the field
        $fieldID = "task_$_key";
        $fieldCode = '<input type="checkbox" class="form-check" name="tx_scheduler[' . $_key . ']" id="' . $fieldID . '" value="true" ' . ($taskInfo[$_key] ? 'checked' : '') . ' ' . ($key->isRequired() ? 'required ' : '') . '>';
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
        $this->initBooleanAddtionalField($taskInfo, $task, ConfigurationKey::DRAFT_MODE, $additionalFields);
        $this->initStringAddtionalField($taskInfo, $task, ConfigurationKey::TITLE_TEMPLATE, $additionalFields);
        $this->initTextAddtionalField($taskInfo, $task, ConfigurationKey::BODY_TEXT_TEMPLATE, $additionalFields);
        $this->initTextAddtionalField($taskInfo, $task, ConfigurationKey::FILTER_RULES, $additionalFields);
        $this->initTextAddtionalField($taskInfo, $task, ConfigurationKey::CATEGORY_RULES, $additionalFields);
        $this->initStringAddtionalField($taskInfo, $task, ConfigurationKey::IMAP_SERVER, $additionalFields);
        $this->initIntegerAddtionalField($taskInfo, $task, ConfigurationKey::IMAP_PORT, $additionalFields);
        $this->initStringAddtionalField($taskInfo, $task, ConfigurationKey::IMAP_ACCOUNT, $additionalFields);
        $this->initPasswordAddtionalField($taskInfo, $task, ConfigurationKey::IMAP_PASSWORD, $additionalFields);
        $this->initIntegerAddtionalField($taskInfo, $task, ConfigurationKey::DEFAULT_MEDIA, $additionalFields);
        $this->initBooleanAddtionalField($taskInfo, $task, ConfigurationKey::IMPORT_ATTACHMENTS, $additionalFields);
        $this->initIntegerAddtionalField($taskInfo, $task, ConfigurationKey::FILE_STORAGE, $additionalFields);
        $this->initStringAddtionalField($taskInfo, $task, ConfigurationKey::ATTACHMENT_FOLDER, $additionalFields);
        $this->initStringAddtionalField($taskInfo, $task, ConfigurationKey::ALLOWED_ATTACHMENT_TYPES, $additionalFields);

        return $additionalFields;
    }

    private function addErrorMessage(string $msgType, ConfigurationKey $key, mixed ...$params): bool
    {
        $msg = $this->getLanguageService()->sL(Mail2NewsAdditionalFieldProvider::TRANSLATION_PREFIX . 'error.' . $msgType . '.' . $key->value);
        if (empty($msg)) {
            $this->addMessage('error.' . $msgType . '.' . $key->value, ContextualFeedbackSeverity::ERROR);
            return false;
        }

        $this->addMessage(
            vsprintf($msg, $params),
            ContextualFeedbackSeverity::ERROR
        );
        return false;
    }

    private function validateIntegerAdditionalField(array &$submittedData, ConfigurationKey $key)
    {
        $_key = $key->value;
        $submittedData[$_key] = (int) $this->get_submitted_data($submittedData, $key);
        if ($submittedData[$_key] < 0) {
            return $this->addErrorMessage('invalid', $key);
        }

        return true;
    }

    private function validatePageUidAdditionalField(array &$submittedData, ConfigurationKey $key)
    {
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        if (count($pageRepository->getPage($this->get_submitted_data($submittedData, $key), true)) === 0) {
            return $this->addErrorMessage('invalid', $key);
        }

        return true;
    }

    private function validateRequiredField(array &$submittedData, ConfigurationKey $key)
    {
        if (empty($this->get_submitted_data($submittedData, $key))) {
            return $this->addErrorMessage('required', $key);
        }
        return true;
    }

    private function validateSitedField(array &$submittedData, ConfigurationKey $key)
    {
        try {
            GeneralUtility::makeInstance(SiteFinder::class)
                ->getSiteByIdentifier(
                    $this->get_submitted_data($submittedData, $key)
                );
            return true;
        } catch (\Exception $e) {
            return $this->addErrorMessage('siteNotFound', $key);
        }
    }

    private function validateExistsFolderField(array &$submittedData, ConfigurationKey $key)
    {
        $value = $this->get_submitted_data($submittedData, ConfigurationKey::ATTACHMENT_FOLDER);
        $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
        $fileStorageUid = $this->get_submitted_data($submittedData, ConfigurationKey::FILE_STORAGE);

        $fileStorage = $fileStorageUid === 0 || $fileStorageUid === ''
            ? $storageRepository->getDefaultStorage()
            : $storageRepository->getStorageObject($fileStorageUid);

        if (!$fileStorage->hasFolder($value)) {
            return $this->addErrorMessage('folderNotExists', $key, $value);

        }
        return true;
    }

    private function validateCategoryRulesField(array &$submittedData, ConfigurationKey $key)
    {
        $categoryRepository = GeneralUtility::makeInstance(CategoryRepository::class);
        try {
            $value = $this->get_submitted_data($submittedData, $key);
            if (trim($value) == '') {
                return true;
            }
            $decoded = json_decode($value, true);
            if ($decoded == null) {
                return $this->addErrorMessage('jsonInvalid', $key);
            }
            if (empty($decoded)) {
                return true;
            }
            foreach ($decoded as $categoryUid => $rules) {
                if ($categoryRepository->findByUid($categoryUid) == null) {
                    return $this->addErrorMessage('categoryUidNotFound', $key, $categoryUid);
                }
                if (!is_array($rules)) {
                    return $this->addErrorMessage('wrongRuleDeclaration', $key, $categoryUid);
                } else {
                    foreach ($rules as $regEx) {
                        if (empty($regEx) || !is_string($regEx)) {
                            return $this->addErrorMessage('invalidRule', $key, '"' . $regEx . "'");
                        } else {
                            try {
                                if (preg_match("/$regEx/", null) === false) {
                                    return $this->addErrorMessage('invalidRule', $key, $regEx);
                                }
                            } catch (\Exception $exception) {
                                return $this->addErrorMessage('invalidRule', $key, $regEx);
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


    private function validateFilterRulesField(array &$submittedData, ConfigurationKey $key)
    {
        try {
            $value = $this->get_submitted_data($submittedData, $key);
            if (trim($value) == '') {
                return true;
            }
            $decoded = json_decode($value, true);
            if ($decoded == null) {
                return $this->addErrorMessage('jsonInvalid', $key);
            }
            if (empty($decoded)) {
                return true;
            }

            foreach ($decoded as $regEx) {
                if (empty($regEx) || !is_string($regEx)) {
                    return $this->addErrorMessage('invalidRule', $key, json_encode($regEx));
                } else {
                    $tmp = '"' . $regEx . '"';
                    try {
                        if (preg_match("/$regEx/", null) === false) {
                            return $this->addErrorMessage('invalidRule', $key, params: $tmp);
                        }
                    } catch (\Exception $exception) {
                        return $this->addErrorMessage('invalidRule', $key, params: $tmp);
                    }
                }
            }
            return true;
        } catch (\Exception $e) {
            debug($e);
            return false;
        }
    }


    private function get_submitted_data(array &$submittedData, ConfigurationKey $key): mixed
    {
        return $submittedData[$key->value];
    }


    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $schedulerModule)
    {
        $result = true;

        foreach (ConfigurationKey::cases() as $key) {
            if ($key->isRequired()) {
                $result &= $this->validateRequiredField($submittedData, $key);
            }
        }

        $result &= $this->validateIntegerAdditionalField($submittedData, ConfigurationKey::NEWS_STORAGE_PAGE_ID)
            && $this->validatePageUidAdditionalField($submittedData, ConfigurationKey::NEWS_STORAGE_PAGE_ID);


        $result &= $this->validateCategoryRulesField($submittedData, ConfigurationKey::CATEGORY_RULES);
        $result &= $this->validateFilterRulesField($submittedData, ConfigurationKey::FILTER_RULES);

        if ($this->get_submitted_data($submittedData, ConfigurationKey::IMPORT_ATTACHMENTS)) {
            $result &= $this->validateRequiredField($submittedData, ConfigurationKey::ATTACHMENT_FOLDER)
                && $this->validateExistsFolderField($submittedData, ConfigurationKey::ATTACHMENT_FOLDER);
            $result &= $this->validateRequiredField($submittedData, ConfigurationKey::ALLOWED_ATTACHMENT_TYPES);

        }

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
