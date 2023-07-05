<?php

namespace Loqate\ApiIntegration\Plugin;


use Magento\Customer\Model\Data\AttributeMetadata;

class ModifyCreateAddressFields
{

    public function afterGetAttributes($subject, $result)
    {//todo
//        var_dump($result);exit;
        if(isset($result['city']))
        {
            /** @var AttributeMetadata $city */
            $city = $result['city'];
//            $this->setupCity($city);

//            $city->setDefaultValue('test');
        }

        return $result;
    }



}
