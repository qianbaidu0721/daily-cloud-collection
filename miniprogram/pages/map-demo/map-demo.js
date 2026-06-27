const { getNavLayout } = require('../../utils/util');
const { showInfo, showFail, showSuccess } = require('../../utils/toast');
const {
  DEFAULT_LAT,
  DEFAULT_LNG,
  MOOD_META,
  buildMockCheckins,
  computeStats,
  isTodayChecked,
  buildMarkers,
  formatDateKey,
} = require('./mock-checkins');

Page({
  data: {
    statusBarHeight: 20,
    navContentHeight: 44,
    currentLat: DEFAULT_LAT,
    currentLng: DEFAULT_LNG,
    mapScale: 12,
    markers: [],
    recordMap: {},
    allRecords: [],
    recentRecords: [],
    totalDays: 0,
    totalCities: 0,
    lastCheckin: '暂无',
    todayChecked: false,
    locating: false,
    selectedRecord: null,
    nextId: 100,
  },

  onLoad() {
    this.setData(getNavLayout());
    this.bootstrapDemo();
    this.refreshLocation();
  },

  /** 初始化模拟打卡数据 */
  bootstrapDemo() {
    const allRecords = buildMockCheckins();
    const { markers, recordMap } = buildMarkers(allRecords);
    const stats = computeStats(allRecords);

    this.setData({
      allRecords,
      recentRecords: allRecords.slice(0, 5),
      markers,
      recordMap,
      totalDays: stats.totalDays,
      totalCities: stats.totalCities,
      lastCheckin: stats.lastCheckin,
      todayChecked: isTodayChecked(allRecords),
      nextId: Math.max(...allRecords.map((r) => r.id), 0) + 1,
    });
  },

  /** 获取精确地理位置（gcj02）—— 审核核心能力演示 */
  refreshLocation() {
    this.setData({ locating: true });

    wx.getLocation({
      type: 'gcj02',
      isHighAccuracy: true,
      highAccuracyExpireTime: 5000,
      success: (res) => {
        this.setData({
          currentLat: res.latitude,
          currentLng: res.longitude,
          mapScale: 11,
        });
      },
      fail: (err) => {
        console.error('getLocation 失败', err);
        showFail('需要精确位置权限以完成云朵打卡');
        this.setData({
          currentLat: DEFAULT_LAT,
          currentLng: DEFAULT_LNG,
          mapScale: 11,
        });
      },
      complete: () => {
        this.setData({ locating: false });
      },
    });
  },

  applyRecords(allRecords) {
    const { markers, recordMap } = buildMarkers(allRecords);
    const stats = computeStats(allRecords);

    this.setData({
      allRecords,
      recentRecords: allRecords.slice(0, 5),
      markers,
      recordMap,
      totalDays: stats.totalDays,
      totalCities: stats.totalCities,
      lastCheckin: stats.lastCheckin,
      todayChecked: isTodayChecked(allRecords),
    });
  },

  onMarkerTap(e) {
    const id = Number(e.detail.markerId);
    const record = this.data.recordMap[id];
    if (record) this.setData({ selectedRecord: record });
  },

  viewRecord(e) {
    const id = Number(e.currentTarget.dataset.id);
    const record = this.data.recordMap[id];
    if (record) this.setData({ selectedRecord: record });
  },

  viewAllRecords() {
    showInfo(`共 ${this.data.allRecords.length} 条打卡记录`);
  },

  closeRecordPopup() {
    this.setData({ selectedRecord: null });
  },

  /** 模拟今日打卡：在当前精确位置新增一条记录 */
  simulateCheckin() {
    if (this.data.todayChecked) {
      showInfo('今日已打卡，明天再来收集云朵吧 ☁️');
      return;
    }

    const now = new Date();
    const mood = 4;
    const moodMeta = MOOD_META[mood];
    const id = this.data.nextId;
    const newRecord = {
      id,
      city: '当前位置',
      date: `${now.getMonth() + 1}月${now.getDate()}日`,
      dateKey: formatDateKey(now),
      time: `${pad(now.getHours())}:${pad(now.getMinutes())}`,
      mood,
      moodEmoji: moodMeta.emoji,
      moodLabel: moodMeta.label,
      cloudType: '积云',
      lat: this.data.currentLat,
      lng: this.data.currentLng,
      timestamp: now.getTime(),
    };

    const allRecords = [newRecord, ...this.data.allRecords];
    this.applyRecords(allRecords);
    this.setData({ nextId: id + 1 });
    showSuccess('打卡成功！已在地图留下足迹 ✨');
  },

  noop() {},
});

function pad(n) {
  return String(n).padStart(2, '0');
}
