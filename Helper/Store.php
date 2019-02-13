<?php

namespace Pimgento\Api\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\Website;
use Pimgento\Api\Helper\Config as ConfigHelper;

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
     * Filtered channel for store
     *
     * @var string $channel
     */
    protected $channel;

    /**
     * Store constructor
     *
     * @param Context $context
     * @param ConfigHelper $configHelper
     * @param Serializer $serializer
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        ConfigHelper $configHelper,
        Serializer $serializer,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);

        $this->serializer   = $serializer;
        $this->storeManager = $storeManager;
        $this->configHelper = $configHelper;
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

            /** @var Mage_Core_Model_Store[] $store */
            $stores = $website->getStores();
            /** @var Mage_Core_Model_Store $store */
            foreach ($stores as $store) {
                /** @var array $combine */
                $combine = [];
                /** @var string $key */
                foreach ($arrayKey as $key) {
                    switch ($key) {
                        case 'store_id':
                            $combine[] = $store->getId();
                            break;
                        case 'store_code':
                            $combine[] = $store->getCode();
                            break;
                        case 'website_id':
                            $combine[] = $website->getId();
                            break;
                        case 'website_code':
                            $combine[] = $website->getCode();
                            break;
                        case 'channel_code':
                            $combine[] = $channel;
                            break;
                        case 'lang':
                            $combine[] = $this->configHelper->getDefaultLocale($store->getId());
                            break;
                        case 'currency':
                            $combine[] = $this->configHelper->getDefaultCurrency($store->getId());
                            break;
                        default:
                            $combine[] = $store->getId();
                            break;
                    }
                }

                /** @var string $key */
                $key = implode('-', $combine);

                if (!isset($data[$key])) {
                    $data[$key] = [];
                }

                $data[$key][] = [
                    'store_id'     => $store->getId(),
                    'store_code'   => $store->getCode(),
                    'website_id'   => $website->getId(),
                    'website_code' => $website->getCode(),
                    'channel_code' => $channel,
                    'lang'         => $this->configHelper->getDefaultLocale($store->getId()),
                    'currency'     => $this->configHelper->getDefaultCurrency($store->getId()),
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
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAllStores()
    {
        /** @var array $stores */
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
