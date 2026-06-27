const TOKEN_KEY = 'token';
const USER_KEY = 'user';

function getToken() {
  return wx.getStorageSync(TOKEN_KEY) || '';
}

function saveAuth(token, user) {
  wx.setStorageSync(TOKEN_KEY, token);
  wx.setStorageSync(USER_KEY, user || {});
}

function clearAuth() {
  wx.removeStorageSync(TOKEN_KEY);
  wx.removeStorageSync(USER_KEY);
}

module.exports = {
  getToken,
  saveAuth,
  clearAuth,
};
