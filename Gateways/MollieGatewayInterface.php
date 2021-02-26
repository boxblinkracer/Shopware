<?php

namespace MollieShopware\Gateways;


use Mollie\Api\Resources\Issuer;

interface MollieGatewayInterface
{

    /**
     * @param $orderId
     * @return mixed
     */
    public function getOrder($orderId);

    /**
     * @param $paymentId
     * @return mixed
     */
    public function getPayment($paymentId);

    /**
     * @return Issuer[]
     */
    public function getIdealIssuers() : array;

}
