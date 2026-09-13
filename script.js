const questions = [
  ['Как называется самая большая планета Солнечной системы?', ['Марс', 'Юпитер', 'Сатурн', 'Венера'], 1],
  ['Какой океан является самым глубоким?', ['Атлантический', 'Индийский', 'Тихий', 'Северный Ледовитый'], 2],
  ['Сколько сторон у правильного шестиугольника?', ['Шесть', 'Пять', 'Восемь', 'Семь'], 0],
  ['Кто написал роман «Мастер и Маргарита»?', ['Чехов', 'Булгаков', 'Достоевский', 'Толстой'], 1],
  ['Какой химический символ у золота?', ['Ag', 'Au', 'Fe', 'Go'], 1],
  ['Столица Австралии — это…', ['Сидней', 'Мельбурн', 'Канберра', 'Перт'], 2],
  ['Сколько минут в двух с половиной часах?', ['120', '135', '150', '180'], 2],
  ['Как называется прибор для измерения температуры?', ['Барометр', 'Термометр', 'Гигрометр', 'Спидометр'], 1],
  ['В каком веке началась Первая мировая война?', ['XVIII', 'XIX', 'XX', 'XXI'], 2],
  ['Какой язык является официальным в Бразилии?', ['Испанский', 'Португальский', 'Французский', 'Английский'], 1]
];
const levels = [50, 100, 250, 500, 1000];
let state = {time: 60, bank: 0, chain: 0, index: 0, playing: false, sound: true, timer: null};
const $ = id => document.getElementById(id);
const els = {answers: $('answers'), text: $('questionText'), count: $('questionCount'), time: $('timerValue'), ring: $('timerCircle'), bank: $('bankValue'), ladder: $('ladder'), message: $('message'), round: $('roundNumber')};
function render() { const q = questions[state.index % questions.length]; els.text.textContent = q[0]; els.count.textContent = String(state.index + 1).padStart(2, '0'); els.answers.innerHTML = q[1].map((a,i) => `<button class="answer" data-answer="${i}"><b>0${i+1}</b>${a}</button>`).join(''); els.time.textContent = state.time; els.ring.style.strokeDashoffset = 327 * (1 - state.time / 60); [...els.ladder.children].forEach((el,i)=>el.classList.toggle('active', i === Math.min(state.chain, 4))); }
function beep(ok) { if (!state.sound) return; const c = new (window.AudioContext || window.webkitAudioContext)(), o = c.createOscillator(), g = c.createGain(); o.frequency.value = ok ? 620 : 180; g.gain.setValueAtTime(.05, c.currentTime); g.gain.exponentialRampToValueAtTime(.001,c.currentTime+.15); o.connect(g).connect(c.destination); o.start();o.stop(c.currentTime+.15); }
function answer(i) { if (!state.playing) return; const q = questions[state.index % questions.length], buttons = [...els.answers.children], correct = i === q[2]; buttons.forEach(b=>b.disabled=true); buttons[q[2]].classList.add('correct'); if (!correct) buttons[i].classList.add('wrong'); beep(correct); if (correct) { state.chain++; els.message.textContent = `Верно. В цепочке ${levels[Math.min(state.chain - 1, 4)]} ₽.`; } else { state.chain = 0; els.message.textContent = 'Неверно. Цепочка сброшена — продолжайте!'; } setTimeout(() => {state.index++; render();}, 750); }
function bank() { if (!state.playing || !state.chain) return; const sum = levels[Math.min(state.chain - 1, 4)]; state.bank += sum; state.chain = 0; els.bank.textContent = `${state.bank.toLocaleString('ru-RU')} ₽`; els.message.textContent = `${sum} ₽ в банке. Стройте новую цепочку!`; beep(true); render(); }
function finish() { state.playing=false; clearInterval(state.timer); $('finalScore').textContent = `${state.bank.toLocaleString('ru-RU')} ₽`; $('resultBackdrop').hidden=false; }
function begin() { state={...state,time:60,bank:0,chain:0,index:0,playing:true}; els.bank.textContent='0 ₽'; $('modalBackdrop').hidden=true; $('resultBackdrop').hidden=true; clearInterval(state.timer); state.timer=setInterval(()=>{state.time--;render();if(state.time<=0) finish();},1000); render(); }
els.answers.addEventListener('click', e => {const b=e.target.closest('.answer');if(b) answer(+b.dataset.answer)}); $('bankButton').onclick=bank; $('rulesButton').onclick=()=>$('modalBackdrop').hidden=false; $('closeModal').onclick=()=>$('modalBackdrop').hidden=true; $('startButton').onclick=begin; $('playAgain').onclick=begin;
$('soundButton').onclick=()=>{state.sound=!state.sound;$('soundButton').textContent=`◖ ЗВУК: ${state.sound?'ВКЛ':'ВЫКЛ'}`};
window.addEventListener('keydown',e=>{if(e.key==='Enter')bank();if(/^[1-4]$/.test(e.key))answer(+e.key-1)});
function scores(data) {$('scores').innerHTML=data.map((s,i)=>`<div><span>${i+1}. ${s.name}</span><b>${Number(s.score).toLocaleString('ru-RU')} ₽</b></div>`).join('') || '<div><span>Пока нет результатов</span></div>'}
$('saveScore').onclick=async()=>{const b=$('saveScore');b.disabled=true;b.textContent='СОХРАНЯЕМ…';try{const r=await fetch('./',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({name:$('playerName').value,score:state.bank})});const data=await r.json();scores(data.scores);b.textContent='СОХРАНЕНО';}catch{b.textContent='НЕ УДАЛОСЬ СОХРАНИТЬ';b.disabled=false}};
scores(window.initialScores || []); render(); $('modalBackdrop').hidden=false;
