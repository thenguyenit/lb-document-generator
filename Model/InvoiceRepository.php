<?php

namespace FossilEcommerce\LBDocumentGenerator\Model;

use FossilEcommerce\LBDocumentGenerator\Api\InvoiceRepositoryInterface;

use \FossilGroup\LogicBroker\Helper\Data;
use Magento\Sales\Api\Data\OrderItemInterface;
use \Magento\Sales\Model\Order;
use \Magento\Sales\Model\OrderFactory;
use \FossilGroup\OrderTracking\Model\ResourceModel\Narvar\CollectionFactory as NarVarCollection;


class InvoiceRepository implements InvoiceRepositoryInterface
{
    /* Mapping Narvar table field names */
    const LOGIC_BLOCKER_CODE_FIELD = 'logic_blocker_code';
    const SAP_CARRIER_CODE_FIELD = 'sap_carrier_code';
    const MAGENTO_SHIPPING_METHOD_FIELD = 'ma_shipping_method';

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_order;

    /**
     * @var \FossilGroup\LogicBroker\Helper\Data
     */
    protected $_helper;

    /**
     * @var \FossilGroup\OrderTracking\Model\ResourceModel\Narvar\CollectionFactory
     */
    protected $_trackingMappingCollection;

    /**
     * PullInvoices constructor.
     *
     * @param \FossilGroup\LogicBroker\Helper\Data $helper
     * @param \Magento\Sales\Model\OrderFactory $order
     * @param NarVarCollection $trackingMappingCollection
     */
    public function __construct(
        Data $helper,
        OrderFactory $order,
        NarVarCollection $trackingMappingCollection
    )
    {
        $this->_helper                    = $helper;
        $this->_order                     = $order;
        $this->_trackingMappingCollection = $trackingMappingCollection;
    }

    /**
     * Find order on Magento site by Order Number
     *
     * @param $orderNumber
     * @return \Magento\Sales\Model\Order|\Magento\Sales\Model\OrderFactory
     */
    protected function findOrderByOrderNumber($orderNumber)
    {
        if ($orderNumber) {
            return $this->_order->create()->loadByIncrementId($orderNumber);
        }

        return null;
    }

