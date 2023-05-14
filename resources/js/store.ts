import { isReturnStatement } from 'typescript'
import { InjectionKey } from 'vue'
import { createStore, Store, useStore as baseUseStore } from 'vuex'

type User = App.Models.User
export interface State {
  user: User|null,
  canActAsAdmin: boolean
}

export const key: InjectionKey<Store<State>> = Symbol()

export const store = createStore<State>({
	state: {
		user: null,
		canActAsAdmin: false
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
		},
		canActAsAdmin(state: State): boolean{
			return state.canActAsAdmin;
		}
	},
	mutations: {
		setUser(state: State, payload){
			if(payload.user === null){
				state.user = null;
				state.canActAsAdmin = false;
				return;
			}
			state.user = payload.user;
			state.canActAsAdmin = payload.user.is_admin;
			state.user.is_admin = payload.user.isActingAsAdmin;
		},
		unsetUser(state: State){
			state.user = null;
		},
		actAsNonAdmin(state: State){
			state.user.is_admin = false;
		},
		actAsAdmin(state: State){
			state.user.is_admin = true;
		}
	}
})

export function useStore () {
	return baseUseStore(key)
}