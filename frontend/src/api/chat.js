import request from '@/utils/request'

export function getChatSessions(params) {
  return request({
    url: '/chat/sessions',
    method: 'get',
    params
  })
}

export function getChatMessages(sessionId) {
  return request({
    url: `/chat/sessions/${sessionId}/messages`,
    method: 'get'
  })
}

export function createChatSession(data) {
  return request({
    url: '/chat/sessions',
    method: 'post',
    data
  })
}

export function closeChatSession(sessionId) {
  return request({
    url: `/chat/sessions/${sessionId}/close`,
    method: 'post'
  })
}
