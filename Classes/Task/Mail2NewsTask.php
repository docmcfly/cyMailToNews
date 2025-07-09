<?php
namespace Cylancer\CyMailToNews\Task;


use Cylancer\CyMailToNews\Domain\Model\ConfigurationKey;
use GeorgRinger\News\Domain\Model\FileReference;
use GeorgRinger\News\Domain\Model\News;
use GeorgRinger\News\Domain\Repository\CategoryRepository;
use GeorgRinger\News\Domain\Repository\NewsRepository;


use IMAP\Connection;
use Symfony\Component\DependencyInjection\Compiler\ResolveFactoryClassPass;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Extbase\Validation\Validator\MimeTypeValidator;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

/**
 * This file is part of the "mail2news" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2025 C. Gogolin <service@cylancer.net>
 *
 */


class Mail2NewsTask extends AbstractTask
{

    private array $configuration = [];

    private ?NewsRepository $newsRepository = null;

    private ?CategoryRepository $categoryRepository = null;

    private ?PersistenceManager $persistenceManager;

    private StorageRepository $storageRepository;

    private ResourceFactory $resourceFactory;

    public function init(): void
    {
        $this->persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);

        $this->newsRepository = GeneralUtility::makeInstance(NewsRepository::class);
        $this->newsRepository->injectPersistenceManager($this->persistenceManager);

