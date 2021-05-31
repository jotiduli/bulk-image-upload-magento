<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Custom\ImageUploader\Controller\Adminhtml\Index;
use Magento\Framework\App\Filesystem\DirectoryList;
class Save extends \Magento\Backend\App\Action
{

    protected $resultPageFactory;
    protected $jsonHelper;
    protected $request;
    protected $formKey;
    protected $fileSystem;
    protected $fileUploaderFactory;
    protected $importModel;
    
    private $file;
    private $dir;


    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context  $context
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Framework\App\Request\Http $request,
        \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory,
        \Magento\Framework\Filesystem $fileSystem,
        \Custom\ImageUploader\Model\Import $importModel,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Filesystem\Io\File $file,
        \Magento\Framework\Filesystem\DirectoryList $dir
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->jsonHelper = $jsonHelper;
        $this->logger = $logger;
        $this->request = $request;
        $this->formKey = $formKey;
        $this->fileUploaderFactory = $fileUploaderFactory;
        $this->fileSystem = $fileSystem;
        $this->importModel = $importModel;
        $this->request->setParam('form_key', $this->formKey->getFormKey());
        $this->file = $file;
        $this->dir = $dir;
        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            $data = $this->getRequest()->getPostValue();
            
            $html = $this->importModel->imgImport($data['subfold'],$data['checknow'],$data['childpath']);
            
            $importimages = $this->dir->getPath('var').'/log/importimages';
            if ( ! file_exists($importimages)) {
                $this->file->mkdir($importimages);
            }
            
            $cdate = date("Y-m-d");
            $cTime = date("h:i:sa");
            $finalctime = $cdate.'_'.$cTime;
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/importimages/'.$finalctime.'.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $logger->info($html);


            
            return $this->jsonResponse(['data'=>$html]);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return $this->jsonResponse($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical($e);
            return $this->jsonResponse($e->getMessage());
        }
    }

    /**
     * Create json response
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function jsonResponse($response = '')
    {
        return $this->getResponse()->representJson(
            $this->jsonHelper->jsonEncode($response)
        );
    }
}