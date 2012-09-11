<?php
class VES_AutoComplete_Block_Autocomplete extends Mage_Core_Block_Template
{
	protected $_config = array();
	protected $_suggestData = null;
	
	
	public function __construct() {
		parent::__construct();
		$this->setTemplate('ves_autocomplete/autocomplete.phtml');
	}
	
	public function _prepareLayout()
    {
		return parent::_prepareLayout();
    }
    
     public function getAutocomplete()     
     { 
        if (!$this->hasData('autocomplete')) {
            $this->setData('autocomplete', Mage::registry('autocomplete'));
        }
        return $this->getData('autocomplete');
    }
    
    
    /**
     * Get suggest data from query string
     * @param
     * @return array
     */
    public function getSuggestData() {
    	if(!$this->_suggestData) {
    		$pattern = $this->helper('autocomplete')->getQueryText();		//pattern to matching
	    	$data = array();
	    	$array = array();
	    	$point = array();
			$model = Mage::getModel('catalog/product')->getCollection()->AddAttributeToSelect('*')
			->addAttributeToFilter('status',array('eq'=>'1'));
			
			/*fetch to array*/
			foreach($model as $product) {
				if(count($data) == $this->_config['show_product']) {break;} 			
				else {
					$array['id'] 				= $product->getId();
					$array['name'] 				= $product->getName();
					$array['shortdescription'] 	= $product->getShortDescription();
					$array['point'] 			= $this->calculator($array, $pattern);
					if($array['point'] != 0) {
						$array['link'] 	= $product->getProductUrl();			//get product url
						$array['image'] = $product->getImageUrl();				//get product image url
						$array['price'] = $product->getPrice();					//get product price
						$array['special_price'] = $product->getFinalPrice();	//get special price
						$data[] = $array;
					}
				}
			}
			/*sorting array data follow by point*/
			$data = $this->subvalSortReserve($data,'point');		

			$this->_suggestData = $data;
    	}
    	
    	return $this->_suggestData;
    }
    
    
    /**
     * Output HTML script to Responds AJAX
     * @param
     * @return string
     */
   /* protected function _toHtml()
    {
    	$configData = $this->getConfigData();
    	$pattern = $this->helper('catalogsearch')->getQueryText();			//get parameter of query string
    	$html = '';
    
    	if (!$this->_beforeToHtml()) {
    		return $html;
    	}
    
    	$suggestData = $this->getSuggestData();
    	
    	if (!($count = count($suggestData))) {
    		return $html;
    	}
    	
		
    	$count--;
    	$html = '<ul><li style="display:none"></li>';
    	$html .= '<li id="header" class="first_last"><p>' . $configData['result_header'] . '</p></li>';
    	foreach ($suggestData as $index => $item) {
    		$item['name'] = $this->highlight($pattern, $item['name']);
    		$item['shortdescription'] = $this->highlight($pattern, $item['shortdescription']);    		
	
    		$html .= '<li>';
    		$html .= 	'<a href="' . $item['link'] . '"';
    		$html .= $configData['is_new_window'] == 1 ? ' target="_blank">' : '>';
    		$html .= 		'<div class="content_div">';
    		$html .= 			'<img src="' . $item['image'] . '" alt="' . htmlentities($item['name']) . '" '
    				. 'width="' . $configData['thumb_width'] . '" height="' . $configData['thumb_height'] . '"';
    		$html .= $configData['show_image'] == 1 ? '/>' : ' style="display: none"/>';
    		$html .= 			'<span class="title">' . $item['name'] . '</span>';
    		$html .= 			'<p class="content">';
    		$html .= $configData['desc_char'] != NULL ? substr($item['shortdescription'],0,$configData['desc_char']) : $item['shortdescription'];
    		$html .= '</p>';
    		$html .= 			'<span class="price">' .  Mage::app()->getLocale()->currency(Mage::app()->getStore()->
     							getCurrentCurrencyCode())->getSymbol() . number_format($item['price'],2) . '</span>';
    		$html .= 		'</div>';
    		$html .= 	'</a>';
    		$html .= '</li>';
    	}
    	
    	$html .= '<li id="footer" class="first_last"><p>' . $configData['result_footer'] . '</p></li>';
    	$html.= '</ul>';
    
    	return $html;
    }
    
    */
    
    /**
	 * Table KMP for processing KMP algorithm
	 * @param string $p pattern
	 * @return array
	 */

