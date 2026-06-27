<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import type { FormInstance, FormRules } from 'element-plus'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import {
  fetchCloudTypes,
  createCloudType,
  updateCloudType,
  deleteCloudType,
  type CloudTypeItem,
  type CloudTypePayload,
} from '@/api/cloud-types'

const loading = ref(false)
const saving = ref(false)
const list = ref<CloudTypeItem[]>([])
const dialogVisible = ref(false)
const isEdit = ref(false)
const editingId = ref<number | null>(null)
const formRef = ref<FormInstance>()

const form = reactive<CloudTypePayload>({
  name: '',
  code: '',
  description: '',
  icon: '',
  sort: 0,
  is_active: true,
})

const rules: FormRules = {
  name: [{ required: true, message: '请输入名称', trigger: 'blur' }],
  code: [{ required: true, message: '请输入编码', trigger: 'blur' }],
}

async function loadData() {
  loading.value = true
  try {
    const data = await fetchCloudTypes()
    list.value = data.list
  } finally {
    loading.value = false
  }
}

function resetForm() {
  form.name = ''
  form.code = ''
  form.description = ''
  form.icon = ''
  form.sort = 0
  form.is_active = true
}

function openCreate() {
  isEdit.value = false
  editingId.value = null
  resetForm()
  dialogVisible.value = true
}

function openEdit(row: CloudTypeItem) {
  isEdit.value = true
  editingId.value = row.id
  form.name = row.name
  form.code = row.code
  form.description = row.description || ''
  form.icon = row.icon || ''
  form.sort = row.sort
  form.is_active = row.is_active
  dialogVisible.value = true
}

async function handleSubmit() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) {
    return
  }

  saving.value = true
  try {
    const payload: CloudTypePayload = {
      name: form.name,
      code: form.code,
      description: form.description || null,
      icon: form.icon || null,
      sort: form.sort,
      is_active: form.is_active,
    }

    if (isEdit.value && editingId.value !== null) {
      await updateCloudType(editingId.value, payload)
      ElMessage.success('更新成功')
    } else {
      await createCloudType(payload)
      ElMessage.success('创建成功')
    }

    dialogVisible.value = false
    await loadData()
  } finally {
    saving.value = false
  }
}

async function handleDelete(row: CloudTypeItem) {
  await ElMessageBox.confirm(`确定删除云类型「${row.name}」吗？`, '警告', {
    type: 'warning',
    confirmButtonText: '删除',
    cancelButtonText: '取消',
  })

  await deleteCloudType(row.id)
  ElMessage.success('删除成功')
  await loadData()
}

async function toggleActive(row: CloudTypeItem) {
  await updateCloudType(row.id, { is_active: !row.is_active })
  ElMessage.success(row.is_active ? '已禁用' : '已启用')
  await loadData()
}

onMounted(loadData)
</script>

<template>
  <el-card shadow="never">
    <template #header>
      <div class="card-header">
        <span>云类型管理</span>
        <el-button type="primary" :icon="Plus" @click="openCreate">新增</el-button>
      </div>
    </template>

    <el-table v-loading="loading" :data="list" stripe>
      <el-table-column prop="id" label="ID" width="70" />
      <el-table-column prop="name" label="名称" min-width="100" />
      <el-table-column prop="code" label="编码" min-width="100" />
      <el-table-column prop="description" label="描述" min-width="160">
        <template #default="{ row }">{{ row.description || '—' }}</template>
      </el-table-column>
      <el-table-column prop="sort" label="排序" width="80" />
      <el-table-column label="状态" width="90">
        <template #default="{ row }">
          <el-tag :type="row.is_active ? 'success' : 'info'" size="small">
            {{ row.is_active ? '启用' : '禁用' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column label="操作" width="200" fixed="right">
        <template #default="{ row }">
          <el-button type="primary" link @click="openEdit(row)">编辑</el-button>
          <el-button type="primary" link @click="toggleActive(row)">
            {{ row.is_active ? '禁用' : '启用' }}
          </el-button>
          <el-button type="danger" link @click="handleDelete(row)">删除</el-button>
        </template>
      </el-table-column>
    </el-table>
  </el-card>

  <el-dialog
    v-model="dialogVisible"
    :title="isEdit ? '编辑云类型' : '新增云类型'"
    width="480px"
    destroy-on-close
  >
    <el-form ref="formRef" :model="form" :rules="rules" label-width="80px">
      <el-form-item label="名称" prop="name">
        <el-input v-model="form.name" placeholder="如：积云" />
      </el-form-item>
      <el-form-item label="编码" prop="code">
        <el-input v-model="form.code" placeholder="如：cumulus" :disabled="isEdit" />
      </el-form-item>
      <el-form-item label="描述">
        <el-input v-model="form.description" type="textarea" :rows="2" />
      </el-form-item>
      <el-form-item label="图标">
        <el-input v-model="form.icon" placeholder="图标 URL，可选" />
      </el-form-item>
      <el-form-item label="排序">
        <el-input-number v-model="form.sort" :min="0" :max="65535" />
      </el-form-item>
      <el-form-item label="启用">
        <el-switch v-model="form.is_active" />
      </el-form-item>
    </el-form>

    <template #footer>
      <el-button @click="dialogVisible = false">取消</el-button>
      <el-button type="primary" :loading="saving" @click="handleSubmit">确定</el-button>
    </template>
  </el-dialog>
</template>

<style scoped>
.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
</style>
