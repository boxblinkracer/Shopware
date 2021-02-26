<?php

namespace MollieShopware\Subscriber;

use Enlight\Event\SubscriberInterface;
use MollieShopware\Components\CurrentCustomer;
use MollieShopware\Components\iDEAL\iDEALInterface;
use Psr\Log\LoggerInterface;
use Shopware\Models\Customer\Customer;

class IdealIssuersSubscriber implements SubscriberInterface
{

    /**
     * @var iDEALInterface
     */
    private $iDeal;

    /**
     * @var CurrentCustomer
     */
    private $customers;

    /**
     * @var LoggerInterface
     */
    private $logger;


    /**
     * IdealIssuersSubscriber constructor.
     * @param iDEALInterface $iDEAL
     * @param CurrentCustomer $customers
     * @param LoggerInterface $logger
     */
    public function __construct(iDEALInterface $iDEAL, CurrentCustomer $customers, LoggerInterface $logger)
    {
        $this->iDeal = $iDEAL;
        $this->customers = $customers;
        $this->logger = $logger;
    }

    /**
     * @return string[]
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Checkout' => 'onCheckoutPaymentPage',
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Account' => 'onAccountPaymentPage',
            # -------------------------------------------------------------------------------------------------
            'Shopware_Modules_Admin_UpdatePayment_FilterSql' => 'onUpdatePaymentForUser',
        ];
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     */
    public function onCheckoutPaymentPage(\Enlight_Event_EventArgs $args)
    {
        $this->loadData($args);
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     */
    public function onAccountPaymentPage(\Enlight_Event_EventArgs $args)
    {
        $this->loadData($args);
    }

    /**
     * When a payment method is changed, the chosen payment method is saved on the user
     * For iDEAL an issuer should also be saved to the database
     * @param \Enlight_Event_EventArgs $args
     * @return mixed
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function onUpdatePaymentForUser(\Enlight_Event_EventArgs $args)
    {
        $query = $args->getReturn();

        $this->updateSelectedIssuer();

        return $query;
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     */
    private function loadData(\Enlight_Event_EventArgs $args)
    {
        /** @var \Enlight_Controller_Action $controller */
        $controller = $args->getSubject();

        /** @var \Enlight_View $view */
        $view = $controller->View();

        $view->addTemplateDir(__DIR__ . '/../Resources/views');


        try {

            $customer = $this->customers->getCurrent();

            if (!$customer instanceof Customer) {
                throw new \Exception('No active customer found for iDEAL list');
            }

            $idealIssuers = $this->iDeal->getIssuers($customer);

            # always update our selected issuer
            # if the page is rendered, we update the
            # used issuer in the database for the user
            $this->updateSelectedIssuer();

            $view->assign('mollieIdealIssuers', $idealIssuers);
            $view->assign('mollieIssues', false);

        } catch (\Exception $ex) {

            $this->logger->error(
                'Error when loading iDEAL issuers for payment screen!',
                array(
                    'error' => $ex->getMessage(),
                )
            );

            $view->assign('mollieIssues', true);
        }
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function updateSelectedIssuer()
    {
        $issuer = (string)Shopware()->Front()->Request()->getPost('mollie-ideal-issuer');

        if (empty($issuer)) {
            return;
        }

        $customer = $this->customers->getCurrent();

        if ($customer instanceof Customer) {
            $this->iDeal->updateCustomerIssuer($customer, $issuer);
        }
    }

}
