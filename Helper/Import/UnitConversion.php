<?php
/**
 * User: Nicolas BATTY
 * Date: 19/02/2019
 * Time: 12:02
 */

namespace Pimgento\Api\Helper\Import;

use Magento\Directory\Helper\Data as DirectoryHelper;

class UnitConversion
{
    const MAGENTO_POUNDS = 'lbs';

    const AKENEO_KILOGRAMS = 'KILOGRAM';

    const KILOGRAMS_TO_POUNDS_MULTIPLIER = 2.205;

    /**
     * @var DirectoryHelper
     */
    protected $directoryHelper;

    public function __construct(DirectoryHelper $directoryHelper)
    {
        $this->directoryHelper = $directoryHelper;
    }

    public function updateProductUnits(array &$product)
    {
        $weightUnit = $this->directoryHelper->getWeightUnit();
        $unit = &$product['values']['weight'][0]['data']['unit'];
        if ($weightUnit == self::MAGENTO_POUNDS && $unit == self::AKENEO_KILOGRAMS) {
            $amount = &$product['values']['weight'][0]['data']['amount'];
            $amount *= self::KILOGRAMS_TO_POUNDS_MULTIPLIER;
            $amount = round($amount, 2);
        }
    }
}
