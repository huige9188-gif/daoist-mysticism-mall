<template>
  <div class="products">
    <el-card>
      <template #header>
        <div class="card-header">
          <span>商品管理</span>
          <el-button type="primary" @click="handleAdd">新增商品</el-button>
        </div>
      </template>

      <!-- 搜索栏 -->
      <el-form :inline="true" :model="searchForm" class="search-form">
        <el-form-item label="搜索">
          <el-input v-model="searchForm.search" placeholder="商品名称" clearable @clear="loadProducts" />
        </el-form-item>
        <el-form-item label="分类">
          <el-select v-model="searchForm.category_id" placeholder="全部分类" clearable @change="loadProducts">
            <el-option v-for="cat in categories" :key="cat.id" :label="cat.name" :value="cat.id" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadProducts">查询</el-button>
        </el-form-item>
      </el-form>

      <!-- 商品列表 -->
      <el-table :data="products" v-loading="loading">
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="name" label="商品名称" width="200" />
        <el-table-column prop="category_name" label="分类" width="120" />
        <el-table-column prop="price" label="价格" width="100">
          <template #default="{ row }">¥{{ row.price }}</template>
        </el-table-column>
        <el-table-column prop="stock" label="库存" width="100" />
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="row.status === 'on_sale' ? 'success' : 'info'">
              {{ row.status === 'on_sale' ? '上架' : '下架' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="180" />
        <el-table-column label="操作" fixed="right" width="240">
          <template #default="{ row }">
            <el-button type="primary" size="small" @click="handleEdit(row)">编辑</el-button>
            <el-button
              :type="row.status === 'on_sale' ? 'warning' : 'success'"
              size="small"
              @click="handleToggleStatus(row)"
            >
              {{ row.status === 'on_sale' ? '下架' : '上架' }}
            </el-button>
            <el-button type="danger" size="small" @click="handleDelete(row)">删除</el-button>
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
        @size-change="loadProducts"
        @current-change="loadProducts"
        class="pagination"
      />
    </el-card>

    <!-- 新增/编辑对话框 -->
    <el-dialog v-model="dialogVisible" :title="dialogTitle" width="600px">
      <el-form :model="form" :rules="rules" ref="formRef" label-width="100px">
        <el-form-item label="商品名称" prop="name">
          <el-input v-model="form.name" />
        </el-form-item>
        <el-form-item label="分类" prop="category_id">
          <el-select v-model="form.category_id" style="width: 100%">
            <el-option v-for="cat in categories" :key="cat.id" :label="cat.name" :value="cat.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="价格" prop="price">
          <el-input-number v-model="form.price" :min="0.01" :precision="2" style="width: 100%" />
        </el-form-item>
        <el-form-item label="库存" prop="stock">
          <el-input-number v-model="form.stock" :min="0" style="width: 100%" />
        </el-form-item>
        <el-form-item label="描述" prop="description">
          <el-input v-model="form.description" type="textarea" :rows="4" />
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-radio-group v-model="form.status">
            <el-radio label="on_sale">上架</el-radio>
            <el-radio label="off_sale">下架</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getProductList, createProduct, updateProduct, deleteProduct, updateProductStatus } from '@/api/product'
import { getCategoryList } from '@/api/category'

export default {
  name: 'Products',
  setup() {
    const loading = ref(false)
    const products = ref([])
    const categories = ref([])
    const dialogVisible = ref(false)
    const isEdit = ref(false)
    const formRef = ref(null)

    const searchForm = reactive({
      search: '',
      category_id: null
    })

    const pagination = reactive({
      page: 1,
      pageSize: 10,
      total: 0
    })

    const form = reactive({
      id: null,
      name: '',
      category_id: null,
      price: 0,
      stock: 0,
      description: '',
      status: 'off_sale'
    })

    const rules = {
      name: [{ required: true, message: '请输入商品名称', trigger: 'blur' }],
      category_id: [{ required: true, message: '请选择分类', trigger: 'change' }],
      price: [{ required: true, message: '请输入价格', trigger: 'blur' }],
      stock: [{ required: true, message: '请输入库存', trigger: 'blur' }]
    }

    const dialogTitle = ref('新增商品')

    const loadCategories = async () => {
      try {
        const response = await getCategoryList()
        categories.value = response.data
      } catch (error) {
        ElMessage.error('加载分类列表失败')
      }
    }

    const loadProducts = async () => {
      loading.value = true
      try {
        const response = await getProductList({
          page: pagination.page,
          pageSize: pagination.pageSize,
          search: searchForm.search,
          category_id: searchForm.category_id
        })
        products.value = response.data.list
        pagination.total = response.data.total
      } catch (error) {
        ElMessage.error('加载商品列表失败')
      } finally {
        loading.value = false
      }
    }

    const handleAdd = () => {
      isEdit.value = false
      dialogTitle.value = '新增商品'
      resetForm()
      dialogVisible.value = true
    }

    const handleEdit = (row) => {
      isEdit.value = true
      dialogTitle.value = '编辑商品'
      Object.assign(form, row)
      dialogVisible.value = true
    }

    const handleSubmit = async () => {
      try {
        await formRef.value.validate()
        if (isEdit.value) {
          await updateProduct(form.id, form)
          ElMessage.success('更新成功')
        } else {
          await createProduct(form)
          ElMessage.success('创建成功')
        }
        dialogVisible.value = false
        loadProducts()
      } catch (error) {
        if (error !== false) {
          ElMessage.error(isEdit.value ? '更新失败' : '创建失败')
        }
      }
    }

    const handleToggleStatus = async (row) => {
      const newStatus = row.status === 'on_sale' ? 'off_sale' : 'on_sale'
      try {
        await updateProductStatus(row.id, newStatus)
        ElMessage.success('状态更新成功')
        loadProducts()
      } catch (error) {
        ElMessage.error('状态更新失败')
      }
    }

    const handleDelete = (row) => {
      ElMessageBox.confirm('确定要删除该商品吗？', '提示', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      }).then(async () => {
        try {
          await deleteProduct(row.id)
          ElMessage.success('删除成功')
          loadProducts()
        } catch (error) {
          ElMessage.error('删除失败')
        }
      })
    }

    const resetForm = () => {
      Object.assign(form, {
        id: null,
        name: '',
        category_id: null,
        price: 0,
        stock: 0,
        description: '',
        status: 'off_sale'
      })
      formRef.value?.clearValidate()
    }

    onMounted(() => {
      loadCategories()
      loadProducts()
    })

    return {
      loading,
      products,
      categories,
      searchForm,
      pagination,
      dialogVisible,
      dialogTitle,
      isEdit,
      form,
      rules,
      formRef,
      loadProducts,
      handleAdd,
      handleEdit,
      handleSubmit,
      handleToggleStatus,
      handleDelete
    }
  }
}
</script>

<style scoped>
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.search-form {
  margin-bottom: 20px;
}

.pagination {
  margin-top: 20px;
  justify-content: flex-end;
}
</style>
