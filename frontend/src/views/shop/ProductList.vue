<template>
  <div class="product-list">
    <el-row :gutter="20">
      <el-col :span="6">
        <el-card class="category-card">
          <template #header>
            <span>商品分类</span>
          </template>
          <el-menu :default-active="selectedCategory" @select="handleCategorySelect">
            <el-menu-item index="">全部商品</el-menu-item>
            <el-menu-item v-for="cat in categories" :key="cat.id" :index="String(cat.id)">
              {{ cat.name }}
            </el-menu-item>
          </el-menu>
        </el-card>
      </el-col>

      <el-col :span="18">
        <div class="search-bar">
          <el-input
            v-model="searchKeyword"
            placeholder="搜索商品"
            clearable
            @clear="loadProducts"
            @keyup.enter="loadProducts"
          >
            <template #append>
              <el-button icon="Search" @click="loadProducts" />
            </template>
          </el-input>
        </div>

        <el-row :gutter="20" v-loading="loading">
          <el-col :span="8" v-for="product in products" :key="product.id">
            <el-card class="product-card" @click="handleViewDetail(product)">
              <div class="product-image">
                <img :src="product.images?.[0] || '/placeholder.png'" :alt="product.name" />
              </div>
              <div class="product-info">
                <h3 class="product-name">{{ product.name }}</h3>
                <p class="product-desc">{{ product.description }}</p>
                <div class="product-footer">
                  <span class="product-price">¥{{ product.price }}</span>
                  <el-button type="primary" size="small" @click.stop="handleAddToCart(product)">
                    加入购物车
                  </el-button>
                </div>
              </div>
            </el-card>
          </el-col>
        </el-row>

        <el-pagination
          v-model:current-page="pagination.page"
          v-model:page-size="pagination.pageSize"
          :total="pagination.total"
          layout="prev, pager, next"
          @current-change="loadProducts"
          class="pagination"
        />
      </el-col>
    </el-row>

    <!-- 商品详情对话框 -->
    <el-dialog v-model="detailVisible" :title="currentProduct?.name" width="800px">
      <div v-if="currentProduct" class="product-detail">
        <el-row :gutter="20">
          <el-col :span="12">
            <img :src="currentProduct.images?.[0] || '/placeholder.png'" class="detail-image" />
          </el-col>
          <el-col :span="12">
            <div class="detail-info">
              <h2>{{ currentProduct.name }}</h2>
              <p class="detail-price">¥{{ currentProduct.price }}</p>
              <p class="detail-stock">库存：{{ currentProduct.stock }}</p>
              <el-divider />
              <p class="detail-desc">{{ currentProduct.description }}</p>
              <el-divider />
              <el-input-number v-model="quantity" :min="1" :max="currentProduct.stock" />
              <el-button type="primary" size="large" @click="handleAddToCart(currentProduct)" style="margin-left: 20px">
                加入购物车
              </el-button>
            </div>
          </el-col>
        </el-row>
      </div>
    </el-dialog>
  </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue'
import { useStore } from 'vuex'
import { ElMessage } from 'element-plus'
import { getProductList } from '@/api/product'
import { getCategoryList } from '@/api/category'

export default {
  name: 'ProductList',
  setup() {
    const store = useStore()
    const loading = ref(false)
    const products = ref([])
    const categories = ref([])
    const selectedCategory = ref('')
    const searchKeyword = ref('')
    const detailVisible = ref(false)
    const currentProduct = ref(null)
    const quantity = ref(1)

    const pagination = reactive({
      page: 1,
      pageSize: 9,
      total: 0
    })

    const loadCategories = async () => {
      try {
        const response = await getCategoryList()
        categories.value = response.data.filter(cat => cat.status === 1)
      } catch (error) {
        ElMessage.error('加载分类失败')
      }
    }

    const loadProducts = async () => {
      loading.value = true
      try {
        const response = await getProductList({
          page: pagination.page,
          pageSize: pagination.pageSize,
          category_id: selectedCategory.value || null,
          search: searchKeyword.value,
          status: 'on_sale'
        })
        products.value = response.data.list
        pagination.total = response.data.total
      } catch (error) {
        ElMessage.error('加载商品失败')
      } finally {
        loading.value = false
      }
    }

    const handleCategorySelect = (index) => {
      selectedCategory.value = index
      pagination.page = 1
      loadProducts()
    }

    const handleViewDetail = (product) => {
      currentProduct.value = product
      quantity.value = 1
      detailVisible.value = true
    }

    const handleAddToCart = (product) => {
      store.dispatch('cart/addToCart', {
        ...product,
        quantity: quantity.value
      })
      ElMessage.success('已加入购物车')
      detailVisible.value = false
    }

    onMounted(() => {
      loadCategories()
      loadProducts()
    })

    return {
      loading,
      products,
      categories,
      selectedCategory,
      searchKeyword,
      pagination,
      detailVisible,
      currentProduct,
      quantity,
      loadProducts,
      handleCategorySelect,
      handleViewDetail,
      handleAddToCart
    }
  }
}
</script>

<style scoped>
.product-list {
  max-width: 1200px;
  margin: 0 auto;
}

.category-card {
  position: sticky;
  top: 20px;
}

.search-bar {
  margin-bottom: 20px;
}

.product-card {
  margin-bottom: 20px;
  cursor: pointer;
  transition: transform 0.3s;
}

.product-card:hover {
  transform: translateY(-5px);
}

.product-image {
  width: 100%;
  height: 200px;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #f5f7fa;
}

.product-image img {
  max-width: 100%;
  max-height: 100%;
  object-fit: cover;
}

.product-info {
  padding: 15px 0;
}

.product-name {
  font-size: 16px;
  margin: 0 0 10px 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.product-desc {
  font-size: 14px;
  color: #909399;
  margin: 0 0 15px 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.product-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.product-price {
  font-size: 20px;
  color: #f56c6c;
  font-weight: bold;
}

.pagination {
  margin-top: 20px;
  justify-content: center;
}

.detail-image {
  width: 100%;
  border-radius: 8px;
}

.detail-info {
  padding: 20px;
}

.detail-price {
  font-size: 28px;
  color: #f56c6c;
  font-weight: bold;
  margin: 10px 0;
}

.detail-stock {
  color: #909399;
  margin: 10px 0;
}

.detail-desc {
  line-height: 1.6;
  color: #606266;
}
</style>
