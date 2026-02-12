<template>
  <div class="user-chat">
    <el-button 
      type="primary" 
      circle 
      size="large" 
      class="chat-trigger"
      @click="openChat"
    >
      <el-icon><ChatDotRound /></el-icon>
    </el-button>

    <el-dialog 
      v-model="chatVisible" 
      title="在线客服" 
      width="400px"
      :close-on-click-modal="false"
    >
      <div class="chat-container">
        <div class="message-list" ref="messageListRef">
          <div 
            v-for="msg in messages" 
            :key="msg.id"
            :class="['message-item', msg.sender_type === 'user' ? 'user-message' : 'admin-message']"
          >
            <div class="message-content">{{ msg.content }}</div>
            <div class="message-time">{{ formatTime(msg.created_at) }}</div>
          </div>
        </div>
        <div class="input-area">
          <el-input
            v-model="messageInput"
            type="textarea"
            :rows="3"
            placeholder="输入消息..."
            @keyup.enter.ctrl="sendMessage"
          />
          <el-button type="primary" @click="sendMessage" :disabled="!messageInput.trim()">
            发送
          </el-button>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script>
import { ref, nextTick, onUnmounted } from 'vue'
import { ElMessage } from 'element-plus'
import { ChatDotRound } from '@element-plus/icons-vue'
import { createChatSession, getChatMessages } from '@/api/chat'

export default {
  name: 'UserChat',
  components: { ChatDotRound },
  setup() {
    const chatVisible = ref(false)
    const messages = ref([])
    const messageInput = ref('')
    const messageListRef = ref(null)
    const sessionId = ref(null)
    const ws = ref(null)

    const openChat = async () => {
      chatVisible.value = true
      if (!sessionId.value) {
        try {
          const response = await createChatSession()
          sessionId.value = response.data.id
          connectWebSocket()
          loadMessages()
        } catch (error) {
          ElMessage.error('创建会话失败')
        }
      }
    }

    const connectWebSocket = () => {
      const wsUrl = `ws://localhost:8282/ws/chat?session_id=${sessionId.value}`
      ws.value = new WebSocket(wsUrl)

      ws.value.onopen = () => {
        console.log('WebSocket connected')
      }

      ws.value.onmessage = (event) => {
        const data = JSON.parse(event.data)
        if (data.type === 'message') {
          messages.value.push(data.message)
          scrollToBottom()
        }
      }

      ws.value.onerror = () => {
        ElMessage.error('连接失败')
      }

      ws.value.onclose = () => {
        console.log('WebSocket disconnected')
      }
    }

    const loadMessages = async () => {
      try {
        const response = await getChatMessages(sessionId.value)
        messages.value = response.data
        scrollToBottom()
      } catch (error) {
        ElMessage.error('加载消息失败')
      }
    }

    const sendMessage = () => {
      if (!messageInput.value.trim()) return

      if (ws.value && ws.value.readyState === WebSocket.OPEN) {
        ws.value.send(JSON.stringify({
          type: 'message',
          content: messageInput.value
        }))
        messageInput.value = ''
      } else {
        ElMessage.error('连接已断开')
      }
    }

    const scrollToBottom = () => {
      nextTick(() => {
        if (messageListRef.value) {
          messageListRef.value.scrollTop = messageListRef.value.scrollHeight
        }
      })
    }

    const formatTime = (time) => {
      return new Date(time).toLocaleTimeString('zh-CN', { 
        hour: '2-digit', 
        minute: '2-digit' 
      })
    }

    onUnmounted(() => {
      if (ws.value) {
        ws.value.close()
      }
    })

    return {
      chatVisible,
      messages,
      messageInput,
      messageListRef,
      openChat,
      sendMessage,
      formatTime
    }
  }
}
</script>

<style scoped>
.chat-trigger {
  position: fixed;
  bottom: 30px;
  right: 30px;
  z-index: 1000;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.3);
}

.chat-container {
  display: flex;
  flex-direction: column;
  height: 500px;
}

.message-list {
  flex: 1;
  overflow-y: auto;
  padding: 10px;
  background-color: #f5f7fa;
  border-radius: 4px;
  margin-bottom: 10px;
}

.message-item {
  margin-bottom: 15px;
}

.user-message {
  text-align: right;
}

.admin-message {
  text-align: left;
}

.message-content {
  display: inline-block;
  max-width: 70%;
  padding: 10px;
  border-radius: 8px;
  word-wrap: break-word;
}

.user-message .message-content {
  background-color: #409eff;
  color: white;
}

.admin-message .message-content {
  background-color: white;
  color: #303133;
}

.message-time {
  font-size: 12px;
  color: #909399;
  margin-top: 5px;
}

.input-area {
  display: flex;
  gap: 10px;
  align-items: flex-end;
}
</style>
