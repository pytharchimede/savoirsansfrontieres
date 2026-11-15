document.querySelectorAll('[data-profile]').forEach(link=>link.addEventListener('click',()=>{const select=document.querySelector('#profile');if(select)select.value=link.dataset.profile;select.dispatchEvent(new Event('change'));}));
(function(){const profile=document.getElementById('profile'),fields=document.getElementById('student-fields'),subject=document.getElementById('subject'),level=document.getElementById('student-level');if(!profile||!fields)return;function sync(){const student=profile.value==='etudiant';fields.hidden=!student;subject.disabled=!student;level.disabled=!student;}profile.addEventListener('change',sync);sync();})();

document.querySelectorAll('[data-search-select]').forEach(box=>{
 const search=box.querySelector('[data-search-input]'),hidden=box.querySelector('[data-search-value]'),menu=box.querySelector('[role=listbox]'),items=[...menu.querySelectorAll('[role=option]')];
 function close(){menu.hidden=true;search.setAttribute('aria-expanded','false');}
 function show(){const q=search.value.trim().toLocaleLowerCase('fr');let count=0;items.forEach(item=>{item.hidden=!item.textContent.toLocaleLowerCase('fr').includes(q);if(!item.hidden)count++;});menu.hidden=count===0;search.setAttribute('aria-expanded',String(count>0));}
 search.addEventListener('focus',show);
 search.addEventListener('input',()=>{hidden.value='';search.setCustomValidity('');show();});
 search.addEventListener('keydown',e=>{if(e.key==='Escape')close();if(e.key==='Enter'&&!menu.hidden){const first=items.find(i=>!i.hidden);if(first){e.preventDefault();first.click();}}});
 items.forEach(item=>item.addEventListener('click',()=>{hidden.value=item.dataset.code;search.value=item.textContent.trim();search.setCustomValidity('');close();}));
 document.addEventListener('click',e=>{if(!box.contains(e.target))close();});
 const form=box.closest('form');form.addEventListener('submit',e=>{if(hidden.disabled)return;if(!hidden.value){e.preventDefault();search.setCustomValidity('Choisissez une valeur dans la liste.');search.reportValidity();search.focus();show();}});
});


// Responsive navigation: accessible toggle, close after navigation.
(()=>{const toggle=document.querySelector('.mobile-menu-toggle');const nav=document.getElementById('primary-navigation');if(!toggle||!nav)return;const close=()=>{nav.classList.remove('is-open');toggle.setAttribute('aria-expanded','false');toggle.setAttribute('aria-label','Ouvrir le menu');const i=toggle.querySelector('i');if(i){i.classList.remove('fa-xmark');i.classList.add('fa-bars');}};toggle.addEventListener('click',()=>{const open=!nav.classList.contains('is-open');if(open){nav.classList.add('is-open');toggle.setAttribute('aria-expanded','true');toggle.setAttribute('aria-label','Fermer le menu');const i=toggle.querySelector('i');if(i){i.classList.remove('fa-bars');i.classList.add('fa-xmark');}}else close();});nav.querySelectorAll('a').forEach(a=>a.addEventListener('click',close));document.addEventListener('keydown',e=>{if(e.key==='Escape')close();});document.addEventListener('click',e=>{if(!nav.contains(e.target)&&!toggle.contains(e.target))close();});window.matchMedia('(min-width: 1001px)').addEventListener('change',close);})();
