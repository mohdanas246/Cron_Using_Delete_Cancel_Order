<?php

namespace Codilar\Cron\Controller\Adminhtml\DeleteOrders;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Registry;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\State;

class Index
{
    protected OrderRepositoryInterface $orderRepository;
    protected SearchCriteriaBuilder $searchCriteriaBuilder;
    protected LoggerInterface $logger;
    protected State $state;
    private Registry $registry;


    public function __construct(
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder    $searchCriteriaBuilder,
        LoggerInterface          $logger,
        State                     $state,
         Registry              $registry

    )
    {
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->logger = $logger;
        $this->state = $state;
        $this->registry = $registry;
    }

    public function execute()
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/testing.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $this->registry->register("isSecureArea", true);

        $searchCriteria = $this->searchCriteriaBuilder->addFilter('status', 'canceled')->create();
        $orders = $this->orderRepository->getList($searchCriteria)->getItems();

        foreach ($orders as $order) {
            $logger->info($order->getId());
            try {
                if ($this->shouldBeDeleted($order)) {
                    $orderId = $order->getId();
                    $this->orderRepository->delete($order);
                    $this->logMessage("Order $orderId deleted.");
                }
            } catch (\Exception $e) {
                $this->logMessage("Error deleting order: " . $e->getTraceAsString());
            }
        }
    }
    private function shouldBeDeleted($order): bool
    {
        $daysOld = 30;
        $orderCreatedAt = strtotime($order->getCreatedAt());
        $cutoffDate = strtotime("-$daysOld days");
        if ($orderCreatedAt < $cutoffDate) {
            return true;
        }
        return false;
    }
    private function logMessage($message)
    {
        $logFile = BP . '/var/log/cron_delete_orders.log';;
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - $message" . PHP_EOL, FILE_APPEND);
    }
}