    /**
     * Find order on Magento site by Order Number
     *
     * @param $shippingMethod
     * @return \Magento\Sales\Model\Order|\Magento\Sales\Model\OrderFactory
     */
    protected function findTrackingMappingByShippingMethod($shippingMethod)
    {
        if ($shippingMethod) {
            return $this->_trackingMapping->create()->loadByMaShippingMethod($shippingMethod);
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getInvoiceShipmentJson(
        $orderNumber,
        $items = [],
        $trackingNumber = null,
        $carrierNumber = null,
        $totalInvoice = null,
        $totalShippingFee = null,
        $amountToCharge = null
    )
    {
        /** @var  Order $order */
        $order = $this->findOrderByOrderNumber($orderNumber);

        if ($order->isEmpty()) {
            return __('Order number is not available');
        }

        /** @var string $shippingMethod */
        $shippingMethod = $order->getShippingMethod();

        /** @var NarVarCollection $orderTrackingCollection */
        $orderTrackingCollection = $this->_trackingMappingCollection->create()
            ->addFieldToSelect([self::SAP_CARRIER_CODE_FIELD, self::LOGIC_BLOCKER_CODE_FIELD])
            ->addFieldToFilter(self::MAGENTO_SHIPPING_METHOD_FIELD, $shippingMethod);
        $orderTracking           = $orderTrackingCollection->fetchItem();

        $orderItems = $order->getItems();
        $_json      = $this->generateShipmentJson($order, $trackingNumber, $totalInvoice, $amountToCharge, $orderTracking);
        // Get shipment info move to shipment lines
        $shipmentInfos = [
            'ClassCode'      => $orderTracking ? $orderTracking->getLogicBlockerCode() : '',
            'Qty'            => (int)$order->getTotalQtyOrdered(),
            'CarrierCode'    => $orderTracking ? $orderTracking->getSapCarrierCode() : '',
            'TrackingNumber' => (string)rand(1000000000000, 9999999999999),
            'ContainerType'  => 'CTN'
        ];
        // Get Shipment line items
        if (count($items)) {
            $_json['ShipmentLines'] = $this->generateCustomizeShipmentLinesJson($orderItems, $items, $shipmentInfos);
        } else {
            $_json['ShipmentLines'] = $this->generateShipmentLinesJson($orderItems, $shipmentInfos);
        }

        $_json       = json_encode($_json, JSON_PRETTY_PRINT);
        $json_string = stripslashes($_json);
        printf($json_string);
        exit();

    }

    /**
     * Generate Shipment lines of order in API shipment of LB
     * @param OrderItemInterface[] $orderItems
     * @param [] $shipmentInfos
     * @return array
     * @internal param $trackingNumber
     */
    public function generateShipmentLinesJson(array $orderItems, $shipmentInfos = [])
    {
        $result = [];

        // Get Shipment line items
        foreach ($orderItems as $orderItem) {
            $shipmentInfos['Qty']                = (int)$orderItem->getQtyOrdered();
            $_shipmentLine                       = [];
            $_shipmentLine['Weight']             = 0;
            $_shipmentLine['Cost']               = 0;
            $_shipmentLine['RetailPrice']        = 0;
            $_shipmentLine['ItemIdentifier']     = ['SupplierSKU' => $orderItem->getSku(), 'PartnerSKU' => $orderItem->getSku()];
            $_shipmentLine['ShipmentInfos'][]    = $shipmentInfos;
            $_shipmentLine['ExtendedAttributes'] = [
                [
                    'Value' => (string)$orderItem->getPrice(),
                    'Name'  => 'SI_ItemPrice'
                ],
                [
                    'Value' => '',
                    'Name'  => 'SI_ItemShpFee'
                ],
                [
                    'Value' => (string)$orderItem->getTaxAmount(),
                    'Name'  => 'SI_ItemTax'
                ],
                [
                    'Value' => (string)(($orderItem->getPrice() + $orderItem->getTaxAmount()) * $orderItem->getQtyOrdered()),
                    'Name'  => 'SI_ItemTotal'
                ],
                [
                    'Value' => '',
                    'Name'  => 'POSKU'
                ],
                [
                    'Value' => '',
                    'Name'  => 'POVariant'
                ],
                [
                    'Value' => (string)$orderItem->getItemId(),
                    'Name'  => 'item_id'
                ],
                [
                    'Value' => '',
                    'Name'  => 'GiftWrap'
                ],
                [
                    'Name' => 'GiftWrapSKU'
                ],
                [
                    'Value' => 'N',
                    'Name'  => 'FreeOfChargeIndicator'
                ],
                [
                    'Value' => 'N',
                    'Name'  => 'IsLinePriceForInformationOnly'
                ],
                [
                    'Value'   => (string)$orderItem->getPrice(),
                    'Name'    => 'DecimalPrice',
                    'Section' => 'BusinessRules'
                ],
                [
                    'Value'   => (string)$orderItem->getPrice(),
                    'Name'    => 'ItemSubTotal',
                    'Section' => 'BusinessRules'
                ],
                [
                    'Value'   => (string)$orderItem->getPrice(),
                    'Name'    => 'RetailPrice',
                    'Section' => 'BusinessRules'
                ]
            ];

            $result[] = $_shipmentLine;
        }

        return $result;
    }

    /**
     * Generate Shipment lines by customize input
     *
     * @param OrderItemInterface[] $orderLineItems
     * @param \FossilGroup\LogicBroker\Api\Data\Invoice\ItemInterface[] $customizeItems
     * @param $shipmentInfos
     * @return array
     * @internal param $trackingNumber
     */
    public function generateCustomizeShipmentLinesJson(array $orderLineItems, array $customizeItems, $shipmentInfos)
    {
        $result          = [];
        $_orderLineItems = $this->getOrderItemIDs($orderLineItems);
        // Get Shipment line items
        foreach ($customizeItems as $item) {

            $_qty                                = (int)$item->getQty();
            $_sku                                = $item->getSku();
            $_price                              = is_null($item->getPrice()) ? $_orderLineItems[$_sku]['price'] : $item->getPrice();
            $_tax                                = is_null($item->getTax()) ? $_orderLineItems[$_sku]['tax'] * $_qty : $item->getTax();
            $_itemTotal                          = ($_price + $_tax) * $_qty;
            $_shipmentLine                       = [];
            $_shipmentLine['Weight']             = 0;
            $_shipmentLine['Cost']               = 0;
            $_shipmentLine['RetailPrice']        = 0;
            $_shipmentLine['ItemIdentifier']     = ['SupplierSKU' => $item->getSku(), 'PartnerSKU' => $item->getSku()];
            $shipmentInfos['Qty']                = $_qty;
            $_shipmentLine['ShipmentInfos'][]    = $shipmentInfos;
            $_shipmentLine['ExtendedAttributes'] = [
                [
                    'Value' => $_price,
                    'Name'  => 'SI_ItemPrice'
                ],
                [
                    'Value' => '',
                    'Name'  => 'SI_ItemShpFee'
                ],
                [
                    'Value' => $_tax,
                    'Name'  => 'SI_ItemTax'
                ],
                [
                    'Value' => (string)$_itemTotal,
                    'Name'  => 'SI_ItemTotal'
                ],
                [
                    'Value' => '',
                    'Name'  => 'POSKU'
                ],
                [
                    'Value' => '',
                    'Name'  => 'POVariant'
                ],
                [
                    'Value' => (string)isset($_orderLineItems[$_sku]) ? $_orderLineItems[$_sku] : '',
                    'Name'  => 'item_id'
                ],
                [
                    'Value' => '',
                    'Name'  => 'GiftWrap'
                ],
                [
                    'Name' => 'GiftWrapSKU'
                ],
                [
                    'Value' => 'N',
                    'Name'  => 'FreeOfChargeIndicator'
                ],
                [
                    'Value' => 'N',
                    'Name'  => 'IsLinePriceForInformationOnly'
                ],
                [
                    'Value'   => (string)$_price,
                    'Name'    => 'DecimalPrice',
                    'Section' => 'BusinessRules'
                ],
                [
                    'Value'   => (string)$_price,
                    'Name'    => 'ItemSubTotal',
                    'Section' => 'BusinessRules'
                ],
                [
                    'Value'   => (string)$_price,
                    'Name'    => 'RetailPrice',
                    'Section' => 'BusinessRules'
                ]
            ];

            $result[] = $_shipmentLine;
        }

        return $result;
    }

    public function generateShipmentJson(
        Order $order,
        $trackingNumber = null,
        $totalInvoice = null,
        $amountToCharge = null,
        $orderTracking = null
    )
    {

        $payment      = $order->getPayment();
        $method       = $payment->getMethod();
        $companyId    = $this->_helper->getSupplierNumber($order->getStoreId());
        $randomNumber = (string)rand(10000000000, 99999999999);
        $currentTime  = date('Y-m-d\TH:i:s', time());
        $linkKey      = $this->getLinkKey($order->getLogicbrokerKey());


        $_json                         = [];
        $_json['ShipFromAddress']      = [
            'ContactType' => 0
        ];
        $_json['ShipmentInfos'][]      = [
            'ClassCode' => $orderTracking ? $orderTracking->getLogicBlockerCode() : ''
        ];
        $_json['Identifier']           = [
            'LogicbrokerKey' => $order->getLogicbrokerKey(),
            'SourceKey'      => $randomNumber,
            'LinkKey'        => $linkKey
        ];
        $_json['PaymentTerm']          = [
            'PayInNumberOfDays'      => 0,
            'DiscountInNumberOfDays' => 0,
            'DueDate'                => $currentTime,
            'AvailableDiscount'      => 0,
            'DiscountDueDate'        => $currentTime,
            'EffectiveDate'          => $currentTime
        ];
        $_json['ShipmentNumber']       = $randomNumber;
        $_json['ShipToAddress']        = [
            'ContactType' => 0,
            'Email'       => $order->getCustomerEmail()
        ];
        $_json['OrderNumber']          = $order->getIncrementId();
        $_json['PartnerPO']            = $order->getIncrementId();
        $_json['ReceiverCompanyId']    = $companyId;
        $_json['StatusCode']           = 0;
        $_json['TotalAmount']          = 0;
        $_json['ExpectedDeliveryDate'] = $currentTime;
        $_json['OrderedByAddress']     = ['ContactType' => 0];
        $_json['ExtendedAttributes']   = [
            [
                'Value' => date('Ymd', time()),
                'Name'  => 'ShipDate'
            ],
            [
                'Value' => $order->getShippingAmount(),
                'Name'  => 'SO_TotalShippingFee'
            ],
            [
                'Value' => $order->getTaxAmount(),
                'Name'  => 'SO_TotalTax'
            ],
            [
                'Value' => isset($totalInvoice) ? $totalInvoice : $order->getGrandTotal(),
                'Name'  => 'SO_TotalInvoice'
            ],
            [
                'Value' => isset($amountToCharge) ? $amountToCharge : $order->getGrandTotal(),
                'Name'  => 'SO_AmountToCharge'
            ],
            [
                'Value' => isset($trackingNumber) ? $trackingNumber : '9274890983426430000439',
                'Name'  => 'SO_TrackingNumber'
            ],
            [
                'Value' => $orderTracking ? $orderTracking->getSapCarrierCode() : '',
                'Name'  => 'SO_CarrierCode'
            ],
            [
                'Value' => $randomNumber,
                'Name'  => 'SO_InvoiceNumber'
            ],
            [
                'Value' => $order->getStoreId(),
                'Name'  => 'StoreId'
            ],
            [
                'Value' => 'False',
                'Name'  => 'IsDeliveryBlock'
            ],
            [
                'Value' => $method,
                'Name'  => 'PaymentMethod'
            ],
            [
                'Value' => 'ZRO',
                'Name'  => 'OrderType'
            ],
            [
                'Value'   => 'CustomXML',
                'Name'    => 'SourceSystem',
                'Section' => 'Documents',
            ],
            [
                'Value'   => "PO{$order->getIncrementId()}-01",
                'Name'    => 'Key',
                'Section' => 'Documents',
            ],
            [
                'Value'   => $order->getBillingAddress()->getRegion(),
                'Name'    => 'Region',
                'Section' => 'BusinessRules',
            ],
            [
                'Value'   => (string)$order->getBillingAddress()->getCountryId(),
                'Name'    => 'CountryCode',
                'Section' => 'BusinessRules',
            ],
            [
                'Value'   => $order->getOrderCurrency()->getCode(),
                'Name'    => 'Currency',
                'Section' => 'BusinessRules',
            ],
            [
                'Value'   => 'N',
                'Name'    => 'InternationalOrder',
                'Section' => 'BusinessRules',
            ],
            [
                'Value'   => 'SK001',
                'Name'    => 'PaddedStoreId',
                'Section' => 'BusinessRules',
            ], [
                'Value'   => '01',
                'Name'    => 'SourceSystemSourceSystem',
                'Section' => 'BusinessRules',
            ], [
                'Value'   => $order->getSubtotal(),
                'Name'    => 'SubTotal',
                'Section' => 'BusinessRules',
            ], [
                'Value'   => $order->getIncrementId(),
                'Name'    => 'SalesOrderNumber',
                'Section' => 'Documents',
            ]
        ];
        $_json['Discounts']            = [
            [
                'DiscountPercent' => 0,
                'DiscountAmount'  => 0,
                'DiscountName'    => 'Magento'
            ],
        ];
        $_json['Price']                = 0;
        $_json['Quantity']             = (int)$order->getTotalQtyOrdered();
        $_json['Taxes']                = [
            [
                'TaxAmount' => $order->getTaxAmount(),
                'TaxTitle'  => 'Magento',
                'TaxRate'   => 0
            ]
        ];

        return $_json;
    }

    /** Get array line item id which key is line item SKU
     * @param OrderItemInterface[] $orderItems
     * @return array
     */
    public function getOrderItemIDs(array $orderItems)
    {
        $result = [];
        foreach ($orderItems as $orderItem) {
            $_item                        = [];
            $_item['id']                  = $orderItem->getItemId();
            $_item['tax']                 = round($orderItem->getPrice() * $orderItem->getTaxPercent() / 100, 2);
            $_item['price']               = $orderItem->getPrice();
            $result[$orderItem->getSKU()] = $_item;
        }

        return $result;
    }

    protected function getLinkKey($lbKey)
    {
        $url = $this->_helper->getApiUrl() . "api/v1/Orders/$lbKey?subscription-key={$this->_helper->getApiKey()}";

        $apiRes   = $this->_helper->getFromApi($url, array('Body'));
        $_apiRes  = $apiRes['Result'];
        $_linkKey = $_apiRes->SalesOrder->Identifier->LinkKey;

        return $_linkKey;
    }
}