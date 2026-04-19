// crop-chatbot.js
// Simple client-side crop assistant demo restricted to crop-related topics.
(function(){
  if (window.__cropChatbotLoaded) return; window.__cropChatbotLoaded = true;

  function el(tag, cls, html){ const e = document.createElement(tag); if(cls) e.className = cls; if(html!==undefined) e.innerHTML = html; return e; }

  function sanitize(text){ return String(text).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

  // Build widget with robot icon button
  const btn = el('button','ccb-button','');
  btn.setAttribute('aria-label','Open crop assistant');
  btn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">'
    + '<rect x="3" y="7" width="18" height="11" rx="2" fill="#F8FAFC" stroke="#0f172a" stroke-opacity="0.06"/>'
    + '<rect x="8" y="3" width="8" height="5" rx="1" fill="#0f172a" opacity="0.06"/>'
    + '<circle cx="9" cy="12" r="1" fill="#0f172a"/>'
    + '<circle cx="15" cy="12" r="1" fill="#0f172a"/>'
    + '<path d="M9 16h6" stroke="#0f172a" stroke-width="1.2" stroke-linecap="round"/>'
    + '</svg>';
  const panel = el('div','ccb-panel'); panel.style.display = 'none';
  const header = el('div','ccb-header','<strong>Crop Assistant</strong><button class="ccb-close" aria-label="Close">✕</button>');
  const convo = el('div','ccb-convo','');
  const form = el('form','ccb-form','<input class="ccb-input" placeholder="Ask about watering, pests, harvest..." autocomplete="off"><button class="ccb-send">Send</button>');

  panel.appendChild(header); panel.appendChild(convo); panel.appendChild(form);
  document.body.appendChild(btn); document.body.appendChild(panel);

  // Read crop context
  const cropEl = document.getElementById('crop-data');
  const cropName = cropEl ? cropEl.dataset.name || '' : '';
  const cropType = cropEl ? cropEl.dataset.type || '' : '';

  function addMessage(who, text){
    const m = el('div','ccb-msg '+(who==='user'?'ccb-user':'ccb-bot'));
    m.innerHTML = '<div class="ccb-msg-inner">'+sanitize(text)+'</div>';
    convo.appendChild(m);
    convo.scrollTop = convo.scrollHeight;
  }

  function botReply(message){
    const msg = message.toLowerCase();
    // simple keyword-based replies (demo). Keep replies crop-focused.
    if(/water|irrigat|dry|thirst/.test(msg)){
      return `For ${cropName||'this crop'}, water according to stage: seedlings need light, frequent watering; established plants need deeper, less frequent watering. Monitor soil moisture and avoid overwatering.`;
    }
    if(/pest|aphid|mite|insect|beetle|weevil/.test(msg)){
      return `Common pests: check leaves for holes or sticky residue. Start with mechanical removal and neem oil for organic control. Identify the pest for targeted treatment.`;
    }
    if(/disease|mold|fungus|blight|rot/.test(msg)){
      return `Look for discoloration, spots, or wilting. Remove affected tissue, improve airflow, avoid overhead watering, and consider appropriate fungicide after identification.`;
    }
    if(/fertil|nutrient|nitrogen|phosphor|potassium|nute/.test(msg)){
      return `Use balanced fertilizers according to label rates. For leaf growth favor higher nitrogen, for flowering use higher phosphorus. Always soil test if unsure.`;
    }
    if(/harvest|ripe|ready/.test(msg)){
      return `Harvest when fruits reach size and color typical for the variety. For many crops, morning harvest preserves quality. Check variety-specific guidelines.`;
    }
    if(/plant|sow|seed|spacing|depth/.test(msg)){
      return `Plant seeds at recommended depth and spacing for the variety. Keep soil warm and moist during germination; thin seedlings to proper spacing.`;
    }
    // out of scope guard
    if(/(how|what|why|when|where|recommend|suggest|advice|tips)/.test(msg)){
      return `I can help with crop topics like watering, pests, diseases, fertilization, planting, and harvest. Try asking about one of those.`;
    }
    return `Sorry — I only answer crop-related questions. Try asking about watering, pests, disease, fertilization, planting, or harvest.`;
  }

  // open/close
  btn.addEventListener('click', function(){ panel.style.display = 'flex'; btn.style.display = 'none'; document.querySelector('.ccb-input').focus(); });
  panel.querySelector('.ccb-close').addEventListener('click', function(){ panel.style.display = 'none'; btn.style.display = 'block'; });

  // handle send
  form.addEventListener('submit', function(e){ e.preventDefault(); const input = form.querySelector('.ccb-input'); const v = input.value.trim(); if(!v) return; addMessage('user', v); input.value = '';
    // simulate thinking
    addMessage('bot','Typing...');
    setTimeout(function(){ // replace last bot "Typing..."
      const last = convo.querySelectorAll('.ccb-bot');
      if(last.length) last[last.length-1].remove();
      addMessage('bot', botReply(v));
    }, 700 + Math.random()*600);
  });

  // add initial greeting with crop context
  setTimeout(function(){ if(cropName) addMessage('bot', `Hello — I'm a crop assistant for ${cropName}. Ask me about watering, pests, diseases, fertilization, planting, or harvest.`); else addMessage('bot', 'Hello — I can help with crop-related questions. Ask about watering, pests, diseases, fertilization, planting, or harvest.'); }, 400);

})();
