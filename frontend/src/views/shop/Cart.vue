<template>
  <div class="cart">
    <el-card>
      <template #header>
        <span>购物车</span>
      </template>

      <el-empty v-if="cartItems.length === 0" description="购物车是空的" />

      <div v-else>
        <el-table :data="cartItems">
          <el-table-column label="商品" width="300">
            <template #default="{ row }">
              <div class="product-info">
                <img :src="row.images?.[0] || '/placeholder.png'" class="product-thumb" />
                <span>{{ row.name }}</span>
              </div>
            </template>
          </el-table-column>
          <el-table-column prop="price" label="单价" width="120">
            <template #default="{ row }">¥{{ row.price }}</template>
          </el-table-column>
          <el-table-column label="数量" width="150">
            <template #default="{ row }">
              <el-input-number
                :model-value="row.quantity"
                :min="1"
                :max="row.stock"
                @change="(val) => handleQuantityChange(row.id, val)"
              />
            </template>
          </el-table-column>
          <el-table-column label="小计" width="120">
            <template #default="{ row }">
              ¥{{ (row.price * row.quantity).toFixed(2) }}
            </template>
          </el-table-column>
          <el-table-column label="操作" width="100">
            <template #default="{ row }">
              <el-button type="danger" size="small" @click="handleRemove(row.id)">
                删除
              </el-button>
            </template>
          </el-table-column>
        </el-table>

        <div class="cart-summary">
          <div class="summary-item">
            <span>商品总数：</span>
            <span class="summary-value">{{ cartCount }} 件</span>
          </div>
          <div class="summary-item">
            <span>总计：</span>
            <span class="summary-price">¥{{ cartTotal.toFixed(2) }}</span>
          </div>
          <el-button type="primary" size="large" @click="handleCheckout">
            去结算
          </el-button>
        </div>
      </div>
    </el-card>

    <!-- 结算对话框 -->
    <el-dialog v-model="checkoutVisible" title="确认订单" width="600px">
      <el-form :model="orderForm" :rules="orderRules" ref="orderFormRef" label-width="100px">
        <el-form-item label="收货人" prop="name">
          <el-input v-model="orderForm.name" />
        </el-form-item>
        <el-form-item label="手机号" prop="phone">
          <el-input v-model="orderForm.phone" />
        </el-form-item>
        <el-form-item label="省份" prop="province">
          <el-input v-model="orderForm.province" />
        </el-form-item>
        <el-form-item label="城市" prop="city">
          <el-input v-model="orderForm.city" />
        </el-form-item>
        <el-form-item label="区县" prop="district">
          <el-input v-model="orderForm.district" />
        </el-form-item>
        <el-form-item label="详细地址" prop="detail">
          <el-input v-model="orderForm.detail" type="textarea" :rows="3" />
        </el-form-item>
        <el-divider />
        <div class="order-summary">
          <p>订单总额：<span class="price">¥{{ cartTotal.toFixed(2) }}</span></p>
        </div>
      </el-form>
      <template #footer>
        <el-button @click="checkoutVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmitOrder" :loading="submitting">
          提交订单
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import { ref, reactive, computed } from 'vue'
import { useStore } from 'vuex'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { createOrder } from '@/api/order'

export default {
  name: 'Cart',
  setup() {
    const store = useStore()
    const router = useRouter()
    const checkoutVisible = ref(false)
    const submitting = ref(false)
    const orderFormRef = ref(null)

    const cartItems = computed(() => store.getters['cart/cartItems'])
    const cartTotal = computed(() => store.getters['cart/cartTotal'])
    const cartCount = computed(() => store.getters['cart/cartCount'])

    const orderForm = reactive({
      name: '',
      phone: '',
      province: '',
      city: '',
      district: '',
      detail: ''
    })

    const orderRules = {
      name: [{ required: true, message: '请输入收货人', trigger: 'blur' }],
      phone: [{ required: true, message: '请输入手机号', trigger: 'blur' }],
      province: [{ required: true, message: '请输入省份', trigger: 'blur' }],
      city: [{ required: true, message: '请输入城市', trigger: 'blur' }],
      district: [{ required: true, message: '请输入区县', trigger: 'blur' }],
      detail: [{ required: true, message: '请输入详细地址', trigger: 'blur' }]
    }

    const handleQuantityChange = (productId, quantity) => {
      store.dispatch('cart/updateQuantity', { productId, quantity })
    }

    const handleRemove = (productId) => {
      ElMessageBox.confirm('确定要删除该商品吗？', '提示', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      }).then(() => {
        store.dispatch('cart/removeFromCart', productId)
        ElMessage.success('已删除')
      })
    }

    const handleCheckout = () => {
      if (cartItems.value.length === 0) {
        ElMessage.warning('购物车是空的')
        return
      }
      checkoutVisible.value = true
    }

    const handleSubmitOrder = async () => {
      try {
        await orderFormRef.value.validate()
        submitting.value = true

        const orderData = {
          items: cartItems.value.map(item => ({
            product_id: item.id,
            quantity: item.quantity
          })),
          address: {
            name: orderForm.name,
            phone: orderForm.phone,
            province: orderForm.province,
            city: orderForm.city,
            district: orderForm.district,
            detail: orderForm.detail
          }
        }

        await createOrder(orderData)
        ElMessage.success('订单创建成功')
        store.dispatch('cart/clearCart')
        checkoutVisible.value = false
        router.push('/shop')
      } catch (error) {
        if (error !== false) {
          ElMessage.error('订单创建失败')
        }
      } finally {
        submitting.value = false
      }
    }

    return {
      cartItems,
      cartTotal,
      cartCount,
      checkoutVisible,
      submitting,
      orderForm,
      orderRules,
      orderFormRef,
      handleQuantityChange,
      handleRemove,
      handleCheckout,
      handleSubmitOrder
    }
  }
}
</script>

<style scoped>
.cart {
  max-width: 1200px;
  margin: 0 auto;
}

.product-info {
  display: flex;
  align-items: center;
  gap: 10px;
}

.product-thumb {
  width: 60px;
  height: 60px;
  object-fit: cover;
  border-radius: 4px;
}

.cart-summary {
  margin-top: 20px;
  padding: 20px;
  background-color: #f5f7fa;
  border-radius: 4px;
  text-align: right;
}

.summary-item {
  margin-bottom: 10px;
  font-size: 16px;
}

.summary-value {
  font-weight: bold;
  margin-left: 10px;
}

.summary-price {
  font-size: 24px;
  color: #f56c6c;
  font-weight: bold;
  margin-left: 10px;
}

.order-summary {
  text-align: right;
  font-size: 16px;
}

.order-summary .price {
  font-size: 24px;
  color: #f56c6c;
  font-weight: bold;
}
</style>
