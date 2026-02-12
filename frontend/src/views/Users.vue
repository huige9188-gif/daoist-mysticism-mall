<template>
  <div class="users">
    <el-card>
      <template #header>
        <div class="card-header">
          <span>用户管理</span>
          <el-button type="primary" @click="handleAdd">新增用户</el-button>
        </div>
      </template>

      <!-- 搜索栏 -->
      <el-form :inline="true" :model="searchForm" class="search-form">
        <el-form-item label="搜索">
          <el-input v-model="searchForm.search" placeholder="用户名/邮箱/手机号" clearable @clear="loadUsers" />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadUsers">查询</el-button>
        </el-form-item>
      </el-form>

      <!-- 用户列表 -->
      <el-table :data="users" v-loading="loading">
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="username" label="用户名" width="150" />
        <el-table-column prop="email" label="邮箱" width="200" />
        <el-table-column prop="phone" label="手机号" width="150" />
        <el-table-column prop="role" label="角色" width="100">
          <template #default="{ row }">
            <el-tag :type="row.role === 'admin' ? 'danger' : 'primary'">
              {{ row.role === 'admin' ? '管理员' : '用户' }}
            </el-tag>
          </template>
        </el-table-column>
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
        <el-table-column prop="created_at" label="注册时间" width="180" />
        <el-table-column label="操作" fixed="right" width="180">
          <template #default="{ row }">
            <el-button type="primary" size="small" @click="handleEdit(row)">编辑</el-button>
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
        @size-change="loadUsers"
        @current-change="loadUsers"
        class="pagination"
      />
    </el-card>

    <!-- 新增/编辑对话框 -->
    <el-dialog
      v-model="dialogVisible"
      :title="dialogTitle"
      width="500px"
    >
      <el-form :model="form" :rules="rules" ref="formRef" label-width="80px">
        <el-form-item label="用户名" prop="username">
          <el-input v-model="form.username" :disabled="isEdit" />
        </el-form-item>
        <el-form-item label="邮箱" prop="email">
          <el-input v-model="form.email" />
        </el-form-item>
        <el-form-item label="手机号" prop="phone">
          <el-input v-model="form.phone" />
        </el-form-item>
        <el-form-item label="密码" prop="password" v-if="!isEdit">
          <el-input v-model="form.password" type="password" />
        </el-form-item>
        <el-form-item label="角色" prop="role">
          <el-select v-model="form.role" style="width: 100%">
            <el-option label="用户" value="user" />
            <el-option label="管理员" value="admin" />
          </el-select>
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
import { getUserList, createUser, updateUser, deleteUser, updateUserStatus } from '@/api/user'

export default {
  name: 'Users',
  setup() {
    const loading = ref(false)
    const users = ref([])
    const dialogVisible = ref(false)
    const isEdit = ref(false)
    const formRef = ref(null)

    const searchForm = reactive({
      search: ''
    })

    const pagination = reactive({
      page: 1,
      pageSize: 10,
      total: 0
    })

    const form = reactive({
      id: null,
      username: '',
      email: '',
      phone: '',
      password: '',
      role: 'user'
    })

    const rules = {
      username: [{ required: true, message: '请输入用户名', trigger: 'blur' }],
      email: [
        { required: true, message: '请输入邮箱', trigger: 'blur' },
        { type: 'email', message: '请输入正确的邮箱格式', trigger: 'blur' }
      ],
      password: [
        { required: true, message: '请输入密码', trigger: 'blur' },
        { min: 6, message: '密码长度至少6位', trigger: 'blur' }
      ],
      role: [{ required: true, message: '请选择角色', trigger: 'change' }]
    }

    const dialogTitle = ref('新增用户')

    const loadUsers = async () => {
      loading.value = true
      try {
        const response = await getUserList({
          page: pagination.page,
          pageSize: pagination.pageSize,
          search: searchForm.search
        })
        users.value = response.data.list
        pagination.total = response.data.total
      } catch (error) {
        ElMessage.error('加载用户列表失败')
      } finally {
        loading.value = false
      }
    }

    const handleAdd = () => {
      isEdit.value = false
      dialogTitle.value = '新增用户'
      resetForm()
      dialogVisible.value = true
    }

    const handleEdit = (row) => {
      isEdit.value = true
      dialogTitle.value = '编辑用户'
      Object.assign(form, row)
      dialogVisible.value = true
    }

    const handleSubmit = async () => {
      try {
        await formRef.value.validate()
        if (isEdit.value) {
          await updateUser(form.id, form)
          ElMessage.success('更新成功')
        } else {
          await createUser(form)
          ElMessage.success('创建成功')
        }
        dialogVisible.value = false
        loadUsers()
      } catch (error) {
        if (error !== false) {
          ElMessage.error(isEdit.value ? '更新失败' : '创建失败')
        }
      }
    }

    const handleDelete = (row) => {
      ElMessageBox.confirm('确定要删除该用户吗？', '提示', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      }).then(async () => {
        try {
          await deleteUser(row.id)
          ElMessage.success('删除成功')
          loadUsers()
        } catch (error) {
          ElMessage.error('删除失败')
        }
      })
    }

    const handleStatusChange = async (row) => {
      try {
        await updateUserStatus(row.id, row.status)
        ElMessage.success('状态更新成功')
      } catch (error) {
        ElMessage.error('状态更新失败')
        row.status = row.status === 1 ? 0 : 1
      }
    }

    const resetForm = () => {
      Object.assign(form, {
        id: null,
        username: '',
        email: '',
        phone: '',
        password: '',
        role: 'user'
      })
      formRef.value?.clearValidate()
    }

    onMounted(() => {
      loadUsers()
    })

    return {
      loading,
      users,
      searchForm,
      pagination,
      dialogVisible,
      dialogTitle,
      isEdit,
      form,
      rules,
      formRef,
      loadUsers,
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

.search-form {
  margin-bottom: 20px;
}

.pagination {
  margin-top: 20px;
  justify-content: flex-end;
}
</style>
