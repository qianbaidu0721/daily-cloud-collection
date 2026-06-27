const { formatCardDate } = require('../../utils/util');

Component({
  properties: {
    imageUrl: {
      type: String,
      value: '',
    },
    date: {
      type: String,
      value: '',
    },
    mood: {
      type: String,
      value: '☁️',
    },
    city: {
      type: String,
      value: '',
    },
    note: {
      type: String,
      value: '',
    },
    layout: {
      type: String,
      value: 'small',
    },
    cloudId: {
      type: Number,
      value: 0,
    },
    isPublic: {
      type: Boolean,
      value: false,
    },
    showPublicBadge: {
      type: Boolean,
      value: false,
    },
    authorLabel: {
      type: String,
      value: '',
    },
    showDate: {
      type: Boolean,
      value: true,
    },
  },

  data: {
    displayDate: '',
    isLarge: false,
    displayUrl: '',
    imageLoaded: false,
    imageError: false,
    retryKey: 0,
  },

  observers: {
    date(val) {
      this.setData({
        displayDate: val ? formatCardDate(val) : '',
      });
    },
    layout(val) {
      this.setData({ isLarge: val === 'large' });
    },
    imageUrl(val) {
      this.resetImageState(val);
    },
  },

  lifetimes: {
    attached() {
      this.setData({
        displayDate: this.properties.date ? formatCardDate(this.properties.date) : '',
        isLarge: this.properties.layout === 'large',
      });
      this.resetImageState(this.properties.imageUrl);
    },
  },

  methods: {
    resetImageState(url) {
      if (!url) {
        this.setData({
          displayUrl: '',
          imageLoaded: false,
          imageError: false,
        });
        return;
      }
      this.setData({
        displayUrl: url,
        imageLoaded: false,
        imageError: false,
      });
    },

    onTap() {
      this.triggerEvent('tap', {
        id: this.properties.cloudId,
        imageUrl: this.properties.imageUrl,
        date: this.properties.date,
        mood: this.properties.mood,
        city: this.properties.city,
        note: this.properties.note,
      });
    },

    onImageLoad() {
      this.setData({ imageLoaded: true, imageError: false });
      this.triggerEvent('load', { id: this.properties.cloudId });
    },

    onImageError() {
      this.setData({ imageLoaded: false, imageError: true });
      this.triggerEvent('error', { id: this.properties.cloudId });
    },

    onRetryImage() {
      const { imageUrl } = this.properties;
      if (!imageUrl) return;
      const retryKey = this.data.retryKey + 1;
      const sep = imageUrl.includes('?') ? '&' : '?';
      this.setData({
        imageError: false,
        imageLoaded: false,
        retryKey,
        displayUrl: `${imageUrl}${sep}_r=${retryKey}`,
      });
    },
  },
});
