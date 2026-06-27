const api = require('../../utils/api');
const { ensureLogin } = require('../../utils/auth');
const { getMoodDisplay } = require('../../utils/constants');
const { generateAndPreviewCard } = require('../../utils/share-card');
const { getNavLayout } = require('../../utils/util');

const TAB_ROUTES = {
  index: '/pages/index/index',
  list: '/pages/list/list',
  calendar: '/pages/calendar/calendar',
  plaza: '/pages/plaza/plaza',
};

Page({
  data: {
    statusBarHeight: 20,
    navContentHeight: 44,
    cloudId: null,
    cloud: null,
    moodEmoji: '☁️',
    loading: true,
    generating: false,
    currentTab: 'index',
  },

  onLoad(options) {
    const layout = getNavLayout();
    const cloudId = Number(options.cloud_id);
    const from = options.from || 'index';

    this.setData({
      ...layout,
      cloudId: cloudId || null,
      currentTab: TAB_ROUTES[from] ? from : 'index',
    });

    if (cloudId) {
      this.loadCloud(cloudId);
    } else {
      this.setData({ loading: false });
    }
  },

  async loadCloud(cloudId) {
    this.setData({ loading: true });

    try {
      await ensureLogin();
      const res = await api.getCloudDetail(cloudId);
      const cloud = res.data;
      const moodDisplay = getMoodDisplay(cloud?.mood);

      this.setData({
        cloud,
        moodEmoji: moodDisplay.emoji,
        loading: false,
      });
    } catch (err) {
      console.error('加载云朵失败', err);
      this.setData({ cloud: null, loading: false });
    }
  },

  async generateCard() {
    const { cloudId, generating } = this.data;
    if (!cloudId || generating) return;

    this.setData({ generating: true });
    try {
      await generateAndPreviewCard(cloudId, true);
    } finally {
      this.setData({ generating: false });
    }
  },

  switchTab(e) {
    const tab = e.currentTarget.dataset.tab;
    const url = TAB_ROUTES[tab];
    if (!url) return;

    wx.switchTab({ url });
  },

  onClose() {
    const pages = getCurrentPages();
    if (pages.length > 1) {
      wx.navigateBack();
      return;
    }
    wx.switchTab({ url: TAB_ROUTES.index });
  },
});
