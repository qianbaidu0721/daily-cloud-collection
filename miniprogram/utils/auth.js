const { post } = require('./request');
const { getToken, saveAuth, clearAuth } = require('./token');

/**
 * 微信登录并换取 JWT
 */
function login() {
  return new Promise((resolve, reject) => {
    wx.login({
      success: async ({ code }) => {
        if (!code) {
          reject(new Error('获取登录凭证失败'));
          return;
        }
        try {
          const res = await post('/auth/login', { code }, { auth: false, loading: true });
          saveAuth(res.data.token, res.data.user);
          resolve(res.data);
        } catch (err) {
          reject(err);
        }
      },
      fail: reject,
    });
  });
}

/**
 * 确保已登录，无 token 时自动登录
 */
async function ensureLogin() {
  if (getToken()) {
    return getToken();
  }
  await login();
  return getToken();
}

module.exports = {
  getToken,
  saveAuth,
  clearAuth,
  login,
  ensureLogin,
};
