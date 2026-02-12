<template>
  <el-container class="main-layout">
    <el-aside width="200px">
      <el-menu
        :default-active="activeMenu"
        router
        background-color="#304156"
        text-color="#bfcbd9"
        active-text-color="#409EFF"
      >
        <div class="logo">
          <h3>道家玄学商城</h3>
        </div>
        <el-menu-item index="/dashboard">
          <span>仪表盘</span>
        </el-menu-item>
        <el-menu-item index="/users">
          <span>用户管理</span>
        </el-menu-item>
        <el-menu-item index="/categories">
          <span>商品分类</span>
        </el-menu-item>
        <el-menu-item index="/products">
          <span>商品管理</span>
        </el-menu-item>
        <el-menu-item index="/orders">
          <span>订单管理</span>
        </el-menu-item>
        <el-menu-item index="/videos">
          <span>视频管理</span>
        </el-menu-item>
        <el-menu-item index="/articles">
          <span>资讯管理</span>
        </el-menu-item>
        <el-menu-item index="/feng-shui-masters">
          <span>风水师管理</span>
        </el-menu-item>
        <el-menu-item index="/payment-config">
          <span>支付配置</span>
        </el-menu-item>
        <el-menu-item index="/chat">
          <span>客服管理</span>
        </el-menu-item>
      </el-menu>
    </el-aside>
    <el-container>
      <el-header>
        <div class="header-content">
          <span>欢迎，{{ userInfo.username }}</span>
          <el-button type="text" @click="handleLogout">退出</el-button>
        </div>
      </el-header>
      <el-main>
        <router-view />
      </el-main>
    </el-container>
  </el-container>
</template>

<script>
import { computed } from 'vue'
import { useStore } from 'vuex'
import { useRouter, useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'

export default {
  name: 'MainLayout',
  setup() {
    const store = useStore()
    const router = useRouter()
    const route = useRoute()

    const userInfo = computed(() => store.getters['user/userInfo'])
    const activeMenu = computed(() => route.path)

    const handleLogout = () => {
      store.dispatch('user/logout')
      ElMessage.success('退出成功')
      router.push('/login')
    }

    return {
      userInfo,
      activeMenu,
      handleLogout
    }
  }
}
</script>

<style scoped>
.main-layout {
  height: 100vh;
}

.el-aside {
  background-color: #304156;
}

.logo {
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 18px;
  border-bottom: 1px solid #1f2d3d;
}

.logo h3 {
  margin: 0;
  font-size: 16px;
}

.el-header {
  background-color: #fff;
  box-shadow: 0 1px 4px rgba(0, 21, 41, 0.08);
  display: flex;
  align-items: center;
  justify-content: flex-end;
  padding: 0 20px;
}

.header-content {
  display: flex;
  align-items: center;
  gap: 20px;
}

.el-main {
  background-color: #f0f2f5;
  padding: 20px;
}
</style>
