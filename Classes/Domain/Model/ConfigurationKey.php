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

enum ConfigurationKey
{
    case NEWS_STORAGE_PAGE_ID = 'newsStoragePageId';
    case TITLE_TEMPLATE = 'titleTemplate';
    case BODY_TEXT_TEMPLATE = 'bodyTextTemplate';
    case FILTER_RULES = 'filterRules';
    case CATEGORY_RULES = 'categoryRules';
    case IMAP_SERVER = 'imapServer';
    case IMAP_PORT = 'imapPort';
    case IMAP_ACCOUNT = 'imapAccount';
    case IMAP_PASSWORD = 'imapPassword';



    public function getDefault(): string|int
    {
        return match ($this) {
            ConfigurationKey::CATEGORY_RULES => '[]',
            ConfigurationKey::IMAP_PORT => 993,
            default => ''
        };
    }

}

