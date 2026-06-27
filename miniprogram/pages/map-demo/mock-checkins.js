/**
 * 云朵打卡地图 · 审核演示用模拟打卡数据
 * 坐标均为 gcj02，分布在沈阳 / 大连不同地点
 */
const MOCK_CHECKIN_TEMPLATES = [
  { id: 1, city: '沈阳和平', dayOffset: 1, time: '18:30', mood: 4, cloudType: '积云', lat: 41.7898, lng: 123.4201 },
  { id: 2, city: '沈阳浑南', dayOffset: 2, time: '12:15', mood: 3, cloudType: '层云', lat: 41.75, lng: 123.45 },
  { id: 3, city: '沈阳沈河', dayOffset: 3, time: '07:45', mood: 5, cloudType: '卷云', lat: 41.81, lng: 123.44 },
  { id: 4, city: '沈阳大东', dayOffset: 4, time: '16:20', mood: 2, cloudType: '雨云', lat: 41.82, lng: 123.47 },
  { id: 5, city: '沈阳铁西', dayOffset: 5, time: '10:00', mood: 4, cloudType: '积云', lat: 41.78, lng: 123.35 },
  { id: 6, city: '沈阳皇姑', dayOffset: 6, time: '14:30', mood: 3, cloudType: '高积云', lat: 41.83, lng: 123.4 },
  { id: 7, city: '沈阳于洪', dayOffset: 7, time: '08:10', mood: 5, cloudType: '卷积云', lat: 41.79, lng: 123.3 },
  { id: 8, city: '沈阳沈北', dayOffset: 8, time: '17:45', mood: 4, cloudType: '积云', lat: 41.89, lng: 123.45 },
  { id: 9, city: '大连中山', dayOffset: 10, time: '11:20', mood: 4, cloudType: '积云', lat: 38.92, lng: 121.64 },
  { id: 10, city: '大连西岗', dayOffset: 11, time: '09:30', mood: 3, cloudType: '层云', lat: 38.91, lng: 121.61 },
];

const MOOD_MARKER_ICON = {
  1: '/assets/map/marker-mood-1.png',
  2: '/assets/map/marker-mood-2.png',
  3: '/assets/map/marker-mood-3.png',
  4: '/assets/map/marker-mood-4.png',
  5: '/assets/map/marker-mood-5.png',
};

const MOOD_META = {
  1: { emoji: '🌧️', label: 'emo' },
  2: { emoji: '⛅', label: '一般' },
  3: { emoji: '☁️', label: '平静' },
  4: { emoji: '🌤️', label: '开心' },
  5: { emoji: '☀️', label: '超开心' },
};

/** 默认中心：沈阳 */
const DEFAULT_LAT = 41.8057;
const DEFAULT_LNG = 123.4315;

function pad(n) {
  return String(n).padStart(2, '0');
}

function formatDateKey(d) {
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

function buildMockCheckins() {
  const now = new Date();
  return MOCK_CHECKIN_TEMPLATES.map((item) => {
    const d = new Date(now);
    d.setDate(d.getDate() - item.dayOffset);
    const dateKey = formatDateKey(d);
    const dateLabel = `${d.getMonth() + 1}月${d.getDate()}日`;
    const mood = MOOD_META[item.mood] || MOOD_META[3];

    return {
      id: item.id,
      city: item.city,
      date: dateLabel,
      dateKey,
      time: item.time,
      mood: item.mood,
      moodEmoji: mood.emoji,
      moodLabel: mood.label,
      cloudType: item.cloudType,
      lat: item.lat,
      lng: item.lng,
      timestamp: d.getTime(),
    };
  }).sort((a, b) => b.timestamp - a.timestamp);
}

function extractCityName(cityStr) {
  if (!cityStr) return '';
  const m = cityStr.match(/([\u4e00-\u9fa5]+(?:市|区|县)?)/);
  return m ? m[1] : cityStr.split('·')[0] || cityStr;
}

function computeStats(records) {
  const dateKeys = new Set(records.map((r) => r.dateKey));
  const cities = new Set(records.map((r) => extractCityName(r.city)));

  let lastCheckin = '暂无';
  if (records.length > 0) {
    const latest = records[0];
    const diffMs = Date.now() - latest.timestamp;
    const hours = Math.floor(diffMs / (1000 * 60 * 60));
    if (hours < 1) lastCheckin = '刚刚';
    else if (hours < 24) lastCheckin = `${hours}小时前`;
    else lastCheckin = `${Math.floor(hours / 24)}天前`;
  }

  return {
    totalDays: dateKeys.size,
    totalCities: cities.size,
    lastCheckin,
  };
}

function isTodayChecked(records) {
  const todayKey = formatDateKey(new Date());
  return records.some((r) => r.dateKey === todayKey);
}

function buildMarkers(records) {
  const recordMap = {};
  const markers = records.map((record) => {
    recordMap[record.id] = record;
    const calloutText = `${record.date} ${record.moodEmoji} ${record.moodLabel}`;

    return {
      id: record.id,
      latitude: record.lat,
      longitude: record.lng,
      width: 40,
      height: 40,
      iconPath: MOOD_MARKER_ICON[record.mood] || MOOD_MARKER_ICON[3],
      callout: {
        content: calloutText,
        display: 'BYCLICK',
        padding: 10,
        borderRadius: 8,
        fontSize: 12,
        color: '#3D4A5A',
        bgColor: '#FFFFFF',
      },
    };
  });

  return { markers, recordMap };
}

module.exports = {
  DEFAULT_LAT,
  DEFAULT_LNG,
  MOOD_META,
  buildMockCheckins,
  computeStats,
  isTodayChecked,
  buildMarkers,
  formatDateKey,
};
