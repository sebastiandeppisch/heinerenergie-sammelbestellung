import { AxiosError } from 'axios';
import notify from 'devextreme/ui/notify';
import moment from 'moment';
import { Ref } from 'vue';
import { reactive } from '@vue/reactivity';

function formatPriceCell(cell): string{
	return formatPrice(parseFloat(cell.value));
}
  
function formatPrice(price: number): string{
	return price.toFixed(2).replace(".", ",") + " €";
}

function notifyError(error: AxiosError): void{
	if(error.response && error.response.status === 422){
	  let validationErrors: Array<String> = [];
	  for(const prop in error.response.data.errors){
		validationErrors = validationErrors.concat(error.response.data.errors[prop] as Array<String>);
	  }
	  notify(validationErrors.join(","), 'error')
	}else{
	  notify(error, 'error');
	}
  }

function formatDateCell(row): string{
	return moment(row.value).format("DD.MM.YY");
}

class AdaptTableHeight{
	private ref: Ref;
	private r;
	constructor(ref: Ref){
		this.ref = ref;
		this.r = reactive({
			height: 450
		});
		window.addEventListener('resize', () => {
			this.calcHeight();
		});
	}

	calcHeight(){
		if(this.ref.value){
			const vh = window.innerHeight;
			const dom: HTMLElement = this.ref.value;
			const footerHeight = 250;
			let height = vh - dom.offsetTop - footerHeight;
			if(height < 450){
				height = 450;
			}
			this.r.height = height;
		}
	}

	getReactive(){
		return this.r;
	}
}
  
export {formatPrice, formatPriceCell, notifyError, formatDateCell, AdaptTableHeight}