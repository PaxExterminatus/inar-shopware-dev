import template from './inar-message.html.twig';
import './inar-message.scss';

Shopware.Component.register('inar-message', {
    template,

    props: {
        type: {
            type: String,
            default: '',
        },

        message: {
            type: String,
            default: '',
        },

        label: {
            type: String,
            default: '',
        },
    },

    computed: {
        icon() {
            if (this.type === 'error') return 'default-badge-error';
            if (this.type === 'success') return 'small-default-checkmark-line-medium';
        },

        color() {
            if (this.type === 'error') return '#ff6961';
            if (this.type === 'success') return '#57d9a3';
        },
    },
});
