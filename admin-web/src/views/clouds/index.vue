<script setup lang="ts">
import { reactive, ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import {
  fetchClouds,
  MOOD_OPTIONS,
  moodLabel,
  type CloudSummary,
} from '@/api/clouds'
import { fetchCloudTypes, type CloudTypeItem } from '@/api/cloud-types'

const router = useRouter()
const loading = ref(false)
const list = ref<CloudSummary[]>([])
const cloudTypes = ref<CloudTypeItem[]>([])

const query = reactive({
  keyword: '',
  is_public: '' as boolean | '',
  mood: '' as number | '',
  cloud_type: '',
  collect_date: '',
  page: 1,
  per_page: 15,
})

const pagination = reactive({
  total: 0,
})

async function loadCloudTypes() {
  try {
    const data = await fetchCloudTypes()
    cloudTypes.value = data.list
  } catch {
    cloudTypes.value = []
  }
}

async function loadData() {
  loading.value = true
  try {
    const data = await fetchClouds({
      keyword: query.keyword || undefined,
      is_public: query.is_public === '' ? undefined : query.is_public,
      mood: query.mood === '' ? undefined : query.mood,
      cloud_type: query.cloud_type || undefined,
      collect_date: query.collect_date || undefined,
      page: query.page,
      per_page: query.per_page,
    })
    list.value = data.list
    pagination.total = data.pagination.total
  } finally {
    loading.value = false
  }
}

function handleSearch() {
  query.page = 1
  loadData()
}

function handleReset() {
  query.keyword = ''
  query.is_public = ''
  query.mood = ''
  query.cloud_type = ''
  query.collect_date = ''
  query.page = 1
  loadData()
}

function handlePageChange(page: number) {
  query.page = page
  loadData()
}

function goDetail(row: CloudSummary) {
  router.push({ name: 'CloudDetail', params: { id: row.id } })
}

function goUser(row: CloudSummary) {
  router.push({ name: 'UserDetail', params: { id: row.user_id } })
}

onMounted(async () => {
  await loadCloudTypes()
  await loadData()
})
</script>

<template>
  <el-card shadow="never">
    <template #header>
      <div class="card-header">
        <span>云朵列表</span>
      </div>
    </template>

    <el-form :inline="true" class="filter-form" @submit.prevent="handleSearch">
      <el-form-item label="关键词">
        <el-input
          v-model="query.keyword"
          placeholder="城市 / 备注 / 昵称"
          clearable
          style="width: 180px"
        />
      </el-form-item>
      <el-form-item label="公开">
        <el-select v-model="query.is_public" placeholder="全部" clearable style="width: 100px">
          <el-option label="是" :value="true" />
          <el-option label="否" :value="false" />
        </el-select>
      </el-form-item>
      <el-form-item label="心情">
        <el-select v-model="query.mood" placeholder="全部" clearable style="width: 110px">
          <el-option
            v-for="item in MOOD_OPTIONS"
            :key="item.value"
            :label="item.label"
            :value="item.value"
          />
        </el-select>
      </el-form-item>
      <el-form-item label="云类型">
        <el-select v-model="query.cloud_type" placeholder="全部" clearable style="width: 120px">
          <el-option
            v-for="item in cloudTypes"
            :key="item.code"
            :label="item.name"
            :value="item.code"
          />
        </el-select>
      </el-form-item>
      <el-form-item label="日期">
        <el-date-picker
          v-model="query.collect_date"
          type="date"
          placeholder="选择日期"
          value-format="YYYY-MM-DD"
          clearable
          style="width: 150px"
        />
      </el-form-item>
      <el-form-item>
        <el-button type="primary" @click="handleSearch">搜索</el-button>
        <el-button @click="handleReset">重置</el-button>
      </el-form-item>
    </el-form>

    <el-table v-loading="loading" :data="list" stripe>
      <el-table-column prop="id" label="ID" width="70" />
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
      <el-table-column prop="collect_date" label="日期" width="110" />
      <el-table-column label="用户" min-width="100">
        <template #default="{ row }">
          <el-button type="primary" link @click="goUser(row)">
            {{ row.user_nickname || `#${row.user_id}` }}
          </el-button>
        </template>
      </el-table-column>
      <el-table-column label="心情" width="90">
        <template #default="{ row }">
          {{ row.mood_label || moodLabel(row.mood) }}
        </template>
      </el-table-column>
      <el-table-column prop="location_city" label="城市" min-width="90">
        <template #default="{ row }">{{ row.location_city || '—' }}</template>
      </el-table-column>
      <el-table-column prop="cloud_type" label="云类型" width="90">
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
          <el-button type="primary" link @click="goDetail(row)">详情</el-button>
        </template>
      </el-table-column>
    </el-table>

    <div class="pagination-wrap">
      <el-pagination
        v-model:current-page="query.page"
        :page-size="query.per_page"
        :total="pagination.total"
        layout="total, prev, pager, next"
        @current-change="handlePageChange"
      />
    </div>
  </el-card>
</template>

<style scoped>
.card-header {
  font-weight: 600;
}

.filter-form {
  margin-bottom: 8px;
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
