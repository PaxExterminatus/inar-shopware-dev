import template from './inar-webar-manager.html.twig';
import './inar-webar-manager.scss';

const { Component, Mixin } = Shopware;

const FIELD_NAME_MODEL_ID = 'inar_webar_model_id';
const FIELD_NAME_MODEL_USDZ = 'inar_webar_model_usdz';
const FIELD_NAME_MODEL_GLB = 'inar_webar_model_glb';
const FIELD_NAME_WEBAR_DISABLE = 'inar_webar_webar_disable';

/**
 * @typedef {{
 *     success: boolean
 *     action: string
 *     message: string
 *     model: {
 *         id: number
 *         name: string
 *         scalable: boolean
 *         sku: string
 *         webArModelId: number
 *     }
 * }} WebArModelResponseData
 */

Component.register('inar-webar-manager', {
    template,

    inject: ['systemConfigApiService', 'WebARApiService'],

    mixins: [
        Mixin.getByName('notification'),
    ],

    data() {
        return {
            state: {
                loading: {
                    glb: false,
                    usdz: false,
                },
            },

            input: {
                glb: '',
                usdz: '',
            },

            modal: {
                on: false,
                text: '',
                title: '',
            },
        };
    },

    computed: {
        storeGlb: {
            get() {
                if (this.input.glb) return this.input.glb;
                return this.storageCustomFields ? this.storageCustomFields[FIELD_NAME_MODEL_GLB] : '';
            },
            set(value) {
                this.input.glb = value;
                if (!this.$store.state.swProductDetail.product.customFields) {
                    this.$store.state.swProductDetail.product.customFields = {};
                }
                this.$store.state.swProductDetail.product.customFields[FIELD_NAME_MODEL_GLB] = value;
            },
        },

        storeUsdz: {
            get() {
                if (this.input.usdz) return this.input.usdz;
                return this.storageCustomFields ? this.storageCustomFields[FIELD_NAME_MODEL_USDZ] : '';
            },
            set(value) {
                this.input.usdz = value;
                if (!this.$store.state.swProductDetail.product.customFields) {
                    this.$store.state.swProductDetail.product.customFields = {};
                }
                this.$store.state.swProductDetail.product.customFields[FIELD_NAME_MODEL_USDZ] = value;
            },
        },

        storeDisabled: {
            get() {
                return this.storageCustomFields ? this.storageCustomFields[FIELD_NAME_WEBAR_DISABLE] : false;
            },
            set(value) {
                if (!this.$store.state.swProductDetail.product.customFields) {
                    this.$store.state.swProductDetail.product.customFields = {};
                }
                this.$store.state.swProductDetail.product.customFields[FIELD_NAME_WEBAR_DISABLE] = value;
            },
        },

        productIsNew() {
            return this.storageProduct._isNew;
        },

        productId() {
            return this.storageProduct.id;
        },

        productName() {
            return this.storageProduct.name;
        },

        productNumber() {
            return this.storageProduct.productNumber;
        },

        storageProduct() {
            return Shopware.State.get('swProductDetail').product;
        },

        storageCustomFields() {
            return this.storageProduct.customFields;
        },

    },

    methods: {
        setModelId(id) {
            this.$store.state.swProductDetail.product.customFields[FIELD_NAME_MODEL_ID] = id;
        },
        /**
         * @param {DragEvent} event
         */
        onDropFilesGlb(event) {
            this.acceptFileGlb(event.dataTransfer.files[0]);
        },

        /**
         * @param {DragEvent} event
         */
        onDropFilesUsdz(event) {
            this.acceptFileUsdz(event.dataTransfer.files[0]);
        },

        formDataMake() {
            const formData = new FormData();
            formData.append('sku', this.productNumber);
            formData.append('name', this.productName);

            return formData;
        },

        onFileInputChangeGlb(event) {
            this.acceptFileGlb(event.target.files[0]);
        },

        /**
         * @returns {PromiseLike<*>|Promise<*>}
         */
        updateProduct({id, mid, glb, usdz}) {
            return this.WebARApiService.update({id, mid, glb, usdz,})
        },

        onFileInputChangeUsdz(event) {
            this.acceptFileUsdz(event.target.files[0])
        },

        /**
         * @param {File} file
         */
        acceptFileGlb(file) {
            if (this.checkFile(file,'glb',10))
            {
                const formData = this.formDataMake();
                formData.append('glb', file);

                this.state.loading.glb = true;
                this.inarApiSendWebarData(formData)
                  .then((response) => {
                      this.updateProduct({
                          id: this.productId,
                          mid: response.model.id,
                          glb: file.name,
                      })
                        .then(() => {
                            this.storeGlb = file.name;
                            this.setModelId(response.model.id);
                        })
                        .finally(() => {
                            this.state.loading.glb = false;
                        });
                  }).catch(() => {
                      this.state.loading.glb = false;
                  })
            }
        },

        /**
         * @param {File} file
         */
        acceptFileUsdz(file) {
            if (this.checkFile(file, 'usdz', 10))
            {
                const formData = this.formDataMake();
                formData.append('usdz', file);

                this.state.loading.usdz = true;
                this.inarApiSendWebarData(formData)
                    .then((response) => {
                        this.updateProduct({
                            id: this.productId,
                            mid: response.model.id,
                            usdz: file.name,
                        })
                          .then(() => {
                              this.storeUsdz = file.name;
                              this.setModelId(response.model.id);
                          })
                          .finally(() => {
                              this.state.loading.usdz = false;
                          });
                    }).catch(() => {
                        this.state.loading.usdz = false;
                    })
            }
        },

        /**
         * @param {File} file
         * @param {string} extension
         * @param {number} megabytes
         * @returns {boolean}
         */
        checkFile(file, extension, megabytes) {
            let success = true;

            if (!this.productName) {
                this.errorProductName();
                success = false;
            }

            if (!this.productNumber) {
                this.errorProductNumber();
                success = false;
            }

            if (!file.name.includes(`.${extension}`)) {
                this.errorFileExtension(extension);
                success = false;
            }

            if (file.size > megabytes * 1024 * 1024) {
                this.errorFileSize();
                success = false;
            }

            return success;
        },

        errorProductName() {
            this.createNotificationError({
                message: 'Missing product name.',
            });
        },

        errorProductNumber() {
            this.createNotificationError({
                message: 'Missing product number.',
            });
        },

        errorFileExtension(extension) {
            this.createNotificationError({
                message: `The file has the wrong extension. Expected ${extension}`,
            });
        },

        errorFileSize() {
            this.createNotificationError({
                message: 'The file is too large. File size should not exceed 10 megabytes.',
            });
        },

        /**
         * @param {FormData} body
         * @returns {Promise<WebArModelResponseData>}
         */
        inarApiSendWebarData(body) {
            const requestParams = {
                method: 'POST',
                body,
                redirect: 'follow',
                headers: new Headers(),
            };

            return this.systemConfigApiService.getValues('WebARManager.config')
                .then((config) => {
                    const authToken = config['WebARManager.config.authToken'];
                    const webAREndPointUrl = 'https://qa-app.theinar.com/api/webArModel';
                    if (!authToken) {
                        this.modalTokenNotSpecified();
                        throw 'The access token is not specified in the plugin settings. Specify the token in the plugin settings.';
                    }
                    else {
                        requestParams.headers.append("Accept", "application/json");
                        requestParams.headers.append("Authorization", "Bearer " + authToken);

                        return this.inarApiSendWebarFetch(webAREndPointUrl, requestParams,)
                            .then(response => {
                                return response;
                            });
                    }
                });

        },

        /**
         * @param {string} url
         * @param {{}} params
         * @returns {Promise<WebArModelResponseData>}
         */
        inarApiSendWebarFetch(url, params) {
            return fetch(url, params)
                .then(response => {
                    if (response.ok) {
                        return response.json();
                    }
                    throw response;
                })
                .then(data => {
                    return data;
                })
                .catch(error => {
                    if (error.status === 401) this.modalTokenUnauthorized();
                    return Promise.reject(error);
                });
        },

        modalOff() {
            this.modal.on = false;
        },

        modalOn(title, text) {
            const modal = this.modal;
            modal.on = true;
            if (text) modal.text = text;
            if (title) modal.title = title;
        },

        modalTokenUnauthorized() {
            this.modalOn(
                'Access Token Error',
                'Your access token failed authorization. Specify the correct token in the plugin settings.'
            );
        },

        modalTokenNotSpecified() {
            this.modalOn(
                'Access Token Error',
                'The access token is not specified in the plugin settings. Specify the token in the plugin settings.'
            );
        },
    },
});
