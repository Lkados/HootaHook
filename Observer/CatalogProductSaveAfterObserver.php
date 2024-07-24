<?php

namespace Hoota\HootaHook\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class CatalogProductSaveAfterObserver
 * @package Hoota\HootaHook\Observer
 */
class CatalogProductSaveAfterObserver implements ObserverInterface
{
    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var $this->logger =
     */
    protected $logger;

    /**
     * @var Json
     */
    protected $jsonSerializer;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * constructor
     *
     * @param Curl $curl
     * @param $this->logger = $logger
     * @param Json $jsonSerializer
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Curl $curl,
        LoggerInterface $logger,
        Json $jsonSerializer,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->curl = $curl;
        $this->logger = $logger;
        $this->jsonSerializer = $jsonSerializer;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        try {
            $product = $observer->getEvent()->getProduct();
            $storeId = $product->getStoreId();

            // Get the webhook URL from configuration
            $webhookUrl = $this->scopeConfig->getValue('hoota_hootahook/general/webhook_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
            if (!$webhookUrl) {
                throw new \Exception('Webhook URL is not configured.');
            }

            // Format the message content
            $message = sprintf(
                "Product Saved:\n\nID: %d\nName: %s\nSKU: %s\nPrice: %s",
                $product->getId(),
                $product->getName(),
                $product->getSku(),
                $product->getPrice()
            );

            // Prepare the data to be sent to the Discord webhook
            $data = [
                'content' => $message,
                'username' => 'Product Save Bot' // Optional: Change the username that appears in Discord
            ];

            // Serialize data to JSON
            $productJson = $this->jsonSerializer->serialize($data);

            // Set headers
            $this->curl->setHeaders([
                'Content-Type' => 'application/json'
            ]);

            // Send the data to the webhook
            $this->curl->post($webhookUrl, $productJson);

            // Log the response
            $response = $this->curl->getBody();
            $this->logger->info('Discord Webhook Response: ' . $response);
        } catch (\Exception $e) {
            $this->logger->error('Error in CatalogProductSaveAfterObserver: ' . $e->getMessage());
        }
    }
}
