import template from './inar-drop-wrapper.html.twig'
import './inar-drop-wrapper.scss';

Shopware.Component.register('inar-drop-wrapper', {
  template,

  props: {
    loading: {
      type: Boolean,
      default: false,
    },
  },

  data() {
    return {
      state: {
        dragover: false,
      },
    };
  },

  computed: {
    wrapperClass() {
      return {
        loading: this.loading,
        dragover: this.state.dragover,
      };
    },
  },

  methods: {
    dragover(event) {
      event.preventDefault();
      this.state.dragover = true;
    },

    dragleave(event) {
      this.state.dragover = false;
    },

    drop(event) {
      this.$emit('drop', event);
      this.state.dragover = false;
    },
  },
});
