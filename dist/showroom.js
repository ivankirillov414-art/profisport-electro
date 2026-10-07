(()=>{
const area=document.querySelector('.image-area'),object=document.querySelector('.product-object'),img=document.querySelector('#model-image'),hotspots=document.querySelector('#hotspots');
// A display-only SVG compositing filter: the original supplier files remain untouched.
const makeFilter=(id,bright)=>`<filter id="${id}" color-interpolation-filters="sRGB"><feComponentTransfer in="SourceGraphic" result="photo"><feFuncR type="linear" slope="${bright}"/><feFuncG type="linear" slope="${bright}"/><feFuncB type="linear" slope="${bright}"/></feComponentTransfer><feColorMatrix in="photo" type="matrix" values="0 0 0 0 1 0 0 0 0 1 0 0 0 0 1 -13.667 -13.667 -13.667 0 40" result="mask"/><feMorphology in="mask" operator="erode" radius="${bright>1?1.0:0}" result="clean"/><feComposite in="photo" in2="clean" operator="arithmetic" k1="0" k2="1" k3="1" k4="-1"/></filter>`;
document.body.insertAdjacentHTML('beforeend',`<svg width="0" height="0" aria-hidden="true" style="position:absolute"><defs>${makeFilter('photo-composite',1.058)}${makeFilter('photo-composite-white',1)}</defs></svg>`);
const layout={g2:{ratio:'0.72',scale:2.35,points:[['battery','Аккумулятор',57,79],['brakes','Тормоза',29,87],['wheels','Колёса',76,78]]},m4:{ratio:'0.9',scale:2.05,points:[['battery','Аккумулятор',52,75],['brakes','Тормоза',23,84],['wheels','Колёса',78,70]]},xt:{ratio:'1.27',scale:1.18,points:[['battery','Аккумулятор',61,53],['brakes','Тормоза',26,71],['wheels','Колёса',80,69]]},v8:{ratio:'1.02',scale:1.18,points:[['battery','Аккумулятор',61,61],['brakes','Тормоза',27,74],['wheels','Колёса',75,74]]},m1:{ratio:'0.88',scale:1.13,points:[['battery','Аккумулятор',60,64],['brakes','Тормоза',26,76],['wheels','Колёса',77,77]]},c1:{ratio:'1.05',scale:2.1,points:[['battery','Аккумулятор',52,78],['brakes','Тормоза',25,83],['wheels','Колёса',74,79]]},wish:{ratio:'1.1',scale:2.05,points:[['battery','Аккумулятор',49,55],['brakes','Тормоза',26,74],['wheels','Колёса',80,70]]}};

const extraLayout={
 hualu:{ratio:'1.05',scale:1.02,points:[['battery','Аккумулятор',60,60],['brakes','Тормоза',23,73],['wheels','Колёса',77,73]]},
 acid_e8:{ratio:'1.10',scale:1.04,points:[['battery','Аккумулятор',35,65],['brakes','Тормоза',25,80],['wheels','Колёса',77,80]]},
 acid_force:{ratio:'1.2',scale:1.02,points:[['battery','Аккумулятор',56,52],['brakes','Тормоза',23,72],['wheels','Колёса',77,72]]},
 gestalt001:{ratio:'0.95',scale:1.02,points:[['battery','Аккумулятор',50,72],['brakes','Тормоза',22,78],['wheels','Колёса',75,78]]},
 gestalt002:{ratio:'1.12',scale:1.02,points:[['battery','Аккумулятор',49,73],['brakes','Тормоза',24,80],['wheels','Колёса',78,80]]},
 gestaltabs01:{ratio:'1.12',scale:1.02,points:[['battery','Аккумулятор',49,46],['brakes','Тормоза',22,71],['wheels','Колёса',80,71]]},
 gestaltabs02:{ratio:'1.12',scale:1.02,points:[['battery','Аккумулятор',52,59],['brakes','Тормоза',23,74],['wheels','Колёса',79,74]]},
 gestaltabs03:{ratio:'1.12',scale:1.02,points:[['battery','Аккумулятор',56,56],['brakes','Тормоза',23,74],['wheels','Колёса',79,74]]}
};
Object.assign(layout,extraLayout);

function update(){const id=new URLSearchParams(location.search).get('model')||'g2',cfg=layout[id]||{ratio:"1.1",scale:1,points:[]};object.style.setProperty('--photo-ratio',cfg.ratio);object.style.setProperty('--photo-scale',cfg.scale);object.dataset.model=id;requestAnimationFrame(cropPhoto);hotspots.innerHTML=cfg.points.map(([key,label,x,y],i)=>`<button class="hotspot ${i===1?'left-label':''}" style="--x:${x}%;--y:${y}%" data-node="${key}" aria-label="${label}"><span class="hotspot-dot"></span><span class="hotspot-label">${label}</span></button>`).join('');hotspots.querySelectorAll('button').forEach(b=>b.onclick=()=>{document.querySelector(`[data-component="${b.dataset.node}"]`).click();hotspots.querySelectorAll('button').forEach(x=>x.classList.toggle('active',x===b));});}
function cropPhoto(){if(object.dataset.model!=='xt'){img.style.clipPath='';return;}const r=img.getBoundingClientRect(),contentHeight=Math.min(r.height,r.width*480/719),bottom=(r.height-contentHeight)/2+contentHeight*.923;img.style.clipPath=`inset(0 0 ${100-bottom/r.height*100}% 0)`;}
img.addEventListener('load',cropPhoto);new ResizeObserver(cropPhoto).observe(object);
new MutationObserver(update).observe(img,{attributes:true,attributeFilter:['src']});update();
})();
