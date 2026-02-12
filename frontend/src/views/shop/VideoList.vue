<template>
  <div class="video-list">
    <el-row :gutter="20" v-loading="loading">
      <el-col :span="8" v-for="video in videos" :key="video.id">
        <el-card class="video-card">
          <div class="video-cover">
            <img :src="video.cover_image || '/placeholder.png'" :alt="video.title" />
            <div class="play-icon">▶</div>
          </div>
          <div class="video-info">
            <h3>{{ video.title }}</h3>
            <p>{{ video.description }}</p>
            <el-button type="primary" @click="handlePlay(video)">观看视频</el-button>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <el-dialog v-model="playerVisible" :title="currentVideo?.title" width="800px">
      <div v-if="currentVideo" class="video-player">
        <video :src="currentVideo.video_url" controls style="width: 100%"></video>
        <p class="video-desc">{{ currentVideo.description }}</p>
      </div>
    </el-dialog>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getVideoList } from '@/api/video'

export default {
  name: 'VideoList',
  setup() {
    const loading = ref(false)
    const videos = ref([])
    const playerVisible = ref(false)
    const currentVideo = ref(null)

    const loadVideos = async () => {
      loading.value = true
      try {
        const response = await getVideoList({ status: 1 })
        videos.value = response.data
      } catch (error) {
        ElMessage.error('加载视频失败')
      } finally {
        loading.value = false
      }
    }

    const handlePlay = (video) => {
      currentVideo.value = video
      playerVisible.value = true
    }

    onMounted(() => {
      loadVideos()
    })

    return {
      loading,
      videos,
      playerVisible,
      currentVideo,
      handlePlay
    }
  }
}
</script>

<style scoped>
.video-list {
  max-width: 1200px;
  margin: 0 auto;
}

.video-card {
  margin-bottom: 20px;
}

.video-cover {
  position: relative;
  width: 100%;
  height: 200px;
  overflow: hidden;
  cursor: pointer;
}

.video-cover img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.play-icon {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 48px;
  color: white;
  text-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
}

.video-info {
  padding: 15px 0;
}

.video-info h3 {
  margin: 0 0 10px 0;
}

.video-info p {
  color: #909399;
  margin: 0 0 15px 0;
}

.video-desc {
  margin-top: 15px;
  line-height: 1.6;
}
</style>
