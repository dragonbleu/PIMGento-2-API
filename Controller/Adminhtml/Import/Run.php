<?php

namespace Pimgento\Api\Controller\Adminhtml\Import;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Pimgento\Api\Api\ImportRepositoryInterface;
use Pimgento\Api\Converter\ArrayToJsonResponseConverter;
use Pimgento\Api\Helper\Output as OutputHelper;
use Pimgento\Api\Helper\Store;
use Pimgento\Api\Job\Import;
use Pimgento\Api\Job\Product;

/**
 * Class Run
 *
 * @category  Class
 * @package   Pimgento\Api\Controller\Adminhtml\Import
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.pimgento.com/
 */
class Run extends Action
{
    /**
     * This variable contains an OutputHelper
     *
     * @var OutputHelper $outputHelper
     */
    private $outputHelper;
    /**
     * This variable contains an ImportRepositoryInterface
     *
     * @var ImportRepositoryInterface $importRepository
     */
    private $importRepository;
    /**
     * This variable contains a ArrayToJsonResponseConverter
     *
     * @var ArrayToJsonResponseConverter $arrayToJsonResponseConverter
     */
    private $arrayToJsonResponseConverter;

    /**
     * @var Store
     */
    private $storeHelper;

    /**
     * Run constructor.
     *
     * @param Context $context
     * @param ImportRepositoryInterface $importRepository
     * @param OutputHelper $output
     * @param ArrayToJsonResponseConverter $arrayToJsonResponseConverter
     * @param Store $storeHelper
     */
    public function __construct(
        Context $context,
        ImportRepositoryInterface $importRepository,
        OutputHelper $output,
        ArrayToJsonResponseConverter $arrayToJsonResponseConverter,
        Store $storeHelper
    ) {
        parent::__construct($context);

        $this->outputHelper                 = $output;
        $this->importRepository             = $importRepository;
        $this->arrayToJsonResponseConverter = $arrayToJsonResponseConverter;
        $this->storeHelper                  = $storeHelper;
    }

    /**
     * Action triggered by request
     *
     * @return Json
     */
    public function execute()
    {
        /** @var RequestInterface $request */
        $request = $this->getRequest();
        /** @var int $step */
        $step = (int)$request->getParam('step');
        /** @var string $code */
        $code = $request->getParam('code');
        /** @var string $identifier */
        $identifier = $request->getParam('identifier');

        /** @var Import $import */
        $import = $this->importRepository->getByCode($code);

        if (!$import) {
            /** @var array $response */
            $response = $this->outputHelper->getNoImportFoundResponse();

            return $this->arrayToJsonResponseConverter->convert($response);
        }

        $import->setIdentifier($identifier)->setStep($step);

        /** @var array $response */
        if ($import instanceof Product) {
            $channel = $request->getParam('channel');
            $this->storeHelper->setChannel($channel);
        }
        $response = $import->execute();

        return $this->arrayToJsonResponseConverter->convert($response);
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Pimgento_Api::import');
    }
}
