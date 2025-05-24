// mostrar / ocultar secciones por tipo
const typeSel = document.getElementById('borrower_type');
const sections = document.querySelectorAll('[data-section]');
function showSection(){
  sections.forEach(s=>s.style.display='none');
  document.querySelector(`[data-section="type${typeSel.value}"]`).style.display='block';
}
typeSel.addEventListener('change',showSection);showSection();

// preview de imágenes
function preview(id,input){
  const f=input.files[0]; if(!f) return;
  document.getElementById(id).src = URL.createObjectURL(f);
}