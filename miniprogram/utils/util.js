const { WEEKDAYS } = require('./constants');

/**
 * 格式化日期 Y-m-d
 */
function formatDate(date = new Date()) {
  const d = date instanceof Date ? date : new Date(date);
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
}

/**
 * 格式化展示用日期
 */
function formatDisplayDate(dateStr) {
  const d = new Date(dateStr.replace(/-/g, '/'));
  const week = WEEKDAYS[d.getDay()];
  return {
    full: dateStr,
    monthDay: `${d.getMonth() + 1}月${d.getDate()}日`,
    weekday: `星期${week}`,
  };
}

/**
 * 生成月度日历网格（周一起始，含上下月补位日期）
 */
function buildMonthCalendar(year, month, cloudDates = new Set()) {
  const today = formatDate();
  const daysInMonth = new Date(year, month, 0).getDate();
  const firstWeekday = new Date(year, month - 1, 1).getDay();
  const startPad = firstWeekday === 0 ? 6 : firstWeekday - 1;

  const prevMonth = month === 1 ? 12 : month - 1;
  const prevYear = month === 1 ? year - 1 : year;
  const prevMonthDays = new Date(prevYear, prevMonth, 0).getDate();

  const nextMonth = month === 12 ? 1 : month + 1;
  const nextYear = month === 12 ? year + 1 : year;

  const cells = [];

  for (let i = startPad - 1; i >= 0; i -= 1) {
    const day = prevMonthDays - i;
    const date = `${prevYear}-${String(prevMonth).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
    cells.push({
      day,
      date,
      isCurrentMonth: false,
      hasCloud: cloudDates.has(date),
      isToday: date === today,
      isWeekend: false,
    });
  }

  for (let day = 1; day <= daysInMonth; day += 1) {
    const date = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
    const weekday = new Date(year, month - 1, day).getDay();
    cells.push({
      day,
      date,
      isCurrentMonth: true,
      hasCloud: cloudDates.has(date),
      isToday: date === today,
      isWeekend: weekday === 0 || weekday === 6,
    });
  }

  let nextDay = 1;
  while (cells.length % 7 !== 0) {
    const date = `${nextYear}-${String(nextMonth).padStart(2, '0')}-${String(nextDay).padStart(2, '0')}`;
    cells.push({
      day: nextDay,
      date,
      isCurrentMonth: false,
      hasCloud: cloudDates.has(date),
      isToday: date === today,
      isWeekend: false,
    });
    nextDay += 1;
  }

  return cells;
}

/**
 * 瀑布流分列（按奇偶索引）
 */
function splitWaterfall(items) {
  const left = [];
  const right = [];
  items.forEach((item, index) => {
    if (index % 2 === 0) left.push(item);
    else right.push(item);
  });
  return { left, right };
}

/**
 * 首页顶部日期：6月25日 · 周四
 */
function formatHeaderDate(date = new Date()) {
  const d = date instanceof Date ? date : new Date(date);
  const week = WEEKDAYS[d.getDay()];
  return `${d.getMonth() + 1}月${d.getDate()}日 · 周${week}`;
}

/**
 * 卡片日期标签：6月25日
 */
function formatCardDate(dateStr) {
  const d = new Date(String(dateStr).replace(/-/g, '/'));
  return `${d.getMonth() + 1}月${d.getDate()}日`;
}

/**
 * 自定义导航栏布局（适配状态栏 + 胶囊按钮）
 */
function getNavLayout() {
  const sys = wx.getSystemInfoSync();
  const menu = wx.getMenuButtonBoundingClientRect();
  const statusBarHeight = sys.statusBarHeight || 20;
  const hasValidMenu = menu && menu.width > 0 && menu.left > 0;

  const gap = hasValidMenu ? menu.top - statusBarHeight : 4;
  const menuHeight = hasValidMenu ? menu.height : 32;
  const menuLeft = hasValidMenu ? menu.left : sys.windowWidth - 87 - 7;

  const navBarHeight = statusBarHeight + menuHeight + gap;
  const navContentHeight = menuHeight;
  const navPaddingRight = hasValidMenu
    ? sys.windowWidth - menuLeft
    : 87 + 7;
  const navSideWidth = (hasValidMenu ? menu.width : 87) + gap * 2;

  return {
    statusBarHeight,
    navBarHeight,
    headerHeight: navBarHeight,
    navContentHeight,
    navPaddingRight,
    navSideWidth,
  };
}

module.exports = {
  formatDate,
  formatDisplayDate,
  formatHeaderDate,
  formatCardDate,
  buildMonthCalendar,
  splitWaterfall,
  getNavLayout,
};
