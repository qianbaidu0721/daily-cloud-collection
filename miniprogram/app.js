/** 云屿集 - 每日云彩收集小程序 */
const { ensureLogin } = require('./utils/auth');

App({
  globalData: {
    user: null,
  },

  onLaunch() {
    this.initLogin();
  },

  /** 启动时静默登录 */
  async initLogin() {
    try {
      await ensureLogin();
    } catch (err) {
      console.error('登录失败', err);
    }
  },
});
