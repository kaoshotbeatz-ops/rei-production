/* REI contact forms -> open the visitor's email app with a pre-formatted message (no backend needed). */
(function(){
  var TO='info@racialequityinstitute.org';
  function lbl(e){var s='';if(e.id){var l=document.querySelector('label[for="'+CSS.escape(e.id)+'"]');if(l)s+=' '+l.textContent;}
    var p=e.closest('.form-item,.field,.ev-field,.form-group,div');if(p){var l2=p.querySelector('label,.title,.caption');if(l2)s+=' '+l2.textContent;}return s;}
  function hay(e){return (e.name+' '+e.id+' '+(e.placeholder||'')+' '+(e.getAttribute('aria-label')||'')+' '+lbl(e)).toLowerCase();}
  function get(form,keys,type){var els=form.querySelectorAll('input,textarea');
    for(var i=0;i<els.length;i++){var e=els[i];if(type&&e.type!==type&&e.tagName.toLowerCase()!==type)continue;
      var h=hay(e);for(var k=0;k<keys.length;k++){if(h.indexOf(keys[k])>-1)return (e.value||'').trim();}}
    return '';}
  function wire(form){
    if(form.__reiMailto)return; if(!form.querySelector('textarea'))return;  /* only contact/question forms */
    form.__reiMailto=1;
    form.addEventListener('submit',function(ev){
      var email=get(form,['email'])||get(form,[''],'email');
      var msg=get(form,['message','comment','inquiry'])||get(form,[''],'textarea');
      var first=get(form,['first','fname','given']), last=get(form,['last','lname','family']);
      var name=((first+' '+last).trim())||get(form,['full name','your name','name']);
      ev.preventDefault(); ev.stopImmediatePropagation();
      var subj='Website inquiry'+(name?(' from '+name):'');
      var body='Name: '+(name||'')+'\nEmail: '+(email||'')+'\n\nMessage:\n'+(msg||'');
      window.location.href='mailto:'+TO+'?subject='+encodeURIComponent(subj)+'&body='+encodeURIComponent(body);
    },true);
    form.setAttribute('data-rei-mailto','1');
  }
  function run(){var f=document.querySelectorAll('form');for(var i=0;i<f.length;i++)wire(f[i]);}
  if(document.readyState!=='loading')run();else document.addEventListener('DOMContentLoaded',run);
  [600,1500,3000].forEach(function(ms){setTimeout(run,ms);});
})();
