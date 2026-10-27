// Cartilla · portada. Sin este archivo todo se lee; aquí viven el agua que sube, la tarjeta viva y el interruptor.
(() => {
  const base = document.querySelector('meta[name="base"]')?.content || '';
  const csrf = document.querySelector('meta[name="csrf"]')?.content || '';
  const reducido = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // ---- La ola sube con el scroll y el titular se hunde, como lo que no se repasa ----
  // El nivel no salta con la rueda: persigue al scroll con interpolación, así el movimiento es continuo.
  const ola = document.querySelector('[data-ola]');
  if (ola && !reducido) {
    const escena = ola.querySelector('.ola__escena');
    let objetivo = 0;
    let actual = 0;
    let corriendo = false;
    const medir = () => {
      const total = ola.offsetHeight - window.innerHeight;
      const p = Math.min(1, Math.max(0, -ola.getBoundingClientRect().top / Math.max(total, 1)));
      objetivo = Math.min(1, p / 0.8);
    };
    const paso = () => {
      actual += (objetivo - actual) * 0.09;
      if (Math.abs(objetivo - actual) < 0.0005) actual = objetivo;
      escena.style.setProperty('--nivel', actual.toFixed(4));
      if (actual !== objetivo) requestAnimationFrame(paso); else corriendo = false;
    };
    const mover = () => {
      medir();
      if (!corriendo) { corriendo = true; requestAnimationFrame(paso); }
    };
    window.addEventListener('scroll', mover, { passive: true });
    window.addEventListener('resize', mover);
    mover();
  }

  // ---- Entradas escalonadas de las partes de cada sección ----
  const partes = document.querySelectorAll(
    '.campo-color, .interruptor-sec__texto > *, .rasgo, .hecho, .boleto, .dudas details, .eleccion__centro'
  );
  if ('IntersectionObserver' in window && !reducido) {
    const io = new IntersectionObserver((regs) => regs.forEach((r) => {
      if (r.isIntersecting) {
        r.target.classList.add('visto');
        io.unobserve(r.target);
        // Al terminar la entrada, el elemento recupera sus transiciones propias (hover, apertura).
        setTimeout(() => r.target.classList.add('listo'), 1300);
      }
    }), { rootMargin: '0px 0px -8% 0px' });
    partes.forEach((el) => {
      const hermanos = [...el.parentElement.children];
      el.style.setProperty('--d', `${Math.min(hermanos.indexOf(el), 6) * 90}ms`);
      el.classList.add('entra');
      io.observe(el);
    });
  }

  // ---- Tarjeta viva: una pregunta real, corregida por el servidor ----
  const ficha = document.querySelector('[data-ficha-viva]');
  const repaso = document.querySelector('[data-ficha-repaso]');
  if (ficha) {
    const hueco = ficha.querySelector('[data-hueco]');
    const boton = ficha.querySelector('.ficha__boton');
    const estado = repaso.querySelector('[data-estado]');
    const cajas = repaso.querySelectorAll('[data-caja]');
    ficha.addEventListener('change', (ev) => {
      if (ev.target.name === 'respuesta') { hueco.textContent = ev.target.value; hueco.className = 'ficha__hueco'; }
    });
    ficha.addEventListener('submit', async (ev) => {
      ev.preventDefault();
      if (boton.dataset.listo) {
        ficha.reset();
        ficha.querySelectorAll('input').forEach((i) => { i.disabled = false; });
        hueco.textContent = ''; hueco.className = 'ficha__hueco';
        boton.textContent = 'Comprobar'; delete boton.dataset.listo;
        return;
      }
      const elegida = ficha.querySelector('input[name="respuesta"]:checked');
      if (!elegida) { estado.innerHTML = '<b>Falta elegir</b>Toca una de las palabras de la tarjeta rosada.'; return; }
      boton.disabled = true; boton.textContent = 'Comprobando…';
      try {
        const res = await fetch(`${base}/api/muestra`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
          body: JSON.stringify({ id: Number(ficha.dataset.id), respuesta: elegida.value }),
        });
        const d = await res.json();
        if (!res.ok) throw new Error(d.error || 'Error');
        ficha.querySelectorAll('input').forEach((i) => { i.disabled = true; });
        if (d.correcta) {
          hueco.textContent = elegida.value;
        } else {
          hueco.innerHTML = '<s></s> ';
          hueco.querySelector('s').textContent = elegida.value;
          hueco.append(d.solucion);
        }
        hueco.className = `ficha__hueco ${d.correcta ? 'bien' : 'mal'}`;
        cajas.forEach((c) => c.classList.toggle('activa', d.correcta ? c.dataset.caja === '2' : c.dataset.caja === '1'));
        estado.innerHTML = d.correcta
          ? '<b>+2 XP</b>Acertaste a la primera. Si la fallas después, vuelve a tu repaso.'
          : `<b>Vuelve mañana</b>La respuesta era «${d.solucion}». Entra a la caja 1 y vuelve en 1 día; si aciertas, en 2, luego 4, 7 y 15.`;
        boton.textContent = 'Probar otra vez'; boton.dataset.listo = '1';
      } catch (e) {
        estado.innerHTML = '<b>Sin conexión</b>No pudimos revisar tu respuesta. Vuelve a intentarlo.';
        boton.textContent = 'Comprobar';
      } finally {
        boton.disabled = false;
      }
    });
  }

  // ---- El interruptor: sin repaso se hunde, con repaso se queda ----
  const sec = document.querySelector('.interruptor-sec');
  if (sec) {
    const ruta = sec.querySelector('[data-curva]');
    const palabra = sec.querySelector('[data-palabra]');
    const cifra = sec.querySelector('[data-cifra]');
    const botones = sec.querySelectorAll('[role="radio"]');
    // Ebbinghaus (1885): b = 184 / ((log10 t)^1.25 + 1.84), t en minutos.
    const ebb = (dias) => (dias <= 0 ? 1 : Math.min(1, 1.84 / (Math.pow(Math.log10(dias * 1440), 1.25) + 1.84)));
    // Modelo simple con repaso: cada repaso devuelve el recuerdo y alarga 2,5 veces la estabilidad.
    const conRepaso = (dias) => {
      const repasos = [1, 2, 4, 7];
      let ultimo = 0; let estabilidad = 1;
      repasos.forEach((r) => { if (dias >= r) { ultimo = r; estabilidad *= 2.5; } });
      return Math.exp(-(dias - ultimo) / estabilidad);
    };
    const curva = (f) => {
      const pts = [];
      for (let i = 0; i <= 60; i++) {
        const dia = (i / 60) * 14;
        pts.push(`${(i / 60) * 300},${(118 - f(dia) * 114).toFixed(1)}`);
      }
      return `M${pts.join(' L')}`;
    };
    const fijar = (modo) => {
      sec.dataset.modo = modo;
      botones.forEach((b) => b.setAttribute('aria-checked', b.dataset.valor === modo ? 'true' : 'false'));
      const f = modo === 'con' ? conRepaso : ebb;
      ruta.setAttribute('d', curva(f));
      palabra.textContent = modo === 'con' ? 'se queda' : 'se hunde';
      sec.querySelector('h2').firstChild.textContent = modo === 'con' ? 'Con repaso, ' : 'Sin repaso, ';
      cifra.textContent = `${Math.round(f(14) * 100)} %`;
    };
    botones.forEach((b) => {
      b.addEventListener('click', () => fijar(b.dataset.valor));
      b.addEventListener('keydown', (ev) => {
        if (ev.key === 'ArrowRight' || ev.key === 'ArrowLeft') {
          const otro = [...botones].find((x) => x !== b);
          otro.focus(); fijar(otro.dataset.valor);
        }
      });
    });
    fijar('sin');
  }
})();
