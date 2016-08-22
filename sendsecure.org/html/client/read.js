document.addEventListener('DOMContentLoaded', fixDate, false);
function fixDate(){
	d = document.getElementById('now');
	d.innerText = moment(Date.parse(d.innerText)).format('ddd, MMM DD, YYYY [at] h:mm A');
}