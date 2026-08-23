<?php /** documentation/inc/foot.php — fermeture + scripts partagés. */ ?>
  <?php if (!empty($docPrev) || !empty($docNext)): /* Précédent / Suivant auto (généré depuis $docNav) */ ?>
  <nav class="doc-next" aria-label="Pagination de la documentation">
    <?php if (!empty($docPrev)): ?>
      <a href="<?= $h($docPrev['path']) ?>"><div class="k">← Précédent</div><div class="v"><?= ($docPrev['icon'] ?? '') ?> <?= $docPrev['label'] ?></div></a>
    <?php else: ?><span></span><?php endif; ?>
    <?php if (!empty($docNext)): ?>
      <a href="<?= $h($docNext['path']) ?>"><div class="k">Suivant →</div><div class="v"><?= ($docNext['icon'] ?? '') ?> <?= $docNext['label'] ?></div></a>
    <?php else: ?><span></span><?php endif; ?>
  </nav>
  <?php endif; ?>
  </main>
</div>

<script>
(function(){
  'use strict';

  /* Thème clair/sombre */
  var tg=document.getElementById('themeToggle');
  function icon(){var d=document.documentElement.getAttribute('data-theme')==='dark';var i=document.getElementById('ti');if(i)i.textContent=d?'☀️':'🌙';}
  tg&&tg.addEventListener('click',function(){var d=document.documentElement.getAttribute('data-theme')==='dark';var n=d?'light':'dark';document.documentElement.setAttribute('data-theme',n);try{localStorage.setItem('gnp-theme',n);}catch(e){}icon();});
  icon();

  /* Menu mobile */
  var b=document.getElementById('burger'),s=document.getElementById('side');
  b&&b.addEventListener('click',function(){s.classList.toggle('open');});
  s&&s.querySelectorAll('a[href^="#"]').forEach(function(a){a.addEventListener('click',function(){s.classList.remove('open');});});

  /* Catégories repliables (accordéon léger : on peut ouvrir/fermer chacune) */
  document.querySelectorAll('.doc-cat-title').forEach(function(t){
    t.addEventListener('click',function(){
      var block=t.closest('.doc-cat-block');
      var open=block.classList.toggle('open');
      t.setAttribute('aria-expanded', open?'true':'false');
    });
  });

  /* Bouton « Copier » sur chaque bloc de code */
  document.querySelectorAll('.doc-main pre').forEach(function(pre){
    var btn=document.createElement('button');
    btn.className='copy-btn'; btn.type='button'; btn.textContent='📋 Copier';
    btn.addEventListener('click',function(){
      var code=pre.querySelector('code'); var txt=code?code.innerText:pre.innerText;
      navigator.clipboard.writeText(txt).then(function(){
        btn.textContent='✓ Copié'; btn.classList.add('done');
        setTimeout(function(){btn.textContent='📋 Copier';btn.classList.remove('done');},1600);
      });
    });
    pre.appendChild(btn);
  });

  /* Recherche : filtre les pages + sous-sections de la sidebar */
  var search=document.getElementById('docSearch'),noRes=document.getElementById('docNoResult');
  if(search){
    var norm=function(t){return (t||'').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g,'');};
    search.addEventListener('input',function(){
      var q=norm(search.value.trim());
      var anyVisible=false;
      document.querySelectorAll('.doc-cat-block').forEach(function(block){
        var catVisible=false;
        block.querySelectorAll('.doc-pg').forEach(function(pg){
          var pageMatch = !q || norm(pg.getAttribute('data-label')).indexOf(q)!==-1;
          // sous-sections éventuelles juste après ce lien
          var sub=pg.nextElementSibling && pg.nextElementSibling.classList.contains('doc-sub')?pg.nextElementSibling:null;
          var subAnyMatch=false;
          if(sub){
            sub.querySelectorAll('a').forEach(function(sa){
              var m=!q || norm(sa.getAttribute('data-label')).indexOf(q)!==-1 || pageMatch;
              sa.style.display=m?'':'none'; if(m)subAnyMatch=true;
            });
          }
          var show=pageMatch||subAnyMatch;
          pg.style.display=show?'':'none';
          if(sub)sub.style.display=show?'':'none';
          if(show)catVisible=true;
        });
        var title=block.querySelector('.doc-cat-title');
        if(title)title.style.display=catVisible?'':'none';
        block.style.display=catVisible?'':'none';
        // Pendant une recherche : ouvrir les catégories qui matchent.
        // Recherche vide : revenir à l'état par défaut (seule la catégorie active ouverte).
        if(q){ block.classList.toggle('open', catVisible); }
        else { block.classList.toggle('open', block.classList.contains('is-active-cat')); }
        if(catVisible)anyVisible=true;
      });
      if(noRes)noRes.style.display=anyVisible?'none':'block';
    });
    // Raccourci "/" pour focus la recherche
    document.addEventListener('keydown',function(e){
      if(e.key==='/'&&document.activeElement!==search){e.preventDefault();search.focus();}
    });
  }

  /* Surlignage de la section active au scroll */
  var links=Array.prototype.slice.call(document.querySelectorAll('.doc-sub a[data-sec]'));
  var map={};links.forEach(function(a){var id=a.getAttribute('data-sec');var el=document.getElementById(id);if(el)map[id]=a;});
  if(window.IntersectionObserver&&links.length){
    var io=new IntersectionObserver(function(es){es.forEach(function(e){if(e.isIntersecting){links.forEach(function(x){x.classList.remove('active');});var a=map[e.target.id];if(a)a.classList.add('active');}});},{rootMargin:'-70px 0px -70% 0px'});
    Object.keys(map).forEach(function(id){io.observe(document.getElementById(id));});
  }
})();
</script>
</body>
</html>
