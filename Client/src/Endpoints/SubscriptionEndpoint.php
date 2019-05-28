<?php

// Mollie Shopware Plugin Version: 1.4.6

namespace Mollie\Api\Endpoints;

use Mollie\Api\Resources\BaseCollection;
use Mollie\Api\Resources\BaseResource;
use Mollie\Api\Resources\Customer;
use Mollie\Api\Resources\Subscription;
use Mollie\Api\Resources\SubscriptionCollection;
class SubscriptionEndpoint extends \Mollie\Api\Endpoints\CollectionEndpointAbstract
{
    protected $resourcePath = "customers_subscriptions";
    /**
     * Get the object that is used by this API endpoint. Every API endpoint uses one type of object.
     *
     * @return Subscription
     */
    protected function getResourceObject()
    {
        return new \Mollie\Api\Resources\Subscription($this->client);
    }
    /**
     * Get the collection object that is used by this API endpoint. Every API endpoint uses one type of collection object.
     *
     * @param int $count
     * @param object[] $_links
     *
     * @return SubscriptionCollection
     */
    protected function getResourceCollectionObject($count, $_links)
    {
        return new \Mollie\Api\Resources\SubscriptionCollection($this->client, $count, $_links);
    }
    /**
     * Create a subscription for a Customer
     *
     * @param Customer $customer
     * @param array $options
     * @param array $filters
     *
     * @return Subscription
     */
    public function createFor(\Mollie\Api\Resources\Customer $customer, array $options = [], array $filters = [])
    {
        $this->parentId = $customer->id;
        return parent::rest_create($options, $filters);
    }
    /**
     * @param Customer $customer
     * @param string $subscriptionId
     * @param array $parameters
     *
     * @return Subscription
     */
    public function getFor(\Mollie\Api\Resources\Customer $customer, $subscriptionId, array $parameters = [])
    {
        $this->parentId = $customer->id;
        return parent::rest_read($subscriptionId, $parameters);
    }
    /**
     * @param Customer $customer
     * @param string $from The first resource ID you want to include in your list.
     * @param int $limit
     * @param array $parameters
     *
     * @return SubscriptionCollection
     */
    public function listFor(\Mollie\Api\Resources\Customer $customer, $from = null, $limit = null, array $parameters = [])
    {
        $this->parentId = $customer->id;
        return parent::rest_list($from, $limit, $parameters);
    }
    /**
     * @param Customer $customer
     * @param string $subscriptionId
     *
     * @param array $data
     * @return null
     * @throws \Mollie\Api\Exceptions\ApiException
     */
    public function cancelFor(\Mollie\Api\Resources\Customer $customer, $subscriptionId, array $data = [])
    {
        $this->parentId = $customer->id;
        return parent::rest_delete($subscriptionId, $data);
    }
}
