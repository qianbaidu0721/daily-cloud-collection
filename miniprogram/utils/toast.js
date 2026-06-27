/** 云屿集 · 统一 Toast 文案与调用 */

const MSG = {
  // 成功
  UPLOAD_SUCCESS: '云朵已收藏 ☁️',
  UPDATE_SUCCESS: '已更新 ✨',
  SHARE_SUCCESS: '分享成功 🎉',
  REFRESH_SUCCESS: '已刷新 ✨',

  // 失败
  UPLOAD_FAIL: '风太大，没传上去，再试一次~',
  NETWORK_FAIL: '网络走丢了，检查一下~',
  LOAD_FAIL: '加载失败了，再试一次~',
  LOCATION_FAIL: '位置没找到，手动输入吧 📍',
  IMAGE_FAIL: '图片加载失败了，点我重试',

  // 提示
  TODAY_UPLOADED: '今天已经收集过云朵啦，明天再来吧 ☁️',
  NO_IMAGE: '选一张云朵照片吧 📸',
  NO_MOOD: '选一下今天的心情吧 ☁️',
  LOAD_ALL: '— 已加载全部云朵 —',
  NO_MORE: '没有更多云朵了 ~',
  LOCATION_AUTH: '需要位置权限才能自动定位哦',
  CHOOSE_IMAGE: '请选择云朵照片 📸',
};

function showSuccess(title, duration = 2000) {
  wx.showToast({
    title: title || MSG.UPLOAD_SUCCESS,
    icon: 'success',
    duration,
  });
}

function showFail(title, duration = 3000) {
  wx.showToast({
    title: title || MSG.UPLOAD_FAIL,
    icon: 'none',
    duration,
  });
}

function showInfo(title, duration = 1500) {
  wx.showToast({
    title: title || '',
    icon: 'none',
    duration,
  });
}

module.exports = {
  MSG,
  showSuccess,
  showFail,
  showInfo,
};
