<template>
  <div class="videos">
    <el-card>
      <template #header>
        <div class="card-header">
          <span>视频管理</span>
          <el-button type="primary" @click="handleAdd">新增视频</el-button>
        </div>
      </template>

      <el-table :data="videos" v-loading="loading">
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="title" label="标题" />
        <el-table-column prop="video_url" label="视频URL" width="200" show-overflow-tooltip />
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-switch v-model="row.status" :active-value="1" :inactive-value="0" @change="handleStatusChange(row)" />
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

    <el-dialog v-model="dialogVisible" :title="dialogTitle" width="600px">
      <el-form :model="form" :rules="rules" ref="formRef" label-width="100px">
        <el-form-item label="标题" prop="title">
          <el-input v-model="form.title" />
        </el-form-item>
        <el-form-item label="视频URL" prop="video_url">
          <el-input v-model="form.video_url" />
        </el-form-item>
        <el-form-item label="描述" prop="description">
          <el-input v-model="form.description" type="textarea" :rows="3" />
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
import { getVideoList, createVideo, updateVideo, deleteVideo, updateVideoStatus } from '@/api/video'

export default {
  name: 'Videos',
  setup() {
    const loading = ref(false)
    const videos = ref([])
    const dialogVisible = ref(false)
    const isEdit = ref(false)
    const formRef = ref(null)

    const form = reactive({
      id: null,
      title: '',
      video_url: '',
      description: '',
      status: 1
    })

    const rules = {
      title: [{ required: true, message: '请输入标题', trigger: 'blur' }],
      video_url: [{ required: true, message: '请输入视频URL', trigger: 'blur' }]
    }

    const dialogTitle = ref('新增视频')

    const loadVideos = async () => {
      loading.value = true
      try {
        const response = await getVideoList()
        videos.value = response.data
      } catch (error) {
        ElMessage.error('加载视频列表失败')
      } finally {
        loading.value = false
      }
    }

    const handleAdd = () => {
      isEdit.value = false
      dialogTitle.value = '新增视频'
      Object.assign(form, { id: null, title: '', video_url: '', description: '', status: 1 })
      formRef.value?.clearValidate()
      dialogVisible.value = true
    }

    const handleEdit = (row) => {
      isEdit.value = true
      dialogTitle.value = '编辑视频'
      Object.assign(form, row)
      dialogVisible.value = true
    }

    const handleSubmit = async () => {
      try {
        await formRef.value.validate()
        if (isEdit.value) {
          await updateVideo(form.id, form)
          ElMessage.success('更新成功')
        } else {
          await createVideo(form)
          ElMessage.success('创建成功')
        }
        dialogVisible.value = false
        loadVideos()
      } catch (error) {
        if (error !== false) ElMessage.error(isEdit.value ? '更新失败' : '创建失败')
      }
    }

    const handleDelete = (row) => {
      ElMessageBox.confirm('确定要删除该视频吗？', '提示', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      }).then(async () => {
        try {
          await deleteVideo(row.id)
          ElMessage.success('删除成功')
          loadVideos()
        } catch (error) {
          ElMessage.error('删除失败')
        }
      })
    }

    const handleStatusChange = async (row) => {
      try {
        await updateVideoStatus(row.id, row.status)
        ElMessage.success('状态更新成功')
      } catch (error) {
        ElMessage.error('状态更新失败')
        row.status = row.status === 1 ? 0 : 1
      }
    }

    onMounted(() => {
      loadVideos()
    })

    return {
      loading,
      videos,
      dialogVisible,
      dialogTitle,
      isEdit,
      form,
      rules,
      formRef,
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
