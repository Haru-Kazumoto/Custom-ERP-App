<?php

namespace App\Enum;

enum TransactionType: string
{
    case CustomerOrder = 'CO';
    case PurchaseOrder = 'PO';
    case DeliveryOrder = 'DO';
    case Invoice = 'INV';
    case SubSalesOrder = 'SSO';
}
