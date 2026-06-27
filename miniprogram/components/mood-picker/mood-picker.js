const { MOODS } = require('../../utils/constants');

Component({
  properties: {
    /** 当前选中心情值 1-5 */
    value: {
      type: Number,
      value: 0,
    },
    title: {
      type: String,
      value: '今天的心情',
    },
  },

  data: {
    moods: MOODS,
    selected: 0,
  },

  observers: {
    value(val) {
      this.setData({ selected: val });
    },
  },

  lifetimes: {
    attached() {
      this.setData({ selected: this.properties.value });
    },
  },

  methods: {
    /** 选择心情 */
    onSelect(e) {
      const { value, label } = e.currentTarget.dataset;
      this.setData({ selected: value });
      this.triggerEvent('change', { value, label });
    },
  },
});
