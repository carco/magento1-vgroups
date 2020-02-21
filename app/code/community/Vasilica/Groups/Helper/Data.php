<?php

class Vasilica_Groups_Helper_Data extends Mage_Core_Helper_Abstract {
	protected $_sizes = array(
        194 => 'accesories_size', 
	);

	protected $_attributes = null;
	protected $_options    = null;

	public function storeSizes($collection) {
		//get all ids

		$productIDs = array();
		foreach($collection as $item) {
			$aSet = $item->getAttributeSetId();
			if(isset($this->_sizes[$aSet]) && $item->getTypeId() == 'configurable') {
				$productIDs[$aSet][] = $item->getId();
			}
		}

		$childrenIDs = array();
		$usedAttribute = array();
		
		$attribute_sorted_values = array();

		foreach($productIDs as $aSet=>$ids) {
			$attribute = $this->_sizes[$aSet];
			$usedAttribute[$aSet] = $attribute;

			if(array_key_exists($aSet, $attribute_sorted_values) == false) {
				$attribute_sorted_values[$aSet] = array();
				$attribute_model = Mage::getModel('eav/entity_attribute')->load( $attribute, 'attribute_code');
				$option_col = Mage::getResourceModel( 'eav/entity_attribute_option_collection')
					->setAttributeFilter( $attribute_model->getId() )
					->setStoreFilter()
					->setPositionOrder( 'ASC' );
				$option_col->getSelect()->order('main_table.sort_order ASC');
				$attr_values = $option_col->toArray();
				foreach($attr_values['items'] as $a) {
					$attribute_sorted_values[$aSet][$a['option_id']] = $a['sort_order'];
				}
				asort($attribute_sorted_values[$aSet]);
			}

			//get children collection

			$childrenCollection = Mage::getResourceModel('catalog/product_type_configurable_product_collection')
				->setFlag('require_stock_items', true)
				->setFlag('product_children', true);

			//add products filter @see Mage_Catalog_Model_Resource_Product_Type_Configurable_Product_Collection:: setProductFilter)
			//$this->getSelect()->where('link_table.parent_id = ?', (int) $product->getId());

			$childrenCollection->getSelect()->where('link_table.parent_id IN (?)', $ids);

			if($store = Mage::app()->getStore()) {
				$childrenCollection->addStoreFilter($store);
			}
			
			$childrenCollection->addAttributeToSelect($usedAttribute[$aSet]);

			$childrenCollection->getSelect()->group('e.entity_id');  //seem to be products with same parent_id ...

			foreach($childrenCollection as $children) {
				$attribute = $usedAttribute[$children->getAttributeSetId()];
				if($children->isSalable() && $children->getData($attribute)) {
					$childrenIDs[$children->getParentId()][$attribute][$children->getData($attribute)] = $children->getAttributeText($attribute);
				}
			}
		}

		foreach($collection as $item) {
			if(isset($childrenIDs[$item->getId()])) {
				foreach($childrenIDs[$item->getId()] as $attr => $sizes) {
					$good_sizes = array();
					foreach ($usedAttribute as $aSet => $attribute) {
						foreach($attribute_sorted_values[$aSet] as $k => $vv) {
							foreach($sizes as $kk => $v) {
								if($kk == $k) {
									$good_sizes[$k] = $v;
								}
							}
						}
					}
					$item->setVsizes($good_sizes);
					break; //keep only first
				}
			} else {
				$item->setVsizes(array());
			}
		}
		return $this;
	}
}
