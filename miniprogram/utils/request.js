const config = require('./config');
const { getToken, clearAuth } = require('./token');
const { MSG, showFail } = require('./toast');

/** 当前 loading 计数，避免多次 show/hide 闪烁 */
let loadingCount = 0;

/**
 * 显示 Loading
 */
function showLoading(title = '加载中...') {
  if (loadingCount === 0) {
    wx.showLoading({ title, mask: true });
  }
  loadingCount += 1;
}

/**
 * 隐藏 Loading
 */
function hideLoading() {
  loadingCount = Math.max(0, loadingCount - 1);
  if (loadingCount === 0) {
    wx.hideLoading();
  }
}

/**
 * 统一错误提示
 */
function showError(msg) {
  showFail(msg || MSG.LOAD_FAIL);
}

/**
 * 处理 Token 过期，尝试重新登录
 */
async function handleUnauthorized(retryFn) {
  clearAuth();
  try {
    const { login } = require('./auth');
    await login();
    if (typeof retryFn === 'function') {
      return retryFn();
    }
  } catch {
    showError('登录已过期，请重新打开小程序');
  }
  throw new Error('未授权');
}

/**
 * 封装 wx.request
 * @param {object} options
 * @param {string} options.url - 相对路径，如 /clouds/today
 * @param {string} [options.method='GET']
 * @param {object} [options.data]
 * @param {boolean} [options.auth=true] - 是否携带 JWT
 * @param {boolean} [options.loading=false] - 是否显示 loading
 * @param {boolean} [options.retryOn401=true] - 401 时是否自动重登并重试
 */
function request(options) {
  const {
    url,
    method = 'GET',
    data = {},
    auth = true,
    loading = false,
    retryOn401 = true,
  } = options;

  const doRequest = () => new Promise((resolve, reject) => {
    const header = {
      'Content-Type': 'application/json',
      Accept: 'application/json',
    };
    if (auth) {
      const token = getToken();
      if (token) {
        header.Authorization = `Bearer ${token}`;
      }
    }

    if (loading) showLoading();

    wx.request({
      url: `${config.baseURL}${url}`,
      method,
      data,
      header,
      success: (res) => {
        if (loading) hideLoading();

        const { statusCode } = res;
        const body = res.data || {};

        // HTTP 401 或未授权
        if (statusCode === 401) {
          if (retryOn401 && auth) {
            handleUnauthorized(doRequest).then(resolve).catch(reject);
            return;
          }
          showError('请先登录');
          reject(new Error('401'));
          return;
        }

        if (statusCode >= 400) {
          showError(body.msg || '网络错误');
          reject(new Error(body.msg || `HTTP ${statusCode}`));
          return;
        }

        // 业务 code 非 0
        if (body.code !== undefined && body.code !== 0) {
          showError(body.msg || '操作失败');
          const err = new Error(body.msg || '业务错误');
          err.code = body.code;
          reject(err);
          return;
        }

        resolve(body);
      },
      fail: (err) => {
        if (loading) hideLoading();
        showError(MSG.NETWORK_FAIL);
        reject(err);
      },
    });
  });

  return doRequest();
}

/**
 * GET 请求
 */
function get(url, data = {}, options = {}) {
  return request({ url, method: 'GET', data, ...options });
}

/**
 * POST 请求
 */
function post(url, data = {}, options = {}) {
  return request({ url, method: 'POST', data, ...options });
}

/**
 * PATCH 请求
 */
function patch(url, data = {}, options = {}) {
  return request({ url, method: 'PATCH', data, ...options });
}

/**
 * 上传云朵图片（multipart/form-data）
 * @param {string} filePath - 本地临时图片路径
 * @param {object} formData - 额外表单字段
 * @param {object} [options]
 * @param {boolean} [options.loading=true] - 是否显示 wx.showLoading
 */
function uploadCloud(filePath, formData = {}, options = {}) {
  const { loading: useLoading = true } = options;

  return new Promise((resolve, reject) => {
    const token = getToken();
    if (useLoading) showLoading('正在上传云朵...');

    wx.uploadFile({
      url: `${config.baseURL}/clouds/upload`,
      filePath,
      name: 'image',
      formData,
      header: {
        Authorization: token ? `Bearer ${token}` : '',
        Accept: 'application/json',
      },
      success: (res) => {
        if (useLoading) hideLoading();
        let body = {};
        try {
          body = typeof res.data === 'string' ? JSON.parse(res.data) : (res.data || {});
        } catch {
          showError(res.statusCode >= 400 ? `上传失败 (${res.statusCode})` : '响应解析失败');
          reject(new Error('parse error'));
          return;
        }

        if (res.statusCode === 401) {
          handleUnauthorized(() => uploadCloud(filePath, formData)).then(resolve).catch(reject);
          return;
        }

        if (res.statusCode >= 400 || (body.code !== undefined && body.code !== 0)) {
          showError(body.msg || MSG.UPLOAD_FAIL);
          const err = new Error(body.msg || '上传失败');
          err.code = body.code;
          reject(err);
          return;
        }

        resolve(body);
      },
      fail: (err) => {
        if (useLoading) hideLoading();
        showError(MSG.UPLOAD_FAIL);
        reject(err);
      },
    });
  });
}

module.exports = {
  request,
  get,
  post,
  patch,
  uploadCloud,
  showLoading,
  hideLoading,
  showError,
};
