<script>
// Format angka saat ketik
function onlyNumber(el){
el.addEventListener('input', ()=>{
el.value = el.value.replace(/[^\d]/g,'');
});
}
</script>
</body></html>