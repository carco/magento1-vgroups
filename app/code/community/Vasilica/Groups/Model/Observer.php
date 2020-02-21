<?php
class Vasilica_Groups_Model_Observer {



    public function addProductGroups($observer) {
        try {
            $product = $observer->getEvent()->getProduct();

            if ($product->getVgroup()) {
                $collection = Mage::getModel('catalog/product')->getCollection();
                $this->_applyCollectionFilters($collection, $product);
                $product->setVgroups($collection);
            } else {
                $product->setVgroups(new Varien_Data_Collection());
            }

        } catch (Exception $e) {
            Mage::logException($e);
        }

    }

    public function addProductCollectionGroupsAndSizes($observer) {



        //add group
        $collection = $observer->getEvent()->getCollection();

        if($collection instanceof Mage_Catalog_Model_Resource_Product_Type_Configurable_Product_Collection) {
            //do nothing
            return $this;
        }

        if(!($collection instanceof Mage_Catalog_Model_Resource_Product_Collection)) {
            return $this;
        }


        //"normal" product collection, add groups (product with same attribute group )

        try {


            //skip collection for children products

            $groups = array();

            foreach ($collection as $product) {

                //add Group
                if ($product->getVgroup()) {
                    if (empty($groups[$product->getVgroup()])) {
                        $groupCollection = Mage::getModel('catalog/product')->getCollection();
                        $this->_applyCollectionFilters($groupCollection, $product);
                        $groups[$product->getVgroup()] = $groupCollection;
                    }
                }

            }

            foreach ($collection as $product) {
                if (!empty($groups[$product->getVgroup()])) {
                    $product->setVgroups($groups[$product->getVgroup()]);
                } else {
                    $product->setVgroups(new Varien_Data_Collection());
                }
            }


            //add sizes
            Mage::helper('vgroups')->storeSizes($collection);


        } catch (Exception $e) {

            Mage::logException($e);
        }

    }

    protected function _applyCollectionFilters($collection, $product) {
        $collection->addAttributeToSelect('*');
        $collection->addAttributeToFilter('vgroup', $product->getVgroup());
        $collection
           // ->addMinimalPrice()
            //->addFinalPrice()
           // ->addTaxPercents()
            //->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
            ->addUrlRewrite();
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);
    }





}