<?php

namespace FossilEcommerce\LBDocumentGenerator\Api;

interface ReturnRepositoryInterface
{
    /**
     * Get Json to create Shipment on Logic Broker
     *
     * @param string $orderNumber
     * @return string
     */
    public function getReturnJson($orderNumber);

}