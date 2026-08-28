/* ===========================================================
   FARMAVIDA - script.js (versión PHP + MySQL vía XAMPP)
   Contiene:
   1) Lógica del formulario de registro -> envía a api.php (accion: registrar_cliente)
   2) Lógica del chatbot de atención al cliente
   3) Al calificar, se envía la conversación a api.php (accion: guardar_conversacion)

   NOTA: Todos los endpoints sueltos (guardar_cliente.php, carrito_*.php,
   guardar_conversacion.php) se fusionaron en un solo archivo: api.php.
   Por eso aquí ya no llamamos a esos archivos directamente, sino a
   api.php mandando siempre un campo "accion" que dice qué operación
   queremos hacer.
   =========================================================== */

async function enviarJSON(url, datos) {
  const respuesta = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(datos)
  });
  return respuesta.json();
}

/* ===========================================================
   1) FORMULARIO DE REGISTRO
   =========================================================== */
const formRegistro = document.getElementById('form-registro');

if (formRegistro) {
  formRegistro.addEventListener('submit', async function (e) {
    e.preventDefault();

    const cliente = {
      accion: 'registrar_cliente',
      // Dato público
      nombre: document.getElementById('nombre').value,
      ciudad: document.getElementById('ciudad').value,
      // Dato semiprivado
      telefono: document.getElementById('telefono').value,
      correo: document.getElementById('correo').value,
      password: document.getElementById('password').value,
      // Dato privado
      direccion: document.getElementById('direccion').value,
      documento: document.getElementById('documento').value,
      // Dato sensible
      condicionSalud: document.getElementById('condicion-salud').value || null,
      eps: document.getElementById('eps').value
    };

    try {
      const resultado = await enviarJSON('api.php', cliente);

      if (resultado.error) {
        alert('Hubo un problema guardando tus datos: ' + resultado.mensaje);
        return;
      }

      document.getElementById('registro-confirmacion').style.display = 'block';
      formRegistro.reset();

      // El registro también inicia sesión en el servidor, así que
      // mandamos al cliente directo a su panel privado.
      setTimeout(() => {
        window.location.href = 'dashboard_cliente.php';
      }, 1200);

    } catch (err) {
      alert('No se pudo conectar con el servidor. ¿Está XAMPP encendido? (Apache + MySQL)');
      console.error(err);
    }
  });
}

/* ===========================================================
   2) CARRITO DE COMPRAS
   El carrito vive en el servidor (sesión PHP). Aquí solo lo
   consultamos, lo mostramos y disparamos las acciones
   (agregar, quitar, eliminar, confirmar) contra api.php.
   =========================================================== */
const mensajeCompra = document.getElementById('compra-mensaje');

const carritoToggle = document.getElementById('carrito-toggle');
const carritoPanel = document.getElementById('carrito-panel');
const carritoBody = document.getElementById('carrito-body');
const carritoBadge = document.getElementById('carrito-badge');
const carritoTotalEl = document.getElementById('carrito-total');
const carritoClose = document.getElementById('carrito-close');
const btnConfirmarCompra = document.getElementById('btn-confirmar-compra');

function mostrarMensajeCompra(texto, esError) {
  if (!mensajeCompra) return;
  mensajeCompra.textContent = texto;
  mensajeCompra.className = 'compra-mensaje ' + (esError ? 'error' : 'ok');
  mensajeCompra.style.display = 'block';
  mensajeCompra.scrollIntoView({ behavior: 'smooth', block: 'center' });
  setTimeout(() => { mensajeCompra.style.display = 'none'; }, 4000);
}

function actualizarBadgeCarrito(cantidadTotal) {
  if (!carritoBadge) return;
  if (cantidadTotal > 0) {
    carritoBadge.textContent = cantidadTotal;
    carritoBadge.style.display = 'flex';
  } else {
    carritoBadge.style.display = 'none';
  }
}

function formatearPrecio(numero) {
  return '$' + Number(numero).toLocaleString('es-CO', { maximumFractionDigits: 0 });
}

