const api = require('./api');
const { showSuccess, showFail, showInfo } = require('./toast');

/**
 * 下载卡片图片到本地临时路径
 * @param {string} url
 */
function downloadCard(url) {
  return new Promise((resolve, reject) => {
    wx.downloadFile({
      url,
      success: (res) => {
        if (res.statusCode === 200 && res.tempFilePath) {
          resolve(res.tempFilePath);
          return;
        }
        reject(new Error(`download ${res.statusCode}`));
      },
      fail: reject,
    });
  });
}

/**
 * 保存卡片到相册
 * @param {string} cardUrl
 */
async function saveCardToAlbum(cardUrl) {
  try {
    const tempPath = await downloadCard(cardUrl);
    await new Promise((resolve, reject) => {
      wx.saveImageToPhotosAlbum({
        filePath: tempPath,
        success: resolve,
        fail: reject,
      });
    });
    showSuccess('已保存到相册 ✨');
    return true;
  } catch (err) {
    const msg = err?.errMsg || '';
    if (msg.includes('auth deny') || msg.includes('authorize')) {
      showInfo('需要相册权限才能保存哦');
      wx.openSetting({});
    } else {
      showFail('保存失败，再试一次~');
    }
    return false;
  }
}

/**
 * 生成分享卡片并预览 / 保存
 * @param {number} cloudId
 * @param {boolean} [force=false]
 */
async function generateAndPreviewCard(cloudId, force = true) {
  wx.showLoading({ title: '生成卡片中...', mask: true });

  try {
    const res = await api.generateCloudCard(cloudId, force);
    wx.hideLoading();

    const cardUrl = res.data?.card_url;
    if (!cardUrl) {
      showFail('卡片生成失败，再试一次~');
      return null;
    }

    return new Promise((resolve) => {
      wx.showActionSheet({
        itemList: ['预览卡片', '保存到相册'],
        success: async (sheet) => {
          if (sheet.tapIndex === 0) {
            wx.previewImage({ urls: [cardUrl], current: cardUrl });
            resolve(cardUrl);
            return;
          }
          if (sheet.tapIndex === 1) {
            await saveCardToAlbum(cardUrl);
            resolve(cardUrl);
          }
        },
        fail: () => {
          wx.previewImage({ urls: [cardUrl], current: cardUrl });
          resolve(cardUrl);
        },
      });
    });
  } catch (err) {
    wx.hideLoading();
    console.error('生成分享卡片失败', err);
    showFail('卡片生成失败，再试一次~');
    return null;
  }
}

module.exports = {
  generateAndPreviewCard,
  saveCardToAlbum,
};
