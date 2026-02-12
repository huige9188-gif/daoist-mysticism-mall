<template>
  <div class="articles">
    <el-card>
      <template #header>
        <div class="card-header">
          <span>资讯管理</span>
          <el-button type="primary" @click="handleAdd">新增文章</el-button>
        </div>
      </template>

      <el-table :data="articles" v-loading="loading">
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="title" label="标题" />
        <el-table-column prop="author" label="作者" width="120" />
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="row.status === 'published' ? 'success' : 'info'">
              {{ row.status === 'published' ? '已发布' : '草稿' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="180" />
        <el-table-column label="操作" fixed="right" width="240">
          <template #default="{ row }">
            <el-button type="primary" size="small" @click="handleEdit(row)">编辑</el-button>
            <el-button
              :type="row.status === 'published' ? 'warning' : 'success'"
              size="small"
              @click="handleToggleStatus(row)"
            >
              {{ row.status === 'published' ? '撤回' : '发布' }}
            </el-button>
            <el-button type="danger" size="small" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <el-dialog v-model="dialogVisible" :title="dialogTitle" width="700px">
      <el-form :model="form" :rules="rules" ref="formRef" label-width="100px">
        <el-form-item label="标题" prop="title">
          <el-input v-model="form.title" />
        </el-form-item>
        <el-form-item label="作者" prop="author">
          <el-input v-model="form.author" />
        </el-form-item>
        <el-form-item label="内容" prop="content">
          <el-input v-model="form.content" type="textarea" :rows="8" />
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-radio-group v-model="form.status">
            <el-radio label="draft">草稿</el-radio>
            <el-radio label="published">发布</el-radio>
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
import { getArticleList, createArticle, updateArticle, deleteArticle, updateArticleStatus } from '@/api/article'

export default {
  name: 'Articles',
  setup() {
    const loading = ref(false)
    const articles = ref([])
    const dialogVisible = ref(false)
    const isEdit = ref(false)
    const formRef = ref(null)

    const form = reactive({
      id: null,
      title: '',
      author: '',
      content: '',
      status: 'draft'
    })

    const rules = {
      title: [{ required: true, message: '请输入标题', trigger: 'blur' }],
      content: [{ required: true, message: '请输入内容', trigger: 'blur' }]
    }

    const dialogTitle = ref('新增文章')

    const loadArticles = async () => {
      loading.value = true
      try {
        const response = await getArticleList()
        articles.value = response.data
      } catch (error) {
        ElMessage.error('加载文章列表失败')
      } finally {
        loading.value = false
      }
    }

    const handleAdd = () => {
      isEdit.value = false
      dialogTitle.value = '新增文章'
      Object.assign(form, { id: null, title: '', author: '', content: '', status: 'draft' })
      formRef.value?.clearValidate()
      dialogVisible.value = true
    }

    const handleEdit = (row) => {
      isEdit.value = true
      dialogTitle.value = '编辑文章'
      Object.assign(form, row)
      dialogVisible.value = true
    }

    const handleSubmit = async () => {
      try {
        await formRef.value.validate()
        if (isEdit.value) {
          await updateArticle(form.id, form)
          ElMessage.success('更新成功')
        } else {
          await createArticle(form)
          ElMessage.success('创建成功')
        }
        dialogVisible.value = false
        loadArticles()
      } catch (error) {
        if (error !== false) ElMessage.error(isEdit.value ? '更新失败' : '创建失败')
      }
    }

    const handleToggleStatus = async (row) => {
      const newStatus = row.status === 'published' ? 'draft' : 'published'
      try {
        await updateArticleStatus(row.id, newStatus)
        ElMessage.success('状态更新成功')
        loadArticles()
      } catch (error) {
        ElMessage.error('状态更新失败')
      }
    }

    const handleDelete = (row) => {
      ElMessageBox.confirm('确定要删除该文章吗？', '提示', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      }).then(async () => {
        try {
          await deleteArticle(row.id)
          ElMessage.success('删除成功')
          loadArticles()
        } catch (error) {
          ElMessage.error('删除失败')
        }
      })
    }

    onMounted(() => {
      loadArticles()
    })

    return {
      loading,
      articles,
      dialogVisible,
      dialogTitle,
      isEdit,
      form,
      rules,
      formRef,
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
</style>
