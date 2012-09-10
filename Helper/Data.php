<?php

class VES_AutoComplete_Helper_Data extends Mage_CatalogSearch_Helper_Data
{
	/**
	 * Retrieve suggest url
	 *
	 * @return string
	 */
	public function getSuggestUrl()
	{
		return $this->_getUrl('autocomplete/ajax/suggest', array(
				'_secure' => Mage::app()->getFrontController()->getRequest()->isSecure()
		));
	}
}