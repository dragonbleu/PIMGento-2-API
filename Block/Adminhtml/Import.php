<?php

namespace Pimgento\Api\Block\Adminhtml;

use \Magento\Backend\Block\Template;
use Magento\Backend\Model\Url;
use \Magento\Backend\Model\UrlFactory;
use \Magento\Backend\Block\Template\Context;
use Pimgento\Api\Api\ImportRepositoryInterface;
use Pimgento\Api\Helper\Store as StoreHelper;
use Pimgento\Api\Model\Source\Filters\Channel;

/**
 * Class Import
 *
 * @category  Class
 * @package   Pimgento\Api\Block\Adminhtml
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.pimgento.com/
 */
class Import extends Template
{

    /**
     * This variable contains an Url
     *
     * @var Url $urlModel
     */
    protected $urlModel;

    /**
     * @var Channel
     */
    protected $channelSource;
    /**
     * @var StoreHelper
     */
    protected $storeHelper;

    /**
     * This variable contains an ImportRepositoryInterface
     *
     * @var ImportRepositoryInterface $importRepository
     */
    private $importRepository;

    /**
     * Import constructor.
     *
     * @param Context $context
     * @param UrlFactory $backendUrlFactory
     * @param ImportRepositoryInterface $importRepository
     * @param Channel $channelSource
     * @param StoreHelper $storeHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        UrlFactory $backendUrlFactory,
        ImportRepositoryInterface $importRepository,
        Channel $channelSource,
        StoreHelper $storeHelper,
        $data = []
    ) {
        parent::__construct($context, $data);

        $this->urlModel         = $backendUrlFactory->create();
        $this->importRepository = $importRepository;
        $this->channelSource    = $channelSource;
        $this->storeHelper      = $storeHelper;
    }

    public function getChannelRefs()
    {
        $options = [];
        foreach ($this->channelSource->toOptionArray() as $option) {
            if (!empty($this->storeHelper->getWebsiteCodesByChannel($option['value']))) {
                $options[] = $option;
            }
        }
        return $options;
    }

    /**
     * Retrieve import collection
     *
     * @return Iterable
     * @throws \Exception
     */
    public function getCollection()
    {
        return $this->importRepository->getList();
    }

    /**
     * Check import is allowed
     *
     * @param string $code
     *
     * @return bool
     */
    public function isAllowed($code)
    {
        return $this->_authorization->isAllowed('Pimgento_Api::import_'.$code);
    }

    /**
     * {@inheritdoc}
     */
    public function _toHtml()
    {
        /** @var string $runUrl */
        $runUrl = $this->_getRunUrl();

        $this->assign(
            [
                'runUrl' => $this->_escaper->escapeHtml($runUrl),
            ]
        );

        return parent::_toHtml();
    }

    /**
     * Retrieve run URL
     *
     * @return string
     */
    public function _getRunUrl()
    {
        return $this->urlModel->getUrl('pimgento_api/import/run');
    }
}
