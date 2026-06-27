<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Cloudy, User, Picture } from '@element-plus/icons-vue'
import { fetchOverview } from '@/api/dashboard'
import type { OverviewStats } from '@/api/dashboard'

const loading = ref(false)
const stats = ref<OverviewStats>({
  users_total: 0,
  clouds_total: 0,
  clouds_today: 0,
  public_clouds_total: 0,
})

const cards = [
  { key: 'users_total' as const, label: '用户总数', icon: User, color: '#409eff' },
  { key: 'clouds_total' as const, label: '云朵总数', icon: Cloudy, color: '#67c23a' },
  { key: 'clouds_today' as const, label: '今日上传', icon: Picture, color: '#e6a23c' },
  { key: 'public_clouds_total' as const, label: '公开云朵', icon: Cloudy, color: '#909399' },
]

onMounted(async () => {
  loading.value = true
  try {
    stats.value = await fetchOverview()
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div v-loading="loading" class="dashboard">
    <el-row :gutter="20">
      <el-col v-for="card in cards" :key="card.key" :xs="24" :sm="12" :lg="6">
        <el-card shadow="hover" class="stat-card">
          <div class="stat-content">
            <div
              class="stat-icon"
              :style="{ backgroundColor: `${card.color}20`, color: card.color }"
            >
              <el-icon :size="28"><component :is="card.icon" /></el-icon>
            </div>
            <div>
              <div class="stat-value">{{ stats[card.key] }}</div>
              <div class="stat-label">{{ card.label }}</div>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <el-card class="welcome-card" shadow="never">
      <template #header>
        <span>欢迎使用</span>
      </template>
      <p>管理后台已就绪，可通过 <code>/admin/</code> 访问。</p>
      <p>默认管理员账号见服务器 <code>.env</code> 中 <code>ADMIN_EMAIL</code> / <code>ADMIN_PASSWORD</code>。</p>
    </el-card>
  </div>
</template>

<style scoped>
.dashboard {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.stat-card {
  margin-bottom: 0;
}

.stat-content {
  display: flex;
  align-items: center;
  gap: 16px;
}

.stat-icon {
  width: 56px;
  height: 56px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.stat-value {
  font-size: 28px;
  font-weight: 700;
  color: #303133;
  line-height: 1.2;
}

.stat-label {
  font-size: 14px;
  color: #909399;
  margin-top: 4px;
}

.welcome-card p {
  margin: 0 0 8px;
  color: #606266;
  line-height: 1.6;
}

.welcome-card code {
  background: #f4f4f5;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 13px;
}
</style>
