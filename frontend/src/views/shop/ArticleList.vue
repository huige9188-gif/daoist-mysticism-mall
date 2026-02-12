<template>
  <div class="article-list">
    <el-row :gutter="20" v-loading="loading">
      <el-col :span="24" v-for="article in articles" :key="article.id">
        <el-card class="article-card" @click="handleViewDetail(article)">
          <el-row :gutter="20">
            <el-col :span="6">
              <img :src="article.cover_image || '/placeholder.png'" class="article-cover" />
            </el-col>
            <el-col :span="18">
              <h2>{{ article.title }}</h2>
              <p class="article-meta">作者：{{ article.author }} | {{ article.created_at }}</p>
              <p class="article-excerpt">{{ getExcerpt(article.content) }}</p>
              <el-button type="primary" text>阅读全文 →</el-button>
            </el-col>
          </el-row>
        </el-card>
      </el-col>
    </el-row>

    <el-dialog v-model="detailVisible" :title="currentArticle?.title" width="800px">
      <div v-if="currentArticle" class="article-detail">
        <p class="article-meta">作者：{{ currentArticle.author }} | {{ currentArticle.created_at }}</p>
        <el-divider />
        <div class="article-content" v-html="currentArticle.content"></div>
      </div>
    </el-dialog>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getArticleList } from '@/api/article'

export default {
  name: 'ArticleList',
  setup() {
    const loading = ref(false)
    const articles = ref([])
    const detailVisible = ref(false)
    const currentArticle = ref(null)

    const loadArticles = async () => {
      loading.value = true
      try {
        const response = await getArticleList({ status: 'published' })
        articles.value = response.data
      } catch (error) {
        ElMessage.error('加载资讯失败')
      } finally {
        loading.value = false
      }
    }

    const getExcerpt = (content) => {
      return content.substring(0, 200) + '...'
    }

    const handleViewDetail = (article) => {
      currentArticle.value = article
      detailVisible.value = true
    }

    onMounted(() => {
      loadArticles()
    })

    return {
      loading,
      articles,
      detailVisible,
      currentArticle,
      getExcerpt,
      handleViewDetail
    }
  }
}
</script>

<style scoped>
.article-list {
  max-width: 1200px;
  margin: 0 auto;
}

.article-card {
  margin-bottom: 20px;
  cursor: pointer;
  transition: transform 0.3s;
}

.article-card:hover {
  transform: translateY(-5px);
}

.article-cover {
  width: 100%;
  height: 150px;
  object-fit: cover;
  border-radius: 4px;
}

.article-card h2 {
  margin: 0 0 10px 0;
}

.article-meta {
  color: #909399;
  font-size: 14px;
  margin: 0 0 15px 0;
}

.article-excerpt {
  line-height: 1.6;
  color: #606266;
  margin: 0 0 15px 0;
}

.article-content {
  line-height: 1.8;
  color: #303133;
}
</style>
