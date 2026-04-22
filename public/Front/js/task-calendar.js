// task-calendar.js
// Calendar widget that reads scheduled dates from .task-card[data-due-date]
(function(){
  if(window.__taskCalendarLoaded) return; window.__taskCalendarLoaded = true;

  function qsa(sel){ return Array.from(document.querySelectorAll(sel)); }
  function qs(sel, root=document){ return root.querySelector(sel); }

  function collect(){
    const map = {};
    qsa('.task-card').forEach(card=>{
      const date = card.dataset.dueDate; if(!date) return;
      const name = card.dataset.name || ('Task #' + (card.dataset.id||''));
      const url = card.dataset.taskUrl || '';
      const id = card.dataset.id || '';
      if(!map[date]) map[date]=[];
      map[date].push({name:name, url:url, id:id});
    });
    return map;
  }

  function formatMonthYear(d){ return d.toLocaleString(undefined,{month:'long', year:'numeric'}); }

  function buildModal(){
    const container = document.getElementById('taskCalendarModal');
    const modal = document.createElement('div'); modal.className='tc-modal';
    modal.innerHTML = `
      <div class="tc-dialog">
        <button class="tc-close">✕</button>
        <div class="tc-header"><button class="tc-prev">‹</button><div class="tc-month"></div><button class="tc-next">›</button></div>
        <div class="tc-body"><div class="tc-calendar"></div><div class="tc-list"><h4>Scheduled Tasks</h4><div class="tc-events"></div></div></div>
      </div>`;
    container.appendChild(modal);
    return modal;
  }

  function render(modal, events, y, m){
    const first = new Date(y,m,1);
    qs('.tc-month',modal).textContent = formatMonthYear(first);
    const cal = qs('.tc-calendar',modal); cal.innerHTML='';
    const start = new Date(y,m,1).getDay();
    const days = new Date(y,m+1,0).getDate();
    const head = document.createElement('div'); head.className='tc-weekdays'; ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'].forEach(w=>{const e=document.createElement('div');e.className='tc-weekday';e.textContent=w;head.appendChild(e);}); cal.appendChild(head);
    const grid = document.createElement('div'); grid.className='tc-grid';
    for(let i=0;i<start;i++){ const c=document.createElement('div'); c.className='tc-cell empty'; grid.appendChild(c); }
    for(let d=1; d<=days; d++){
      const key = `${y}-${String(m+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
      const cell = document.createElement('div'); cell.className='tc-cell';
      const num = document.createElement('div'); num.className='tc-daynum'; num.textContent=d; cell.appendChild(num);
      if(events[key]){
        const dot = document.createElement('div'); dot.className='tc-dot'; cell.appendChild(dot);
        cell.addEventListener('click', ()=> showEvents(key, modal, events));
      }
      grid.appendChild(cell);
    }
    cal.appendChild(grid);
    qs('.tc-events',modal).innerHTML = '<p class="tc-empty">Select a date to view scheduled tasks</p>';
  }

  function showEvents(key, modal, events){
    const list = qs('.tc-events',modal); list.innerHTML='';
    const arr = events[key]||[]; if(arr.length===0){ list.innerHTML='<p class="tc-empty">No tasks</p>'; return; }
    arr.forEach(it=>{
      const row = document.createElement('div'); row.className='tc-event';
      if(it.url){ row.innerHTML = `<a class="tc-evt-link" href="${it.url}"><div class="tc-ename">${it.name}</div><div class="tc-eid">#${it.id}</div></a>`; }
      else { row.innerHTML = `<div class="tc-ename">${it.name}</div><div class="tc-eid">#${it.id}</div>`; }
      list.appendChild(row);
    });
  }

  function open(){
    const container = document.getElementById('taskCalendarModal'); container.style.display='flex'; container.innerHTML='';
    const modal = buildModal();
    const events = collect();
    let now = new Date(); let y=now.getFullYear(), m=now.getMonth();
    render(modal, events, y, m);
    qs('.tc-prev',modal).addEventListener('click', ()=>{ m--; if(m<0){m=11;y--;} render(modal, events, y, m);});
    qs('.tc-next',modal).addEventListener('click', ()=>{ m++; if(m>11){m=0;y++;} render(modal, events, y, m);});
    qs('.tc-close',modal).addEventListener('click', ()=>{ container.style.display='none'; container.innerHTML=''; });
    container.addEventListener('click', (e)=>{ if(e.target===container){ container.style.display='none'; container.innerHTML=''; } });
  }

  const btn = document.getElementById('taskCalendarBtn'); if(btn) btn.addEventListener('click', open);

})();
