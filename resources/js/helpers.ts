import { AxiosError } from 'axios';
import notify from 'devextreme/ui/notify';
import moment from 'moment';

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

function formatDate(dateString: string): string{
	const date = moment(dateString);
	return date.format("DD.MM.YY");
}
  
export {formatPrice, formatPriceCell, notifyError, formatDate}