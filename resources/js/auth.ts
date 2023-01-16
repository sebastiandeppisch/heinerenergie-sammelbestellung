import axios, { AxiosError } from 'axios'
import notify from 'devextreme/ui/notify';
import { store } from './store'
export default {
  async logIn(email, password) {
    try {
      // Send request
      await axios.post('api/login', {
        email, password
      }).then(response => response.data).then(response => {
        store.commit('setUser', {
          user: response.user as App.Models.User
        })
      });
      return {
        isOk: true
      }
    }
    catch {
      return {
        isOk: false,
        message: "Ungültige Zugangsdaten"
      };
    }
  },

  async logOut() {
    await axios.post('api/logout').then(response => {
      notify('Du wurdest ausgeloggt', 'success');
      store.commit('unsetUser');
    }).catch(error =>  {
      notify('Fehler beim ausloggen', 'error');
    })
  },

  async resetPassword(email) {
    try{
      await axios.post('api/forgot-password', {
        email
      })
      return {
        isOk: true
      };
    }catch(error){
      let message = '';
      if(error.response && error.response.status === 422){
        for(const prop in error.response.data.errors){
          if(prop === 'email'){
            const validationErrors  = error.response.data.errors[prop] as Array<String>;
            message = validationErrors.join(",");
          }
        }
      }else{
        message = error
      }
      return{
        isOk: false,
        message
      }
    }
  },
  async changePassword(email, password, password_confirmation, token) {
    try {
      await axios.post('api/reset-password', {
        email,
        password,
        password_confirmation,
        token
      })
      return {
        isOk: true
      };
    }catch(error){
      let message = '';
      if(error.response && error.response.status === 422){
        for(const prop in error.response.data.errors){
          const validationErrors  = error.response.data.errors[prop] as Array<String>;
          message += validationErrors.join(",");
        }
      }else{
        message = error
      }
      return{
        isOk: false,
        message
      }
    }
  },

  async createAccount(email, password) {
    try {
      // Send request
      console.log(email, password);

      return {
        isOk: true
      };
    }
    catch {
      return {
        isOk: false,
        message: "Failed to create account"
      };
    }
  },

  async initLogin(){
    if('user' in window){
      const user = (window as any).user as App.Models.User;
      store.commit('setUser', {
        user
      })
    }else{
      await axios.get('api/login').then(response => response.data).then(response => {
        if(store !== undefined){
          if(response.isLoggedIn){
            store.commit('setUser', {
              user: response.user as App.Models.User
            })
          }else{
            store.commit('unsetUser');
          }
        }
      })
    }
   
  },

  loggedIn(){
    if(store !== undefined){
      return store.getters.isLoggedIn;
    }
    return false;
  }
};