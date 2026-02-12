<template>
  <div class="orders">
    <el-card>
      <template #header>
        <span>订单管理</span>
      </template>

      <!-- 搜索栏 -->
      <el-form :inline="true" :model="searchForm" class="search-form">
        <el-form-item label="搜索">
          <el-input v-model="searchForm.search" placeholder="订单号/用户名" clearable @clear="loadOrders" />
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="全部状态" clearable @change="loadOrders">
            <el-option label="待支付" value="pending" />
            <el-option label="待发货" value="paid" />
            <el-option label="已发货" value="shipped" />
            <el-option label="已完成" value="completed" />
            <el-option label="已取消" value="cancelled" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadOrders">查询</el-button>
        </el-form-item>
      </el-form>

      <!-- 订单列表 -->
      <el-table :data="orders" v-loading="loading">
        <el-table-column prop="order_no" label="订单号" width="180" />
        <el-table-column prop="user_name" label="用户名" width="120" />
        <el-table-column prop="total_amount" label="金额" width="120">
          <template #default="{ row }">¥{{ row.total_amount }}</template>
        </el-table-column>
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)">
              {{ getStatusLabel(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="180" />
        <el-table-column label="操作" fixed="right" width="240">
          <template #default="{ row }">
            <el-button type="primary" size="small" @click="handleViewDetail(row)">详情</el-button>
            <el-button
              v-if="row.status === 'paid'"
              type="success"
              size="small"
              @click="handleShip(row)"
            >
              发货
            </el-button>
            <el-button
              v-if="['pending', 'paid'].includes(row.status)"
              type="danger"
              size="small"
              @click="handleCancel(row)"
            >
              取消
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
      <el-pagination
        v-model:current-page="pagination.page"
        v-model:page-size="pagination.pageSize"
        :total="pagination.total"
        :page-sizes="[10, 20, 50, 100]"
        layout="total, sizes, prev, pager, next, jumper"
        @size-change="loadOrders"
        @current-change="loadOrders"
        class="pagination"
      />
    </el-card>

    <!-- 订单详情对话框 -->
    <el-dialog v-model="detailVisible" title="订单详情" width="700px">
      <el-descriptions :column="2" border v-if="orderDetail">
        <el-descriptions-item label="订单号">{{ orderDetail.order_no }}</el-descriptions-item>
        <el-descriptions-item label="用户名">{{ orderDetail.user_name }}</el-descriptions-item>
        <el-descriptions-item label="总金额">¥{{ orderDetail.total_amount }}</el-descriptions-item>
        <el-descriptions-item label="状态">
          <el-tag :type="getStatusType(orderDetail.status)">
            {{ getStatusLabel(orderDetail.status) }}
          </el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="收货地址" :span="2">
          {{ formatAddress(orderDetail.address) }}
        </el-descriptions-item>
        <el-descriptions-item label="物流公司" v-if="orderDetail.logistics_company">
          {{ orderDetail.logistics_company }}
        </el-descriptions-item>
        <el-descriptions-item label="物流单号" v-if="orderDetail.logistics_number">
          {{ orderDetail.logistics_number }}
        </el-descriptions-item>
        <el-descriptions-item label="创建时间">{{ orderDetail.created_at }}</el-descriptions-item>
        <el-descriptions-item label="支付时间" v-if="orderDetail.paid_at">
          {{ orderDetail.paid_at }}
        </el-descriptions-item>
      </el-descriptions>
      
      <el-divider>订单商品</el-divider>
      <el-table :data="orderDetail?.items || []">
        <el-table-column prop="product_name" label="商品名称" />
        <el-table-column prop="price" label="单价" width="100">
          <template #default="{ row }">¥{{ row.price }}</template>
        </el-table-column>
        <el-table-column prop="quantity" label="数量" width="80" />
        <el-table-column label="小计" width="100">
          <template #default="{ row }">¥{{ (row.price * row.quantity).toFixed(2) }}</template>
        </el-table-column>
      </el-table>
    </el-dialog>

    <!-- 发货对话框 -->
    <el-dialog v-model="shipVisible" title="订单发货" width="500px">
      <el-form :model="shipForm" :rules="shipRules" ref="shipFormRef" label-width="100px">
        <el-form-item label="物流公司" prop="logistics_company">
          <el-input v-model="shipForm.logistics_company" />
        </el-form-item>
        <el-form-item label="物流单号" prop="logistics_number">
          <el-input v-model="shipForm.logistics_number" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="shipVisible = false">取消</el-button>
        <el-button type="primary" @click="handleShipSubmit">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getOrderList, getOrderDetail, shipOrder, cancelOrder } from '@/api/order'

export default {
  name: 'Orders',
  setup() {
    const loading = ref(false)
    const orders = ref([])
    const detailVisible = ref(false)
    const shipVisible = ref(false)
    const orderDetail = ref(null)
    const currentOrder = ref(null)
    const shipFormRef = ref(null)

    const searchForm = reactive({
      search: '',
      status: null
    })

    const pagination = reactive({
      page: 1,
      pageSize: 10,
      total: 0
    })

    const shipForm = reactive({
      logistics_company: '',
      logistics_number: ''
    })

    const shipRules = {
      logistics_company: [{ required: true, message: '请输入物流公司', trigger: 'blur' }],
      logistics_number: [{ required: true, message: '请输入物流单号', trigger: 'blur' }]
    }

    const statusLabels = {
      pending: '待支付',
      paid: '待发货',
      shipped: '已发货',
      completed: '已完成',
      cancelled: '已取消'
    }

    const getStatusLabel = (status) => statusLabels[status] || status

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

    const formatAddress = (address) => {
      if (typeof address === 'string') {
        try {
          address = JSON.parse(address)
        } catch {
          return address
        }
      }
      return `${address.province} ${address.city} ${address.district} ${address.detail}`
    }

    const loadOrders = async () => {
      loading.value = true
      try {
        const response = await getOrderList({
          page: pagination.page,
          pageSize: pagination.pageSize,
          search: searchForm.search,
          status: searchForm.status
        })
        orders.value = response.data.list
        pagination.total = response.data.total
      } catch (error) {
        ElMessage.error('加载订单列表失败')
      } finally {
        loading.value = false
      }
    }

    const handleViewDetail = async (row) => {
      try {
        const response = await getOrderDetail(row.id)
        orderDetail.value = response.data
        detailVisible.value = true
      } catch (error) {
        ElMessage.error('加载订单详情失败')
      }
    }

    const handleShip = (row) => {
      currentOrder.value = row
      shipForm.logistics_company = ''
      shipForm.logistics_number = ''
      shipVisible.value = true
    }

    const handleShipSubmit = async () => {
      try {
        await shipFormRef.value.validate()
        await shipOrder(currentOrder.value.id, shipForm)
        ElMessage.success('发货成功')
        shipVisible.value = false
        loadOrders()
      } catch (error) {
        if (error !== false) {
          ElMessage.error('发货失败')
        }
      }
    }

    const handleCancel = (row) => {
      ElMessageBox.confirm('确定要取消该订单吗？', '提示', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      }).then(async () => {
        try {
          await cancelOrder(row.id)
          ElMessage.success('取消成功')
          loadOrders()
        } catch (error) {
          ElMessage.error('取消失败')
        }
      })
    }

    onMounted(() => {
      loadOrders()
    })

    return {
      loading,
      orders,
      searchForm,
      pagination,
      detailVisible,
      shipVisible,
      orderDetail,
      shipForm,
      shipRules,
      shipFormRef,
      getStatusLabel,
      getStatusType,
      formatAddress,
      loadOrders,
      handleViewDetail,
      handleShip,
      handleShipSubmit,
      handleCancel
    }
  }
}
</script>

<style scoped>
.search-form {
  margin-bottom: 20px;
}

.pagination {
  margin-top: 20px;
  justify-content: flex-end;
}
</style>
