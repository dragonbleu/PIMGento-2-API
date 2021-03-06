<?php

namespace Pimgento\Api\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\Website;
use Pimgento\Api\Helper\Config as ConfigHelper;
use Magento\Store\Model\ResourceModel\Website as WebsiteResource;

/**
 * Class Authenticator
 *
 * @category  Class
 * @package   Pimgento\Api\Helper
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.pimgento.com/
 */
class Store extends AbstractHelper
{
    /**
     * This variable contains a ConfigHelper
     *
     * @var ConfigHelper $configHelper
     */
    protected $configHelper;
    /**
     * This variable contains a StoreManagerInterface
     *
     * @var StoreManagerInterface $storeManager
     */
    protected $storeManager;
    /**
     * This variable contains a Serializer
     *
     * @var Serializer $serializer
     */
    protected $serializer;
    /**
     * Website Resource Model
     *
     * @var WebsiteResource $websiteResource
     */
    protected $websiteResource;

    /**
     * Filtered channel for store
     *
     * @var string $channel
     */
    protected $channel;

    /**
     * Store constructor
     *
     * @param Context               $context
     * @param ConfigHelper          $configHelper
     * @param Serializer            $serializer
     * @param StoreManagerInterface $storeManager
     * @param WebsiteResource       $websiteResource
     */
    public function __construct(
        Context $context,
        ConfigHelper $configHelper,
        Serializer $serializer,
        StoreManagerInterface $storeManager,
        WebsiteResource $websiteResource
    ) {
        parent::__construct($context);

        $this->configHelper    = $configHelper;
        $this->serializer      = $serializer;
        $this->storeManager    = $storeManager;
        $this->websiteResource = $websiteResource;
    }

    /**
     * @param string $channel
     * @return self
     */
    public function setChannel(string $channel)
    {
        $this->channel = $channel;

        return $this;
    }

    public function getChannel(): string
    {
        return $this->channel;
    }

    /**
     * Retrieve all stores information
     *
     * @param string|string[] $arrayKey
     *
     * @return mixed[]
     */
    public function getStores($arrayKey = 'store_id')
    {
        if (!is_array($arrayKey)) {
            $arrayKey = [$arrayKey];
        }

        /** @var mixed[] $data */
        $data = [];
        /** @var string[] $websiteDefaultStores */
        $websiteDefaultStores = $this->getWebsiteDefaultStores(true);
        /** @var mixed[] $mapping */
        $mapping = $this->configHelper->getWebsiteMapping();
        /** @var string[] $match */
        foreach ($mapping as $match) {
            if (empty($match['channel']) || empty($match['website'])) {
                continue;
            }
            /** @var string $channel */
            $channel = $match['channel'];
            /** @var string $websiteCode */
            $websiteCode = $match['website'];
            /** @var WebsiteInterface $website */
            $website = $this->storeManager->getWebsite($websiteCode);
            /** @var int $websiteId */
            $websiteId = $website->getId();
            if (!isset($websiteId)) {
                continue;
            }

            /** @var string $currency */
            $currency = $website->getBaseCurrencyCode();
            /** @var string[] $siblings */
            $siblings = $website->getStoreIds();
            /** @var Magento\Store\Model\Store\Interceptor[] $store */
            $stores = $website->getStores();
            /** @var Magento\Store\Model\Store\Interceptor $store */
            foreach ($stores as $store) {
                /** @var int $storeId */
                $storeId = $store->getId();
                /** @var string $storeCode */
                $storeCode = $store->getCode();
                /** @var string $storeLang */
                $storeLang = $this->scopeConfig->getValue(\Magento\Directory\Helper\Data::XML_PATH_DEFAULT_LOCALE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
                /** @var bool $isDefault */
                $isDefault = false;
                if (in_array($storeId, $websiteDefaultStores)) {
                    $isDefault = true;
                }

                /** @var mixed[] $combine */
                $combine = [];
                /** @var string $key */
                foreach ($arrayKey as $key) {
                    switch ($key) {
                        case 'store_id':
                            $combine[] = $storeId;
                            break;
                        case 'store_code':
                            $combine[] = $storeCode;
                            break;
                        case 'website_id':
                            $combine[] = $websiteId;
                            break;
                        case 'website_code':
                            $combine[] = $websiteCode;
                            break;
                        case 'channel_code':
                            $combine[] = $channel;
                            break;
                        case 'lang':
                            $combine[] = $storeLang;
                            break;
                        case 'currency':
                            $combine[] = $currency;
                            break;
                        default:
                            $combine[] = $storeId;
                            break;
                    }
                }

                /** @var string $key */
                $key = implode('-', $combine);

                if (!isset($data[$key])) {
                    $data[$key] = [];
                }

                $data[$key][] = [
                    'store_id'           => $storeId,
                    'store_code'         => $storeCode,
                    'is_website_default' => $isDefault,
                    'siblings'           => $siblings,
                    'website_id'         => $websiteId,
                    'website_code'       => $websiteCode,
                    'channel_code'       => $channel,
                    'lang'               => $storeLang,
                    'currency'           => $currency,
                ];
            }
        }

        return $data;
    }

    /**
     * @param string $channel
     * @return int[]
     */
    public function getWebsiteCodesByChannel(string $channel): array
    {
        $mappings = $this->configHelper->getWebsiteMapping();
        $onlyChannelMapping = array_filter($mappings, function ($mapping) use ($channel) {
            return $mapping['channel'] == $channel;
        });
        $websiteCodes = array_column($onlyChannelMapping, 'website');

        return $websiteCodes;
    }

    /**
     * @param string $channel
     * @return Website[]
     */
    public function getWebsitesByChannel(string $channel): array
    {
        $websiteCodes = $this->getWebsiteCodesByChannel($channel);

        $websites = array_map(function ($websiteCode) {
            return $this->storeManager->getWebsite($websiteCode);
        }, $websiteCodes);

        return $websites;
    }

    /**
     * Retrieve all store combination
     *
     * @return mixed[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAllStores()
    {
        /** @var mixed[] $stores */
        $stores = array_merge(
            $this->getStores(['lang']), // en_US
            $this->getStores(['lang', 'channel_code']), // en_US-channel
            $this->getStores(['channel_code']), // channel
            $this->getStores(['currency']), // USD
            $this->getStores(['channel_code', 'currency']), // channel-USD
            $this->getStores(['lang', 'channel_code', 'currency']) // en_US-channel-USD
        );

        return $stores;
    }

