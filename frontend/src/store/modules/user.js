import { login as loginApi } from '@/api/auth'

const state = {
  token: localStorage.getItem('token') || '',
  userInfo: JSON.parse(localStorage.getItem('userInfo') || '{}')
}

const getters = {
  isAuthenticated: state => !!state.token,
  userInfo: state => state.userInfo
}

const mutations = {
  SET_TOKEN(state, token) {
    state.token = token
    localStorage.setItem('token', token)
  },
  SET_USER_INFO(state, userInfo) {
    state.userInfo = userInfo
    localStorage.setItem('userInfo', JSON.stringify(userInfo))
  },
  CLEAR_AUTH(state) {
    state.token = ''
    state.userInfo = {}
    localStorage.removeItem('token')
    localStorage.removeItem('userInfo')
  }
}

const actions = {
  async login({ commit }, credentials) {
    try {
      const response = await loginApi(credentials)
      const { token, user } = response.data
      commit('SET_TOKEN', token)
      commit('SET_USER_INFO', user)
      return response
    } catch (error) {
      throw error
    }
  },
  logout({ commit }) {
    commit('CLEAR_AUTH')
  }
}

export default {
  namespaced: true,
  state,
  getters,
  mutations,
  actions
}
