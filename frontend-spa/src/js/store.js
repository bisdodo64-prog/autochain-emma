import { createStore } from 'vuex'
import auth from './store/modules/auth'
import vehicles from './store/modules/vehicles'
import blockchain from './store/modules/blockchain'
import ui from './store/modules/ui'

export default createStore({
  modules: { auth, vehicles, blockchain, ui }
})
