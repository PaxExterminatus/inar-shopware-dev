import template from './sw-product-list.twig';

const {Component} = Shopware;

Component.override('sw-product-list', {
    template,
    computed: {
      productColumns() {
        return [
          ...this.getProductColumns(),
          {
            property: 'webarModelId',
            dataIndex: 'webarModelId',
            label: 'WebAR availability',
            allowResize: true,
            align: 'center',
            sortable: false,
          }
        ];
      },
    },
  
  },
);
