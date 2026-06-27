const api = require('../../utils/api');
const { ensureLogin } = require('../../utils/auth');
const { WEEKDAYS_MON, getMoodDisplay } = require('../../utils/constants');
const { MSG, showFail, showInfo } = require('../../utils/toast');
const { buildMonthCalendar, formatCardDate, formatDate, getNavLayout } = require('../../utils/util');

const HIGHLIGHT_DATE_KEY = 'list_highlight_date';
const SLOW_LOAD_MS = 2000;

Page({
  data: {
    statusBarHeight: 20,
    navBarHeight: 64,
    year: 0,
    month: 0,
    todayYear: 0,
    todayMonth: 0,
    todayDate: '',
    isCurrentMonth: true,
    weekdays: WEEKDAYS_MON,
    calendarCells: [],
    cloudMap: {},
    monthCloudList: [],
    monthCloudCount: 0,
    daysInMonth: 30,
    monthAnimating: false,
    isLoading: false,
    loadingSlow: false,
    loadFailed: false,
    selectedDate: '',
    selectedDisplayDate: '',
    selectedCloud: null,
    selectedHasCloud: false,
  },

  onLoad() {
    const now = new Date();
    const nav = getNavLayout();
    const todayDate = formatDate(now);

    this.setData({
      ...nav,
      year: now.getFullYear(),
      month: now.getMonth() + 1,
      todayYear: now.getFullYear(),
      todayMonth: now.getMonth() + 1,
      todayDate,
      selectedDate: todayDate,
      daysInMonth: new Date(now.getFullYear(), now.getMonth() + 1, 0).getDate(),
      isCurrentMonth: true,
    });
  },

  onShow() {
    const { year, month } = this.data;
    if (year && month) {
      this.loadCalendar(year, month);
    }
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

  checkIsCurrentMonth(year, month) {
    return year === this.data.todayYear && month === this.data.todayMonth;
  },

  buildMonthCloudList(cloudMap) {
    return Object.keys(cloudMap)
      .sort((a, b) => b.localeCompare(a))
      .map((date) => {
        const cloud = cloudMap[date];
        const moodDisplay = getMoodDisplay(cloud.mood);
        return {
          ...cloud,
          collect_date: date,
          displayDate: formatCardDate(date),
          moodEmoji: moodDisplay.emoji,
          moodLabel: moodDisplay.label,
        };
      });
  },

  updateSelectedPreview(date) {
    if (!date) {
      this.setData({
        selectedDate: '',
        selectedDisplayDate: '',
        selectedCloud: null,
        selectedHasCloud: false,
      });
      this.renderCalendar();
      return;
    }

    const cloud = this.data.cloudMap[date];
    const moodDisplay = cloud ? getMoodDisplay(cloud.mood) : null;

    this.setData({
      selectedDate: date,
      selectedDisplayDate: formatCardDate(date),
      selectedHasCloud: !!cloud,
      selectedCloud: cloud ? {
        ...cloud,
        moodEmoji: moodDisplay.emoji,
        moodLabel: moodDisplay.label,
      } : null,
    });
    this.renderCalendar();
  },

  async loadCalendar(year, month) {
    this.setData({ isLoading: true, loadFailed: false });
    this.startSlowTimer();

    try {
      await ensureLogin();
      const res = await api.getCloudCalendar(year, month);

      const { records = {}, total_days: totalDays = 0 } = res.data || {};
      const cloudMap = records;
      const monthCloudList = this.buildMonthCloudList(cloudMap);
      const daysInMonth = new Date(year, month, 0).getDate();
      const isCurrentMonth = this.checkIsCurrentMonth(year, month);

      let { selectedDate } = this.data;
      if (selectedDate) {
        const inMonth = selectedDate.startsWith(
          `${year}-${String(month).padStart(2, '0')}-`
        );
        if (!inMonth) {
          selectedDate = isCurrentMonth ? this.data.todayDate : '';
        }
      } else if (isCurrentMonth && cloudMap[this.data.todayDate]) {
        selectedDate = this.data.todayDate;
      } else if (monthCloudList.length > 0) {
        selectedDate = monthCloudList[0].collect_date;
      }

      this.setData({
        cloudMap,
        monthCloudList,
        monthCloudCount: totalDays,
        daysInMonth,
        isCurrentMonth,
        monthAnimating: false,
        isLoading: false,
        loadFailed: false,
      });

      this.updateSelectedPreview(selectedDate);
    } catch (err) {
      console.error('加载日历失败', err);
      this.setData({ monthAnimating: false, isLoading: false, loadFailed: true });
      showFail(MSG.LOAD_FAIL);
    } finally {
      this.clearSlowTimer();
      this.setData({ loadingSlow: false });
    }
  },

  retryLoad() {
    const { year, month } = this.data;
    if (year && month) {
      this.loadCalendar(year, month);
    }
  },

  renderCalendar() {
    const { year, month, cloudMap, selectedDate } = this.data;
    const cloudDates = new Set(Object.keys(cloudMap));
    const calendarCells = buildMonthCalendar(year, month, cloudDates).map((cell) => ({
      ...cell,
      isSelected: cell.date === selectedDate,
    }));

    this.setData({ calendarCells });
  },

  changeMonth(delta) {
    if (this.data.monthAnimating || this.data.isLoading) return;

    let { year, month } = this.data;
    month += delta;
    if (month < 1) {
      month = 12;
      year -= 1;
    } else if (month > 12) {
      month = 1;
      year += 1;
    }

    this.setData({ monthAnimating: true, year, month });
    this.loadCalendar(year, month);
  },

  prevMonth() {
    this.changeMonth(-1);
  },

  nextMonth() {
    this.changeMonth(1);
  },

  goToToday() {
    const { todayYear, todayMonth, todayDate } = this.data;
    if (this.data.year === todayYear && this.data.month === todayMonth) {
      this.updateSelectedPreview(todayDate);
      return;
    }

    this.setData({ monthAnimating: true, year: todayYear, month: todayMonth });
    this.loadCalendar(todayYear, todayMonth);
  },

  onDayTap(e) {
    const { date } = e.currentTarget.dataset;
    if (!date) return;
    this.updateSelectedPreview(date);
  },

  onRecordTap(e) {
    const { date } = e.currentTarget.dataset;
    if (date) {
      this.updateSelectedPreview(date);
    }
  },

  goToList() {
    const { selectedDate } = this.data;
    if (selectedDate) {
      wx.setStorageSync(HIGHLIGHT_DATE_KEY, selectedDate);
    }
    wx.switchTab({ url: '/pages/list/list' });
  },

  goCollect() {
    wx.switchTab({ url: '/pages/index/index' });
  },

  previewImage() {
    const url = this.data.selectedCloud?.image_url;
    if (!url) return;
    wx.previewImage({ current: url, urls: [url] });
  },

  goShareCard() {
    const cloudId = this.data.selectedCloud?.id;
    if (!cloudId) return;
    wx.navigateTo({
      url: `/pages/share-card/share-card?cloud_id=${cloudId}&from=calendar`,
    });
  },
});
