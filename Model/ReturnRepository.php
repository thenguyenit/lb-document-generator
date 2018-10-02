<?php

namespace FossilEcommerce\LBDocumentGenerator\Model;

use FossilEcommerce\LBDocumentGenerator\Api\ReturnRepositoryInterface;
use \FossilGroup\LogicBroker\Helper\Data;
use Magento\Sales\Api\Data\OrderItemInterface;
use \Magento\Sales\Model\Order;
use \Magento\Sales\Model\OrderFactory;

class ReturnRepository implements ReturnRepositoryInterface
{

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_order;

    /**
     * @var \FossilGroup\LogicBroker\Helper\Data
     */
    protected $_helper;

    /**
     * ReturnRepository constructor.
     * @param Data $helper
     * @param OrderFactory $order
     */
    public function __construct(Data $helper, OrderFactory $order)
    {
        $this->_helper                    = $helper;
        $this->_order                     = $order;
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
     * {@inheritDoc}
     */
    public function getReturnJson($orderNumber)
    {
        /** @var  Order $order */
        $order = $this->findOrderByOrderNumber($orderNumber);

        if ($order->isEmpty()) {
            exit('Order number is not available');
        }

        $_json = $this->generateReturnJson($order);
        if (empty($_json)) {
            exit('Failed generate return, since the order not found on Logic Broker');
        }
        $_json = json_encode($_json, JSON_PRETTY_PRINT);
        $json_string = stripslashes($_json);
        printf($json_string);
        exit();
    }

    /**
     * @param array $orderItems
     * @return array
     */
    public function generateReturnLinesJson(array $orderItems)
    {
        $result = [];

        // Get Shipment line items
        foreach ($orderItems as $orderItem) {
            if ($orderItem->getProductType() === "simple" && $orderItem->getParentItemId() !== null) {
                continue;
            }
            $_returnLine                       = [];
            $_returnLine['Quantity']           = (int)$orderItem->getQtyOrdered();
            $_returnLine['Weight']             = 0;
            $_returnLine['Cost']               = 0;
            $_returnLine['RetailPrice']        = 0;
            $_returnLine['ItemIdentifier']     = [
                'SupplierSKU' => $orderItem->getSku(),
                'PartnerSKU' => $orderItem->getSku()
            ];
            $_returnLine['ExtendedAttributes'] = [
                [
                    'Value' => '10',
                    'Name'  => 'SI_returnCode'
                ],
                [
                    'Value' => '10',
                    'Name'  => 'OrderLine_ReturnReason'
                ],
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
                    'Value' => (string)$orderItem->getItemId(),
                    'Name'  => 'item_id'
                ],
                [
                    'Value' => '0',
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

            $result[] = $_returnLine;
        }

        return $result;
    }

    public function generateReturnJson(Order $order)
    {
        $payment      = $order->getPayment();
        $method       = $payment->getMethod();
        $companyId    = $this->_helper->getSupplierNumber($order->getStoreId());
        $randomNumber = (string)rand(10000000000, 99999999999);
        $currentTime  = date('Y-m-d\TH:i:s', time());
        $linkKey      = $this->getLinkKey($order->getLogicbrokerKey());
        if (is_null($linkKey)) {
            return false;
        }

        $_json                         = [];
        $_json['ShipFromAddress']      = [
            'ContactType' => 0
        ];

        $_json['Identifier']           = [
            'LogicbrokerKey' => $order->getLogicbrokerKey(),
            'SourceKey'      => $randomNumber,
            'LinkKey'        => $linkKey
        ];

        $_json['ReturnNumber']       = $randomNumber;
        $_json['ReturnDate']       = $currentTime;

        $_json['ShipToAddress']        = [
            'ContactType' => 0,
            'Email'       => $order->getCustomerEmail()
        ];
        $_json['OrderNumber']          = $order->getIncrementId();
        $_json['PartnerPO']            = $order->getIncrementId();
        $_json['ReceiverCompanyId']    = $companyId;
        $_json['StatusCode']           = 0;
        $_json['ExpectedDeliveryDate'] = $currentTime;
        $_json['OrderedByAddress']     = ['ContactType' => 0];
        $_json['ExtendedAttributes']   = [
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
                'Name'  => 'SO_GrandTotal'
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
            ], [
                'Value'   => 'TRUE',
                'Name'    => 'OrderIsPO',
                'Section' => 'Documents',
            ]
        ];

        $_json['ReturnLines'] = $this->generateReturnLinesJson($order->getItems());

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

        $apiRes = $this->_helper->getFromApi($url, array('Body'));
        $_apiRes = $apiRes['Result'];
        if (property_exists($_apiRes, 'SalesOrder')) {

            $_linkKey = $_apiRes->SalesOrder->Identifier->LinkKey;
            return $_linkKey;
        }

        return null;
    }
}