	public function preKmp($p) {
		$kmpNext = array();
		$kmpNext[0] = -1;
		$i = 0;$m = strlen($p);
		$j = $kmpNext[0];
		
		while($i < $m) {
			while($j > -1 && $p[$i] != $p[$j])
				$j = $kmpNext[$j];
			$i++;
			$j++;
			if($p[$i] == $p[$j]) {
				$kmpNext[$i] = $kmpNext[$j];
			}else {
				$kmpNext[$i] = $j;
			}
		}
		
		return $kmpNext;
	}
    
    
	/**
	 * Matching a pattern in string,return number occurent of pattern in string
	 * if no matching ,return 0
	 * @param string $s string
	 * @param string $p pattern
	 * @return int
	 */
	public function kmpMatching($s,$p) {
		$i = 0;$j = 0;
		$result_count = 0;	//number of lan xuat hien pattern trong string
	
		$s = ltrim($s); $p = ltrim($p);
		$s = strtolower($s); $p = strtolower($p);
	
		/* Preprocessing */
		$kmpnext = $this->preKmp($p);		//table kmp
		$m = strlen($p);			//lenght of pattern
		$n = strlen($s);			//lenght of string
	
	
		/* Searching */
		while($j < $n) {
			while(($i > -1) && (substr($p,$i,1) != substr($s,$j,1)))
				$i = $kmpnext[$i];
			$i++;
			$j++;
	
			//matching all
			if($i >= $m) {
				$result_count++;
				$i = $kmpnext[$i];
			}
		}
	
		return $result_count;
	}
    
    
	
	/**
	 * Calculator point of result
	 * @param int $count number of 
	 * @param $p pattern
	 * @param $type type of description : name or shortdescription
	 * @return integer
	 */
	
	public function pointing($count, $type = "name") {
		$point = 0;
		if($count == 0) return 0;
	
		if($type == "name") $point += ($count - 1) * 5 + 10;
		else if($type == "shortdescription") $point += ($count -1) * 1 + 3;
	
		return $point;
	}
	
	
	/**
	 * calculator total point of pattern by product name and description
	 * return total point of pattern
	 * @param array $product array of 1 product contain name and description
	 * @param string $pattern string to search
	 * @return number
	 */
	public function calculator($product, $pattern) {
		$point = 0;
		$strName = $product['name'];
		$strDesc = $product['shortdescription'];
	
		$count_name = $this->kmpMatching($strName,$pattern);
		$count_desc = $this->kmpMatching($strDesc,$pattern);
	
		$point += $this->pointing($count_name,"name")
				 + $this->pointing($count_desc,"shortdescription");
	
	
		return $point;
	}
	
	/**
	 * Sorting reserve array with key on sub array
	 * @param array $a array to input
	 * @param string $subkey key of sub array on father array
	 * @return array
	 */
	public function subvalSortReserve($a,$subkey) {
		foreach($a as $k=>$v) {
			$b[$k] = strtolower($v[$subkey]);
		}
		arsort($b);
		foreach($b as $key=>$val) {
			$c[] = $a[$key];
		}
		return $c;
	}
	
	/**
	 * highlight sub string on father string with class important in css
	 * return new father string
	 * @param string $needle substring
	 * @param string $haystack father string
	 * @return string
	 */
	public function highlight($needle, $haystack){
		$ind = stripos($haystack, $needle);
		$len = strlen($needle);
		if($ind !== false){
			return substr($haystack, 0, $ind) . '<span class="important">' . substr($haystack, $ind, $len) . '</span>' .
					$this->highlight($needle, substr($haystack, $ind + $len));
		} else return $haystack;
	}
	
	
	/**
	 * Get data config in core_config_data table
	 * in config module system/configuration
	 * return config array
	 * @return array
	 */
	public function getConfigData($config = NULL) {
		$this->_config['thumbnail_height'] 			= Mage::getStoreConfig('autocomplete/config/thumbnail_height',Mage::app()->getStore());
		$this->_config['thumbnail_width'] 			= Mage::getStoreConfig('autocomplete/config/thumbnail_width',Mage::app()->getStore());
		$this->_config['query_delay'] 			= Mage::getStoreConfig('autocomplete/config/query_delay',Mage::app()->getStore());
		$this->_config['result_footer'] 		= Mage::getStoreConfig('autocomplete/config/result_footer',Mage::app()->getStore());
		$this->_config['result_header'] 		= Mage::getStoreConfig('autocomplete/config/result_header',Mage::app()->getStore());
		$this->_config['new_window'] 			= Mage::getStoreConfig('autocomplete/config/new_window',Mage::app()->getStore());
		$this->_config['show_product'] 			= Mage::getStoreConfig('autocomplete/config/show_product',Mage::app()->getStore());
		$this->_config['suggest_window_width'] 	= Mage::getStoreConfig('autocomplete/config/suggest_window_width',Mage::app()->getStore());
		$this->_config['min_char'] 				= Mage::getStoreConfig('autocomplete/config/min_char',Mage::app()->getStore());
		$this->_config['show_image'] 			= Mage::getStoreConfig('autocomplete/config/show_image',Mage::app()->getStore());
		$this->_config['desc_char'] 			= Mage::getStoreConfig('autocomplete/config/desc_char',Mage::app()->getStore());
		
		if($config == NULL) return $this->_config;
		else return $this->_config[$config];
	}
}