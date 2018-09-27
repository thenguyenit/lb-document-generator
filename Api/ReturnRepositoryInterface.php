<?php

namespace FossilEcommerce\LBDocumentGenerator\Api;

interface ReturnRepositoryInterface
{
    /**
     * Get Json to create Shipment on Logic Broker
     *
     * @param string $orderNumber
     * @param \FossilEcommerce\LBDocumentGenerator\Api\Data\Returns\ItemInterface[] $items
     * @return string
     */
    public function getReturnJson(
        $orderNumber,
        $items = []
    );

}