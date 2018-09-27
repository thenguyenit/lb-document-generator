<?php

namespace FossilEcommerce\LBDocumentGenerator\Api\Data\Returns;

/**
 * Interface Invoice Item for LogicBroker.
 */
interface ItemInterface
{
    /**
     * SKU product line item Invoice
     */
    const SKU = 'SupplierSKU';

    /*
     * Product Price from SAP
     */
    const PRICE = 'SI_ItemPrice';

    /**
     * Quantity of line item from SAP
     */
    const QTY = 'Qty';

    /**
     * Quantity Canceled of line item from SAP
     */
    const QTY_CANCELED = 'QtyCancelled';

    /**
     * Shipping fee of line item
     */
    const SHIPPING_FEE = 'SI_ItemShpFee';

    /**
     * Tax of line item from SAP
     */
    const TAX = 'SI_ItemTax';

    /**
     * Line item total - include all field
     */
    const ROW_TOTAL = 'SI_ItemTotal';

    /**
     * @return string
     */
    public function getSku();

    /**
     * @param string $sku
     * @return ItemInterface
     */
    public function setSku($sku);

    /**
     * @return float
     */
    public function getPrice();

    /**
     * @param float $price
     * @return ItemInterface
     */
    public function setPrice($price);

    /**
     * @return int
     */
    public function getQty();

    /**
     * @param int $qty
     * @return ItemInterface
     */
    public function setQty($qty);

    /**
     * @return int
     */
    public function getQtyCanceled();

    /**
     * @param int $qty_canceled
     * @return ItemInterface
     */
    public function setQtyCanceled($qty_canceled);

    /**
     * @return float
     */
    public function getTax();

    /**
     * @param float $tax
     * @return ItemInterface
     */
    public function setTax($tax);

    /**
     * @return float
     */
    public function getRowTotal();

    /**
     * @param float $row_total
     * @return ItemInterface
     */
    public function setRowTotal($row_total);
}