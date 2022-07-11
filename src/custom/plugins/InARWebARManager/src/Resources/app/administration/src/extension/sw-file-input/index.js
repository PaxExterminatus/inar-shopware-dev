import template from './sw-file-input.html.twig';

const { Component } = Shopware;

Component.override("sw-file-input", {
    template,
    methods: {
        onFileInputChange(event) {
            this.$emit('onFileInputChange', event);
        },
    },
});
