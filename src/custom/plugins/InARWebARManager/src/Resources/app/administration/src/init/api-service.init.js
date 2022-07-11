import WebARApiService from '../core/service/api/WebARApiService';

const { Application } = Shopware;

Application.addServiceProvider('WebARApiService', (container) => {

    const initContainer = Application.getContainer('init');

    return new WebARApiService(initContainer.httpClient, container.loginService);
});
