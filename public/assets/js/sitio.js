// Cartilla · comportamiento del sitio público. Todo funciona sin este archivo, salvo la pregunta en vivo.
(() => {
  const base = document.querySelector('meta[name="base"]')?.content || '';
  const csrf = document.querySelector('meta[name="csrf"]')?.content || '';
  const reducido = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // Fundido lento entre las fotos de la portada; se pausa si la pestaña no está visible.
  const fundido = document.querySelector('[data-fundido]');
  if (fundido && !reducido) {
    const fotos = [...fundido.querySelectorAll('img')];
    let i = 0;
    fotos[0].classList.add('activa');
    setInterval(() => {
      if (document.hidden) return;
      fotos[i].classList.remove('activa');
      i = (i + 1) % fotos.length;
      fotos[i].classList.add('activa');
    }, 6000);
  } else if (fundido) {
    fundido.querySelector('img')?.classList.add('activa');
  }

  // Entradas escalonadas por sección.
  const entradas = document.querySelectorAll('.entra');
  if ('IntersectionObserver' in window && !reducido) {
    const io = new IntersectionObserver((registros) => {
      registros.forEach((r) => {
        if (!r.isIntersecting) return;
        r.target.classList.add('visto');
        io.unobserve(r.target);
      });
    }, { rootMargin: '0px 0px -10% 0px' });
    entradas.forEach((el) => {
      const hermanos = [...el.parentElement.querySelectorAll(':scope > .entra')];
      el.style.setProperty('--d', `${hermanos.indexOf(el) * 80}ms`);
      io.observe(el);
    });
  } else {
    entradas.forEach((el) => el.classList.add('visto'));
  }

  // Pregunta real en la portada, corregida por el servidor.
  const panel = document.querySelector('[data-muestras]');
  if (panel) {
    const muestras = JSON.parse(panel.dataset.muestras || '[]');
    const form = panel.querySelector('[data-muestra]');
    const enunciado = form.querySelector('.muestra__enunciado');
    const opciones = form.querySelector('.muestra__opciones');
    const resultado = form.querySelector('.muestra__resultado');
    const boton = form.querySelector('button[type="submit"]');
    const pestanas = [...panel.querySelectorAll('[role="tab"]')];
    let actual = 0;

    const pintar = (indice) => {
      actual = indice;
      const m = muestras[indice];
      enunciado.textContent = m.enunciado;
      opciones.innerHTML = '';
      m.opciones.forEach((op) => {
        const label = document.createElement('label');
        label.className = 'opcion';
        label.innerHTML = '<input type="radio" name="respuesta"><span></span>';
        label.querySelector('input').value = op.valor;
        label.querySelector('span').textContent = op.texto;
        opciones.append(label);
      });
      resultado.textContent = '';
      resultado.className = 'muestra__resultado';
      boton.disabled = false;
      boton.textContent = 'Comprobar';
      pestanas.forEach((t, k) => {
        t.setAttribute('aria-selected', k === indice ? 'true' : 'false');
        t.tabIndex = k === indice ? 0 : -1;
      });
      panel.querySelector('[role="tabpanel"]').setAttribute('aria-labelledby', pestanas[indice].id);
    };

    pestanas.forEach((t, k) => {
      t.addEventListener('click', () => pintar(k));
      t.addEventListener('keydown', (ev) => {
        if (ev.key !== 'ArrowRight' && ev.key !== 'ArrowLeft') return;
        const sig = (k + (ev.key === 'ArrowRight' ? 1 : -1) + pestanas.length) % pestanas.length;
        pestanas[sig].focus();
        pintar(sig);
      });
    });

    form.addEventListener('submit', async (ev) => {
      ev.preventDefault();
      if (boton.textContent === 'Otra pregunta') {
        pintar((actual + 1) % muestras.length);
        return;
      }
      const elegida = form.querySelector('input[name="respuesta"]:checked');
      if (!elegida) {
        resultado.className = 'muestra__resultado mal';
        resultado.textContent = 'Elige una opción antes de comprobar.';
        return;
      }
      boton.disabled = true;
      boton.innerHTML = '<span class="girando" aria-hidden="true"></span> Comprobando';
      try {
        const res = await fetch(`${base}/api/muestra`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
          body: JSON.stringify({ id: muestras[actual].id, respuesta: elegida.value }),
        });
        const datos = await res.json();
        if (!res.ok) throw new Error(datos.error || 'Error');
        opciones.querySelectorAll('.opcion').forEach((l) => {
          const input = l.querySelector('input');
          input.disabled = true;
          if (l.querySelector('span').textContent === datos.solucion) l.classList.add('es-correcta');
          else if (input.checked) l.classList.add('es-incorrecta');
        });
        resultado.className = 'muestra__resultado ' + (datos.correcta ? 'bien' : 'mal');
        resultado.textContent = (datos.correcta ? 'Correcto. ' : `No es esa. La respuesta es «${datos.solucion}». `) + (datos.explicacion || '');
        boton.textContent = 'Otra pregunta';
      } catch (e) {
        resultado.className = 'muestra__resultado mal';
        resultado.textContent = 'No pudimos revisar tu respuesta. Revisa tu conexión y vuelve a intentarlo.';
        boton.textContent = 'Comprobar';
      } finally {
        boton.disabled = false;
      }
    });
  }

  // Mostrar u ocultar contraseña.
  document.querySelectorAll('[data-ver-clave]').forEach((b) => {
    b.addEventListener('click', () => {
      const input = document.getElementById(b.getAttribute('aria-controls'));
      const ver = input.type === 'password';
      input.type = ver ? 'text' : 'password';
      b.textContent = ver ? 'Ocultar' : 'Mostrar';
      b.setAttribute('aria-pressed', ver ? 'true' : 'false');
    });
  });

  // Estado de carga en formularios: evita doble envío y avisa que algo pasa.
  document.querySelectorAll('form[data-cargando]').forEach((f) => {
    f.addEventListener('submit', () => {
      const b = f.querySelector('button[type="submit"]');
      if (!b || b.disabled) return;
      setTimeout(() => {
        b.disabled = true;
        b.innerHTML = `<span class="girando" aria-hidden="true"></span> ${f.dataset.cargando}`;
      }, 0);
    });
  });

  // Contador de caracteres para textos largos.
  document.querySelectorAll('[data-contador]').forEach((area) => {
    const salida = document.getElementById(area.dataset.contador);
    const minimo = Number(area.dataset.minimo || 0);
    const actualizar = () => {
      const n = area.value.trim().length;
      salida.textContent = n < minimo ? `${n} caracteres · faltan ${minimo - n}` : `${n} caracteres · suficiente`;
    };
    area.addEventListener('input', actualizar);
    actualizar();
  });

  // Rellena el texto de ejemplo del docente.
  document.querySelectorAll('[data-rellenar]').forEach((b) => {
    b.addEventListener('click', () => {
      const area = document.getElementById(b.dataset.rellenar);
      const fuente = document.getElementById(b.dataset.fuente);
      area.value = (fuente.content ? fuente.content.textContent : fuente.textContent).trim();
      area.dispatchEvent(new Event('input'));
      const titulo = document.getElementById(b.dataset.titulo || '');
      if (titulo && !titulo.value) titulo.value = b.dataset.tituloValor || '';
      area.focus();
    });
  });

  // Confirmación antes de acciones destructivas.
  document.querySelectorAll('form[data-confirmar]').forEach((f) => {
    f.addEventListener('submit', (ev) => {
      if (!window.confirm(f.dataset.confirmar)) ev.preventDefault();
    });
  });
})();

// Lleva el foco al resumen de errores tras un envío fallido.
document.querySelector('[data-enfocar]')?.focus();
