<?php
namespace Cylancer\CyMailToNews\Task;


use Cylancer\CyMailToNews\Domain\Model\ConfigurationKey;
use GeorgRinger\News\Domain\Model\News;
use GeorgRinger\News\Domain\Repository\CategoryRepository;
use GeorgRinger\News\Domain\Repository\NewsRepository;


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

    public function init(): void
    {
        $this->persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);

        $this->newsRepository = GeneralUtility::makeInstance(NewsRepository::class);
        $this->newsRepository->injectPersistenceManager($this->persistenceManager);

        $querySettings = $this->newsRepository->createQuery()->getQuerySettings();
        // $querySettings->setStoragePageIds([
        //     $this->get(ConfigurationKey::NEWS_STORAGE_PAGE_ID)
        // ]);
        $this->newsRepository->setDefaultQuerySettings($querySettings);
        $this->categoryRepository = GeneralUtility::makeInstance(CategoryRepository::class);

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
                $overview = imap_fetch_overview($inbox, $email_number, 0);
                $message = imap_fetchbody($inbox, $email_number, 1);

                /**@var News */
                $n = new News();
                $n->setType(0);
                $n->setTitle($this->get(ConfigurationKey::TITLE_TEMPLATE));
                $n->setBodytext($this->get(ConfigurationKey::BODY_TEXT_TEMPLATE));
                $n->setPid($this->get(ConfigurationKey::NEWS_STORAGE_PAGE_ID));
                $n->setDatetime(new \DateTime($overview[0]->date));


                $categories = $this->addCategories($n, $overview[0]->subject, $message);
                if (count($categories) > 0) {
                    $n->setTitle($categories[0]->getTitle());
                }


                $this->newsRepository->add($n);
                imap_setflag_full($inbox, $email_number, '\\', ST_UID);
            }
        }

        imap_close($inbox);


        $this->persistenceManager->persistAll();
        return true;
    }

    private function addCategories(News &$news, string $subject, string $content): array
    {
        $subjectDecoded = imap_mime_header_decode($subject);
        if ($subjectDecoded !== false) {
            $subject = $subjectDecoded[0]->text;
        }

        $categoryRules = json_decode($this->get(ConfigurationKey::CATEGORY_RULES), true);
        $categories = [];
        foreach ($categoryRules as $cUid => $rules) {
            $categoryUid = intval($cUid);
            foreach ($rules as $source => $rule) {
                $data = $source === 'subject' ? $subject : ($source === 'content' ? $content : null);
                if (preg_match("/$rule/", $data)) {
                    /**@var Category */
                    $category = $this->categoryRepository->findByUid($categoryUid);
                    /**@var Category */
                    $categories[] = $category;
                    $news->addCategory($category);
                }
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


    public function get(ConfigurationKey $key): int|string
    {
        return $this->configuration[$key->value];
    }

    public function set(array $data): void
    {
        foreach (ConfigurationKey::cases() as $key) {
            $configuration[$key->value] = $data[$key->value];
        }
    }


}
