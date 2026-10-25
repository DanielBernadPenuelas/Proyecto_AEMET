# El tiempo por comunidad autónoma con la API de AEMET

Aplicación web en PHP que consulta la API de **AEMET OpenData** (Agencia Estatal de Meteorología). Con un selector se elige una comunidad autónoma y la página muestra:

- **La predicción oficial del día** para esa comunidad.
- **La observación más reciente** de una estación representativa: temperatura, humedad, viento y precipitación.

Autor: Daniel Bernad Peñuelas · Fuente de los datos: © AEMET

---

## ¿Qué problema resuelve en una empresa?

Muchas decisiones y trámites de una empresa dependen del tiempo que hizo o que va a hacer. Normalmente alguien lo busca a mano en webs distintas y no queda registro. Este proyecto pone en un solo sitio los **datos oficiales de AEMET**, por comunidad y en un formato que se puede guardar, comparar y ampliar.

### 1. Reclamación de seguros y gestión de siniestros

Es el caso más claro. Cuando un fenómeno meteorológico causa daños, la aseguradora o el perito piden pruebas de que ese día hubo lluvia intensa, viento fuerte, granizo o heladas.

| Situación | Dato de AEMET que la respalda |
| --- | --- |
| Tejado o toldo dañado por el viento | Racha máxima de viento (`racha`, `vmax`) en la estación cercana |
| Inundación de un local o almacén | Precipitación acumulada (`prec`) por horas o en el día |
| Mercancía perdida por una helada | Temperatura mínima (`tamin`) |
| Cosecha dañada por granizo o tormenta | Predicción y avisos de la zona, junto con la precipitación registrada |
| Retraso de una obra por mal tiempo | Días de lluvia o viento por encima del umbral pactado en contrato |

**Qué aporta el proyecto:**
- Consulta rápida de los datos de la estación más próxima al siniestro.
- Base para un **informe automático**: fecha, estación, valores medidos y fuente, listo para adjuntar a un parte.
- Datos objetivos de un organismo público, en lugar de "llovió mucho" o capturas de pantalla.

> **Importante.** La versión actual muestra las últimas 24 horas. Un siniestro de hace días o semanas necesita los datos históricos. Se obtienen con el endpoint `/valores/climatologicos/diarios/datos/fechaini/{inicio}/fechafin/{fin}/estacion/{idema}` (ver *Hoja de ruta*).
> Si la reclamación llega a la vía judicial, puede hacer falta un **certificado oficial** de AEMET, que se tramita aparte. Los datos de OpenData sirven como respaldo e información, pero conviene confirmar con la aseguradora o el abogado qué documento exigen.

### 2. Logística y transporte

- Prever rutas afectadas por nieve, viento o lluvia intensa en cada comunidad.
- Justificar ante el cliente un retraso en la entrega por causas meteorológicas.
- Planificar el transporte de mercancía sensible a la temperatura (alimentación, farmacia).

### 3. Construcción y obra pública

- Decidir si se hormigona, se trabaja en altura o se usa una grúa según el viento y la temperatura previstos.
- Documentar las **jornadas perdidas por mal tiempo**, que suelen dar derecho a ampliar plazos o evitar penalizaciones.
- Cumplir con prevención de riesgos laborales en olas de calor o de frío.

### 4. Agricultura y agroalimentación

- Planificar riego, tratamientos y cosecha con la predicción.
- Respaldar las reclamaciones a seguros agrarios por helada, granizo o sequía.
- Llevar un histórico de condiciones por campaña y por finca.

### 5. Eventos, turismo y hostelería

- Decidir con antelación si un evento al aire libre se mantiene, se traslada o se aplaza.
- Ajustar personal y compras en terrazas, chiringuitos o actividades de aventura según la previsión.
- Justificar una cancelación ante clientes o proveedores.

### 6. Energía, mantenimiento e instalaciones

- Relacionar la producción de placas solares o aerogeneradores con la radiación y el viento reales.
- Programar el mantenimiento de cubiertas, fachadas o instalaciones exteriores en días adecuados.
- Prever picos de consumo de climatización.

### 7. Comercio y recursos humanos

- Ajustar el stock de productos estacionales (paraguas, ventiladores, ropa de abrigo) por zona.
- Activar protocolos de teletrabajo o cambio de horario ante avisos de temporal o calor extremo.

---

## Cómo funciona

```
Navegador ──GET ?ccaa=mad──▶ index.php (servidor) ──api_key──▶ AEMET /opendata/api/…
    ▲                            │   ▲                              │
    └──────── HTML ──────────────┘   └──── datos (texto/JSON) ◀─────┘ URL «datos»
                                 │
                          caché en disco (30 min)
```

1. El usuario elige una comunidad en el selector.
2. `index.php` valida el código contra una lista cerrada y mira si hay datos en caché.
3. Si no los hay, pide los metadatos a AEMET con la API key y después descarga la URL que viene en el campo `datos`.
4. Convierte el texto a UTF-8, lo guarda en caché y pinta el HTML escapado.

La API key **nunca llega al navegador**: todas las peticiones salen del servidor.

---

## Estructura del proyecto

```
proyecto/
├── .gitignore          ← contiene la línea: privado/
|    └── Api.env        ← AEMET_API_KEY="tu-clave"   (no se sube a Git)
└── public/             ← raíz de la web
    ├── index.php
    └── style.css
```

## Instalación

1. Pide una API key gratuita en <https://opendata.aemet.es/centrodedescargas/altaUsuario>.
2. Crea `privado/.env` con el contenido:
   ```
   AEMET_API_KEY="pega-aquí-tu-clave"
   ```
3. Comprueba que `.gitignore` (archivo en la raíz) contiene `privado/`.
4. Apunta la raíz del servidor web a `public/`. Requisitos: PHP 7.4 o superior con las extensiones cURL y mbstring.
5. Abre `index.php` en el navegador y elige una comunidad.

## Seguridad

- La clave se guarda fuera de la carpeta pública y fuera del repositorio.
- El parámetro `ccaa` solo se acepta si está en la lista de comunidades, así que no se pueden inyectar rutas en la petición a AEMET.
- Todo lo que llega de AEMET se escapa con `htmlspecialchars` antes de mostrarse.
- La caché de 30 minutos evita agotar el límite de peticiones de la clave.

---

## Hoja de ruta

- [ ] **Consulta histórica por fecha y estación**, imprescindible para el caso de los seguros.
- [ ] **Exportar un informe en PDF** con fecha, estación, valores y fuente, para adjuntarlo a un parte o reclamación.
- [ ] **Selector de estación o municipio**, para usar la más cercana al lugar del siniestro y no solo la capital.
- [ ] **Avisos meteorológicos** (amarillo, naranja, rojo) por zona.
- [ ] **Predicción de 7 días** por municipio, con iconos.
- [ ] **Gráfica de temperaturas** de las últimas 24 horas.
- [ ] **Alertas por correo** cuando se superen umbrales definidos por la empresa (viento > X km/h, helada…).

## Licencia de los datos

Los datos proceden de AEMET OpenData y se usan según sus condiciones de reutilización, que exigen citar la fuente: **© AEMET**.
