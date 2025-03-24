import DataSource from "devextreme/data/data_source";
import axios, { AxiosError } from 'axios'
import CustomStore from 'devextreme/data/custom_store';
import { LaravelValidationError } from "./helpers";

export default class LaravelDataSource extends DataSource{
	constructor(url: string){
		super({
			store: new CustomStore({
				key: 'id',
				load: (options) => {
					var params = options;
					return axios.get(url,{
						params
					}).then(response => {
						return response.data;
					});
				},
				insert: (values) => {
					return axios.post(url, values).catch(error => {
						this.formatError(error);
					})
				},
				remove: (key) => {
					return axios.delete(url + '/' + key);
				},
				update: (key, values) => {
					return axios.put(url + '/' + key, values).catch(error => {
						this.formatError(error);
					});
				},
				byKey: (key) => {
					return axios.get(url + '/' + key).then(response => {
						return response.data;
					});
				}
			}),
			reshapeOnPush: true
		});
	}

	private formatError(error: AxiosError<LaravelValidationError>): void{
		if(error.response && error.response.status === 422){
			for(const prop  in error.response.data.errors){
				const validationErrors  = error.response.data.errors[prop] as Array<String>;
				throw(validationErrors.join(","));
			}
		}
		throw(error);
	}
}
