const api = require('../../utils/api');
const { ensureLogin } = require('../../utils/auth');
const { getMoodDisplay } = require('../../utils/constants');
const { MSG, showInfo } = require('../../utils/toast');
const { formatDate, formatHeaderDate } = require('../../utils/util');

const TODAY_CACHE_KEY = 'today_cloud_cache';
const SLOW_LOAD_MS = 2000;

Page({
  data: {
    dateHeader: '',
    isLoading: true,
    loadingSlow: false,
    pageReady: false,
    uploaded: false,
    isFirstTime: false,
    totalClouds: 0,
    todayCloud: null,
    moodEmoji: '☁️',
    moodDisplayLabel: '平静',
  },

  onShow() {
    this.initPage();
  },

  onPullDownRefresh() {
    this.initPage(true).finally(() => {
      wx.stopPullDownRefresh();
    });
  },

  onUnload() {
    this.clearSlowTimer();
  },

  clearSlowTimer() {
    if (this._slowTimer) {
      clearTimeout(this._slowTimer);
      this._slowTimer = null;
    }
  },

  startSlowTimer() {
    this.clearSlowTimer();
    this.setData({ loadingSlow: false });
    this._slowTimer = setTimeout(() => {
      if (this.data.isLoading) {
        this.setData({ loadingSlow: true });
      }
    }, SLOW_LOAD_MS);
  },

  async initPage(fromRefresh = false) {
    this.setData({
      dateHeader: formatHeaderDate(),
      isLoading: true,
      pageReady: false,
      loadingSlow: false,
    });
    this.startSlowTimer();

    const cached = wx.getStorageSync(TODAY_CACHE_KEY);
    if (cached && cached.collect_date === formatDate()) {
      this.applyTodayData(cached, true);
    }

    try {
      await ensureLogin();
      await Promise.all([
        this.loadTodayStatus(fromRefresh),
        this.loadTotalClouds(),
      ]);
      this.setData({
        isFirstTime: !this.data.uploaded && this.data.totalClouds === 0,
      });
    } catch (err) {
      console.error('初始化失败', err);
    } finally {
      this.clearSlowTimer();
      this.setData({ isLoading: false, pageReady: true });
    }
  },

  async loadTodayStatus(fromRefresh = false) {
    const res = await api.getTodayStatus();
    const { uploaded, cloud } = res.data || {};

    if (uploaded && cloud) {
      wx.setStorageSync(TODAY_CACHE_KEY, cloud);
      this.applyTodayData(cloud, true);
    } else {
      wx.removeStorageSync(TODAY_CACHE_KEY);
      this.setData({
        uploaded: false,
        todayCloud: null,
        moodEmoji: '☁️',
        moodDisplayLabel: '平静',
      });
    }

    if (fromRefresh) {
      showInfo(MSG.REFRESH_SUCCESS);
    }
  },

  async loadTotalClouds() {
    try {
      const res = await api.getCloudList(1);
      const total = res.data?.pagination?.total ?? 0;
      this.setData({ totalClouds: total });
    } catch {
      // 静默失败，不影响主流程
    }
  },

  applyTodayData(cloud, uploaded) {
    const moodDisplay = getMoodDisplay(cloud.mood);
    this.setData({
      uploaded: !!uploaded,
      todayCloud: cloud,
      moodEmoji: moodDisplay.emoji,
      moodDisplayLabel: moodDisplay.label,
      isFirstTime: false,
    });
  },

  startUpload() {
    wx.navigateTo({ url: '/pages/upload/upload' });
  },

  onQuickTap(e) {
    if (this.data.uploaded) return;
    const type = e.currentTarget.dataset.type;
    wx.navigateTo({ url: `/pages/upload/upload?focus=${type}` });
  },

  goToList() {
    wx.switchTab({ url: '/pages/list/list' });
  },

  previewTodayCloud() {
    const url = this.data.todayCloud?.image_url;
    if (!url) return;
    wx.previewImage({ current: url, urls: [url] });
  },

  /** 进入分享卡片页 */
  goShareCard() {
    const cloudId = this.data.todayCloud?.id;
    if (!cloudId) return;
    wx.navigateTo({
      url: `/pages/share-card/share-card?cloud_id=${cloudId}&from=index`,
    });
  },
});
