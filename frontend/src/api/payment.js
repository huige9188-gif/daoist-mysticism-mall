import request from '@/utils/request'

export function getPaymentConfigs() {
  return request({
    url: '/payment-configs',
    method: 'get'
  })
}

export function savePaymentConfig(data) {
  return request({
    url: '/payment-configs',
    method: 'post',
    data
  })
}
