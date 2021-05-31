<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Custom\ImageUploader\Block\Adminhtml\Index;

class Index extends \Magento\Backend\Block\Template
{

   public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Data\Form\FormKey $formKey,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->formKey = $formKey;
    }

    public function getFormKey()
    {
         return $this->formKey->getFormKey();
    }

    public function getAjaxUrl()
    {
        return $this->getUrl('imageuploader/index/save');
    }

}

