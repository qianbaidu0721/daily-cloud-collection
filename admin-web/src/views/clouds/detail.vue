<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { ArrowLeft } from '@element-plus/icons-vue'
import {
  fetchCloud,
  updateCloud,
  deleteCloud,
  moodLabel,
  type CloudDetail,
} from '@/api/clouds'

const route = useRoute()
const router = useRouter()
const cloudId = computed(() => Number(route.params.id))

const loading = ref(false)
const saving = ref(false)
const cloud = ref<CloudDetail | null>(null)

const form = reactive({
  is_public: false,
  note: '',
})

async function loadCloud() {
  loading.value = true
  try {
    cloud.value = await fetchCloud(cloudId.value)
    form.is_public = cloud.value.is_public
    form.note = cloud.value.note || ''
  } catch {
    ElMessage.error('云朵不存在')
    router.replace({ name: 'Clouds' })
  } finally {
    loading.value = false
  }
}

async function handleSave() {
  saving.value = true
  try {
    cloud.value = await updateCloud(cloudId.value, {
      is_public: form.is_public,
      note: form.note || null,
    })
    ElMessage.success('保存成功')
  } finally {
    saving.value = false
  }
}

async function handleDelete() {
  await ElMessageBox.confirm('删除后不可恢复，确定删除该云朵吗？', '警告', {
    type: 'warning',
    confirmButtonText: '删除',
    cancelButtonText: '取消',
  })

  await deleteCloud(cloudId.value)
  ElMessage.success('已删除')
  router.push({ name: 'Clouds' })
}

function goBack() {
  router.back()
}

function goUser() {
  if (cloud.value?.user_id) {
    router.push({ name: 'UserDetail', params: { id: cloud.value.user_id } })
  }
}

onMounted(loadCloud)
</script>

<template>
  <div v-loading="loading" class="cloud-detail">
    <el-page-header :icon="ArrowLeft" @back="goBack">
      <template #content>
        <span>云朵详情 #{{ cloudId }}</span>
      </template>
    </el-page-header>

    <template v-if="cloud">
      <el-row :gutter="20" class="content-row">
        <el-col :xs="24" :lg="10">
          <el-card shadow="never">
            <el-image
              :src="cloud.image_url"
              :preview-src-list="[cloud.image_url]"
              fit="contain"
              class="cloud-image"
              preview-teleported
            />
          </el-card>
        </el-col>

        <el-col :xs="24" :lg="14">
          <el-card shadow="never">
            <template #header>
              <span>基本信息</span>
            </template>

            <el-descriptions :column="1" border>
              <el-descriptions-item label="收集日期">{{ cloud.collect_date }}</el-descriptions-item>
              <el-descriptions-item label="心情">
                {{ cloud.mood_label || moodLabel(cloud.mood) }}
              </el-descriptions-item>
              <el-descriptions-item label="城市">
                {{ cloud.location_city || '—' }}
              </el-descriptions-item>
              <el-descriptions-item label="坐标">
                <template v-if="cloud.location_lat && cloud.location_lng">
                  {{ cloud.location_lat }}, {{ cloud.location_lng }}
                </template>
                <template v-else>—</template>
              </el-descriptions-item>
              <el-descriptions-item label="云类型">{{ cloud.cloud_type || '—' }}</el-descriptions-item>
              <el-descriptions-item label="用户">
                <el-button v-if="cloud.user" type="primary" link @click="goUser">
                  {{ cloud.user.nickname || `#${cloud.user_id}` }}
                </el-button>
                <span v-else>—</span>
              </el-descriptions-item>
              <el-descriptions-item label="创建时间">{{ cloud.created_at || '—' }}</el-descriptions-item>
            </el-descriptions>
          </el-card>

          <el-card shadow="never" class="edit-card">
            <template #header>
              <span>编辑</span>
            </template>

            <el-form label-width="80px">
              <el-form-item label="公开">
                <el-switch v-model="form.is_public" active-text="公开" inactive-text="私密" />
              </el-form-item>
              <el-form-item label="备注">
                <el-input
                  v-model="form.note"
                  type="textarea"
                  :rows="3"
                  maxlength="500"
                  show-word-limit
                  placeholder="备注内容"
                />
              </el-form-item>
              <el-form-item>
                <el-button type="primary" :loading="saving" @click="handleSave">保存</el-button>
                <el-button type="danger" plain @click="handleDelete">删除云朵</el-button>
              </el-form-item>
            </el-form>
          </el-card>
        </el-col>
      </el-row>
    </template>
  </div>
</template>

<style scoped>
.cloud-detail {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.content-row {
  margin-top: 8px;
}

.cloud-image {
  width: 100%;
  max-height: 480px;
  border-radius: 8px;
}

.edit-card {
  margin-top: 16px;
}
</style>
