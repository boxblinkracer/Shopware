<?php

// Mollie Shopware Plugin Version: 1.4.6

namespace Mollie\Api\Endpoints;

use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\Resources\Method;
use Mollie\Api\Resources\Organization;
use Mollie\Api\Resources\OrganizationCollection;
class OrganizationEndpoint extends \Mollie\Api\Endpoints\CollectionEndpointAbstract
{
    protected $resourcePath = "organizations";
    /**
     * @return Organization
     */
    protected function getResourceObject()
    {
        return new \Mollie\Api\Resources\Organization($this->client);
    }
    /**
     * Get the collection object that is used by this API endpoint. Every API endpoint uses one type of collection object.
     *
     * @param int $count
     * @param object[] $_links
     *
     * @return OrganizationCollection
     */
    protected function getResourceCollectionObject($count, $_links)
    {
        return new \Mollie\Api\Resources\OrganizationCollection($this->client, $count, $_links);
    }
    /**
     * Retrieve an organization from Mollie.
     *
     * Will throw a ApiException if the organization id is invalid or the resource cannot be found.
     *
     * @param string $organizationId
     * @param array $parameters
     * @return Method
     * @throws ApiException
     */
    public function get($organizationId, array $parameters = [])
    {
        if (empty($organizationId)) {
            throw new \Mollie\Api\Exceptions\ApiException("Organization ID is empty.");
        }
        return parent::rest_read($organizationId, $parameters);
    }
    /**
     * Retrieve the current organization from Mollie.
     *
     * @param array $parameters
     * @return Method
     * @throws ApiException
     */
    public function current(array $parameters = [])
    {
        return parent::rest_read('me', $parameters);
    }
}