        $querySettings = $this->newsRepository->createQuery()->getQuerySettings();
        $querySettings->setStoragePageIds([
            $this->get(ConfigurationKey::NEWS_STORAGE_PAGE_ID)
        ]);
        $this->newsRepository->setDefaultQuerySettings($querySettings);
        $this->categoryRepository = GeneralUtility::makeInstance(CategoryRepository::class);
        $this->resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
        $this->storageRepository = GeneralUtility::makeInstance(StorageRepository::class);

    }


    public function execute(): bool
    {
        $this->init();
        $hostname = '{' . $this->get(ConfigurationKey::IMAP_SERVER) . ':' . $this->get(ConfigurationKey::IMAP_PORT) . '/imap/ssl}INBOX';
        $inbox = imap_open($hostname, $this->get(ConfigurationKey::IMAP_ACCOUNT), $this->get(ConfigurationKey::IMAP_PASSWORD))
            or die('Verbindung fehlgeschlagen: ' . imap_last_error());

        $emails = imap_search($inbox, 'UNSEEN');

        if ($emails) {

            foreach ($emails as $email_number) {


                $header = $this->parseMailHeaders(imap_fetchheader($inbox, $email_number));
                $structure = imap_fetchstructure($inbox, $email_number);
                $bodies = $this->get_bodies($inbox, $email_number, $structure);

                if ($this->filter($header, $bodies)) {

                    $date = new \DateTime($header['Date']);

                    $highPrio = (isset($header['X-Priority']) && intval($header['X-Priority']) < 3)
                        || (isset($header['Importance']) && strtolower($header['Importance']) === 'high')
                        || (isset($header['X-MSMail-Priority']) && strtolower($header['X-MSMail-Priority']) === 'high');

                    /** @var News */
                    $n = new News();

                    $n->setHidden($this->get_data(ConfigurationKey::DRAFT_MODE));

                    $categories = $this->addCategories($n, $header, $bodies);


                    $n->setType(0);
                    $n->setIstopnews($highPrio);
                    $title = htmlspecialchars_decode($this->render($this->get(ConfigurationKey::TITLE_TEMPLATE), $header, $bodies, $categories));
                    $n->setTitle(empty(trim($title)) ? 'none title ' : $title);
                    $bodyText = $this->clean_html($this->render($this->get(ConfigurationKey::BODY_TEXT_TEMPLATE), $header, $bodies, $categories));
                    $n->setBodytext($bodyText);
                    $n->setPid($this->get(ConfigurationKey::NEWS_STORAGE_PAGE_ID));
                    $n->setDatetime($date);


                    $setDefaultMedia = $this->get_data(ConfigurationKey::DEFAULT_MEDIA);

                    if ($this->get(ConfigurationKey::IMPORT_ATTACHMENTS)) {

                        $attachments = $this->extract_attachments($inbox, $email_number, $structure);
                        if (count($attachments) > 0) {

                            /** @var ResourceStorage */
                            $fileStorage = $this->get_data(ConfigurationKey::FILE_STORAGE) === 0 ? $this->storageRepository->getDefaultStorage() : $this->storageRepository->getStorageObject($this->get_data(ConfigurationKey::FILE_STORAGE));

                            $folderPath = $this->sanitizePath($fileStorage, $this->get(ConfigurationKey::ATTACHMENT_FOLDER) . $date->format("YmdHisu") . '_' . $header['Message-ID']);

                            if (!$fileStorage->hasFolder($folderPath)) {
                                $fileStorage->createFolder($folderPath);
                            }
                            $folder = $fileStorage->getFolder($folderPath);

                            $first = true;
                            foreach ($attachments as $attachment) {
                                if ($this->is_file_type_allowed($attachment['type'])) {

                                    $filename = $this->sanitizePath($fileStorage, $attachment['filename']);
                                    $attachment_data = $this->fetch($inbox, $email_number, $attachment);

                                    $file = null;
                                    if ($folder->hasFile($filename)) {
                                        $file = $folder->getFile($filename);
                                    } else {
                                        $file = $fileStorage->createFile($filename, $folder);
                                        $file->setContents($attachment_data);
                                    }

                                    $fr = new FileReference();
                                    $fr->setFileUid($file->getUid());
                                    $fr->setShowinpreview($first ? FileReference::VIEW_LIST_AND_DETAIL : FileReference::VIEW_DETAIL_ONLY);
                                    $fr->setTitle($filename);
                                    $fr->setDescription(null);
                                    $fr->setAlternative(null);
                                    $n->addFalMedia($fr);
                                    $setDefaultMedia = false;
                                }
                                $first = false;
                            }
                        }
                    }

                    if ($setDefaultMedia) {
                        try {
                            $defaultMediaFile = $this->resourceFactory->getFileObject($this->get(ConfigurationKey::DEFAULT_MEDIA));
                            $fr = new FileReference();
                            $fr->setFileUid($defaultMediaFile->getUid());
                            $fr->setShowinpreview(FileReference::VIEW_LIST_AND_DETAIL);
                            $fr->setTitle($defaultMediaFile->getName());
                            $fr->setDescription(null);
                            $fr->setAlternative(null);
                            $n->addFalMedia($fr);
                        } catch (FileDoesNotExistException $e) {
                            // do nothing
                        }


                    }
                    $this->newsRepository->add($n);
                    $this->persistenceManager->persistAll();
                } else {
                    imap_clearflag_full($inbox, $email_number, '\\Seen', ST_UID);
                }
            }
        }

        imap_close($inbox);


        return true;
    }

    private function is_file_type_allowed(string $type): bool
    {
        $type = trim(strtolower($type));
        foreach ($this->get_data(ConfigurationKey::ALLOWED_ATTACHMENT_TYPES) as $allowed_type) {
            if (trim(strtolower($allowed_type)) === $type) {
                return true;
            }
        }
        return false;


    }

    private function fetch(Connection $inbox, int $email_number, array $meta)
    {
        $tmp = imap_fetchbody($inbox, $email_number, $meta['part_number']);
        return match ($meta['encoding']) {
            ENCBASE64 => base64_decode($tmp),
            ENCQUOTEDPRINTABLE => quoted_printable_decode($tmp),
            default => $tmp
        };
    }

    private function sanitizePath(ResourceStorage $resourceStorage, string $path): string
    {
        $tmp = explode('/', $path);
        $return = '';
        foreach ($tmp as $part) {
            if ($part) {
                $return .= '/' . $resourceStorage->sanitizeFileName($part);
            }
        }
        return $return;

    }


    private function get_bodies($inbox, $email_id, $structure): array
    {
        $bodies = $this->extract_bodies($inbox, $email_id, $structure);
        $auto = 'plain';
        if (isset($bodies['plain']['part_number'])) {
            $content = $this->fetch($inbox, $email_id, $bodies['plain']);
            $bodies['plain']['content'] = $content;
        }
        if (isset($bodies['html']['part_number'])) {
            $content = $this->clean_html($this->fetch($inbox, $email_id, $bodies['html']));
            $bodies['html']['content'] = $content;
            $auto = 'html';

        }
        if ($auto !== 'auto') {
            $bodies['auto'] = [
                'type' => $auto
            ];
        }
        return $bodies;

    }

    private function clean_html(string $html): string
    {
        $bodyPos = stripos($html, '<body');
        if ($bodyPos === false) {
            return $html;
        }
        $html = substr($html, $bodyPos);
        $html = substr($html, 0, strripos($html, '</body'));
        $html = preg_replace('/<body([^>]*)>/is', '', $html);
        return $html;
    }


    // Funktion zum Extrahieren von Attachments
    private function extract_bodies(
        $inbox,
        $email_id,
        $structure,
        $part_number = 0,
        &$plain_found_on = 0,
        &$bodies = [
            'plain' => ['content' => ''],
            'html' => ['content' => ''],
            'auto' => ['content' => ''],
        ]
    ): array {


        if (isset($structure->parts) && is_array($structure->parts)) {
            foreach ($structure->parts as $key => $part) {
                $current_part_number = $part_number ? $part_number . '.' . ($key + 1) : ($key + 1);
                if ( // PLAIN
                    !isset($bodies['plain']['part_number'])
                    && $part->type == 0
                    && $part->ifsubtype
                    && strtolower($part->subtype) === 'plain'
                ) {
                    $bodies['plain']['part_number'] = $current_part_number;
                    $bodies['plain']['type'] = $part->type;
                    $bodies['plain']['encoding'] = $part->encoding;
                    $plain_found_on = $part_number;
                }

                if ( // HTML
                    $plain_found_on == $part_number
                    && !isset($bodies['html']['part_number'])
                    && $part->type == 0
                    && $part->ifsubtype
                    && strtolower($part->subtype) === 'html'
                ) {
                    $bodies['html']['part_number'] = $current_part_number;
                    $bodies['html']['type'] = $part->type;
                    $bodies['html']['encoding'] = $part->encoding;
                }

                if (
                    !isset($bodies['plain']['part_number'])
                    && isset($part->parts)
                    && is_array($part->parts)
                ) {
                    $this->extract_bodies($inbox, $email_id, $part, $current_part_number, $plain_found_on, $bodies);
                }
            }
        }
        return $bodies;
    }


    private function extract_attachments($inbox, $email_id, $structure, $part_number = 0): array
    {
        $attachments = [];

        if (isset($structure->parts) && is_array($structure->parts)) {
            foreach ($structure->parts as $key => $part) {
                $current_part_number = $part_number ? $part_number . '.' . ($key + 1) : ($key + 1);
                if (isset($part->dparameters) && is_array($part->dparameters)) {
                    foreach ($part->dparameters as $object) {
                        if (strtolower($object->attribute) == 'filename') {
                            $attachments[] = [
                                'part_number' => $current_part_number,
                                'filename' => $object->value,
                                'type' => $part->subtype,
                                'encoding' => $part->encoding
                            ];
                        }
                    }
                }
                if ($structure->ifsubtype && $structure->subtype !== 'ALTERNATIVE' && isset($part->parts) && is_array($part->parts)) {
                    $attachments = array_merge($attachments, $this->extract_attachments($inbox, $email_id, $part, $current_part_number));
                }
            }
        } else {
            // Wenn keine parts vorhanden sind, prüfen wir den Hauptteil der E-Mail
            if (isset($structure->dparameters) && is_array($structure->dparameters)) {
                foreach ($structure->dparameters as $object) {
                    if (strtolower($object->attribute) == 'filename') {
                        $attachments[] = [
                            'part_number' => $part_number,
                            'filename' => $object->value,
                            'type' => $structure->subtype,
                            'encoding' => $structure->encoding
                        ];
                    }
                }
            }
        }

        return $attachments;
    }

    private function decodeMimeWord($encodedText)
    {
        // Entferne das MIME-encoded-Word-Präfix und Suffix
        if (preg_match('/=\?([^?]+)\?([QqBb])\?(.+)\?=/', $encodedText, $matches)) {
            $encoding = $matches[2];
            $encodedString = $matches[3];

            if ($encoding == 'Q' || $encoding == 'q') {
                // Quoted-Printable Dekodierung
                $decodedString = quoted_printable_decode(str_replace('_', ' ', $encodedString));
            } elseif ($encoding == 'B' || $encoding == 'b') {
                // Base64 Dekodierung
                $decodedString = base64_decode($encodedString);
            } else {
                return $encodedText; // Unbekannte Kodierung
            }

            // Konvertiere den dekodierten String in HTML-Entitäten
            return htmlentities($decodedString, ENT_QUOTES, 'UTF-8');
        }

        return $encodedText; // Keine MIME-encoded-Word-Kodierung gefunden
    }

    private function decodeSubject($subject)
    {
        // Teile das Subject in MIME-encoded-Word und den restlichen Text
        $parts = preg_split('/(?<=\?=) /', $subject);
        $decodedParts = [];

        foreach ($parts as $part) {
            if (preg_match('/=\?([^?]+)\?([QqBb])\?(.+)\?=/', $part)) {
                $decodedParts[] = $this->decodeMimeWord($part);
            } else {
                $decodedParts[] = htmlentities($part, ENT_QUOTES, 'UTF-8');
            }
        }

        return implode(' ', $decodedParts);
    }


    private function render(string $template, array $header, array $body, array $categories): string
    {
        // simple
        $search = [
            '{body}',
            '{bodyHtml}',
            '{bodyPlain}'
        ];


        $replace = [
            $body['auto']['type'] == 'html'
            ? trim(str_replace(["\n\r", "\r", "\n"], ["\n", "\n", " "], $body['html']['content']))
            : htmlspecialchars($body['plain']['content']),

            trim(str_replace(["\n\r", "\r", "\n"], ["\n", "\n", " "], $body['html']['content'])),
            htmlspecialchars($body['plain']['content']),
        ];

        if (count($categories) > 0) {
            $search[] = '{categoryFirst}';
            $replace[] = $categories[0]->getTitle();

            $search[] = '{categoryList}';
            $tmp = [];
            foreach ($categories as $c) {
                $tmp[] = $c->getTitle();
            }
            $replace[] = implode(",", $tmp);
        } else {
            $search[] = '{categoryFirst}';
            $replace[] = '';

            $search[] = '{categoryList}';
            $replace[] = '';
        }

        foreach ($header as $headerKey => $headerValue) {
            $search[] = '{' . $headerKey . '}';
            if ($headerKey === 'Subject') {
                $replace[] = $this->decodeSubject($headerValue);
            } else {
                $replace[] = $headerValue;
            }
        }

        $tmp = str_replace($search, $replace, $template);

        foreach ($search as $c => $s) {
            $pattern = '/' . substr($s, 0, -1) . ' +pattern="(.+)" +replacement="(.+)" *}/';
            $matches = [];
            preg_match($pattern, $tmp, $matches);
            if (!empty($matches)) {
                $subPattern = '/' . $matches[1] . '/msu';
                $subReplacement = $matches[2];
                $count = 0;
                $replacement = preg_replace($subPattern, $subReplacement, $replace[$c], -1, $count);
                if ($count > 0) {
                    $tmp = str_replace($matches[0], $replacement, $tmp);
                } else {
                    $tmp = str_replace($matches[0], $replace[$c], $tmp);
                }
            }

        }

        return $tmp;
    }


    private function filter(array $header, array $bodies): bool
    {
        $filterRules = json_decode($this->get(ConfigurationKey::FILTER_RULES), true);

        foreach ($filterRules as $source => $rule) {
            $accept = true;
            switch ($source) {
                case "body":
                    $accept &= $this->hasMatch($rule, $bodies[$bodies['auto']['type']]['content']);
                    break;
                case "bodyHtml":
                    $accept &= $this->hasMatch($rule, $bodies['html']['content']);
                    break;
                case "bodyPlain":
                    $accept &= $this->hasMatch($rule, $bodies['plain']['content']);
                    break;
                default:
                    if (!isset($header[$source])) {
                        $accept &= false;
                        break;
                    }
                    $accept &= $this->hasMatch($rule, $header[$source]);

            }
            if (!$accept) {
                return false;
            }

        }
        return true;
    }

    private function hasMatch(string $regex, string $text): bool
    {
        return
            preg_match(
                '/' . $regex . '/',
                trim(str_replace(["\n\r", "\r", "\n"], ["\n", "\n", " "], $text))
            );
    }

    private function parseMailHeaders($rawHeaders): array
    {
        $headers = [];
        $lines = explode(separator: "\n", string: $rawHeaders);
        $currentHeader = '';
        foreach ($lines as $line) {
            if (strlen(trim($line)) == 0) {
                return $headers;
            }
            if (strlen(ltrim(($line))) < strlen($line)) {
                $headers[$currentHeader] .= trim($line);
            } else {
                $tmp = explode(':', trim($line), 2);
                $currentHeader = trim($tmp[0]);
                $headers[$currentHeader] = trim($tmp[1]);
            }
        }
        return $headers;
    }

    private function addCategories(News &$news, array $header, array $bodies): array
    {
        if (empty(trim($this->get_data(ConfigurationKey::CATEGORY_RULES)))) {
            return [];
        }
        $categoryRules = json_decode($this->get_data(ConfigurationKey::CATEGORY_RULES), true);

        $categories = [];
        foreach ($categoryRules as $cUid => $rules) {
            $categoryUid = intval($cUid);
            $accept = true;
            foreach ($rules as $source => $rule) {

                switch ($source) {
                    case "body":
                        $accept &= $this->hasMatch($rule, $bodies[$bodies['auto']['type']]['content']);
                        break;
                    case "bodyHtml":
                        $accept &= $this->hasMatch($rule, $bodies['html']['content']);
                        break;
                    case "bodyPlain":
                        $accept &= $this->hasMatch($rule, $bodies['plain']['content']);
                        break;
                    default:
                        if (!isset($header[$source])) {
                            $accept &= false;
                            break;
                        }
                        $accept &= $this->hasMatch($rule, $$header[$source]);
                }
            }
            if ($accept) {
                /**@var Category */
                $category = $this->categoryRepository->findByUid($categoryUid);
                /**@var Category */
                $categories[] = $category;
                $news->addCategory($category);
            }
        }
        return $categories;


    }


    public function getAdditionalInformation(): string
    {
        return 'News page id :' . $this->get(ConfigurationKey::NEWS_STORAGE_PAGE_ID) . //
            ' / Imap server: ' . $this->get(ConfigurationKey::IMAP_SERVER) . ':' . $this->get(ConfigurationKey::IMAP_PORT) . //
            ' / Imap account: ' . $this->get(ConfigurationKey::IMAP_ACCOUNT) . //
            '';
    }


    public function get(ConfigurationKey $key): int|string|array
    {
        return isset($this->configuration[$key->value]) ? $key->convert2ui($this->configuration[$key->value]) : $key->getDefault();
    }

    public function get_data(ConfigurationKey $key): int|string|array
    {
        return isset($this->configuration[$key->value]) ? $this->configuration[$key->value] : $key->getDefault();
    }
    public function set(array $data): void
    {
        foreach (ConfigurationKey::cases() as $key) {
            $this->configuration[$key->value] = isset($data[$key->value]) ? $key->convert2data($data[$key->value]) : $key->getDefault();
        }
    }


}
