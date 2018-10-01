<?php

namespace FossilEcommerce\LBDocumentGenerator\Model;

use FossilEcommerce\LBDocumentGenerator\Api\GeneratorRepositoryInterface;
use FossilEcommerce\LBDocumentGenerator\Api\InvoiceRepositoryInterface;
use FossilEcommerce\LBDocumentGenerator\Api\ReturnRepositoryInterface;

class GeneratorRepository implements GeneratorRepositoryInterface
{
    protected $invoiceGeneratorRepository;
    protected $returnGeneratorRepository;

    public function __construct(InvoiceRepositoryInterface $invoiceRepository, ReturnRepositoryInterface $returnRepository)
    {
        $this->invoiceGeneratorRepository = $invoiceRepository;
        $this->returnGeneratorRepository = $returnRepository;
    }


    public function getReturnJson($orderNumber)
    {
        return $this->returnGeneratorRepository->getReturnJson($orderNumber);
    }

    public function getInvoiceShipmentJson(
        $orderNumber,
        $items = [],
        $trackingNumber = null,
        $carrierNumber = null,
        $totalInvoice = null,
        $amountToCharge = null
    )
    {
        return $this->invoiceGeneratorRepository->getInvoiceShipmentJson($orderNumber,
            $items = [],
            $trackingNumber = null,
            $carrierNumber = null,
            $totalInvoice = null,
            $amountToCharge = null);
    }
}