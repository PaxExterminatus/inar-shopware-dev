<?php declare (strict_types = 1);

namespace InAR\WebARManager;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\System\CustomField\CustomFieldTypes;

class WebARManager extends Plugin
{
    const CUSTOM_FIELD_REPOSITORY_NAME = 'inar_webar_custom_field_set';
    const FIELD_NAME_MODEL_ID = 'inar_webar_model_id';
    const FIELD_NAME_MODEL_USDZ = 'inar_webar_model_usdz';
    const FIELD_NAME_MODEL_GLB = 'inar_webar_model_glb';
    const FIELD_NAME_WEBAR_DISABLE = 'inar_webar_webar_disable';

    public function install(InstallContext $installContext): void
    {
        $customFieldRepository = $this->container->get('custom_field_set.repository');
        $customFieldRepository->create([[
            'name' => self::CUSTOM_FIELD_REPOSITORY_NAME,
            'config' => [
                'label' => [
                    'en-GB' => 'WebAR',
                    'de-DE' => 'WebAR',
                ],
            ],

            'customFields' => [
                [
                    'name' => self::FIELD_NAME_MODEL_ID,
                    'type' => CustomFieldTypes::INT,
                    'config' => [
                        'label' => [
                            'en-GB' => 'WebAR ID',
                            'de-DE' => 'WebAR ID',
                        ],
                        'helpText' => [
                            'en-GB' => 'Model ID in InAR API',
                            'de-DE' => 'Modell-ID in der InAR-API',
                        ],
                        'componentName' => 'sw-field',
                        'customFieldType' => 'int',
                        'customFieldPosition' => 1,
                        'type' => 'number',
                    ],
                ],
                [
                    'name' => self::FIELD_NAME_MODEL_USDZ,
                    'type' => CustomFieldTypes::TEXT,
                    'config' => [
                        'label' => [
                            'en-GB' => 'WebAR Usdz',
                            'de-DE' => 'WebAR Usdz',
                        ],
                        'helpText' => [
                            'en-GB' => 'Uploaded file name',
                            'de-DE' => 'Hochgeladener Dateiname',
                        ],
                        'componentName' => 'sw-field',
                        'customFieldType' => 'text',
                        'customFieldPosition' => 1,
                        'type' => 'text',
                    ],
                ],
                [
                    'name' => self::FIELD_NAME_MODEL_GLB,
                    'type' => CustomFieldTypes::TEXT,
                    'config' => [
                        'label' => [
                            'en-GB' => 'WebAR GLB',
                            'de-DE' => 'WebAR GLB',
                        ],
                        'helpText' => [
                            'en-GB' => 'Uploaded file name',
                            'de-DE' => 'Hochgeladener Dateiname',
                        ],
                        'componentName' => 'sw-field',
                        'customFieldType' => 'text',
                        'customFieldPosition' => 1,
                        'type' => 'text',
                    ],
                ],
                [
                    'name' => self::FIELD_NAME_WEBAR_DISABLE,
                    'type' => CustomFieldTypes::BOOL,
                    'config' => [
                        'label' => [
                            'en-GB' => 'Disabled',
                            'de-DE' => 'Deaktiviert',
                        ],
                        'helpText' => [
                            'en-GB' => 'You can turn off WebAR support for this product.',
                            'de-DE' => 'Sie können die WebAR-Unterstützung für dieses Produkt deaktivieren.',
                        ],
                        'componentName' => 'sw-switch-field',
                        'customFieldType' => 'checkbox',
                        'customFieldPosition' => 1,
                        'type' => 'checkbox',
                    ],
                ],
            ],
            'relations' => [
                ['entityName' => 'product'],
            ],
        ]], $installContext->getContext());

        parent::install($installContext);
    }

    public function activate(ActivateContext $context): void
    {
        $this->addProductListConfig();
    }

    public function deactivate(DeactivateContext $context): void
    {
        $this->removeProductListConfig();
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        $this->removeCustomField($uninstallContext);

        if (!$uninstallContext->keepUserData()) {
            $this->removeCustomFieldsData();
        }
    }

