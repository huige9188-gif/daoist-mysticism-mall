import request from '@/utils/request'

export function getFengShuiMasterList(params) {
  return request({
    url: '/feng-shui-masters',
    method: 'get',
    params
  })
}

export function createFengShuiMaster(data) {
  return request({
    url: '/feng-shui-masters',
    method: 'post',
    data
  })
}

export function updateFengShuiMaster(id, data) {
  return request({
    url: `/feng-shui-masters/${id}`,
    method: 'put',
    data
  })
}

export function deleteFengShuiMaster(id) {
  return request({
    url: `/feng-shui-masters/${id}`,
    method: 'delete'
  })
}

export function updateFengShuiMasterStatus(id, status) {
  return request({
    url: `/feng-shui-masters/${id}/status`,
    method: 'patch',
    data: { status }
  })
}
