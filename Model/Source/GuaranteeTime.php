<?php

namespace Payone\Core\Model\Source;

use Magento\Framework\Option\ArrayInterface;

class GuaranteeTime implements ArrayInterface
{
    /**
     * @var int
     */
    protected $iAllowedDaysMin = 1;

    /**
     * @var int
     */
    protected $iAllowedDaysMax = 15;

    /**
     * Return allowed guarantee times
     *
     * @return array
     */
    public function toOptionArray()
    {
        $aOptions = [];
        for($i = $this->iAllowedDaysMin; $i <= $this->iAllowedDaysMax; $i ++) {
            $aOptions[] = [
                'value' => $i,
                'label' => $i,
            ];
        }
        return $aOptions;
    }
}
