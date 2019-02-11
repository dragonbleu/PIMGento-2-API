<?php
/**
 * User: Nicolas BATTY
 * Date: 05/02/2019
 * Time: 15:14
 */

namespace Pimgento\Api\Helper\Import;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Pimgento\Api\Helper\Import\Product as ProductImportHelper;
use Zend_Db_Expr as Expr;

class Axis extends AbstractHelper
{
    /**
     * @var Product
     */
    protected $entitiesHelper;

    public function __construct(
        Context $context,
        ProductImportHelper $entitiesHelper
    )
    {
        parent::__construct($context);
        $this->entitiesHelper = $entitiesHelper;
    }

    /**
     * @return array
     */
    public function getChildrenWithAxis($tmpTable)
    {
        $connection = $this->entitiesHelper->getConnection();
        $select = $connection->select()
            ->from(['tmp' => $tmpTable])
            ->columns([
                'parent_axis' => new Expr('IFNULL(tmp_gparent._axis, tmp_parent._axis)')
            ])
            ->joinInner(
                ['tmp_parent' => $tmpTable],
                'tmp.parent = tmp_parent.identifier',
                []
            )
            ->joinLeft(
                ['tmp_gparent' => $tmpTable],
                'tmp_parent.parent = tmp_gparent.identifier',
                []
            )
            ->where('tmp.parent IS NOT NULL');

        $items = $connection->fetchAll($select);

        return $items;
    }

    public function getOptionValueById($optionId, $storeId)
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        $attrOptionValueTable = $connection->getTableName('eav_attribute_option_value');

        $select = $connection->select()
            ->from($attrOptionValueTable, 'value')
            ->where('option_id = ?', $optionId)
            ->where('store_id = ?', $storeId);

        $optionValue = $connection->fetchOne($select);

        return $optionValue;
    }

    public function getFirstvalidStoreScope(array $stores, $attribute)
    {
        $split = explode('-', $attribute);
        if (isset($split[1]) && isset($stores[$split[1]])) {
            return current($stores[$split[1]]);
        }
        return null;
    }


    public function getNameColumns(array $items)
    {
        $nameColumns = [];
        if (count($items)) {
            $item = current($items);
            foreach ($item as $columnName => $value) {
                if (strpos($columnName, 'name-') === 0) {
                    $nameColumns[] = $columnName;
                }
            }
        }
        return $nameColumns;
    }
}
