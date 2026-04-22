// harvest-calendar.js
// Lightweight calendar that reads expected harvest dates from crop cards
(function(){
  if(window.__harvestCalendarLoaded) return; window.__harvestCalendarLoaded = true;

  function qs(sel,root=document){return root.querySelector(sel);}
  function qsa(sel,root=document){return Array.from((root||document).querySelectorAll(sel));}

  // Gather harvest events from crop cards
  function collectEvents(){
    const events = {}; // yyyy-mm-dd -> [{name, cropId}]
    qsa('.crop-card').forEach(card=>{
      const date = card.dataset.expectedHarvest; // format Y-m-d
      if(!date) return;
      const name = card.dataset.name || card.dataset.id || 'Crop';
      const id = card.dataset.id || '';
      if(!events[date]) events[date]=[];
      events[date].push({name:name, id:id});
    });
    return events;
  }

  function formatMonthYear(date){
    return date.toLocaleString(undefined,{month:'long', year:'numeric'});
  }

  function buildModal(){
    const modal = document.createElement('div'); modal.id='hc-modal'; modal.className='hc-modal';
    modal.innerHTML = `
      <div class="hc-dialog">
        <button class="hc-close" aria-label="Close">✕</button>
        <div class="hc-header">
          <button class="hc-prev">‹</button>
          <div class="hc-month"></div>
          <button class="hc-next">›</button>
        </div>
        <div class="hc-body">
          <div class="hc-calendar"></div>
          <div class="hc-list"><h4>Harvests</h4><div class="hc-events"></div></div>
        </div>
      </div>
    `;
    document.getElementById('harvestCalendarModal').appendChild(modal);
    return modal;
  }

  function renderCalendar(modal, eventsMap, year, month){
    const first = new Date(year,month,1);
    const monthDisplay = qs('.hc-month',modal);
    monthDisplay.textContent = formatMonthYear(first);
    const calendar = qs('.hc-calendar',modal);
    calendar.innerHTML = '';

    const startWeekDay = new Date(year,month,1).getDay();
    const daysInMonth = new Date(year,month+1,0).getDate();

    // weekday headers
    const weekNames = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    const head = document.createElement('div'); head.className='hc-weekdays';
    weekNames.forEach(w=>{ const d=document.createElement('div'); d.className='hc-weekday'; d.textContent=w; head.appendChild(d); });
    calendar.appendChild(head);

    const grid = document.createElement('div'); grid.className='hc-grid';

    // leading blanks
    for(let i=0;i<startWeekDay;i++){ const c=document.createElement('div'); c.className='hc-cell empty'; grid.appendChild(c); }

    for(let d=1; d<=daysInMonth; d++){
      const dateKey = `${year}-${String(month+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
      const cell = document.createElement('div'); cell.className='hc-cell';
      const num = document.createElement('div'); num.className='hc-daynum'; num.textContent=d; cell.appendChild(num);
      if(eventsMap[dateKey]){
        const dot = document.createElement('div'); dot.className='hc-dot'; dot.title = eventsMap[dateKey].map(e=>e.name).join(', ');
        cell.appendChild(dot);
        cell.addEventListener('click', ()=> showEventsFor(dateKey, modal, eventsMap));
      }
      grid.appendChild(cell);
    }

    calendar.appendChild(grid);

    // clear list
    qs('.hc-events',modal).innerHTML = '<p class="hc-empty">Select a date to view harvests</p>';
  }

  function showEventsFor(dateKey, modal, eventsMap){
    const list = qs('.hc-events',modal);
    list.innerHTML = '';
    const arr = eventsMap[dateKey] || [];
    if(arr.length===0){ list.innerHTML = '<p class="hc-empty">No harvests</p>'; return; }
    arr.forEach(ev=>{
      const item = document.createElement('div'); item.className='hc-event';
      item.innerHTML = `<div class="hc-ename">${ev.name}</div><div class="hc-eid">#${ev.id}</div>`;
      list.appendChild(item);
    });
  }

  // setup and events
  function openCalendar(){
    const container = document.getElementById('harvestCalendarModal');
    container.style.display = 'flex';
    container.innerHTML=''; // ensure empty
    const modal = buildModal();
    const events = collectEvents();
    const today = new Date(); let year = today.getFullYear(), month = today.getMonth();
    renderCalendar(modal, events, year, month);

    qs('.hc-prev',modal).addEventListener('click', ()=>{ month--; if(month<0){ month=11; year--; } renderCalendar(modal, events, year, month); });
    qs('.hc-next',modal).addEventListener('click', ()=>{ month++; if(month>11){ month=0; year++; } renderCalendar(modal, events, year, month); });
    qs('.hc-close',modal).addEventListener('click', ()=>{ container.style.display='none'; container.innerHTML=''; });
    container.addEventListener('click', (e)=>{ if(e.target===container){ container.style.display='none'; container.innerHTML=''; }});
  }

  document.getElementById('harvestCalendarBtn').addEventListener('click', openCalendar);

})();
