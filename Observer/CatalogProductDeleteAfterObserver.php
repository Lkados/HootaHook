<?php

namespace Hoota\HootaHook\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class CatalogProductDeleteAfterObserver implements ObserverInterface
{
    protected $curl;
    protected $logger;
    protected $jsonSerializer;
    private $webhookUrl;

    public function __construct(
        Curl $curl,
        LoggerInterface $logger,
        Json $jsonSerializer,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->curl = $curl;
        $this->logger = $logger;
        $this->jsonSerializer = $jsonSerializer;
        $this->webhookUrl = 'https://discord.com/api/webhooks/1263679537074405386/-FGT_Ir5taAjS8UTWaVEfodUmdlkRfv_8cF9OQd-z-W9cm9Y-BcHgrdXlev7JZV7rTF0'; // Hardcoded URL
    }

    public function execute(Observer $observer)
    {
        try {
            $product = $observer->getEvent()->getProduct();
            $message = sprintf(
                "Product Deleted:\n\nID: %d\nName: %s\nSKU: %s",
                $product->getId(),
                $product->getName(),
                $product->getSku()
            );

            $data = ['content' => $message, 'username' => 'Product Delete Bot'];
            $productJson = $this->jsonSerializer->serialize($data);

            $this->curl->setHeaders(['Content-Type' => 'application/json']);
            $this->curl->post($this->webhookUrl, $productJson);

            $response = $this->curl->getBody();
            $this->logger->info('Discord Webhook Response: ' . $response);
        } catch (\Exception $e) {
            $this->logger->error('Error in CatalogProductDeleteAfterObserver: ' . $e->getMessage());
        }
    }
}
