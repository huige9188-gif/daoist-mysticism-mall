import { createStore } from 'vuex'
import user from './modules/user'
import cart from './modules/cart'

export default createStore({
  modules: {
    user,
    cart
  }
})
