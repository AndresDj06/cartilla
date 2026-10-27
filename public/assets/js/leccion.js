// Cartilla · reproductor de lecciones. La corrección ocurre siempre en el servidor.
(() => {
  const raiz = document.querySelector('.jugador');
  if (!raiz) return;

  const base = document.querySelector('meta[name="base"]').content;
  const csrf = document.querySelector('meta[name="csrf"]').content;
  const paquete = JSON.parse(raiz.dataset.paquete);
  const nombresTipo = JSON.parse(raiz.dataset.tipos);
  const porId = new Map(paquete.ejercicios.map((e) => [e.id, e]));
  const resueltos = new Set(paquete.resueltos);
  let cola = paquete.ejercicios.map((e) => e.id).filter((id) => !resueltos.has(id));
  let vidas = paquete.vidas;
  let fase = 'respondiendo';
  let actual = null;
  let leerRespuesta = () => null;
  let marcarResultado = () => {};

  const $ = (sel, el = raiz) => el.querySelector(sel);
  const tipoEl = $('.ejercicio__tipo');
  const enunciadoEl = $('.ejercicio__enunciado');
  const zona = $('.ejercicio__zona');
  const pie = $('.jugador__pie');
  const retro = $('.retro');
  const boton = $('.jugador__boton');
  const barra = $('.jugador__avance');
  const vidasEl = $('.jugador__vidas');

  const crear = (tag, clase, texto) => {
    const el = document.createElement(tag);
    if (clase) el.className = clase;
    if (texto !== undefined) el.textContent = texto;
    return el;
  };

  const corazon = (lleno) => `<svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path d="M12 21s-7.5-4.6-9.5-9.2C1 8.3 3.2 5 6.6 5c2 0 3.4 1.1 5.4 3 2-1.9 3.4-3 5.4-3C20.8 5 23 8.3 21.5 11.8 19.5 16.4 12 21 12 21z" fill="${lleno ? 'currentColor' : 'none'}" stroke="currentColor" stroke-width="2"/></svg>`;

  const pintarEstado = () => {
    const total = paquete.ejercicios.length;
    barra.firstElementChild.style.width = `${(100 * resueltos.size) / total}%`;
    barra.setAttribute('aria-valuenow', String(resueltos.size));
    barra.setAttribute('aria-valuetext', `${resueltos.size} de ${total} resueltos`);
    if (paquete.vidasMax) {
      vidasEl.innerHTML = Array.from({ length: paquete.vidasMax }, (_, i) => corazon(i < vidas)).join('') + `<span class="sr">Vidas: ${vidas} de ${paquete.vidasMax}</span>`;
    }
  };

  const listo = (ok) => {
    if (fase !== 'respondiendo') return;
    boton.disabled = !ok;
  };

  // ---------- Juegos ----------

  const opciones = (ej) => {
    const grupo = crear('div', 'opciones');
    grupo.setAttribute('role', 'radiogroup');
    grupo.setAttribute('aria-labelledby', 'enunciado');
    let elegido = null;
    const botones = ej.opciones.map((op, i) => {
      const b = crear('button', 'opcion-boton');
      b.type = 'button';
      b.setAttribute('role', 'radio');
      b.setAttribute('aria-checked', 'false');
      b.dataset.valor = op.valor;
      b.innerHTML = `<span class="opcion-boton__tecla" aria-hidden="true">${i + 1}</span><span class="opcion-boton__texto"></span>`;
      b.querySelector('.opcion-boton__texto').textContent = op.texto;
      b.addEventListener('click', () => {
        if (fase !== 'respondiendo') return;
        botones.forEach((o) => o.setAttribute('aria-checked', o === b ? 'true' : 'false'));
        elegido = op.valor;
        listo(true);
      });
      grupo.append(b);
      return b;
    });
    zona.append(grupo);
    leerRespuesta = () => elegido;
    marcarResultado = (correcta, solucion) => {
      botones.forEach((b) => {
        b.disabled = true;
        const texto = b.querySelector('.opcion-boton__texto').textContent;
        if (texto === solucion) b.classList.add('es-correcta');
        else if (b.getAttribute('aria-checked') === 'true') b.classList.add('es-incorrecta');
      });
    };
    raiz._teclas = (ev) => {
      const n = Number(ev.key);
      if (n >= 1 && n <= botones.length) botones[n - 1].click();
    };
  };

  const completar = (ej) => {
    const partes = ej.enunciado.split('___');
    enunciadoEl.textContent = 'Completa la frase';
    const frase = crear('p', 'frase');
    frase.append(document.createTextNode(partes[0] || ''));
    let valor = '';
    let campo;
    if (ej.banco && ej.banco.length) {
      campo = crear('span', 'hueco', ' ');
      campo.setAttribute('aria-label', 'Espacio en blanco');
      frase.append(campo, document.createTextNode(partes[1] || ''));
      zona.append(frase);
      const banco = crear('div', 'fichas');
      banco.setAttribute('aria-label', 'Banco de palabras');
      const fichas = ej.banco.map((palabra) => {
        const f = crear('button', 'ficha', palabra);
        f.type = 'button';
        f.setAttribute('aria-pressed', 'false');
        f.addEventListener('click', () => {
          if (fase !== 'respondiendo') return;
          const ya = f.getAttribute('aria-pressed') === 'true';
          fichas.forEach((o) => o.setAttribute('aria-pressed', 'false'));
          valor = ya ? '' : palabra;
          f.setAttribute('aria-pressed', ya ? 'false' : 'true');
          campo.textContent = valor || ' ';
          campo.classList.toggle('lleno', !!valor);
          listo(!!valor);
        });
        banco.append(f);
        return f;
      });
      zona.append(banco);
      marcarResultado = (correcta) => {
        fichas.forEach((f) => { f.disabled = true; });
        campo.classList.add(correcta ? 'es-correcta' : 'es-incorrecta');
      };
    } else {
      campo = crear('input', 'hueco-input');
      campo.type = 'text';
      campo.autocomplete = 'off';
      campo.spellcheck = false;
      campo.setAttribute('aria-label', 'Palabra que falta');
      campo.addEventListener('input', () => { valor = campo.value; listo(valor.trim().length > 0); });
      frase.append(campo, document.createTextNode(partes[1] || ''));
      zona.append(frase);
      zona.append(crear('p', 'pista-teclado', 'Escribe la palabra y presiona Enter. No importan las tildes ni las mayúsculas.'));
      setTimeout(() => campo.focus(), 50);
      marcarResultado = (correcta) => {
        campo.disabled = true;
        campo.classList.add(correcta ? 'es-correcta' : 'es-incorrecta');
      };
    }
    leerRespuesta = () => valor.trim();
  };

  const ordenar = (ej, esLetras) => {
    if (esLetras && ej.pista) zona.append(crear('p', 'pista', ej.pista));
    const linea = crear('div', esLetras ? 'linea linea--letras' : 'linea');
    linea.setAttribute('aria-label', 'Tu respuesta');
    const banco = crear('div', esLetras ? 'fichas fichas--letras' : 'fichas');
    banco.setAttribute('aria-label', esLetras ? 'Letras disponibles' : 'Palabras disponibles');
    const elegidas = [];
    const refrescar = () => {
      linea.innerHTML = '';
      if (!elegidas.length) linea.append(crear('span', 'linea__vacia', esLetras ? 'Toca las letras en orden' : 'Toca las palabras en orden'));
      elegidas.forEach((k, pos) => {
        const f = crear('button', 'ficha ficha--puesta', ej.fichas[k]);
        f.type = 'button';
        f.setAttribute('aria-label', `${ej.fichas[k]}, posición ${pos + 1}. Quitar`);
        f.addEventListener('click', () => {
          if (fase !== 'respondiendo') return;
          elegidas.splice(pos, 1);
          originales[k].classList.remove('usada');
          originales[k].disabled = false;
          refrescar();
        });
        linea.append(f);
      });
      listo(elegidas.length === ej.fichas.length);
    };
    const originales = ej.fichas.map((texto, k) => {
      const f = crear('button', 'ficha', texto);
      f.type = 'button';
      f.addEventListener('click', () => {
        if (fase !== 'respondiendo' || f.classList.contains('usada')) return;
        elegidas.push(k);
        f.classList.add('usada');
        f.disabled = true;
        refrescar();
      });
      banco.append(f);
      return f;
    });
    zona.append(linea, banco);
    refrescar();
    leerRespuesta = () => elegidas.map((k) => ej.fichas[k]);
    marcarResultado = (correcta) => {
      linea.classList.add(correcta ? 'es-correcta' : 'es-incorrecta');
      linea.querySelectorAll('button').forEach((b) => { b.disabled = true; });
      originales.forEach((b) => { b.disabled = true; });
    };
  };

  const relacionar = (ej) => {
    zona.append(crear('p', 'pista-teclado', 'Toca un elemento de cada columna para unirlos. Toca una pareja para deshacerla.'));
    const tabla = crear('div', 'relacionar');
    const colA = crear('div', 'relacionar__col');
    const colB = crear('div', 'relacionar__col');
    const pares = new Map(); // izquierda -> derecha
    let elegido = null; // {lado, valor, boton}
    const botonesA = [];
    const botonesB = [];

    const numeroDe = (lado, valor) => {
      let i = 0;
      for (const [a, b] of pares) {
        i++;
        if ((lado === 'a' && a === valor) || (lado === 'b' && b === valor)) return i;
      }
      return 0;
    };
    const refrescar = () => {
      [...botonesA.map((b) => ['a', b]), ...botonesB.map((b) => ['b', b])].forEach(([lado, b]) => {
        const n = numeroDe(lado, b.dataset.valor);
        b.querySelector('.par').textContent = n ? String(n) : '';
        b.classList.toggle('emparejado', !!n);
        b.setAttribute('aria-pressed', elegido && elegido.boton === b ? 'true' : 'false');
        b.setAttribute('aria-label', b.dataset.valor + (n ? `, pareja ${n}` : ''));
      });
      listo(pares.size === ej.izquierda.length);
    };
    const tocar = (lado, valor, b) => {
      if (fase !== 'respondiendo') return;
      // Deshacer una pareja existente.
      for (const [a, d] of pares) {
        if ((lado === 'a' && a === valor) || (lado === 'b' && d === valor)) {
          pares.delete(a);
          elegido = null;
          refrescar();
          return;
        }
      }
      if (!elegido || elegido.lado === lado) {
        elegido = { lado, valor, boton: b };
      } else {
        const a = lado === 'a' ? valor : elegido.valor;
        const d = lado === 'b' ? valor : elegido.valor;
        pares.set(a, d);
        elegido = null;
      }
      refrescar();
    };
    const hacer = (lado, valor, col, lista) => {
      const b = crear('button', 'rel-boton');
      b.type = 'button';
      b.dataset.valor = valor;
      b.innerHTML = '<span class="par" aria-hidden="true"></span><span class="rel-texto"></span>';
      b.querySelector('.rel-texto').textContent = valor;
      b.addEventListener('click', () => tocar(lado, valor, b));
      col.append(b);
      lista.push(b);
    };
    ej.izquierda.forEach((v) => hacer('a', v, colA, botonesA));
    ej.derecha.forEach((v) => hacer('b', v, colB, botonesB));
    tabla.append(colA, colB);
    zona.append(tabla);
    refrescar();
    leerRespuesta = () => [...pares.entries()];
    marcarResultado = (correcta) => {
      tabla.classList.add(correcta ? 'es-correcta' : 'es-incorrecta');
      [...botonesA, ...botonesB].forEach((b) => { b.disabled = true; });
    };
  };

  // ---------- Ciclo ----------

  const mostrar = () => {
    actual = porId.get(cola[0]);
    fase = 'respondiendo';
    // Cada ejercicio entra deslizándose: se reinicia la animación del contenedor.
    const caja = $('.ejercicio');
    caja.style.animation = 'none';
    void caja.offsetHeight;
    caja.style.animation = '';
    pie.dataset.estado = 'respondiendo';
    retro.innerHTML = '';
    zona.innerHTML = '';
    raiz._teclas = null;
    boton.textContent = 'Comprobar';
    boton.disabled = true;
    tipoEl.textContent = nombresTipo[actual.tipo] || '';
    enunciadoEl.id = 'enunciado';
    enunciadoEl.textContent = actual.enunciado;
    if (actual.tipo === 'test' || actual.tipo === 'si_no') opciones(actual);
    else if (actual.tipo === 'completar') completar(actual);
    else if (actual.tipo === 'ordenar_palabras') ordenar(actual, false);
    else if (actual.tipo === 'ordenar_letras') ordenar(actual, true);
    else if (actual.tipo === 'relacionar') relacionar(actual);
    pintarEstado();
    if (actual.tipo !== 'completar' || (actual.banco && actual.banco.length)) enunciadoEl.focus({ preventScroll: true });
    window.scrollTo({ top: 0 });
  };

  const post = async (ruta, cuerpo) => {
    const res = await fetch(`${base}${ruta}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
      body: JSON.stringify(cuerpo || {}),
    });
    let datos = {};
    try { datos = await res.json(); } catch (e) { /* respuesta vacía */ }
    if (!res.ok) {
      const err = new Error(datos.error || 'Error del servidor');
      err.estado = res.status;
      throw err;
    }
    return datos;
  };

  const comprobar = async () => {
    const respuesta = leerRespuesta();
    if (respuesta === null || respuesta === '' || (Array.isArray(respuesta) && !respuesta.length)) return;
    fase = 'enviando';
    boton.disabled = true;
    boton.innerHTML = '<span class="girando" aria-hidden="true"></span> Comprobando';
    try {
      const r = await post(`/api/intentos/${paquete.intento}/responder`, { ejercicio: actual.id, respuesta });
      fase = 'revisado';
      vidas = r.vidas;
      marcarResultado(r.correcta, r.solucion);
      pie.dataset.estado = r.correcta ? 'bien' : 'mal';
      const titulo = r.correcta ? 'Bien hecho' : 'Corrección';
      let html = `<p class="retro__titulo">${titulo}</p>`;
      if (!r.correcta) html += '<p class="retro__solucion"></p>';
      if (r.explicacion) html += '<p class="retro__explicacion"></p>';
      if (r.fuente && paquete.modo !== 'repaso' && r.fuente.length < 400 && r.fuente !== actual.enunciado) html += '<p class="retro__fuente"></p>';
      retro.innerHTML = html;
      if (!r.correcta) retro.querySelector('.retro__solucion').textContent = `Respuesta correcta: ${r.solucion}`;
      if (r.explicacion) retro.querySelector('.retro__explicacion').textContent = r.explicacion;
      const fuente = retro.querySelector('.retro__fuente');
      if (fuente) fuente.textContent = `Sale del texto: «${r.fuente}»`;

      if (r.correcta) {
        resueltos.add(actual.id);
        cola.shift();
      } else {
        // Como en las apps de idiomas: lo fallado vuelve al final de la lección.
        cola.push(cola.shift());
      }
      pintarEstado();
      if (r.sinVidas) {
        retro.insertAdjacentHTML('beforeend', '<p class="retro__explicacion"><b>Te quedaste sin vidas.</b> Tu avance queda guardado y puedes repetir la lección.</p>');
        boton.textContent = 'Ver mi resultado';
        boton.dataset.accion = 'terminar';
      } else {
        boton.textContent = cola.length ? 'Continuar' : 'Terminar lección';
        boton.dataset.accion = cola.length ? 'seguir' : 'terminar';
      }
      boton.disabled = false;
      boton.focus();
    } catch (e) {
      fase = 'respondiendo';
      pie.dataset.estado = 'error';
      retro.innerHTML = '<p class="retro__titulo">No se guardó tu respuesta</p><p class="retro__explicacion"></p>';
      retro.querySelector('.retro__explicacion').textContent = e.estado === 419
        ? 'La sesión expiró. Recarga la página: lo que ya respondiste está guardado.'
        : (e.estado ? e.message : 'Revisa tu conexión a internet y vuelve a intentarlo. Tu respuesta sigue marcada.');
      boton.textContent = 'Reintentar';
      boton.disabled = false;
    }
  };

  const terminar = async () => {
    fase = 'fin';
    boton.disabled = true;
    boton.innerHTML = '<span class="girando" aria-hidden="true"></span> Guardando resultado';
    try {
      const r = await post(`/api/intentos/${paquete.intento}/terminar`);
      window.location.href = r.destino;
    } catch (e) {
      pie.dataset.estado = 'error';
      retro.innerHTML = '<p class="retro__titulo">No pudimos cerrar la lección</p><p class="retro__explicacion">Revisa tu conexión y vuelve a intentarlo. Tus respuestas ya están guardadas.</p>';
      boton.textContent = 'Reintentar';
      boton.dataset.accion = 'terminar';
      boton.disabled = false;
      fase = 'revisado';
    }
  };

  boton.addEventListener('click', () => {
    if (fase === 'respondiendo') comprobar();
    else if (fase === 'revisado') {
      if (boton.dataset.accion === 'terminar') terminar();
      else mostrar();
    }
  });

  document.addEventListener('keydown', (ev) => {
    // Enter siempre ejecuta la acción principal (Comprobar o Continuar); Espacio activa el elemento enfocado.
    if (ev.key === 'Enter' && ev.target !== boton && !(ev.target instanceof HTMLAnchorElement) && !boton.disabled) {
      ev.preventDefault();
      boton.click();
      return;
    }
    if (fase === 'respondiendo' && raiz._teclas && !(ev.target instanceof HTMLInputElement)) raiz._teclas(ev);
  });

  $('[data-salir-enlace]').addEventListener('click', (ev) => {
    if (resueltos.size > 0 && resueltos.size < paquete.ejercicios.length && fase !== 'fin') {
      if (!window.confirm('¿Salir de la lección? Lo que respondiste queda guardado y puedes seguir hoy mismo.')) ev.preventDefault();
    }
  });

  if (!cola.length) {
    // Todo estaba resuelto (por ejemplo, al volver tras perder la conexión): solo falta cerrar.
    tipoEl.textContent = '';
    enunciadoEl.textContent = 'Ya respondiste todo';
    zona.innerHTML = '<p>Guarda tu resultado para ver tu XP y tus errores.</p>';
    fase = 'revisado';
    boton.textContent = 'Ver mi resultado';
    boton.dataset.accion = 'terminar';
    boton.disabled = false;
    pintarEstado();
  } else if (paquete.vidasMax && vidas <= 0) {
    fase = 'revisado';
    enunciadoEl.textContent = 'Te quedaste sin vidas';
    boton.textContent = 'Ver mi resultado';
    boton.dataset.accion = 'terminar';
    boton.disabled = false;
    pintarEstado();
  } else {
    mostrar();
  }
})();
