<template>
  <div class="dashboard">
    <h1>仪表盘</h1>
    
    <!-- 统计卡片 -->
    <el-row :gutter="20" class="stats-row">
      <el-col :span="6">
        <el-card class="stat-card">
          <div class="stat-content">
            <div class="stat-icon orders">
              <el-icon><Document /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ stats.totalOrders }}</div>
              <div class="stat-label">总订单数</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card class="stat-card">
          <div class="stat-content">
            <div class="stat-icon sales">
              <el-icon><Money /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">¥{{ stats.totalSales }}</div>
              <div class="stat-label">总销售额</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card class="stat-card">
          <div class="stat-content">
            <div class="stat-icon users">
              <el-icon><User /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ stats.totalUsers }}</div>
              <div class="stat-label">总用户数</div>
            </div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card class="stat-card">
          <div class="stat-content">
            <div class="stat-icon products">
              <el-icon><Goods /></el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-value">{{ stats.totalProducts }}</div>
              <div class="stat-label">总商品数</div>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 订单状态统计 -->
    <el-card class="order-status-card">
      <template #header>
        <span>订单状态统计</span>
      </template>
      <el-row :gutter="20">
        <el-col :span="4" v-for="(count, status) in stats.orderStatusCounts" :key="status">
          <div class="status-item">
            <div class="status-count">{{ count }}</div>
            <div class="status-label">{{ getStatusLabel(status) }}</div>
          </div>
        </el-col>
      </el-row>
    </el-card>

    <!-- 最近订单 -->
    <el-card class="recent-orders-card">
      <template #header>
        <span>最近订单</span>
      </template>
      <el-table :data="stats.recentOrders" v-loading="loading">
        <el-table-column prop="order_no" label="订单号" width="180" />
        <el-table-column prop="user_name" label="用户名" width="120" />
        <el-table-column prop="total_amount" label="金额" width="120">
          <template #default="{ row }">
            ¥{{ row.total_amount }}
          </template>
        </el-table-column>
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)">
              {{ getStatusLabel(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" />
      </el-table>
    </el-card>
  </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue'
import { Document, Money, User, Goods } from '@element-plus/icons-vue'
import { getDashboardStats } from '@/api/dashboard'
import { ElMessage } from 'element-plus'

export default {
  name: 'Dashboard',
  components: {
    Document,
    Money,
    User,
    Goods
  },
  setup() {
    const loading = ref(false)
    const stats = reactive({
      totalOrders: 0,
      totalSales: 0,
      totalUsers: 0,
      totalProducts: 0,
      orderStatusCounts: {},
      recentOrders: []
    })

    const statusLabels = {
      pending: '待支付',
      paid: '待发货',
      shipped: '已发货',
      completed: '已完成',
      cancelled: '已取消'
    }

    const getStatusLabel = (status) => {
      return statusLabels[status] || status
    }

    const getStatusType = (status) => {
      const types = {
        pending: 'warning',
        paid: 'primary',
        shipped: 'info',
        completed: 'success',
        cancelled: 'danger'
      }
      return types[status] || 'info'
    }

    const loadStats = async () => {
      loading.value = true
      try {
        const response = await getDashboardStats()
        Object.assign(stats, response.data)
      } catch (error) {
        ElMessage.error('加载统计数据失败')
      } finally {
        loading.value = false
      }
    }

    onMounted(() => {
      loadStats()
    })

    return {
      loading,
      stats,
      getStatusLabel,
      getStatusType
    }
  }
}
</script>

<style scoped>
.dashboard {
  padding: 20px;
}

.stats-row {
  margin-bottom: 20px;
}

.stat-card {
  cursor: pointer;
  transition: transform 0.3s;
}

.stat-card:hover {
  transform: translateY(-5px);
}

.stat-content {
  display: flex;
  align-items: center;
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 30px;
  color: white;
  margin-right: 15px;
}

.stat-icon.orders {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stat-icon.sales {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.stat-icon.users {
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.stat-icon.products {
  background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.stat-info {
  flex: 1;
}

.stat-value {
  font-size: 24px;
  font-weight: bold;
  color: #303133;
}

.stat-label {
  font-size: 14px;
  color: #909399;
  margin-top: 5px;
}

.order-status-card,
.recent-orders-card {
  margin-bottom: 20px;
}

.status-item {
  text-align: center;
  padding: 20px 0;
}

.status-count {
  font-size: 28px;
  font-weight: bold;
  color: #409eff;
}

.status-label {
  font-size: 14px;
  color: #606266;
  margin-top: 10px;
}
</style>
