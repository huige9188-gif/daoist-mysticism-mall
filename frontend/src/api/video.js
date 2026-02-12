import request from '@/utils/request'

export function getVideoList(params) {
  return request({
    url: '/videos',
    method: 'get',
    params
  })
}

export function createVideo(data) {
  return request({
    url: '/videos',
    method: 'post',
    data
  })
}

export function updateVideo(id, data) {
  return request({
    url: `/videos/${id}`,
    method: 'put',
    data
  })
}

export function deleteVideo(id) {
  return request({
    url: `/videos/${id}`,
    method: 'delete'
  })
}

export function updateVideoStatus(id, status) {
  return request({
    url: `/videos/${id}/status`,
    method: 'patch',
    data: { status }
  })
}
