<?php

namespace Loqate\ApiIntegration\Observer;


use Loqate\ApiIntegration\Helper\Controller;
use Loqate\ApiIntegration\Logger\Logger;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Address;

class OrderAddressSave implements ObserverInterface {

    protected $addressRepository;
    private Logger $logger;

    public function __construct(
        Logger $logger,
        AddressRepositoryInterface $addressRepository

    ) {
        $this->logger = $logger;
        $this->addressRepository = $addressRepository;
    }


    public function execute(\Magento\Framework\Event\Observer $observer) {
        /** @var Address $orderAddress */
        $orderAddress = $observer->getEvent()->getAddress();
        $this->logger->info($orderAddress->getId());

        $isDuringCreateOrder = empty($orderAddress->getId());

        if ($isDuringCreateOrder) {
            $customerAddressId = $orderAddress->getCustomerAddressId();
            if (!empty($customerAddressId)) {
                $customerAddress = $this->addressRepository->getById($customerAddressId);

                for ($i = 1; $i <= Controller::MAX_DATA_SETS_FIELDS; $i++) {
                    $field = "loqate_field{$i}_format";
                    //todo this needs to be taken from quote not customer address
                    //todo remove logger
                    $customAttribute = $customerAddress->getCustomAttribute($field);
                    if (!empty($customAttribute)) {
                        $orderAddress->setData($field, $customAttribute->getValue());
                    }
                }
            }
        }

//        $address->setData('loqate_field1_format','ooo');
//        $this->logger->info('orderaddresssave');
//        $this->logger->info();
//        $address = $observer->getEvent()->getAddress();
//        $order->getAddresses();
//        $billingAddress = $order->getBillingAddressId();
//        $shippingAddress = $order->getShippingAddressId();
//        $quote = $this->quoteRepository->get($order->getQuoteId());
//        $this->logger->info($billingAddress);
//        $this->logger->info($billingAddress);
//        $quoteBillingAddress = $quote->getBillingAddress();
//        $quoteShippingAddress = $quote->getShippingAddress();
//
//        $customerBillingAddressId = $quoteBillingAddress->getCustomerAddressId();
//        $customerShippingAddressId = $quoteShippingAddress->getCustomerAddressId();
//
//        $this->logger->info($order->getId());
//        $this->logger->info($order->getQuoteId());
//        $this->logger->info($quoteBillingAddress->getId());
//        $this->logger->info($quoteShippingAddress->getId());
//        $this->logger->info($customerBillingAddressId);
//        $this->logger->info($customerShippingAddressId);
//
//
//        $billingOrderAddressCollection = $this->orderAddressCollectionFactory->create();
//        $billingOrderAddressCollection->addFieldToFilter('quote_address_id', $quoteBillingAddress->getId());
//        $salesOrderBillingAddress = $billingOrderAddressCollection->getFirstItem();
//        $this->logger->info($salesOrderBillingAddress->getId());
//
//        $shippingOrderAddressCollection = $this->orderAddressCollectionFactory->create();
//        $shippingOrderAddressCollection->addFieldToFilter('quote_address_id', $quoteShippingAddress->getId());
//        $salesOrderShippingAddress = $shippingOrderAddressCollection->getFirstItem();
//        $this->logger->info($salesOrderShippingAddress->getId());

        return $this;
    }

}
