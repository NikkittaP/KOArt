(function(){
  function esc(s){return String(s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];});}

  // mobile menu
  var burger=document.getElementById('burger'), nav=document.getElementById('nav');
  if(burger&&nav){
    burger.addEventListener('click',function(){
      var o=nav.classList.toggle('open'); burger.classList.toggle('open',o);
      document.body.style.overflow=o?'hidden':'';
    });
    nav.querySelectorAll('a').forEach(function(a){
      a.addEventListener('click',function(){nav.classList.remove('open');burger.classList.remove('open');document.body.style.overflow='';});
    });
  }

  // lightbox
  var figs=[].slice.call(document.querySelectorAll('figure[data-full]'));
  if(!figs.length) return;
  var lb=document.getElementById('lb'), lbimg=document.getElementById('lbimg'), lbcap=document.getElementById('lbcap');
  // spinner (created here so no HTML change needed)
  var spin=document.createElement('div'); spin.className='lb-spin'; spin.setAttribute('aria-hidden','true'); lb.appendChild(spin);
  var idx=0;

  function caption(f){
    var t=f.getAttribute('data-title')||'', mat=f.getAttribute('data-mat')||'',
        y=f.getAttribute('data-year')||'', s=f.getAttribute('data-size')||'', d=f.getAttribute('data-desc')||'';
    var l2=[mat,y,s].filter(Boolean).join('  ·  '); var h='';
    if(t) h+="<div class='ct'>"+esc(t)+"</div>";
    if(l2) h+="<div class='cm'>"+esc(l2)+"</div>";
    if(d) h+="<div class='cd'>"+esc(d)+"</div>";
    return h;
  }
  function preload(i){ var f=figs[(i+figs.length)%figs.length]; if(f){ var im=new Image(); im.src=f.getAttribute('data-full'); } }
  function show(i){
    idx=(i+figs.length)%figs.length; var f=figs[idx]; var url=f.getAttribute('data-full');
    lb.classList.add('loading');
    lbimg.onload=lbimg.onerror=function(){ if(lbimg.getAttribute('src')===url) lb.classList.remove('loading'); };
    lbimg.alt=f.getAttribute('data-title')||''; lbcap.innerHTML=caption(f);
    lbimg.src=url;
    if(lbimg.complete && lbimg.naturalWidth>0) lb.classList.remove('loading');
    preload(idx+1); preload(idx-1);   // neighbours load in the background -> instant swipe
  }
  function domClose(){ lb.classList.remove('open'); lb.classList.remove('loading'); document.body.style.overflow=''; lbimg.src=''; }
  function open(i){
    show(i); lb.classList.add('open'); document.body.style.overflow='hidden';
    try{ history.pushState({lb:true},''); }catch(e){}
  }
  function requestClose(){ if(history.state&&history.state.lb){ history.back(); } else { domClose(); } }
  window.addEventListener('popstate',function(){ if(lb.classList.contains('open')) domClose(); });

  figs.forEach(function(f,i){f.addEventListener('click',function(){open(i);});});
  document.getElementById('lbx').onclick=function(e){e.stopPropagation();requestClose();};
  document.getElementById('lbnext').onclick=function(e){e.stopPropagation();show(idx+1);};
  document.getElementById('lbprev').onclick=function(e){e.stopPropagation();show(idx-1);};
  lb.addEventListener('click',function(e){ if(e.target===lb) requestClose(); });
  document.addEventListener('keydown',function(e){
    if(!lb.classList.contains('open'))return;
    if(e.key==='Escape')requestClose();
    if(e.key==='ArrowRight')show(idx+1);
    if(e.key==='ArrowLeft')show(idx-1);
  });
  var sx=0, sy=0;
  lb.addEventListener('touchstart',function(e){sx=e.touches[0].clientX; sy=e.touches[0].clientY;},{passive:true});
  lb.addEventListener('touchend',function(e){
    var dx=e.changedTouches[0].clientX-sx, dy=e.changedTouches[0].clientY-sy;
    if(Math.abs(dx)>Math.abs(dy)){ if(Math.abs(dx)>40) show(idx+(dx<0?1:-1)); }
    else if(dy>70){ requestClose(); }
  },{passive:true});
})();
