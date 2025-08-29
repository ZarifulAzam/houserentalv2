document.addEventListener('DOMContentLoaded',()=>{
	const alerts=document.querySelectorAll('.flash');
	alerts.forEach(a=>setTimeout(()=>a.remove(),4000));
});


