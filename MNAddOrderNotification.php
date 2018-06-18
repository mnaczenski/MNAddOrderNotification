<?php

namespace MNAddOrderNotification;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Models\Mail\Mail;
use Shopware\Components\Model\ModelManager;



class MNAddOrderNotification extends \Shopware\Components\Plugin
{

    /**
     * @var \Shopware\Models\Mail\Repository
     */
    private $mailRepository;



    public function activate(ActivateContext $context)
    {
        $context->scheduleClearCache(ActivateContext::CACHE_LIST_DEFAULT);
    }
    public function deactivate(DeactivateContext $context)
    {
        $context->scheduleClearCache(DeactivateContext::CACHE_LIST_DEFAULT);
    }
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Modules_Order_SendMail_BeforeSend' => 'onOrdermail',
        ];
    }

    public function install(InstallContext $context)
    {
        $this->createMailTemplate();
    }


    private function createMailTemplate()
    {
        $entityManger = $this->container->get('models');
        //check, if mail template already exists (because secureUninstall and update)
        $mail = $this->getMailRepository()->findOneBy(['name' => 'sORDERNOTIFICATION']);

        if ($mail) {
            return;
        }

        //Template
        $content = "Eine neue Bestellung ist eingegangen";

        $mail = new Mail();
        $mail->setName('sORDERNOTIFICATION');
        $mail->setFromMail('');
        $mail->setFromName('');
        $mail->setSubject('Eine neue Bestellung ist eingegangen');
        $mail->setContent($content);
        $mail->setMailtype(Mail::MAILTYPE_SYSTEM);

        $entityManger->persist($mail);
        $entityManger->flush($mail);
    }


    /**
     * Helper function to get the MailRepository
     *
     * @return \Shopware\Models\Mail\Repository
     */
    private function getMailRepository()
    {
        /** @var \Shopware\Components\Model\ModelManager $entityManager */
        $entityManger = $this->container->get('models');

        if (!$this->mailRepository) {
            $this->mailRepository = $entityManger->getRepository(Mail::class);
        }

        return $this->mailRepository;
    }





    public function onOrdermail(\Enlight_Event_EventArgs $args)
    {
        $context = $args->get('variables');
        try {
            /* @var $ordernotification \Zend_Mail */
            $ordernotification = $this->container->get('templateMail')->createMail('sORDERNOTIFICATION', $context);
            $ordernotification->addTo($this->container->get('config')->getByNamespace('MNAddOrderNotification','recipient'));
            $ordernotification->send();
        } catch (\Exception $e) {
            Shopware()->Container()->get('pluginlogger')->log(Logger::ERROR, $e->getMessage());
        }
    }
}