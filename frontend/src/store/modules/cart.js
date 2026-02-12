const state = {
  items: JSON.parse(localStorage.getItem('cart') || '[]')
}

const getters = {
  cartItems: state => state.items,
  cartTotal: state => {
    return state.items.reduce((total, item) => {
      return total + item.price * item.quantity
    }, 0)
  },
  cartCount: state => {
    return state.items.reduce((count, item) => count + item.quantity, 0)
  }
}

const mutations = {
  ADD_TO_CART(state, product) {
    const existingItem = state.items.find(item => item.id === product.id)
    if (existingItem) {
      existingItem.quantity += product.quantity || 1
    } else {
      state.items.push({
        ...product,
        quantity: product.quantity || 1
      })
    }
    localStorage.setItem('cart', JSON.stringify(state.items))
  },
  REMOVE_FROM_CART(state, productId) {
    state.items = state.items.filter(item => item.id !== productId)
    localStorage.setItem('cart', JSON.stringify(state.items))
  },
  UPDATE_QUANTITY(state, { productId, quantity }) {
    const item = state.items.find(item => item.id === productId)
    if (item) {
      item.quantity = quantity
      localStorage.setItem('cart', JSON.stringify(state.items))
    }
  },
  CLEAR_CART(state) {
    state.items = []
    localStorage.removeItem('cart')
  }
}

const actions = {
  addToCart({ commit }, product) {
    commit('ADD_TO_CART', product)
  },
  removeFromCart({ commit }, productId) {
    commit('REMOVE_FROM_CART', productId)
  },
  updateQuantity({ commit }, payload) {
    commit('UPDATE_QUANTITY', payload)
  },
  clearCart({ commit }) {
    commit('CLEAR_CART')
  }
}

export default {
  namespaced: true,
  state,
  getters,
  mutations,
  actions
}
