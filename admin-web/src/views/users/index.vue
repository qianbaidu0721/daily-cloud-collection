<script setup lang="ts">
import { reactive, ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { fetchUsers, type UserSummary } from '@/api/users'

const router = useRouter()
const loading = ref(false)
const list = ref<UserSummary[]>([])

const query = reactive({
  keyword: '',
  page: 1,
  per_page: 15,
})

const pagination = reactive({
  total: 0,
  last_page: 1,
})

async function loadData() {
  loading.value = true
  try {
    const data = await fetchUsers({
      keyword: query.keyword || undefined,
      page: query.page,
      per_page: query.per_page,
    })
    list.value = data.list
    pagination.total = data.pagination.total
    pagination.last_page = data.pagination.last_page
  } finally {
    loading.value = false
  }
}

function handleSearch() {
  query.page = 1
  loadData()
}

function handlePageChange(page: number) {
  query.page = page
  loadData()
}

function goDetail(row: UserSummary) {
  router.push({ name: 'UserDetail', params: { id: row.id } })
}

onMounted(loadData)
</script>

<template>
  <el-card shadow="never">
    <template #header>
      <div class="card-header">
        <span>用户列表</span>
        <el-form :inline="true" @submit.prevent="handleSearch">
          <el-form-item label="关键词">
            <el-input
              v-model="query.keyword"
              placeholder="昵称 / OpenID"
              clearable
              style="width: 220px"
              @clear="handleSearch"
            />
          </el-form-item>
          <el-form-item>
            <el-button type="primary" @click="handleSearch">搜索</el-button>
          </el-form-item>
        </el-form>
      </div>
    </template>

    <el-table v-loading="loading" :data="list" stripe>
      <el-table-column prop="id" label="ID" width="80" />
      <el-table-column label="头像" width="80">
        <template #default="{ row }">
          <el-avatar :size="36" :src="row.avatar || undefined">
            {{ row.nickname?.[0] || 'U' }}
          </el-avatar>
        </template>
      </el-table-column>
      <el-table-column prop="nickname" label="昵称" min-width="120">
        <template #default="{ row }">
          {{ row.nickname || '—' }}
        </template>
      </el-table-column>
      <el-table-column prop="openid" label="OpenID" min-width="160" />
      <el-table-column prop="total_days" label="累计天数" width="100" />
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
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
}

.card-header .el-form {
  margin-bottom: 0;
}

.card-header .el-form-item {
  margin-bottom: 0;
}

.pagination-wrap {
  display: flex;
  justify-content: flex-end;
  margin-top: 16px;
}
</style>
