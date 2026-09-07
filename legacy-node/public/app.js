document.addEventListener('DOMContentLoaded', () => {
  const clock = document.querySelector('#liveClock');
  if (clock) {
    const tick = () => clock.textContent = new Intl.DateTimeFormat('id-ID', { timeZone:'Asia/Jakarta', hour:'2-digit', minute:'2-digit', second:'2-digit', hourCycle:'h23' }).format(new Date()) + ' WIB';
    tick(); setInterval(tick, 1000);
  }
  const form = document.querySelector('#attendanceForm');
  if (!form) return;
  const video=document.querySelector('#camera'), canvas=document.querySelector('#snapshot'), fileInput=document.querySelector('#photo');
  const start=document.querySelector('#startCamera'), take=document.querySelector('#takePhoto'), submit=document.querySelector('#submitAttendance'), placeholder=document.querySelector('#cameraPlaceholder'), loc=document.querySelector('#locationState');
  let stream, hasPhoto=false, hasLocation=false;
  function ready(){ submit.disabled=!(hasPhoto&&hasLocation); }
  function locate(){
    if(!navigator.geolocation){ loc.textContent='GPS tidak didukung perangkat ini';loc.classList.add('bad');return; }
    loc.textContent='⌖ Sedang mengambil lokasi…';
    navigator.geolocation.getCurrentPosition(p=>{ document.querySelector('#latitude').value=p.coords.latitude;document.querySelector('#longitude').value=p.coords.longitude;document.querySelector('#accuracy').value=p.coords.accuracy;hasLocation=true;loc.textContent=`✓ Lokasi didapat · akurasi ±${Math.round(p.coords.accuracy)} m`;loc.classList.add('good');ready(); },()=>{loc.textContent='Lokasi gagal. Izinkan GPS lalu muat ulang halaman.';loc.classList.add('bad')},{enableHighAccuracy:true,timeout:15000,maximumAge:0});
  }
  locate();
  start.addEventListener('click',async()=>{ try{stream=await navigator.mediaDevices.getUserMedia({video:{facingMode:'user'},audio:false});video.srcObject=stream;video.style.display='block';placeholder.style.display='none';take.disabled=false;start.textContent='Kamera aktif';}catch{fileInput.hidden=false;fileInput.required=true;loc.textContent='Kamera browser gagal. Gunakan tombol pilih kamera di bawah.';} });
  take.addEventListener('click',()=>{canvas.width=video.videoWidth;canvas.height=video.videoHeight;canvas.getContext('2d').drawImage(video,0,0);canvas.toBlob(blob=>{const dt=new DataTransfer();dt.items.add(new File([blob],'selfie.jpg',{type:'image/jpeg'}));fileInput.files=dt.files;hasPhoto=true;take.textContent='✓ Foto diambil ulang';video.classList.add('captured');ready();},'image/jpeg',.85);});
  fileInput.addEventListener('change',()=>{hasPhoto=fileInput.files.length>0;ready();});
  form.addEventListener('submit',()=>{submit.disabled=true;submit.textContent='Mengirim absensi…';if(stream)stream.getTracks().forEach(t=>t.stop());});
});
