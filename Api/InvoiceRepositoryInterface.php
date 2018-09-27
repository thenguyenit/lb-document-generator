<?php

namespace FossilEcommerce\LBDocumentGenerator\Api;

interface InvoiceRepositoryInterface
{
    /**
     * Get Json to create Shipment on Logic Broker
     *
     * @param string $orderNumber
     * @param \FossilEcommerce\LBDocumentGenerator\Api\Data\Invoice\ItemInterface[] $items
     * @param string $trackingNumber
     * @param string $carrierNumber
     * @param string $totalInvoice
     * @param float $amountToCharge
     * @return string
     */
    public function getInvoiceShipmentJson(
        $orderNumber,
        $items = [],
        $trackingNumber = null,
        $carrierNumber = null,
        $totalInvoice = null,
        $amountToCharge = null
    );

}