const ApiService = Shopware.Classes.ApiService;

class WebARApiService extends ApiService {

  constructor(httpClient, loginService, apiEndpoint = '_action')
  {
      super(httpClient, loginService, apiEndpoint);
  }

  /**
   * @param {{
   *   id: string
   *   mid: string
   *   usdz: string|null
   *   glb: string|null
   * }} data
   * @returns {PromiseLike<any> | Promise<any>}
   */
  update(data)
  {
    const headers = this.getBasicHeaders();

    const postData = {
      id: data.id,
      mid: data.mid,
      usdz: data.usdz || null,
      glb: data.glb || null,
    };

    return this.httpClient.post(`${this.getApiBasePath()}/theinarupdate`, postData, {headers})
      .then(response => {
        return ApiService.handleResponse(response);
      });
  }
}

export default WebARApiService;
