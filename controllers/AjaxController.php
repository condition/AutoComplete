<?php
include('Mage/CatalogSearch/controllers/AjaxController.php');
class VES_AutoComplete_AjaxController extends Mage_CatalogSearch_AjaxController
{
    public function suggestAction()
    {
    	if (!$this->getRequest()->getParam('q', false)) {
    		$this->getResponse()->setRedirect(Mage::getSingleton('core/url')->getBaseUrl());
    	}
    
    	$this->getResponse()->setBody($this->getLayout()->createBlock('autocomplete/autocomplete')->toHtml());
    }
}