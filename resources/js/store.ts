import { isReturnStatement } from 'typescript'
import { InjectionKey } from 'vue'
import { createStore, Store, useStore as baseUseStore } from 'vuex'

type User = App.Models.User
export interface State {
  user: User|null
}

export const key: InjectionKey<Store<State>> = Symbol()

export const store = createStore<State>({
	state: {
		user: null
	},
	getters: {
		isLoggedIn(state: State): boolean{
			return state.user !== null;
		},
		email(state: State): string|null{
			if(state.user === null || state.user === undefined){
				return null;
			}
			return state.user.email;
		},
		user(state: State): User|null{
			if(state.user === null || state.user === undefined){
				return null;
			}
			return state.user;
		}
	},
	mutations: {
		setUser(state: State, payload){
			state.user = payload.user;
		},
		unsetUser(state: State){
			state.user = null;
		}
	}
})

export function useStore () {
	return baseUseStore(key)
}