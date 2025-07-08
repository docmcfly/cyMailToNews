<?php
namespace Cylancer\CyMailToNews\Domain\Model;

/**
 * This file is part of the "mail2news" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2025 C. Gogolin <service@cylancer.net>
 *
 */

enum ConfigurationKey: string
{
    case DRAFT_MODE = 'draftMode';
    case NEWS_STORAGE_PAGE_ID = 'newsStoragePageId';
    case TITLE_TEMPLATE = 'titleTemplate';
    case BODY_TEXT_TEMPLATE = 'bodyTextTemplate';
    case FILTER_RULES = 'filterRules';
    case CATEGORY_RULES = 'categoryRules';
    case IMAP_SERVER = 'imapServer';
    case IMAP_PORT = 'imapPort';
    case IMAP_ACCOUNT = 'imapAccount';
    case IMAP_PASSWORD = 'imapPassword';

    case DEFAULT_MEDIA = 'defaultMedia';

    case IMPORT_ATTACHMENTS = 'importAttachmnents';
    case FILE_STORAGE = 'fileStorage';
    case ATTACHMENT_FOLDER = 'attachmentFolder';
    case ALLOWED_ATTACHMENT_TYPES = 'allowedAttachmentTypes';


    public function convert2data(string $string): string|int|bool|array
    {
        return match ($this) {
            self::NEWS_STORAGE_PAGE_ID => intval($string),
            self::IMAP_PORT => intval($string),
            self::DEFAULT_MEDIA => intval($string),
            self::FILE_STORAGE => intval($string),
            self::ALLOWED_ATTACHMENT_TYPES => explode(',',str_replace(" ", "", $string)),
            self::IMPORT_ATTACHMENTS => boolval($string),
            self::DRAFT_MODE => boolval($string),
            default => $string
        };
    } 

    public function isRequired():bool 
    {
        return match ($this) {
            self::NEWS_STORAGE_PAGE_ID => true,
            self::IMAP_SERVER => true,
            self::IMAP_PORT => true,
            self::IMAP_ACCOUNT => true,
            self::IMAP_PASSWORD => true,
            default => false
        }; 
    }


    public function convert2ui(string|int|bool|array $data): string|int|bool
    {
        return match ($this) {
            self::ALLOWED_ATTACHMENT_TYPES => implode(',', $data),
            self::DEFAULT_MEDIA => $data == 0 ? '' : strval($data),
            self::FILE_STORAGE => $data == 0 ? '' : strval($data),
            default => $data
        };
    }


    public function getDefault(): string|int|bool
    {
        return match ($this) {
            self::CATEGORY_RULES => '',
            self::DEFAULT_MEDIA => '',
            self::FILE_STORAGE => '',
            self::IMAP_PORT => 993,
            self::ALLOWED_ATTACHMENT_TYPES => 'pdf,jpeg,jpg,png',
            self::ATTACHMENT_FOLDER => '/mail2news',
            self::IMPORT_ATTACHMENTS => false,
            self::DRAFT_MODE => true,
            default => ''
        };
    }

}

