<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { DataBoard, SwitchButton, User, Cloudy, Collection, Refresh } from '@element-plus/icons-vue'
import { useAuthStore } from '@/stores/auth'
import { logout } from '@/api/auth'
import { clearCache } from '@/api/system'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const clearingCache = ref(false)

const activeMenu = computed(() => route.path)

const menuItems = [
  { path: '/dashboard', title: '仪表盘', icon: DataBoard },
  { path: '/users', title: '用户管理', icon: User },
  { path: '/clouds', title: '云朵管理', icon: Cloudy },
  { path: '/cloud-types', title: '云类型', icon: Collection },
]

async function handleClearCache() {
  await ElMessageBox.confirm(
    '将清除路由、配置、应用与视图缓存，部署新代码后建议执行。确定继续吗？',
    '更新缓存',
    {
      type: 'info',
      confirmButtonText: '确定',
      cancelButtonText: '取消',
    },
  )

  clearingCache.value = true
  try {
    const result = await clearCache()
    ElMessage.success(`缓存已更新（${result.cleared_at}）`)
  } finally {
    clearingCache.value = false
  }
}

async function handleLogout() {
  await ElMessageBox.confirm('确定退出登录吗？', '提示', {
    type: 'warning',
    confirmButtonText: '退出',
    cancelButtonText: '取消',
  })

  try {
    await logout()
  } catch {
    // 后端未就绪时仍允许本地退出
  }

  auth.clearAuth()
  router.push({ name: 'Login' })
}
</script>

<template>
  <el-container class="admin-layout">
    <el-aside width="220px" class="admin-aside">
      <div class="brand">
        <span class="brand-icon">☁</span>
        <span class="brand-text">云彩管理后台</span>
      </div>
      <el-menu
        :default-active="activeMenu"
        router
        background-color="#1d2b3a"
        text-color="#bfcbd9"
        active-text-color="#409eff"
      >
        <el-menu-item
          v-for="item in menuItems"
          :key="item.path"
          :index="item.path"
        >
          <el-icon><component :is="item.icon" /></el-icon>
          <span>{{ item.title }}</span>
        </el-menu-item>
      </el-menu>
    </el-aside>

    <el-container>
      <el-header class="admin-header">
        <div class="header-title">{{ route.meta.title || '管理后台' }}</div>
        <div class="header-actions">
          <el-button type="primary" link :loading="clearingCache" @click="handleClearCache">
            <el-icon><Refresh /></el-icon>
            更新缓存
          </el-button>
          <span class="admin-name">{{ auth.admin?.name || auth.admin?.email }}</span>
          <el-button type="danger" link @click="handleLogout">
            <el-icon><SwitchButton /></el-icon>
            退出
          </el-button>
        </div>
      </el-header>

      <el-main class="admin-main">
        <router-view />
      </el-main>
    </el-container>
  </el-container>
</template>

<style scoped>
.admin-layout {
  height: 100vh;
}

.admin-aside {
  background: #1d2b3a;
  display: flex;
  flex-direction: column;
}

.brand {
  height: 60px;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 0 20px;
  color: #fff;
  font-size: 16px;
  font-weight: 600;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.brand-icon {
  font-size: 22px;
}

.admin-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #fff;
  border-bottom: 1px solid #ebeef5;
  height: 60px;
}

.header-title {
  font-size: 18px;
  font-weight: 600;
  color: #303133;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.admin-name {
  color: #606266;
  font-size: 14px;
}

.admin-main {
  background: #f5f7fa;
  padding: 20px;
}
</style>
