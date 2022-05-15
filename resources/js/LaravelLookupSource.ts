import DataSource from "devextreme/data/data_source";
import axios from 'axios'
import CustomStore from 'devextreme/data/custom_store';

export default class LaravelLookupSource extends CustomStore{
	constructor(url: string){
		super({
				key: 'id',
				load: (options) => {
					return axios.get(url,{
						params: options
					}).then(response => {
						return response.data;
					});
				},
				insert: (values) => {
					throw new Error('insert not implemented in lookup')
				},
				remove: (key) => {
					throw new Error('insert not implemented in lookup')
				},
				update: (key, values) => {
					throw new Error('insert not implemented in lookup')
				},
				byKey: (key) => {
					return axios.get(url + '/' + key).then(response => {
						return response.data;
					});
				}
		})
	}
}