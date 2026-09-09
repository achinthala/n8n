<?php
/**
 * OrderRestrictions Save Controller.
 * @category  Dotcomweavers
 * @package   Dotcomweavers_OrderRestrictions
 * @author    Dotcomweavers
 * @copyright Copyright (c) Dotcomweavers
 */

namespace Dotcomweavers\OrderRestrictions\Controller\Adminhtml\Restrictions\Rule;

class Save extends \Dotcomweavers\OrderRestrictions\Controller\Adminhtml\Restrictions\Rule
{
    /**
     * Rule save action
     *
     * @return void
     */
    public function execute()
    {
        if (!$this->getRequest()->getPostValue()) {
            $this->_redirect('orderrestrictions/*/');
        }

        try {
            /** @var $model \Dotcomweavers\OrderRestrictions\Model\Rule */
            $model = $this->ruleFactory->create();
            $this->_eventManager->dispatch(
                'adminhtml_controller_restrictions_rules_prepare_save',
                ['request' => $this->getRequest()]
            );
            $data = $this->getRequest()->getPostValue();

            /* $data['stores'] = implode(',', $this->getRequest()->getParam('stores')); OLD Code */
            /* @Start : Implode code error fixed by Ram - 20th June 2023 */
            if(is_array($this->getRequest()->getParam('stores')))
			{
				$data['stores'] = implode(',', $this->getRequest()->getParam('stores'));
			}
			else if (is_string($this->getRequest()->getParam('stores')))
			{
				$data['stores'] = implode(' ', (array)$this->getRequest()->getParam('stores'));
			}
			/* @Stop : Implode code error fixed by Ram - 20th June 2023 */
			
            if ($this->getRequest()->getParam('customer_groups') !== null) {
                $data['customer_groups'] = implode(',', $this->getRequest()->getParam('customer_groups'));
            } else {
                $data['customer_groups'] = "";
            }
            
            $id = $this->getRequest()->getParam('restriction_id');
            if ($id) {
                $model->load($id);
            }

            $validateResult = $model->validateData(new \Magento\Framework\DataObject($data));
            if ($validateResult !== true) {
                foreach ($validateResult as $errorMessage) {
                    $this->messageManager->addErrorMessage($errorMessage);
                }
                $this->_session->setPageData($data);
                $this->_redirect('orderrestrictions/*/edit', ['id' => $model->getId()]);
                return;
            }

            $data = $this->prepareData($data);
            $model->loadPost($data);

            $this->_session->setPageData($model->getData());
            
            $model->save();
            $this->messageManager->addSuccessMessage(__('You saved the rule.'));
            $this->_session->setPageData(false);
            if ($this->getRequest()->getParam('back')) {
                $this->_redirect('orderrestrictions/*/edit', ['id' => $model->getId()]);
                return;
            }
            $this->_redirect('orderrestrictions/*/');
            return;
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $id = (int)$this->getRequest()->getParam('restriction_id');
            if (!empty($id)) {
                $this->_redirect('orderrestrictions/*/edit', ['id' => $id]);
            } else {
                $this->_redirect('orderrestrictions/*/new');
            }
            return;
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __('Something went wrong while saving the rule data. Please review the error log.')
            );
            $this->logger->critical($e);
            $data = !empty($data) ? $data : [];
            $this->_session->setPageData($data);
            $this->_redirect('orderrestrictions/*/edit', ['id' => $this->getRequest()->getParam('restriction_id')]);
            return;
        }
    }

    /**
     * Prepares specific data
     *
     * @param array $data
     * @return array
     */
    protected function prepareData($data)
    {

        if (isset($data['rule']['conditions'])) {
            $data['conditions'] = $data['rule']['conditions'];
        }

        unset($data['rule']);

        return $data;
    }
}
