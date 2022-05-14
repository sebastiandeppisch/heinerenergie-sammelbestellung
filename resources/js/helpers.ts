function formatPriceCell(cell): string{
	return formatPrice(parseFloat(cell.value));
}
  
function formatPrice(price: number): string{
	return price.toFixed(2).replace(".", ",") + " €";
}
  
export {formatPrice, formatPriceCell}