<?php

namespace Pimgento\Api\Helper;

use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use \Pimgento\Api\Helper\Config as ConfigHelper;
use Pimgento\Api\Model\Source\Filters\Completeness;
use Pimgento\Api\Model\Source\Filters\Mode;
use Pimgento\Api\Model\Source\Filters\Status;

/**
 * Class ProductFiltersHelper
 *
 * @category  Class
 * @package   Pimgento\Api\Helper
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.pimgento.com/
 */
class ProductFilters extends AbstractHelper
{
    /**
     * This variable contains a ConfigHelper
     *
     * @var ConfigHelper $configHelper
     */
    protected $configHelper;
    /**
     * This variable contains a SearchBuilder
     *
     * @var SearchBuilder $searchBuilder
     */
    protected $searchBuilder;

    /**
     * @var Store
     */
    protected $storeHelper;

    /**
     * ProductFilters constructor
     *
     * @param ConfigHelper $configHelper
     * @param SearchBuilder $searchBuilder
     * @param Store $storeHelper
     * @param Context $context
     */
    public function __construct(
        ConfigHelper $configHelper,
        SearchBuilder $searchBuilder,
        Store $storeHelper,
        Context $context
    ) {
        parent::__construct($context);

        $this->configHelper = $configHelper;
        $this->searchBuilder = $searchBuilder;
        $this->storeHelper = $storeHelper;
    }

    /**
     * Get the filters for the product API query
     *
     * @return array
     */
    public function getFilters()
    {
        /** @var string $mode */
        $mode = $this->configHelper->getFilterMode();
        if ($mode == Mode::ADVANCED) {
            return $this->configHelper->getAdvancedFilters();
        }
        $globalFilter = [];
        if ($mode == Mode::CHANNEL) {
            $globalFilter['scope'] = $this->storeHelper->getChannel();
        } else {
            $this->addCompletenessFilter();
        }
        $this->addStatusFilter();
        $this->addFamiliesFilter();
        $this->addUpdatedFilter();
        /** @var array $filters */
        $filters = $this->searchBuilder->getFilters();

        if (!empty($filters)) {
            $globalFilter['search'] = $filters;
        }

        return $globalFilter;
    }

    /**
     * Add completeness filter for Akeneo API
     *
     * @return void
     */
    protected function addCompletenessFilter()
    {
        /** @var string $filterType */
        $filterType = $this->configHelper->getCompletenessTypeFilter();
        if ($filterType === Completeness::NO_CONDITION) {
            return;
        }
        /** @var string $filterValue */
        $filterValue = $this->configHelper->getCompletenessValueFilter();

        /** @var mixed $locales */
        $locales = $this->configHelper->getCompletenessLocalesFilter();
        $locales = explode(',', $locales);

        /** @var string $scope */
        $scope = $this->configHelper->getCompletenessScopeFilter();

        $options = ['scope' => $scope];

        /** @var array $localesType */
        $localesType = [
            Completeness::LOWER_OR_EQUALS_THAN_ON_ALL_LOCALES,
            Completeness::LOWER_THAN_ON_ALL_LOCALES,
            Completeness::GREATER_THAN_ON_ALL_LOCALES,
            Completeness::GREATER_OR_EQUALS_THAN_ON_ALL_LOCALES
        ];
        if (in_array($filterType, $localesType)) {
            $options['locales'] = $locales;
        }
        $this->searchBuilder->addFilter('completeness', $filterType, $filterValue, $options);

        return;
    }

    /**
     * Add status filter for Akeneo API
     *
     * @return void
     */
    protected function addStatusFilter()
    {
        /** @var string $filter */
        $filter = $this->configHelper->getStatusFilter();
        if ($filter === Status::STATUS_NO_CONDITION) {
            return;
        }
        $this->searchBuilder->addFilter('enabled', '=', (bool)$filter);

        return;
    }

    /**
     * Add updated filter for Akeneo API
     *
     * @return void
     */
    protected function addUpdatedFilter()
    {
        /** @var string $filter */
        $filter = $this->configHelper->getUpdatedFilter();
        if (!is_numeric($filter)) {
            return;
        }
        $this->searchBuilder->addFilter('updated', 'SINCE LAST N DAYS', (int)$filter);

        return;
    }

    /**
     * Add families filter for Akeneo API
     *
     * @return void
     */
    protected function addFamiliesFilter()
    {
        /** @var mixed $filter */
        $filter = $this->configHelper->getFamiliesFilter();
        if (!$filter) {
            return;
        }
        $filter = explode(',', $filter);

        $this->searchBuilder->addFilter('family', 'NOT IN', $filter);

        return;
    }
}
