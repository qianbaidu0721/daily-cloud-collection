const api = require('../../utils/api');
const { ensureLogin } = require('../../utils/auth');
const { CLOUD_TYPES, MOODS } = require('../../utils/constants');
const { MSG, showSuccess, showFail, showInfo } = require('../../utils/toast');
const { showLoading, hideLoading } = require('../../utils/request');
const { formatDate, getNavLayout } = require('../../utils/util');

Page({
  data: {
    statusBarHeight: 20,
    navBarHeight: 64,
    tempImagePath: '',
    moods: MOODS,
    cloudTypes: [],
    cloudTypesLoading: true,
    form: {
      mood: 3,
      mood_label: '平静',
      cloud_type: '',
      note: '',
      location_lat: '',
      location_lng: '',
      location_name: '',
    },
    noteLength: 0,
    noteFocus: false,
    locationDisplay: '正在获取位置...',
    locationLoading: true,
    locationFailed: false,
    canSubmit: false,
    submitting: false,
    isPublic: false,
    showUploadProgress: false,
    uploadProgress: 0,
    uploadPhase: 'prepare',
  },

  onLoad(options) {
    const nav = getNavLayout();
    this.setData({
      ...nav,
      noteFocus: options.focus === 'note',
    });
    this.initPage();
  },

  onUnload() {
    this.clearProgressTimer();
    hideLoading();
  },

  clearProgressTimer() {
    if (this._progressTimer) {
      clearInterval(this._progressTimer);
      this._progressTimer = null;
    }
  },

  async initPage() {
    try {
      await ensureLogin();
      const res = await api.getTodayStatus();
      if (res.data?.uploaded) {
        showInfo(MSG.TODAY_UPLOADED);
        setTimeout(() => wx.navigateBack(), 1500);
        return;
      }
      this.fetchLocation();
      this.loadCloudTypes();
    } catch (err) {
      console.error('上传页初始化失败', err);
    }
  },

  async loadCloudTypes() {
    this.setData({ cloudTypesLoading: true });
    try {
      await ensureLogin();
      const res = await api.getCloudTypes();
      const items = res.data?.items || [];
      if (items.length > 0) {
        this.setData({ cloudTypes: items, cloudTypesLoading: false });
        return;
      }
    } catch (err) {
      console.error('加载云类型失败，使用本地兜底', err);
    }
    this.setData({
      cloudTypes: CLOUD_TYPES.map(({ name, code, description }) => ({
        name,
        code,
        description: description || '',
      })),
      cloudTypesLoading: false,
    });
  },

  chooseImage() {
    wx.chooseMedia({
      count: 1,
      mediaType: ['image'],
      sourceType: ['album', 'camera'],
      success: (res) => {
        const file = res.tempFiles[0];
        if (!file?.tempFilePath) {
          showInfo('未获取到图片');
          return;
        }
        if (file.size > 5 * 1024 * 1024) {
          showInfo('图片不能超过 5MB');
          return;
        }
        this.setData({
          tempImagePath: file.tempFilePath,
          canSubmit: true,
        });
      },
      fail: () => {
        showInfo(MSG.NO_IMAGE);
      },
    });
  },

  removeImage() {
    this.setData({ tempImagePath: '', canSubmit: false });
  },

  onMoodSelect(e) {
    const { value, label } = e.currentTarget.dataset;
    this.setData({
      'form.mood': Number(value),
      'form.mood_label': label,
    });
  },

  onTypeSelect(e) {
    const name = e.currentTarget.dataset.name;
    const current = this.data.form.cloud_type;
    this.setData({
      'form.cloud_type': current === name ? '' : name,
    });
  },

  onNoteInput(e) {
    const value = e.detail.value;
    this.setData({ 'form.note': value, noteLength: value.length });
  },

  onPublicToggle(e) {
    this.setData({ isPublic: e.detail.value });
  },

  fetchLocation() {
    this.setData({
      locationLoading: true,
      locationFailed: false,
      locationDisplay: '正在获取位置...',
    });

    wx.getLocation({
      type: 'gcj02',
      success: async ({ latitude, longitude }) => {
        this.setData({
          'form.location_lat': latitude,
          'form.location_lng': longitude,
          locationLoading: false,
        });
        await this.resolveLocationDisplay(latitude, longitude);
      },
      fail: () => {
        this.setData({
          locationLoading: false,
          locationFailed: true,
          locationDisplay: '点击选择位置',
          'form.location_lat': '',
          'form.location_lng': '',
        });
        showInfo(MSG.LOCATION_AUTH);
      },
    });
  },

  async resolveLocationDisplay(lat, lng) {
    try {
      const res = await api.resolveLocation(lat, lng);
      const { display } = res.data || {};
      if (display) {
        this.setData({
          locationDisplay: `📍 ${display}`,
          locationFailed: false,
        });
        return;
      }
    } catch {
      // 静默失败
    }
    this.setData({
      locationDisplay: '📍 已获取当前位置',
      locationFailed: false,
    });
  },

  onLocationTap() {
    if (!this.data.locationFailed) return;
    this.chooseLocation();
  },

  chooseLocation() {
    wx.chooseLocation({
      success: async ({ latitude, longitude, name, address }) => {
        this.setData({
          'form.location_lat': latitude,
          'form.location_lng': longitude,
          'form.location_name': name || address,
          locationLoading: false,
          locationFailed: false,
        });
        if (name || address) {
          this.setData({
            locationDisplay: name ? `📍 ${name}` : `📍 ${address}`,
          });
          return;
        }
        await this.resolveLocationDisplay(latitude, longitude);
      },
      fail: () => {
        showInfo(MSG.LOCATION_FAIL);
      },
    });
  },

  /** 模拟上传进度 0→100，完成后执行真实上传 */
  simulateProgress() {
    return new Promise((resolve) => {
      this.setData({
        showUploadProgress: true,
        uploadProgress: 0,
        uploadPhase: 'prepare',
      });

      let progress = 0;
      this._progressTimer = setInterval(() => {
        progress += Math.floor(Math.random() * 12) + 8;
        if (progress >= 100) {
          progress = 100;
          this.clearProgressTimer();
          this.setData({ uploadProgress: 100, uploadPhase: 'uploading' });
          resolve();
          return;
        }
        this.setData({ uploadProgress: progress });
      }, 120);
    });
  },

  async submitUpload() {
    if (this.data.submitting) return;

    if (!this.data.canSubmit || !this.data.tempImagePath) {
      showInfo(MSG.NO_IMAGE);
      return;
    }

    const { tempImagePath, form } = this.data;
    if (!form.mood) {
      showInfo(MSG.NO_MOOD);
      return;
    }

    this.setData({ submitting: true });

    try {
      await ensureLogin();
      await this.simulateProgress();

      showLoading('正在上传云朵...');

      const formData = {
        mood: String(form.mood),
        mood_label: form.mood_label,
        collect_date: formatDate(),
        note: form.note || '',
        is_public: this.data.isPublic ? '1' : '0',
      };

      if (form.cloud_type) {
        formData.cloud_type = form.cloud_type;
      }
      if (form.location_lat && form.location_lng) {
        formData.location_lat = String(form.location_lat);
        formData.location_lng = String(form.location_lng);
      }

      await api.uploadCloudImage(tempImagePath, formData, { loading: false });

      hideLoading();
      this.setData({ showUploadProgress: false });
      showSuccess(MSG.UPLOAD_SUCCESS);

      setTimeout(() => wx.navigateBack(), 1200);
    } catch (err) {
      console.error('上传失败', err);
      hideLoading();
      this.clearProgressTimer();
      this.setData({
        submitting: false,
        showUploadProgress: false,
        uploadProgress: 0,
      });
      showFail(MSG.UPLOAD_FAIL);
    }
  },

  onClose() {
    wx.navigateBack();
  },
});