    /**
     * Retrieve needed store ids from website/channel mapping
     *
     * @return string[]
     * @throws Exception
     */
    public function getMappedWebsitesStoreIds()
    {
        /** @var mixed[] $websites */
        $websites = $this->getStores('website_code');
        /** @var string[] $storeIds */
        $storeIds = [];
        /** @var mixed[] $website */
        foreach ($websites as $website) {
            /** @var string[] $websiteStoreIds */
            $websiteStoreIds = array_column($website, 'store_id');
            $storeIds        = array_merge($storeIds, array_diff($websiteStoreIds, $storeIds));
        }

        return $storeIds;
    }

    /**
     * Retrieve needed store languages from website/channel mapping
     *
     * @return string[]
     * @throws Exception
     */
    public function getMappedWebsitesStoreLangs()
    {
        /** @var mixed[] $websites */
        $websites = $this->getStores('website_code');
        /** @var string[] $langs */
        $langs = [];
        /** @var mixed[] $website */
        foreach ($websites as $website) {
            /** @var string[] $websiteStoreIds */
            $websiteStoreIds = array_column($website, 'lang');
            $langs           = array_merge($langs, array_diff($websiteStoreIds, $langs));
        }

        return $langs;
    }

    /**
     * Get websites default stores
     *
     * @param bool $withAdmin
     *
     * @return string[]
     */
    public function getWebsiteDefaultStores($withAdmin = false)
    {
        /** @var \Magento\Store\Model\ResourceModel\Website $websiteResource */
        $websiteResource = $this->websiteResource;
        /** @var \Magento\Framework\DB\Select $select */
        $select = $websiteResource->getDefaultStoresSelect($withAdmin);
        /** @var string[] $websiteDefaultStores */
        $websiteDefaultStores = $websiteResource->getConnection()->fetchPairs($select);

        return $websiteDefaultStores;
    }

    /**
     * Retrieve admin store lang setting
     * Default: return Mage_Core_Model_Locale::DEFAULT_LOCALE
     *
     * @return string
     */
    public function getAdminLang()
    {
        /** @var string $adminLang */
        $adminLang = \Magento\Framework\Locale\Resolver::DEFAULT_LOCALE;

        if ($this->scopeConfig->isSetFlag(\Magento\Directory\Helper\Data::XML_PATH_DEFAULT_LOCALE)) {
            $adminLang = $this->scopeConfig->getValue(\Magento\Directory\Helper\Data::XML_PATH_DEFAULT_LOCALE);
        }

        return $adminLang;
    }
}
