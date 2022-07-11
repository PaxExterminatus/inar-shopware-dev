<?php declare (strict_types = 1);

namespace InAR\WebARManager\Storefront\Page\Product\Subscriber;

use Doctrine\DBAL\Connection;
use InAR\WebARManager\WebARManager;
use Shopware\Core\Framework\Struct\ArrayEntity;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductPageLoadedSubscriber implements EventSubscriberInterface
{
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public static function getSubscribedEvents()
    {
        return [
            ProductPageLoadedEvent::class => 'onProductPageLoaded',
        ];
    }

    public function onProductPageLoaded(ProductPageLoadedEvent $event): void
    {
        $id = $event->getPage()->getProduct()->getId();
        $parentId = $event->getPage()->getProduct()->getParentId();

        if ($parentId != null) {
            $id = $parentId;
        }

        $query = $this->connection->executeQuery(
            "SELECT custom_fields FROM product_translation WHERE LCASE(HEX(product_id)) = '" . $id . "'"
        );

        $productTranslations = $query->fetchAll();

        foreach ($productTranslations as $pt)
        {
            if (!empty($pt['custom_fields']))
            {
                $data = json_decode($pt['custom_fields'], true);

                if (array_key_exists(WebARManager::FIELD_NAME_MODEL_ID, $data))
                {
                    $config['webarModelId'] = $data[WebARManager::FIELD_NAME_MODEL_ID];
                    $config['webArEnabled'] =
                        $data[WebARManager::FIELD_NAME_MODEL_USDZ] &&
                        $data[WebARManager::FIELD_NAME_MODEL_GLB] &&
                        $data[WebARManager::FIELD_NAME_MODEL_ID] &&
                        !($data[WebARManager::FIELD_NAME_WEBAR_DISABLE] ?? false);

                    $page = $event->getPage();
                    $page->addExtension('inar', new ArrayEntity($config));
                    break;
                }
            }
        }
    }
}
