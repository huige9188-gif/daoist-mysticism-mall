<template>
  <div class="chat">
    <el-row :gutter="20">
      <el-col :span="8">
        <el-card>
          <template #header>
            <span>会话列表</span>
          </template>
          <el-table
            :data="sessions"
            v-loading="loading"
            highlight-current-row
            @current-change="handleSessionChange"
            style="cursor: pointer"
          >
            <el-table-column prop="user_name" label="用户" />
            <el-table-column prop="status" label="状态" width="80">
              <template #default="{ row }">
                <el-tag :type="getStatusType(row.status)" size="small">
                  {{ getStatusLabel(row.status) }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="started_at" label="开始时间" width="150" />
          </el-table>
        </el-card>
      </el-col>

      <el-col :span="16">
        <el-card v-if="currentSession">
          <template #header>
            <div class="chat-header">
              <span>与 {{ currentSession.user_name }} 的对话</span>
              <el-button
                v-if="currentSession.status === 'active'"
                type="danger"
                size="small"
                @click="handleCloseSession"
              >
                结束会话
              </el-button>
            </div>
          </template>

          <div class="chat-messages" ref="messagesContainer">
            <div
              v-for="msg in messages"
              :key="msg.id"
              :class="['message', msg.sender_id === currentSession.user_id ? 'user' : 'admin']"
            >
              <div class="message-content">{{ msg.content }}</div>
              <div class="message-time">{{ msg.created_at }}</div>
            </div>
          </div>

          <div class="chat-input" v-if="currentSession.status === 'active'">
            <el-input
              v-model="messageInput"
              placeholder="输入消息..."
              @keyup.enter="handleSendMessage"
            >
              <template #append>
                <el-button type="primary" @click="handleSendMessage">发送</el-button>
              </template>
            </el-input>
          </div>
        </el-card>

        <el-empty v-else description="请选择一个会话" />
      </el-col>
    </el-row>
  </div>
</template>

<script>
import { ref, reactive, onMounted, nextTick } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getChatSessions, getChatMessages, closeChatSession } from '@/api/chat'

export default {
  name: 'Chat',
  setup() {
    const loading = ref(false)
    const sessions = ref([])
    const currentSession = ref(null)
    const messages = ref([])
    const messageInput = ref('')
    const messagesContainer = ref(null)

    const statusLabels = {
      active: '活跃',
      inactive: '不活跃',
      closed: '已结束'
    }

    const getStatusLabel = (status) => statusLabels[status] || status

    const getStatusType = (status) => {
      const types = {
        active: 'success',
        inactive: 'warning',
        closed: 'info'
      }
      return types[status] || 'info'
    }

    const loadSessions = async () => {
      loading.value = true
      try {
        const response = await getChatSessions()
        sessions.value = response.data
      } catch (error) {
        ElMessage.error('加载会话列表失败')
      } finally {
        loading.value = false
      }
    }

    const handleSessionChange = async (session) => {
      if (!session) return
      
      currentSession.value = session
      try {
        const response = await getChatMessages(session.id)
        messages.value = response.data
        await nextTick()
        scrollToBottom()
      } catch (error) {
        ElMessage.error('加载聊天记录失败')
      }
    }

    const handleSendMessage = () => {
      if (!messageInput.value.trim()) return

      // 这里应该通过WebSocket发送消息
      // 简化实现：直接添加到消息列表
      messages.value.push({
        id: Date.now(),
        sender_id: 0, // 管理员ID
        content: messageInput.value,
        created_at: new Date().toLocaleString()
      })

      messageInput.value = ''
      nextTick(() => scrollToBottom())
      ElMessage.info('WebSocket功能需要后端支持')
    }

    const handleCloseSession = () => {
      ElMessageBox.confirm('确定要结束该会话吗？', '提示', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      }).then(async () => {
        try {
          await closeChatSession(currentSession.value.id)
          ElMessage.success('会话已结束')
          currentSession.value.status = 'closed'
          loadSessions()
        } catch (error) {
          ElMessage.error('结束会话失败')
        }
      })
    }

    const scrollToBottom = () => {
      if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
      }
    }

    onMounted(() => {
      loadSessions()
    })

    return {
      loading,
      sessions,
      currentSession,
      messages,
      messageInput,
      messagesContainer,
      getStatusLabel,
      getStatusType,
      handleSessionChange,
      handleSendMessage,
      handleCloseSession
    }
  }
}
</script>

<style scoped>
.chat-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.chat-messages {
  height: 400px;
  overflow-y: auto;
  padding: 20px;
  background-color: #f5f7fa;
  margin-bottom: 20px;
}

.message {
  margin-bottom: 15px;
  display: flex;
  flex-direction: column;
}

.message.user {
  align-items: flex-end;
}

.message.admin {
  align-items: flex-start;
}

.message-content {
  max-width: 60%;
  padding: 10px 15px;
  border-radius: 8px;
  word-wrap: break-word;
}

.message.user .message-content {
  background-color: #409eff;
  color: white;
}

.message.admin .message-content {
  background-color: white;
  border: 1px solid #dcdfe6;
}

.message-time {
  font-size: 12px;
  color: #909399;
  margin-top: 5px;
}
</style>
