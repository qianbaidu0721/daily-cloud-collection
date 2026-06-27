/**
 * 心情选项（对应后端 mood 1-5，天气系 Emoji）
 */
const MOODS = [
  { value: 1, emoji: '🌧️', label: 'emo' },
  { value: 2, emoji: '⛅', label: '一般' },
  { value: 3, emoji: '☁️', label: '平静' },
  { value: 4, emoji: '🌤️', label: '开心' },
  { value: 5, emoji: '☀️', label: '超开心' },
];

/**
 * 根据 mood 数值获取展示用 emoji 与标签（兼容旧数据）
 * @param {number} moodValue
 */
function getMoodDisplay(moodValue) {
  const item = MOODS.find((m) => m.value === Number(moodValue));
  return item || { value: 3, emoji: '☁️', label: '平静' };
}

/**
 * 云类型（接口失败时的离线兜底，与 CloudTypeSeeder 一致）
 */
const CLOUD_TYPES = [
  { name: '积云', code: 'cumulus' },
  { name: '层云', code: 'stratus' },
  { name: '卷云', code: 'cirrus' },
  { name: '积雨云', code: 'cumulonimbus' },
  { name: '层积云', code: 'stratocumulus' },
  { name: '高积云', code: 'altocumulus' },
  { name: '卷层云', code: 'cirrostratus' },
  { name: '其他', code: 'other' },
];

const WEEKDAYS = ['日', '一', '二', '三', '四', '五', '六'];

/** 日历星期行（周一起始） */
const WEEKDAYS_MON = ['一', '二', '三', '四', '五', '六', '日'];

module.exports = {
  MOODS,
  getMoodDisplay,
  CLOUD_TYPES,
  WEEKDAYS,
  WEEKDAYS_MON,
};
