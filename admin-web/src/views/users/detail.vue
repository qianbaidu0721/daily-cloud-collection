<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { ArrowLeft } from '@element-plus/icons-vue'
import { fetchUser, fetchUserClouds, type UserDetail, type UserCloudItem } from '@/api/users'
import { moodLabel } from '@/api/clouds'

const route = useRoute()
const router = useRouter()
const userId = computed(() => Number(route.params.id))

const loading = ref(false)
const cloudsLoading = ref(false)
const user = ref<UserDetail | null>(null)
const clouds = ref<UserCloudItem[]>([])

const cloudQuery = reactive({
  page: 1,
  per_page: 10,
})

const cloudPagination = reactive({
  total: 0,
})

async function loadUser() {
  loading.value = true
  try {
    user.value = await fetchUser(userId.value)
  } catch {
    ElMessage.error('用户不存在')
    router.replace({ name: 'Users' })
  } finally {
    loading.value = false
  }
}

async function loadClouds() {
  cloudsLoading.value = true
  try {
    const data = await fetchUserClouds(userId.value, cloudQuery)
    clouds.value = data.list
    cloudPagination.total = data.pagination.total
  } finally {
    cloudsLoading.value = false
  }
}

function handleCloudPageChange(page: number) {
  cloudQuery.page = page
  loadClouds()
}

function goCloudDetail(row: UserCloudItem) {
  router.push({ name: 'CloudDetail', params: { id: row.id } })
}

function goBack() {
  router.push({ name: 'Users' })
}

onMounted(async () => {
  await loadUser()
  await loadClouds()
})
</script>

<template>
  <div v-loading="loading" class="user-detail">
    <el-page-header :icon="ArrowLeft" @back="goBack">
      <template #content>
        <span>用户详情 #{{ userId }}</span>
      </template>
    </el-page-header>

    <el-card v-if="user" shadow="never" class="info-card">
      <div class="user-profile">
        <el-avatar :size="64" :src="user.avatar || undefined">
          {{ user.nickname?.[0] || 'U' }}
        </el-avatar>
        <div class="user-meta">
          <h2>{{ user.nickname || '未设置昵称' }}</h2>
          <p>OpenID：{{ user.openid }}</p>
          <p>注册时间：{{ user.created_at || '—' }}</p>
        </div>
        <div class="user-stats">
          <div class="stat-item">
            <div class="stat-value">{{ user.total_days }}</div>
            <div class="stat-label">累计天数</div>
          </div>
          <div class="stat-item">
            <div class="stat-value">{{ user.clouds_count }}</div>
            <div class="stat-label">云朵总数</div>
          </div>
          <div class="stat-item">
            <div class="stat-value">{{ user.public_clouds_count }}</div>
            <div class="stat-label">公开云朵</div>
          </div>
        </div>
      </div>
    </el-card>

    <el-card shadow="never">
      <template #header>
        <span>云朵记录</span>
      </template>

      <el-table v-loading="cloudsLoading" :data="clouds" stripe>
        <el-table-column label="缩略图" width="90">
          <template #default="{ row }">
            <el-image
              :src="row.image_url"
              :preview-src-list="[row.image_url]"
              fit="cover"
              class="thumb"
              preview-teleported
            />
          </template>
        </el-table-column>
        <el-table-column prop="collect_date" label="日期" width="120" />
        <el-table-column label="心情" width="100">
          <template #default="{ row }">
            {{ row.mood_label || moodLabel(row.mood) }}
          </template>
        </el-table-column>
        <el-table-column prop="location_city" label="城市" min-width="100">
          <template #default="{ row }">{{ row.location_city || '—' }}</template>
        </el-table-column>
        <el-table-column prop="cloud_type" label="云类型" width="100">
          <template #default="{ row }">{{ row.cloud_type || '—' }}</template>
        </el-table-column>
        <el-table-column label="公开" width="80">
          <template #default="{ row }">
            <el-tag :type="row.is_public ? 'success' : 'info'" size="small">
              {{ row.is_public ? '是' : '否' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="100" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link @click="goCloudDetail(row)">详情</el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination-wrap">
        <el-pagination
          v-model:current-page="cloudQuery.page"
          :page-size="cloudQuery.per_page"
          :total="cloudPagination.total"
          layout="total, prev, pager, next"
          @current-change="handleCloudPageChange"
        />
      </div>
    </el-card>
  </div>
</template>

<style scoped>
.user-detail {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.info-card {
  margin-top: 8px;
}

.user-profile {
  display: flex;
  align-items: center;
  gap: 24px;
  flex-wrap: wrap;
}

.user-meta h2 {
  margin: 0 0 8px;
  font-size: 20px;
}

.user-meta p {
  margin: 0 0 4px;
  color: #606266;
  font-size: 14px;
}

.user-stats {
  display: flex;
  gap: 32px;
  margin-left: auto;
}

.stat-item {
  text-align: center;
}

.stat-value {
  font-size: 24px;
  font-weight: 700;
  color: #409eff;
}

.stat-label {
  font-size: 13px;
  color: #909399;
  margin-top: 4px;
}

.thumb {
  width: 56px;
  height: 56px;
  border-radius: 6px;
}

.pagination-wrap {
  display: flex;
  justify-content: flex-end;
  margin-top: 16px;
}
</style>
