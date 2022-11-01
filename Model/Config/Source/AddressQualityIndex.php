<?php

namespace Loqate\ApiIntegration\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * AddressQualityIndex class
 */
class AddressQualityIndex implements OptionSourceInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'A', 'label' => __('Excellent')],
            ['value' => 'B', 'label' => __('Good')],
            ['value' => 'C', 'label' => __('Average')],
            ['value' => 'D', 'label' => __('Poor')],
            ['value' => 'E', 'label' => __('Bad')],
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            ['A' => __('Excellent')],
            ['B' => __('Good')],
            ['C' => __('Average')],
            ['D' => __('Poor')],
            ['E' => __('Bad')],
        ];
    }
}