    private function addProductListConfig()
    {
        $connection = $this->container->get(Connection::class);

        $query = $connection->createQueryBuilder()
            ->select('id, value')
            ->from('user_config')
            ->where("user_config.key = 'grid.setting.sw-product-list'");

        $productListConfigs = $query->execute()->fetchAll();

        foreach ($productListConfigs as $config)
        {
            $value = json_decode($config['value']);

            if ($value->columns ?? false) {
                $filteredColumns = [];

                foreach ($value->columns as $column)
                {
                    if ($column->dataIndex !== 'webarModelId') {
                        $filteredColumns[] = $column;
                    }
                }

                $filteredColumns[] = [
                    'width' => 'auto',
                    'allowResize' => true,
                    'sortable' => false,
                    'visible' => true,
                    'align' => 'center',
                    'property' => 'webarModelId',
                    'dataIndex' => 'webarModelId',
                    'label' => 'WebAR Availability',
                ];
                $value->columns = $filteredColumns;

                $setValue = "'" . json_encode($value) . "'";
                $connection->executeUpdate(
                    "UPDATE user_config SET value = " . $setValue .
                    " WHERE LCASE(HEX(id)) = " . "'" . strtolower(bin2hex($config['id'])) . "'"
                );
            }
        }
    }

    private function removeProductListConfig()
    {
        $connection = $this->container->get(Connection::class);

        $query = $connection->createQueryBuilder()
            ->select('id, value')
            ->from('user_config')
            ->where("user_config.key = 'grid.setting.sw-product-list'");

        $productListConfigs = $query->execute()->fetchAll();

        foreach ($productListConfigs as $config)
        {
            $value = json_decode($config['value']);

            $configHasWebArColumn = false;

            if ($value->columns ?? false)
            {
                $filteredColumns = [];

                foreach ($value->columns as $column)
                {
                    if ($column->dataIndex !== 'webarModelId') {
                        $filteredColumns[] = $column;
                    } else {
                        $configHasWebArColumn = true;
                    }
                }

                if ($configHasWebArColumn)
                {
                    $value->columns = $filteredColumns;

                    $setValue = "'" . json_encode($value) . "'";
                    $connection->executeUpdate(
                        "UPDATE user_config SET value = " . $setValue .
                        " WHERE LCASE(HEX(id)) = " . "'" . strtolower(bin2hex($config['id'])) . "'"
                    );
                }
            }
        }
    }


    private function removeCustomFieldsData()
    {
        $connection = $this->container->get(Connection::class);

        $query = $connection->createQueryBuilder()
            ->select('product_id, product_version_id, language_id, custom_fields')
            ->from('product_translation');

        $productTranslations = $query->execute()->fetchAll();

        foreach ($productTranslations as $productTranslation)
        {
            if ($productTranslation['custom_fields'])
            {
                $data = json_decode($productTranslation['custom_fields'], true);

                unset($data[WebARManager::FIELD_NAME_MODEL_ID]);
                unset($data[WebARManager::FIELD_NAME_MODEL_GLB]);
                unset($data[WebARManager::FIELD_NAME_MODEL_USDZ]);
                unset($data[WebARManager::FIELD_NAME_WEBAR_DISABLE]);

                $setValue = count($data) ? "'" . json_encode($data) . "'" : 'NULL';

                $connection->executeUpdate(
                    "UPDATE product_translation SET custom_fields = " . $setValue .
                    " WHERE LCASE(HEX(product_id)) = " . "'" . strtolower(bin2hex($productTranslation['product_id'])) . "'" .
                    " AND LCASE(HEX(product_version_id)) = " . "'" . strtolower(bin2hex($productTranslation['product_version_id'])) . "'" .
                    " AND LCASE(HEX(language_id)) = " . "'" . strtolower(bin2hex($productTranslation['language_id'])) . "'");
            }
        }
    }

    private function removeCustomField(UninstallContext $uninstallContext)
    {
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');

        $fieldIds = $this->customFieldsExist($uninstallContext->getContext());

        if ($fieldIds) {
            $customFieldSetRepository->delete(array_values($fieldIds->getData()), $uninstallContext->getContext());
        }
    }

    private function customFieldsExist(Context $context): ?IdSearchResult
    {
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('name', [self::CUSTOM_FIELD_REPOSITORY_NAME]));

        $ids = $customFieldSetRepository->searchIds($criteria, $context);

        return $ids->getTotal() > 0 ? $ids : null;
    }

}
