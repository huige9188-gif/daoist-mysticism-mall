import request from '@/utils/request'

export function getCategoryList(params) {
  return request({
    url: '/categories',
    method: 'get',
    params
  })
}

export function createCategory(data) {
  return request({
    url: '/categories',
    method: 'post',
    data
  })
}

export function updateCategory(id, data) {
  return request({
    url: `/categories/${id}`,
    method: 'put',
    data
  })
}

export function deleteCategory(id) {
  return request({
    url: `/categories/${id}`,
    method: 'delete'
  })
}

export function updateCategoryStatus(id, status) {
  return request({
    url: `/categories/${id}/status`,
    method: 'patch',
    data: { status }
  })
}
