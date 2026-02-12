<template>
  <div class="front-layout">
    <el-header class="header">
      <div class="header-content">
        <div class="logo">道家玄学商城</div>
        <el-menu mode="horizontal" :default-active="activeMenu" router>
          <el-menu-item index="/shop">商城</el-menu-item>
          <el-menu-item index="/shop/videos">视频</el-menu-item>
          <el-menu-item index="/shop/articles">资讯</el-menu-item>
          <el-menu-item index="/shop/masters">风水师</el-menu-item>
        </el-menu>
        <div class="header-actions">
          <el-badge :value="cartCount" class="cart-badge">
            <el-button icon="ShoppingCart" @click="$router.push('/shop/cart')">购物车</el-button>
          </el-badge>
        </div>
      </div>
    </el-header>
    <el-main class="main-content">
      <router-view />
    </el-main>
    <el-footer class="footer">
      <p>© 2026 道家玄学商城系统 - 版权所有</p>
    </el-footer>
    <UserChat />
  </div>
</template>

<script>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useStore } from 'vuex'
import UserChat from '@/views/shop/UserChat.vue'

export default {
  name: 'FrontLayout',
  components: { UserChat },
  setup() {
    const route = useRoute()
    const store = useStore()

    const activeMenu = computed(() => route.path)
    const cartCount = computed(() => store.getters['cart/cartCount'])

    return {
      activeMenu,
      cartCount
    }
  }
}
</script>

<style scoped>
.front-layout {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.header {
  background-color: #fff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  padding: 0;
}

.header-content {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 100%;
  padding: 0 20px;
}

.logo {
  font-size: 20px;
  font-weight: bold;
  color: #409eff;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.cart-badge {
  margin-right: 10px;
}

.main-content {
  flex: 1;
  background-color: #f5f7fa;
  padding: 20px;
}

.footer {
  background-color: #303133;
  color: #fff;
  text-align: center;
  padding: 20px;
}
</style>
