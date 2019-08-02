<?php

// Mollie Shopware Plugin Version: 1.4.10

namespace MollieShopware\Components\Services;

use MollieShopware\Components\Logger;

class BasketService
{
    /** @var \MollieShopware\Components\Config $config */
    protected $config;

    /** @var \Shopware\Components\Model\ModelManager $modelManager */
    protected $modelManager;

    /** @var \Shopware_Components_Modules $basketModule */
    protected $basketModule;

    /** @var \MollieShopware\Components\Services\OrderService $orderService */
    protected $orderService;

    /**
     * Constructor
     *
     * @param \Shopware\Components\Model\ModelManager $modelManager
     */
    public function __construct(\Shopware\Components\Model\ModelManager $modelManager)
    {
        $this->config = Shopware()->Container()
            ->get('mollie_shopware.config');

        $this->modelManager = $modelManager;

        $this->basketModule = Shopware()->Modules()->Basket();

        $this->orderService = Shopware()->Container()
            ->get('mollie_shopware.order_service');
    }

    /**
     * Restore Basket
     *
     * @param \Shopware\Models\Order\Order|int $orderId
     *
     * @throws \Exception
     */
    public function restoreBasket($orderId)
    {
        // get the order model
        if ($orderId instanceof \Shopware\Models\Order\Order) {
            $order = $orderId;
        }
        else {
            $order = $this->orderService->getOrderById($orderId);
        }

        if (!empty($order)) {
            // get order details
            $orderDetails = $order->getDetails();

            if (!empty($orderDetails)) {
                // clear basket
                $this->basketModule->clearBasket();

                // set comment
                $commentText = "The payment on this order failed, the customer is retrying. ";

                // iterate over products and add them to the basket
                foreach ($orderDetails as $orderDetail) {
                    $result = null;

                    if ($orderDetail->getMode() == 2) {
                        // get voucher from database
                        $voucher = $this->getVoucherById($orderDetail->getArticleId());

                        if (!empty($voucher)) {
                            // remove voucher from original order
                            $this->removeOrderDetail($orderDetail->getId());

                            // set comment
                            $commentText = $commentText . "Voucher code (" . $voucher->getVoucherCode() .
                                ") is removed van this order and reused in the newly created basket. ";

                            // add voucher to basket
                            $this->basketModule->sAddVoucher($voucher->getVoucherCode());

                            // restore order price
                            $order->setInvoiceAmount($order->getInvoiceAmount() - $orderDetail->getPrice());
                        }
                    } else {
                        // add product to basket
                        $this->basketModule->sAddArticle(
                            $orderDetail->getArticleNumber(),
                            $orderDetail->getQuantity()
                        );
                    }
                }

                // append internal comment
                if (!strstr($order->getInternalComment(), $commentText))
                    $order = $this->appendInternalComment($order, $commentText);

                // recalculate order
                $order->calculateInvoiceAmount();

                /** @var \Shopware\Models\Order\Status $statusCanceled */
                $statusCanceled = Shopware()->Models()->getRepository(
                    \Shopware\Models\Order\Status::class
                )->find(
                    \Shopware\Models\Order\Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED
                );

                // set payment status
                if ($this->config->cancelFailedOrders())
                    $order->setPaymentStatus($statusCanceled);

                // save order
                $this->modelManager->persist($order);
                $this->modelManager->flush();
            }
        }

        // refresh the basket
        $this->basketModule->sRefreshBasket();
    }

    /**
     * Get a voucher by it's id
     *
     * @param int $voucherId
     *
     * @return \Shopware\Models\Voucher\Voucher $voucher
     *
     * @throws \Exception
     */
    public function getVoucherById($voucherId)
    {
        $voucher = null;

        try {
            /** @var \Shopware\Models\Voucher\Repository $voucherRepo */
            $voucherRepo = $this->modelManager->getRepository(
                \Shopware\Models\Voucher\Voucher::class
            );

            /** @var \Shopware\Models\Voucher\Voucher $voucher */
            $voucher = $voucherRepo->findOneBy([
                'id' => $voucherId
            ]);
        }
        catch (\Exception $ex) {
            Logger::log(
                'error',
                $ex->getMessage(),
                $ex
            );
        }

        return $voucher;
    }

    /**
     * Remove detail from order
     *
     * @param int $orderDetailId
     *
     * @return int $result
     *
     * @throws \Exception
     */
    public function removeOrderDetail($orderDetailId)
    {
        $result = null;

        try {
            // init db
            $db = Shopware()->Container()->get('db');

            // prepare database statement
            $q = $db->prepare('
                DELETE FROM 
                s_order_details 
                WHERE id=?
            ');

            // execute sql query
            $result = $q->execute([
                $orderDetailId,
            ]);
        }
        catch (\Exception $ex) {
            Logger::log(
                'error',
                $ex->getMessage(),
                $ex
            );
        }

        return $result;
    }

    /**
     * Reset the order quantity for a canceled order
     *
     * @param \Shopware\Models\Order\Detail $orderDetail
     *
     * @return \Shopware\Models\Order\Detail $orderDetail
     *
     * @throws \Exception
     */
    public function resetOrderDetailQuantity(\Shopware\Models\Order\Detail $orderDetail) {
        // variables
        $article = null;
        $orderedQuantity = $orderDetail->getQuantity();

        // reset quantity
        $orderDetail->setQuantity(0);

        // build order detail repository
        $articleDetailRepo = Shopware()->Models()->getRepository(
            \Shopware\Models\Article\Detail::class
        );

        try {
            $article = $articleDetailRepo->findOneBy([
                'number' => $orderDetail->getArticleNumber()
            ]);
        } catch (\Exception $ex) {
            // write exception to log
            Logger::log(
                'error',
                $ex->getMessage(),
                $ex
            );
        }

        // restore stock
        if (!empty($article)) {
            $article->setInStock($article->getInStock() + $orderedQuantity);

            Shopware()->Models()->persist($article);
        }

        return $orderDetail;
    }

    /**
     * Append internal comment on order
     *
     * @param \Shopware\Models\Order\Order $order
     * @param string $text
     *
     * @return \Shopware\Models\Order\Order $order;
     */
    public function appendInternalComment(\Shopware\Models\Order\Order $order, $text)
    {
        $comment = $order->getInternalComment();
        $comment = $comment . (strlen($comment) ? "\n\n" : "") . $text;

        return $order->setInternalComment($comment);
    }
}