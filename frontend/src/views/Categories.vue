<template>
  <div class="categories">
    <el-card>
      <template #header>
        <div class="card-header">
          <span>商品分类管理</span>
          <el-button type="primary" @click="handleAdd">新增分类</el-button>
        </div>
      </template>

      <!-- 分类列表 -->
      <el-table :data="categories" v-loading="loading">
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="name" label="分类名称" />
        <el-table-column prop="sort_order" label="排序" width="100" />
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-switch
              v-model="row.status"
              :active-value="1"
              :inactive-value="0"
              @change="handleStatusChange(row)"
            />
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="180" />
        <el-table-column label="操作" fixed="right" width="180">
          <template #default="{ row }">
            <el-button type="primary" size="small" @click="handleEdit(row)">编辑</el-button>
            <el-button type="danger" size="small" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <!-- 新增/编辑对话框 -->
    <el-dialog
      v-model="dialogVisible"
      :title="dialogTitle"
      width="500px"
    >
      <el-form :model="form" :rules="rules" ref="formRef" label-width="100px">
        <el-form-item label="分类名称" prop="name">
          <el-input v-model="form.name" />
        </el-form-item>
        <el-form-item label="排序值" prop="sort_order">
          <el-input-number v-model="form.sort_order" :min="0" style="width: 100%" />
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-radio-group v-model="form.status">
            <el-radio :label="1">启用</el-radio>
            <el-radio :label="0">禁用</el-radio>
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
import { getCategoryList, createCategory, updateCategory, deleteCategory, updateCategoryStatus } from '@/api/category'

export default {
  name: 'Categories',
  setup() {
    const loading = ref(false)
    const categories = ref([])
    const dialogVisible = ref(false)
    const isEdit = ref(false)
    const formRef = ref(null)

    const form = reactive({
      id: null,
      name: '',
      sort_order: 0,
      status: 1
    })

    const rules = {
      name: [{ required: true, message: '请输入分类名称', trigger: 'blur' }],
      sort_order: [{ required: true, message: '请输入排序值', trigger: 'blur' }]
    }

    const dialogTitle = ref('新增分类')

    const loadCategories = async () => {
      loading.value = true
      try {
        const response = await getCategoryList()
        categories.value = response.data
      } catch (error) {
        ElMessage.error('加载分类列表失败')
      } finally {
        loading.value = false
      }
    }

    const handleAdd = () => {
      isEdit.value = false
      dialogTitle.value = '新增分类'
      resetForm()
      dialogVisible.value = true
    }

    const handleEdit = (row) => {
      isEdit.value = true
      dialogTitle.value = '编辑分类'
      Object.assign(form, row)
      dialogVisible.value = true
    }

    const handleSubmit = async () => {
      try {
        await formRef.value.validate()
        if (isEdit.value) {
          await updateCategory(form.id, form)
          ElMessage.success('更新成功')
        } else {
          await createCategory(form)
          ElMessage.success('创建成功')
        }
        dialogVisible.value = false
        loadCategories()
      } catch (error) {
        if (error !== false) {
          ElMessage.error(isEdit.value ? '更新失败' : '创建失败')
        }
      }
    }

    const handleDelete = (row) => {
      ElMessageBox.confirm('确定要删除该分类吗？', '提示', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      }).then(async () => {
        try {
          await deleteCategory(row.id)
          ElMessage.success('删除成功')
          loadCategories()
        } catch (error) {
          ElMessage.error('删除失败')
        }
      })
    }

    const handleStatusChange = async (row) => {
      try {
        await updateCategoryStatus(row.id, row.status)
        ElMessage.success('状态更新成功')
      } catch (error) {
        ElMessage.error('状态更新失败')
        row.status = row.status === 1 ? 0 : 1
      }
    }

    const resetForm = () => {
      Object.assign(form, {
        id: null,
        name: '',
        sort_order: 0,
        status: 1
      })
      formRef.value?.clearValidate()
    }

    onMounted(() => {
      loadCategories()
    })

    return {
      loading,
      categories,
      dialogVisible,
      dialogTitle,
      isEdit,
      form,
      rules,
      formRef,
      loadCategories,
      handleAdd,
      handleEdit,
      handleSubmit,
      handleDelete,
      handleStatusChange
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
</style>