async function cargarCarrito() {
  if (!window.CLIENTE_LOGUEADO || !carritoBody) return;

  let data;
  try {
    data = await (await fetch('api.php?accion=carrito_datos')).json();
  } catch (err) {
    carritoBody.innerHTML = '<div class="carrito-vacio">No se pudo cargar tu carrito.</div>';
    return;
  }
  if (data.error) return;

  const totalItems = data.items.reduce((s, i) => s + i.cantidad, 0);
  actualizarBadgeCarrito(totalItems);

  carritoBody.innerHTML = '';

  if (data.items.length === 0) {
    carritoBody.innerHTML = '<div class="carrito-vacio">Tu carrito está vacío. Agrega productos desde el catálogo.</div>';
    carritoTotalEl.textContent = '$0';
    btnConfirmarCompra.disabled = true;
    return;
  }

  btnConfirmarCompra.disabled = false;

  data.items.forEach(item => {
    const div = document.createElement('div');
    div.className = 'carrito-item';
    div.innerHTML = `
      <div class="carrito-item-icon">${item.icono}</div>
      <div class="carrito-item-info">
        <div class="carrito-item-nombre">${item.nombre}</div>
        <div class="carrito-item-precio">${formatearPrecio(item.precio)} c/u</div>
      </div>
      <div class="carrito-item-cantidad">
        <button type="button" class="carrito-btn-menos" data-id="${item.producto_id}">−</button>
        <span>${item.cantidad}</span>
        <button type="button" class="carrito-btn-mas" data-id="${item.producto_id}">+</button>
      </div>
      <div class="carrito-item-subtotal">${formatearPrecio(item.subtotal)}</div>
      <button type="button" class="carrito-btn-quitar" data-id="${item.producto_id}" title="Quitar del carrito">🗑️</button>
    `;
    carritoBody.appendChild(div);
  });

  carritoTotalEl.textContent = formatearPrecio(data.total);

  carritoBody.querySelectorAll('.carrito-btn-mas').forEach(btn => {
    btn.addEventListener('click', async () => {
      await enviarJSON('api.php', { accion: 'carrito_agregar', producto_id: btn.dataset.id });
      cargarCarrito();
    });
  });
  carritoBody.querySelectorAll('.carrito-btn-menos').forEach(btn => {
    btn.addEventListener('click', async () => {
      await enviarJSON('api.php', { accion: 'carrito_quitar', producto_id: btn.dataset.id });
      cargarCarrito();
    });
  });
  carritoBody.querySelectorAll('.carrito-btn-quitar').forEach(btn => {
    btn.addEventListener('click', async () => {
      await enviarJSON('api.php', { accion: 'carrito_eliminar', producto_id: btn.dataset.id });
      cargarCarrito();
    });
  });
}

if (carritoToggle) {
  carritoToggle.addEventListener('click', () => {
    if (!window.CLIENTE_LOGUEADO) {
      window.location.href = 'login.php';
      return;
    }
    carritoPanel.classList.toggle('abierto');
    if (carritoPanel.classList.contains('abierto')) cargarCarrito();
  });
}

if (carritoClose) {
  carritoClose.addEventListener('click', () => {
    carritoPanel.classList.remove('abierto');
  });
}

// Botones "Agregar al carrito" del catálogo
document.querySelectorAll('.btn-agregar-carrito').forEach(boton => {
  boton.addEventListener('click', async () => {
    if (!window.CLIENTE_LOGUEADO) {
      window.location.href = 'login.php';
      return;
    }
    const productoId = boton.dataset.id;
    boton.disabled = true;
    const textoOriginal = boton.textContent;
    boton.textContent = 'Agregando...';

    try {
      const resultado = await enviarJSON('api.php', { accion: 'carrito_agregar', producto_id: productoId });
      mostrarMensajeCompra(resultado.mensaje, resultado.error);
      if (!resultado.error) {
        actualizarBadgeCarrito(resultado.totalItems);
      }
    } catch (err) {
      mostrarMensajeCompra('No se pudo conectar con el servidor.', true);
      console.error(err);
    } finally {
      boton.disabled = false;
      boton.textContent = textoOriginal;
    }
  });
});

// Botón "Confirmar compra" dentro del panel del carrito
if (btnConfirmarCompra) {
  btnConfirmarCompra.addEventListener('click', async () => {
    btnConfirmarCompra.disabled = true;
    const textoOriginal = btnConfirmarCompra.textContent;
    btnConfirmarCompra.textContent = 'Procesando...';

    try {
      const resultado = await enviarJSON('api.php', { accion: 'carrito_confirmar' });
      mostrarMensajeCompra(resultado.mensaje, resultado.error);
      if (!resultado.error) {
        carritoPanel.classList.remove('abierto');
        actualizarBadgeCarrito(0);
      }
    } catch (err) {
      mostrarMensajeCompra('No se pudo conectar con el servidor.', true);
      console.error(err);
    } finally {
      btnConfirmarCompra.disabled = false;
      btnConfirmarCompra.textContent = textoOriginal;
    }
  });
}

// Al cargar la página, si hay sesión de cliente, mostramos cuántos
// artículos tiene ya guardados en el carrito (por si volvió después)
if (window.CLIENTE_LOGUEADO && carritoBadge) {
  (async () => {
    try {
      const data = await (await fetch('api.php?accion=carrito_datos')).json();
      if (!data.error) {
        actualizarBadgeCarrito(data.items.reduce((s, i) => s + i.cantidad, 0));
      }
    } catch (err) {
      console.error('No se pudo consultar el carrito:', err);
    }
  })();
}

/* ===========================================================
   3) CHATBOT DE ATENCIÓN AL CLIENTE
   =========================================================== */

const chatToggle = document.getElementById('chat-toggle');
const chatPanel = document.getElementById('chat-panel');
const chatBody = document.getElementById('chat-body');
const chatForm = document.getElementById('chat-form');
const chatInput = document.getElementById('chat-input');
const chatClose = document.getElementById('chat-close');

let sesionChat = {
  inicio: new Date().toISOString(),
  mensajes: [],
  calificacion: null
};

