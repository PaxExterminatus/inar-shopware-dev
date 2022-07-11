<?php declare (strict_types = 1);

namespace InAR\WebARManager\Controller;

use InAR\WebARManager\WebARManager;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;

/**
 * @RouteScope(scopes={"api"})
 */
class WebARController
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    /**
     * ApiController constructor.
     * @param Connection $connection
     * @param SystemConfigService $systemConfigService
     */
    public function __construct(Connection $connection, SystemConfigService $systemConfigService)
    {
        $this->connection = $connection;
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * @Route("/api/_action/theinarupdate", name="api.action.theinarupdate", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     */
    public function theinarupdate(Request $request): JsonResponse
    {
        $modelId = $request->get('mid');
        $product_id = $request->get('id');
        $glb = $request->get('glb') ?? null;
        $usdz = $request->get('usdz') ?? null;

        $product = $this->connection
            ->createQueryBuilder()
            ->select('id, version_id')
            ->from('product')
            ->where('lcase(hex(id)) = "' . $product_id . '"')
            ->execute()
            ->fetchAll()[0];

        if ($modelId && $product_id)
        {
            $languages = $this->connection
                ->createQueryBuilder()
                ->select('id, name')
                ->from('language')
                ->execute()
                ->fetchAll();

            foreach ($languages as $lang)
            {
                $productTranslations = $this->connection
                    ->createQueryBuilder()
                    ->select('custom_fields')
                    ->from('product_translation')
                    ->where('lcase(hex(product_id)) = "' . $product_id . '"')
                    ->andWhere('lcase(hex(language_id)) = "' . strtolower(bin2hex($lang['id'])) . '"')
                    ->execute()
                    ->fetchAll();

                if (count($productTranslations))
                {
                    foreach ($productTranslations as $productTranslation)
                    {
                        $customFieldsData = [];
                        if ($productTranslation['custom_fields']) {
                            $customFieldsData = json_decode($productTranslation['custom_fields'], true);
                        }

                        $customFieldsData[WebARManager::FIELD_NAME_MODEL_ID] = $modelId;
                        if ($glb) $customFieldsData[WebARManager::FIELD_NAME_MODEL_GLB] = $glb;
                        if ($usdz) $customFieldsData[WebARManager::FIELD_NAME_MODEL_USDZ] = $usdz;

                        $sqlCustomFieldsData = "'" . json_encode($customFieldsData) . "'";
                        $sqlProductId = "'" . $product_id . "'";
                        $sqlLanguageId = "'" . strtolower(bin2hex($lang['id'])) . "'";
                        $sqlVersionId = "'" . strtolower(bin2hex($product['version_id'])) . "'";

                        $this->connection->executeUpdate(
                            "UPDATE product_translation SET custom_fields = " . $sqlCustomFieldsData .
                            " WHERE LCASE(HEX(product_id)) = " . $sqlProductId .
                            " AND LCASE(HEX(language_id)) = " . $sqlLanguageId .
                            " AND LCASE(HEX(product_version_id)) = " . $sqlVersionId
                        );
                    }
                }
                else {
                    $customFieldsData[WebARManager::FIELD_NAME_MODEL_ID] = $modelId;
                    if ($glb) $customFieldsData[WebARManager::FIELD_NAME_MODEL_GLB] = $glb;
                    if ($usdz) $customFieldsData[WebARManager::FIELD_NAME_MODEL_USDZ] = $usdz;

                    $this->connection->insert(
                        'product_translation',
                        [
                            'product_id' => $product['id'],
                            'product_version_id' => $product['version_id'],
                            'language_id' => $lang['id'],
                            'created_at' => date("Y-m-d H:i:s"),
                            'custom_fields' => json_encode($customFieldsData),
                        ]
                    );
                }
            }
        }

        return new JsonResponse(['success' => true], 200);
    }

    //[version compatibility 6.3]
    /**
     * @Route("/api/v2/_action/theinarupdate", name="api.action.theinarupdate63", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     */
    public function theinarupdate63(Request $request): JsonResponse
    {
        return $this->theinarupdate($request);
    }
}