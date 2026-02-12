<template>
  <div class="master-list">
    <el-row :gutter="20" v-loading="loading">
      <el-col :span="8" v-for="master in masters" :key="master.id">
        <el-card class="master-card">
          <div class="master-avatar">
            <img :src="master.avatar || '/placeholder.png'" :alt="master.name" />
          </div>
          <div class="master-info">
            <h2>{{ master.name }}</h2>
            <p class="master-specialty">专长：{{ master.specialty }}</p>
            <p class="master-bio">{{ master.bio }}</p>
            <el-button type="primary" @click="handleContact(master)">联系咨询</el-button>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <el-dialog v-model="contactVisible" :title="`联系 ${currentMaster?.name}`" width="500px">
      <div v-if="currentMaster">
        <p><strong>专长：</strong>{{ currentMaster.specialty }}</p>
        <p><strong>联系方式：</strong>{{ currentMaster.contact }}</p>
        <el-divider />
        <p>{{ currentMaster.bio }}</p>
      </div>
    </el-dialog>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getFengShuiMasterList } from '@/api/fengShuiMaster'

export default {
  name: 'MasterList',
  setup() {
    const loading = ref(false)
    const masters = ref([])
    const contactVisible = ref(false)
    const currentMaster = ref(null)

    const loadMasters = async () => {
      loading.value = true
      try {
        const response = await getFengShuiMasterList({ status: 1 })
        masters.value = response.data
      } catch (error) {
        ElMessage.error('加载风水师列表失败')
      } finally {
        loading.value = false
      }
    }

    const handleContact = (master) => {
      currentMaster.value = master
      contactVisible.value = true
    }

    onMounted(() => {
      loadMasters()
    })

    return {
      loading,
      masters,
      contactVisible,
      currentMaster,
      handleContact
    }
  }
}
</script>

<style scoped>
.master-list {
  max-width: 1200px;
  margin: 0 auto;
}

.master-card {
  margin-bottom: 20px;
  text-align: center;
}

.master-avatar {
  width: 150px;
  height: 150px;
  margin: 0 auto 20px;
  border-radius: 50%;
  overflow: hidden;
}

.master-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.master-info h2 {
  margin: 0 0 10px 0;
}

.master-specialty {
  color: #409eff;
  margin: 0 0 10px 0;
}

.master-bio {
  color: #606266;
  line-height: 1.6;
  margin: 0 0 20px 0;
}
</style>
