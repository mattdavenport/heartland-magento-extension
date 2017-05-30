<?php

class Hps_Securesubmit_Model_Observer
{
    /**
     * @param Varien_Event_Observer $observer
     */
    public function customerAddressSaveBefore(Varien_Event_Observer $observer)
    {
        $customerAddress = $observer->getCustomerAddress(); /** @var $customerAddress Mage_Customer_Model_Address */
        if ($customerAddress->getId() && $customerAddress->hasDataChanges()) {
            /*
             * Need to load saved customer address as in some reason in Mage_Customer_AddressController::formPostAction()
             * instead of loading existing address, only the address id is assigned to the new object.
             * Also, because of this, need manually check whether the address has been changed.
             */
            $oldAddress = Mage::getModel('customer/address')->load($customerAddress->getId());
            foreach (array('street', 'city', 'region_id', 'postcode', 'country_id') as $field) {
                if ($customerAddress->getData($field) != $oldAddress->getData($field)) {
                    $customerAddress->setRemoveStoredCards(TRUE);
                    break;
                }
            }
        } else if ($customerAddress->isObjectNew()) {
            $customerAddress->setIsNewAddress(TRUE);
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function customerAddressSaveAfter(Varien_Event_Observer $observer)
    {
        $customerAddress = $observer->getCustomerAddress(); /** @var $customerAddress Mage_Customer_Model_Address */
        if ($customerAddress->getRemoveStoredCards()) {
            Mage::helper('hps_securesubmit')->removeStoredCards($customerAddress->getId());
        }
    }

    /**
     * Add layout handles for JS and CSS resources to checkout pages only if SecureSubmit is enabled.
     *
     * @param Varien_Event_Observer $observer
     */
    public function frontendAddLayoutHandles(Varien_Event_Observer $observer)
    {
        if (Mage::helper('hps_securesubmit')->isActive()) {
            $action = $observer->getAction(); /* @var $action Mage_Core_Controller_Varien_Action */
            switch ($action->getFullActionName()) {
                case 'opc_index_index':
                case 'onestepcheckout_index_index':
                case 'checkout_onepage_index':
                case 'onepagecheckout_index_index':
                case 'checkout_multishipping_billing':
                    $action->getLayout()->getUpdate()->addHandle('hps_securesubmit_add_js');
                    $action->getLayout()->getUpdate()->addHandle('hps_securesubmit_add_css');
                    if (Mage::helper('hps_securesubmit')->is3DSecureActive()) {
                        $action->getLayout()->getUpdate()->addHandle('hps_securesubmit_add_3dsecure');
                    }
            }
        }

    }
}
