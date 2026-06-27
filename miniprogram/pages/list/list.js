const api = require('../../utils/api');
const { ensureLogin } = require('../../utils/auth');
const { getMoodDisplay } = require('../../utils/constants');
const { MSG, showInfo } = require('../../utils/toast');
const { formatCardDate, getNavLayout } = require('../../utils/util');

Page({
  data: {
    statusBarHeight: 20,
    navBarHeight: 64,
    items: [],
    page: 1,
    lastPage: 1,
    loading: false,
    loadingMore: false,
    showSkeleton: false,
    loadFailed: false,
    noMore: false,
    detailCloud: null,
    visibilityLoading: false,
    batchShareLoading: false,
    privateCount: 0,
  },

  onLoad() {
    this.setData(getNavLayout());
  },

  onShow() {
    this.resetAndLoad();
  },

  onPullDownRefresh() {
    this.resetAndLoad().finally(() => {
      wx.stopPullDownRefresh();
    });
  },

  onReachBottom() {
    if (!this.data.noMore && !this.data.loading && !this.data.loadingMore && !this.data.loadFailed) {
      this.loadList(this.data.page + 1, true);
    }
  },

  async resetAndLoad() {
    this.setData({
      items: [],
      page: 1,
      lastPage: 1,
      noMore: false,
      loadFailed: false,
      showSkeleton: true,
    });
    await this.loadList(1);
  },

  enrichItem(item) {
    const moodDisplay = getMoodDisplay(item.mood);
    return {
      ...item,
      is_public: !!item.is_public,
      displayDate: formatCardDate(item.collect_date),
      moodEmoji: moodDisplay.emoji,
      moodLabel: moodDisplay.label,
    };
  },

  countPrivateItems(items) {
    return items.filter((item) => !item.is_public).length;
  },

  async loadList(page = 1, isLoadMore = false) {
    if (this.data.loading || this.data.loadingMore) return;

    if (isLoadMore) {
      this.setData({ loadingMore: true, loadFailed: false });
    } else {
      this.setData({
        loading: true,
        loadFailed: false,
        showSkeleton: page === 1 && this.data.items.length === 0,
      });
    }

    try {
      await ensureLogin();
      const res = await api.getCloudList(page);
      const { items = [], pagination = {} } = res.data || {};
      const newItems = items.map((item) => this.enrichItem(item));
      const allItems = page === 1 ? newItems : [...this.data.items, ...newItems];
      const currentPage = pagination.current_page || page;
      const lastPage = pagination.last_page || 1;

      this.setData({
        items: allItems,
        page: currentPage,
        lastPage,
        noMore: currentPage >= lastPage,
        loading: false,
        loadingMore: false,
        showSkeleton: false,
        loadFailed: false,
        privateCount: this.countPrivateItems(allItems),
      });
    } catch (err) {
      console.error('加载列表失败', err);
      this.setData({
        loading: false,
        loadingMore: false,
        showSkeleton: false,
        loadFailed: true,
      });
    }
  },

  retryLoad() {
    if (this.data.items.length === 0) {
      this.resetAndLoad();
      return;
    }
    this.loadList(this.data.page + 1, true);
  },

  onCardTap(e) {
    const id = Number(e.detail?.id);
    const cloud = this.data.items.find((item) => item.id === id);
    if (cloud) {
      this.setData({ detailCloud: cloud });
    }
  },

  previewImage(e) {
    const url = e.currentTarget.dataset.url;
    const urls = this.data.items.map((item) => item.image_url);
    wx.previewImage({
      current: url,
      urls: urls.length ? urls : [url],
    });
  },

  closeDetail() {
    this.setData({ detailCloud: null });
  },

  async toggleDetailVisibility() {
    const cloud = this.data.detailCloud;
    if (!cloud || this.data.visibilityLoading) return;

    const nextPublic = !cloud.is_public;
    this.setData({ visibilityLoading: true });

    try {
      const res = await api.updateCloudVisibility(cloud.id, nextPublic);
      const updated = this.enrichItem(res.data || { ...cloud, is_public: nextPublic });
      const items = this.data.items.map((item) => (
        item.id === cloud.id ? { ...item, is_public: updated.is_public } : item
      ));

      this.setData({
        items,
        detailCloud: { ...cloud, ...updated },
        privateCount: this.countPrivateItems(items),
        visibilityLoading: false,
      });

      showInfo(nextPublic ? '已共享到广场 🌍' : '已设为仅自己可见 🔒');
    } catch (err) {
      console.error('切换共享失败', err);
      this.setData({ visibilityLoading: false });
    }
  },

  batchShareAll() {
    if (this.data.batchShareLoading || this.data.items.length === 0) return;

    wx.showModal({
      title: '一键共享',
      content: '将您所有的私有云朵共享到广场？备注不会公开。',
      confirmText: '全部共享',
      success: async (res) => {
        if (!res.confirm) return;

        this.setData({ batchShareLoading: true });
        try {
          const result = await api.batchShareClouds();
          const updatedCount = result.data?.updated_count || 0;

          showInfo(result.msg || MSG.UPDATE_SUCCESS);

          if (updatedCount > 0) {
            this.setData({ detailCloud: null });
            await this.resetAndLoad();
          }
        } catch (err) {
          console.error('一键共享失败', err);
        } finally {
          this.setData({ batchShareLoading: false });
        }
      },
    });
  },

  goCollect() {
    wx.switchTab({ url: '/pages/index/index' });
  },

  /** 进入分享卡片页 */
  goShareCard() {
    const cloudId = this.data.detailCloud?.id;
    if (!cloudId) return;
    this.setData({ detailCloud: null });
    wx.navigateTo({
      url: `/pages/share-card/share-card?cloud_id=${cloudId}&from=list`,
    });
  },

  noop() {},
});
