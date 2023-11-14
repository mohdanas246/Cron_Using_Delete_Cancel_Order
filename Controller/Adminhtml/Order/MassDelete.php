<?php declare(strict_types=1);

namespace Codilar\Cron\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Sales\Api\OrderRepositoryInterface;

class MassDelete extends Action
{
    protected OrderRepositoryInterface $orderRepository;
    public function __construct(
        Action\Context $context,
        OrderRepositoryInterface $orderRepository
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
    }
    public function execute()
    {
        $selected = $this->getRequest()->getParam('selected');

        if (!empty($selected)) {
            foreach ($selected as $orderId) {
                try {
                    $order = $this->orderRepository->get($orderId);
                    $this->orderRepository->delete($order);
                } catch (\Exception $e) {
                    $this->messageManager->addError(__('Error deleting order with ID %1: %2', $orderId, $e->getMessage()));
                }
            }
            $this->messageManager->addSuccessMessage(__('Selected order(s) have been deleted.'));
        } else {
            $this->messageManager->addErrorMessage(__('Please select order(s) to delete.'));
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('sales/order/index');
    }
}
