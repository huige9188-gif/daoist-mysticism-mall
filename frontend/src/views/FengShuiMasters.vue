<template>
  <div class="feng-shui-masters">
    <el-card>
      <template #header>
        <div class="card-header">
          <span>风水师管理</span>
          <el-button type="primary" @click="handleAdd">新增风水师</el-button>
        </div>
      </template>

      <el-table :data="masters" v-loading="loading">
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="name" label="姓名" width="120" />
        <el-table-column prop="specialty" label="专长" />
        <el-table-column prop="contact" label="联系方式" width="150" />
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
        <el-form-item label="姓名" prop="name">
          <el-input v-model="form.name" />
        </el-form-item>
        <el-form-item label="简介" prop="bio">
          <el-input v-model="form.bio" type="textarea" :rows="3" />
        </el-form-item>
        <el-form-item label="专长" prop="specialty">
          <el-input v-model="form.specialty" />
        </el-form-item>
        <el-form-item label="联系方式" prop="contact">
          <el-input v-model="form.contact" />
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
import { getFengShuiMasterList, createFengShuiMaster, updateFengShuiMaster, deleteFengShuiMaster, updateFengShuiMasterStatus } from '@/api/fengShuiMaster'

export default {
  name: 'FengShuiMasters',
  setup() {
    const loading = ref(false)
    const masters = ref([])
    const dialogVisible = ref(false)
    const isEdit = ref(false)
    const formRef = ref(null)

    const form = reactive({
      id: null,
      name: '',
      bio: '',
      specialty: '',
      contact: '',
      status: 1
    })

    const rules = {
      name: [{ required: true, message: '请输入姓名', trigger: 'blur' }]
    }

    const dialogTitle = ref('新增风水师')

    const loadMasters = async () => {
      loading.value = true
      try {
        const response = await getFengShuiMasterList()
        masters.value = response.data
      } catch (error) {
        ElMessage.error('加载风水师列表失败')
      } finally {
        loading.value = false
      }
    }

    const handleAdd = () => {
      isEdit.value = false
      dialogTitle.value = '新增风水师'
      Object.assign(form, { id: null, name: '', bio: '', specialty: '', contact: '', status: 1 })
      formRef.value?.clearValidate()
      dialogVisible.value = true
    }

    const handleEdit = (row) => {
      isEdit.value = true
      dialogTitle.value = '编辑风水师'
      Object.assign(form, row)
      dialogVisible.value = true
    }

    const handleSubmit = async () => {
      try {
        await formRef.value.validate()
        if (isEdit.value) {
          await updateFengShuiMaster(form.id, form)
          ElMessage.success('更新成功')
        } else {
          await createFengShuiMaster(form)
          ElMessage.success('创建成功')
        }
        dialogVisible.value = false
        loadMasters()
      } catch (error) {
        if (error !== false) ElMessage.error(isEdit.value ? '更新失败' : '创建失败')
      }
    }

    const handleDelete = (row) => {
      ElMessageBox.confirm('确定要删除该风水师吗？', '提示', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      }).then(async () => {
        try {
          await deleteFengShuiMaster(row.id)
          ElMessage.success('删除成功')
          loadMasters()
        } catch (error) {
          ElMessage.error('删除失败')
        }
      })
    }

    const handleStatusChange = async (row) => {
      try {
        await updateFengShuiMasterStatus(row.id, row.status)
        ElMessage.success('状态更新成功')
      } catch (error) {
        ElMessage.error('状态更新失败')
        row.status = row.status === 1 ? 0 : 1
      }
    }

    onMounted(() => {
      loadMasters()
    })

    return {
      loading,
      masters,
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
