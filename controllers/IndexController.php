<?php
class VES_AutoComplete_IndexController extends Mage_Core_Controller_Front_Action {
	public function indexAction() {
		$model = Mage::getModel('catalog/product')->getCollection()->AddAttributeToSelect('*')
		->addAttributeToFilter('status',array('eq'=>'1'));
		
		foreach($model as $product) {
			echo $product->getStatus();
		}
	}
}