const RESPUESTAS = [
  { palabras: ['hola', 'buenas', 'buenos dias', 'buenas tardes'],
    respuesta: '¡Hola! Bienvenido a FarmaVida 👋 ¿En qué te puedo ayudar? Puedo darte info sobre horarios, envíos, productos o tu pedido.' },
  { palabras: ['horario', 'hora', 'abren', 'cierran'],
    respuesta: 'Nuestro horario de atención es de lunes a sábado de 7:00 a.m. a 9:00 p.m., y domingos de 8:00 a.m. a 6:00 p.m.' },
  { palabras: ['envio', 'envío', 'domicilio', 'entrega'],
    respuesta: 'Hacemos envíos a domicilio en Bucaramanga y su área metropolitana. El tiempo estimado es de 45 a 90 minutos, con costo desde $4.500.' },
  { palabras: ['precio', 'precios', 'costo', 'cuanto vale', 'cuánto vale'],
    respuesta: 'Los precios varían según el producto. Puedes revisar nuestro catálogo en la sección "Productos", o dime qué producto buscas.' },
  { palabras: ['formula', 'fórmula', 'receta', 'medico', 'médico'],
    respuesta: 'Para medicamentos que requieren fórmula médica, puedes subir una foto de tu receta al momento de hacer el pedido.' },
  { palabras: ['pedido', 'orden', 'donde esta', 'dónde está', 'estado'],
    respuesta: 'Para consultar el estado de tu pedido necesito tu número de orden.' },
  { palabras: ['gracias', 'listo', 'ok', 'vale'],
    respuesta: '¡Con gusto! ¿Necesitas algo más?' },
  { palabras: ['humano', 'asesor', 'persona', 'agente'],
    respuesta: 'Claro, te puedo comunicar con un asesor humano en horario de atención (7 a.m. - 9 p.m.). ¿Quieres que te deje en espera?' },
  { palabras: ['dato', 'privacidad', 'informacion personal', 'información personal'],
    respuesta: 'Tus datos están protegidos según la Ley 1581 de 2012. Solo pedimos la información necesaria para atenderte.' }
];

const RESPUESTA_DEFECTO = 'No estoy seguro de haber entendido eso. Puedo ayudarte con: horarios, envíos, precios, fórmulas médicas o el estado de tu pedido.';

function normalizar(texto) {
  return texto.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
}

function buscarRespuesta(mensajeUsuario) {
  const texto = normalizar(mensajeUsuario);
  for (const item of RESPUESTAS) {
    for (const palabra of item.palabras) {
      if (texto.includes(normalizar(palabra))) return item.respuesta;
    }
  }
  return RESPUESTA_DEFECTO;
}

function agregarMensaje(texto, tipo) {
  const div = document.createElement('div');
  div.className = 'msg ' + tipo;
  div.textContent = texto;
  chatBody.appendChild(div);
  chatBody.scrollTop = chatBody.scrollHeight;
  sesionChat.mensajes.push({ tipo, texto, hora: new Date().toISOString() });
}

function botResponde(mensajeUsuario) {
  setTimeout(() => {
    agregarMensaje(buscarRespuesta(mensajeUsuario), 'bot');
  }, 500);
}

if (chatToggle) {
  chatToggle.addEventListener('click', () => {
    chatPanel.classList.toggle('abierto');
  });
}

if (chatClose) {
  chatClose.addEventListener('click', () => cerrarChatYCalificar());
}

if (chatForm) {
  chatForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const texto = chatInput.value.trim();
    if (!texto) return;
    agregarMensaje(texto, 'user');
    chatInput.value = '';
    botResponde(texto);
  });
}

document.querySelectorAll('.chat-quick button').forEach(btn => {
  btn.addEventListener('click', () => {
    agregarMensaje(btn.textContent, 'user');
    botResponde(btn.textContent);
  });
});

function cerrarChatYCalificar() {
  if (sesionChat.mensajes.length === 0) {
    chatPanel.classList.remove('abierto');
    return;
  }
  document.getElementById('chat-rating-box').style.display = 'block';
}

const estrellasEls = document.querySelectorAll('.estrellas span');
estrellasEls.forEach(estrella => {
  estrella.addEventListener('click', async () => {
    const valor = parseInt(estrella.dataset.valor);
    sesionChat.calificacion = valor;

    estrellasEls.forEach(e => {
      e.classList.toggle('activa', parseInt(e.dataset.valor) <= valor);
    });

    sesionChat.fin = new Date().toISOString();

    try {
      await enviarJSON('api.php', { accion: 'guardar_conversacion', ...sesionChat });
    } catch (err) {
      console.error('No se pudo guardar la conversación en el servidor:', err);
    }

    setTimeout(() => {
      sesionChat = { inicio: new Date().toISOString(), mensajes: [], calificacion: null };
      chatBody.innerHTML = '<div class="msg bot">¡Hola! Bienvenido a FarmaVida 👋 ¿En qué te puedo ayudar?</div>';
      document.getElementById('chat-rating-box').style.display = 'none';
      chatPanel.classList.remove('abierto');
    }, 600);
  });
});
