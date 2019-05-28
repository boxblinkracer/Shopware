<?php

// Mollie Shopware Plugin Version: 1.4.6

namespace Mollie\Api\Endpoints;

use Mollie\Api\Resources\BaseCollection;
abstract class CollectionEndpointAbstract extends \Mollie\Api\Endpoints\EndpointAbstract
{
    /**
     * Get the collection object that is used by this API endpoint. Every API endpoint uses one type of collection object.
     *
     * @param int $count
     * @param object[] $_links
     *
     * @return BaseCollection
     */
    protected abstract function getResourceCollectionObject($count, $_links);
}
