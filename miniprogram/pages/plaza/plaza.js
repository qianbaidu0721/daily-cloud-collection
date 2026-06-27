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
      displayDate: formatCardDate(item.collect_date),
      moodEmoji: moodDisplay.emoji,
      moodLabel: moodDisplay.label,
      authorLabel: item.author_label || '某位云友',
    };
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
      const res = await api.getPublicCloudList(page);
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
      });
    } catch (err) {
      console.error('加载广场失败', err);
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

  async onCardTap(e) {
    const id = Number(e.detail?.id);
    if (!id) return;

    const cached = this.data.items.find((item) => item.id === id);
    if (cached) {
      this.setData({ detailCloud: cached });
    }

    try {
      const res = await api.getPublicCloudDetail(id);
      if (res.data) {
        this.setData({ detailCloud: this.enrichItem(res.data) });
      }
    } catch (err) {
      console.error('加载广场详情失败', err);
      showInfo(MSG.LOAD_FAIL);
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

  goCollect() {
    wx.switchTab({ url: '/pages/index/index' });
  },

  noop() {},
});
