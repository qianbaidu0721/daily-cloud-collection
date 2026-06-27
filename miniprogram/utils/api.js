const { get, post, patch, uploadCloud } = require('./request');

/**
 * 查询今日是否已收集
 */
function getTodayStatus() {
  return get('/clouds/today');
}

/**
 * 云朵列表（分页）
 * @param {number} page
 */
function getCloudList(page = 1) {
  return get('/clouds', { page });
}

/**
 * 云朵详情
 * @param {number} id
 */
function getCloudDetail(id) {
  return get(`/clouds/${id}`);
}

/**
 * 上传云朵
 * @param {object} [options] - 传给 uploadCloud 的选项
 */
function uploadCloudImage(filePath, formData, options = {}) {
  return uploadCloud(filePath, formData, options);
}

/**
 * 拉取全部云朵（用于日历标记，自动翻页）
 */
async function getAllClouds() {
  const all = [];
  let page = 1;
  let lastPage = 1;

  do {
    const res = await getCloudList(page);
    const items = res.data?.items || [];
    all.push(...items);
    lastPage = res.data?.pagination?.last_page || 1;
    page += 1;
  } while (page <= lastPage);

  return all;
}

/**
 * 获取月度日历数据
 * @param {number} year
 * @param {number} month
 */
function getCloudCalendar(year, month) {
  return get('/clouds/calendar', { year, month });
}

/**
 * 获取云类型列表
 */
function getCloudTypes() {
  return get('/cloud-types', {}, { loading: false });
}

/**
 * 逆解析经纬度为城市名（服务端调用高德）
 * @param {number} lat
 * @param {number} lng
 */
function resolveLocation(lat, lng) {
  return get('/location/reverse', { lat, lng }, { loading: false });
}

/**
 * 广场：共享云朵列表
 * @param {number} page
 */
function getPublicCloudList(page = 1) {
  return get('/clouds/public', { page });
}

/**
 * 广场：共享云朵详情
 * @param {number} id
 */
function getPublicCloudDetail(id) {
  return get(`/clouds/public/${id}`);
}

/**
 * 切换单条云朵共享状态
 * @param {number} id
 * @param {boolean} isPublic
 */
function updateCloudVisibility(id, isPublic) {
  return patch(`/clouds/${id}/visibility`, { is_public: isPublic }, { loading: true });
}

/**
 * 一键共享全部私有云朵
 */
function batchShareClouds() {
  return post('/clouds/batch-share', { scope: 'all' }, { loading: true });
}

/**
 * 生成云朵分享卡片
 * @param {number} cloudId
 * @param {boolean} [force=false]
 */
function generateCloudCard(cloudId, force = false) {
  return post('/clouds/card', { cloud_id: cloudId, force: force ? 1 : 0 }, { loading: false });
}

module.exports = {
  getTodayStatus,
  getCloudList,
  getCloudDetail,
  uploadCloudImage,
  getAllClouds,
  getCloudCalendar,
  resolveLocation,
  getCloudTypes,
  getPublicCloudList,
  getPublicCloudDetail,
  updateCloudVisibility,
  batchShareClouds,
  generateCloudCard,
};
