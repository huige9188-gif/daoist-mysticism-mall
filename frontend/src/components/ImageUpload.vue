<template>
  <div class="image-upload">
    <el-upload
      :action="uploadUrl"
      :headers="uploadHeaders"
      :before-upload="beforeUpload"
      :on-success="handleSuccess"
      :on-error="handleError"
      :on-progress="handleProgress"
      :file-list="fileList"
      :limit="limit"
      :multiple="multiple"
      list-type="picture-card"
      :disabled="disabled"
    >
      <el-icon><Plus /></el-icon>
      <template #file="{ file }">
        <div class="upload-file">
          <img :src="file.url" class="upload-image" />
          <div class="upload-actions">
            <el-icon @click="handlePreview(file)"><ZoomIn /></el-icon>
            <el-icon @click="handleRemove(file)"><Delete /></el-icon>
          </div>
        </div>
      </template>
    </el-upload>

    <el-dialog v-model="previewVisible" title="图片预览">
      <img :src="previewUrl" style="width: 100%" />
    </el-dialog>
  </div>
</template>

<script>
import { ref, computed, watch } from 'vue'
import { ElMessage } from 'element-plus'
import { Plus, ZoomIn, Delete } from '@element-plus/icons-vue'
import { getToken } from '@/utils/auth'

export default {
  name: 'ImageUpload',
  components: { Plus, ZoomIn, Delete },
  props: {
    modelValue: {
      type: [String, Array],
      default: ''
    },
    limit: {
      type: Number,
      default: 1
    },
    multiple: {
      type: Boolean,
      default: false
    },
    disabled: {
      type: Boolean,
      default: false
    },
    maxSize: {
      type: Number,
      default: 5 // MB
    }
  },
  emits: ['update:modelValue'],
  setup(props, { emit }) {
    const uploadUrl = ref(`${import.meta.env.VITE_API_BASE_URL}/api/upload/image`)
    const uploadHeaders = computed(() => ({
      Authorization: `Bearer ${getToken()}`
    }))
    const fileList = ref([])
    const previewVisible = ref(false)
    const previewUrl = ref('')

    // 初始化文件列表
    watch(() => props.modelValue, (val) => {
      if (val) {
        if (Array.isArray(val)) {
          fileList.value = val.map((url, index) => ({
            uid: index,
            url
          }))
        } else if (val) {
          fileList.value = [{
            uid: 0,
            url: val
          }]
        }
      } else {
        fileList.value = []
      }
    }, { immediate: true })

    const beforeUpload = (file) => {
      // 验证文件类型
      const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif']
      if (!allowedTypes.includes(file.type)) {
        ElMessage.error('只支持 JPG、JPEG、PNG、GIF 格式的图片')
        return false
      }

      // 验证文件大小
      const maxSizeBytes = props.maxSize * 1024 * 1024
      if (file.size > maxSizeBytes) {
        ElMessage.error(`图片大小不能超过 ${props.maxSize}MB`)
        return false
      }

      return true
    }

    const handleSuccess = (response, file) => {
      if (response.code === 200) {
        const url = response.data.url
        
        if (props.multiple) {
          const urls = fileList.value.map(f => f.url)
          urls.push(url)
          emit('update:modelValue', urls)
        } else {
          emit('update:modelValue', url)
        }
        
        ElMessage.success('上传成功')
      } else {
        ElMessage.error(response.message || '上传失败')
      }
    }

    const handleError = () => {
      ElMessage.error('上传失败')
    }

    const handleProgress = (event, file) => {
      // 可以在这里显示上传进度
    }

    const handlePreview = (file) => {
      previewUrl.value = file.url
      previewVisible.value = true
    }

    const handleRemove = (file) => {
      if (props.multiple) {
        const urls = fileList.value.filter(f => f.uid !== file.uid).map(f => f.url)
        emit('update:modelValue', urls)
      } else {
        emit('update:modelValue', '')
      }
    }

    return {
      uploadUrl,
      uploadHeaders,
      fileList,
      previewVisible,
      previewUrl,
      beforeUpload,
      handleSuccess,
      handleError,
      handleProgress,
      handlePreview,
      handleRemove
    }
  }
}
</script>

<style scoped>
.image-upload :deep(.el-upload-list__item) {
  transition: all 0.3s;
}

.upload-file {
  position: relative;
  width: 100%;
  height: 100%;
}

.upload-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.upload-actions {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  background-color: rgba(0, 0, 0, 0.5);
  opacity: 0;
  transition: opacity 0.3s;
}

.upload-file:hover .upload-actions {
  opacity: 1;
}

.upload-actions .el-icon {
  font-size: 20px;
  color: white;
  cursor: pointer;
}
</style>
