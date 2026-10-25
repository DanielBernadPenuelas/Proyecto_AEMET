<?php
//Obtenemos la API de un archivo seguro .env
$rutaEnv = __DIR__ . '/../.gitignore/.env';
$env = is_readable($rutaEnv) ? parse_ini_file($rutaEnv) : [];
$clave = trim($env['AEMET_API_KEY'] ?? '');

//Ponemos a prueba si la api es válida o no
if ($clave === '') {
    http_response_code(500);
    exit('Error de configuración: falta la API key de AEMET.');
}
define('AEMET_API_KEY', $clave);

//Hacemos una función, con la estructura que se ha de llevar a la hora de solicitar datos
function peticion(string $url): ?string
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => ['api_key: ' . AEMET_API_KEY, 'Accept: application/json'],
    ]);
    $cuerpo = curl_exec($ch);
    $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    //Hacemos un manejo de errores en caso de que no se encuentre el dato
    return ($cuerpo !== false && $codigo === 200) ? $cuerpo : null;
}

//Una función que hace la petición a la Api, de forma segura y códificada con el estándar 
//UTF-8: Formato de transformación Unicode de 8 bits
//Endpoint: Donde una Api recibe las solicitudes digitales y la envia
function aemet(string $endpoint): ?string
{
    $cache = sys_get_temp_dir() . '/aemet_' . md5($endpoint);
    if (is_file($cache) && time() - filemtime($cache) < CACHE_TTL) {
        return file_get_contents($cache);
    }

    $meta = json_decode(peticion(AEMET_BASE . $endpoint) ?? '', true);
    if (($meta['estado'] ?? 0) !== 200) {
        return null;
    }

    $datos = peticion($meta['datos']);
    if ($datos === null) {
        return null;
    }
    if (!mb_check_encoding($datos, 'UTF-8')) {
        $datos = mb_convert_encoding($datos, 'UTF-8', 'ISO-8859-15');
    }

    file_put_contents($cache, $datos);
    return $datos;
}

//He hecho una función, para limpiar de caracteres especiales, el texto.
function e($texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

$comunidades = [
    'and' => ['Andalucía', '5783'],
    'arn' => ['Aragón', '9434'],
    'ast' => ['Asturias', null],
    'bal' => ['Illes Balears', null],
    'coo' => ['Canarias', null],
    'can' => ['Cantabria', null],
    'cle' => ['Castilla y León', null],
    'clm' => ['Castilla-La Mancha', null],
    'cat' => ['Cataluña', '0076'],
    'val' => ['Comunitat Valenciana', '8416'],
    'ext' => ['Extremadura', null],
    'gal' => ['Galicia', null],
    'mad' => ['Comunidad de Madrid', '3195'],
    'mur' => ['Región de Murcia', null],
    'nav' => ['Navarra', null],
    'pva' => ['País Vasco', '1082'],
    'rio' => ['La Rioja', null],
    'ceu' => ['Ceuta', null],
    'mel' => ['Melilla', null],
];

$ccaa = $_GET['ccaa'] ?? '';
$prediccion = $observacion = null;

if (isset($comunidades[$ccaa])) {
    $prediccion = aemet("/prediccion/ccaa/hoy/$ccaa");

    $idema = $comunidades[$ccaa][1];
    if ($idema) {
        $registros = json_decode(aemet("/observacion/convencional/datos/estacion/$idema") ?? '', true);
        $observacion = is_array($registros) && $registros ? end($registros) : null; // la hora más reciente
    }
}
//Códigos de las comunidades áutonomas
$comunidades = [
    'and' => ['Andalucía', '5783'],
    'arn' => ['Aragón', '9434'],
    'ast' => ['Asturias', null],
    'bal' => ['Illes Balears', null], 
    'coo' => ['Canarias', null],
    'can' => ['Cantabria', null],
    'cle' => ['Castilla y León', null],
    'clm' => ['Castilla-La Mancha', null],
    'cat' => ['Cataluña', '0076'],
    'val' => ['Comunitat Valenciana', '8416'],
    'ext' => ['Extremadura', null],
    'gal' => ['Galicia', null],
    'mad' => ['Comunidad de Madrid', '3195'],
    'mur' => ['Región de Murcia', null],
    'nav' => ['Navarra', null],
    'pva' => ['País Vasco', '1082'],
    'rio' => ['La Rioja', null],
    'ceu' => ['Ceuta', null],
    'mel' => ['Melilla', null],
];

$ccaa = $_GET['ccaa'] ?? '';
$prediccion = $observacion = null;

if (isset($comunidades[$ccaa])) {
    $prediccion = aemet("/prediccion/ccaa/hoy/$ccaa");

    $idema = $comunidades[$ccaa][1];
    if ($idema) {
        $registros = json_decode(aemet("/observacion/convencional/datos/estacion/$idema") ?? '', true);
        $observacion = is_array($registros) && $registros ? end($registros) : null; // la hora más reciente
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AEMET</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<main class="contenedor">
    <h1>El tiempo por comunidad autónoma</h1>

    <form method="get" class="selector">
        <label for="ccaa">Comunidad autónoma</label>
        <select name="ccaa" id="ccaa" onchange="this.form.submit()">
            <option value="">Elige una:</option>
            <?php foreach ($comunidades as $codigo => [$nombre]): ?>
                <option value="<?= e($codigo) ?>" <?= $codigo === $ccaa ? 'selected' : '' ?>><?= e($nombre) ?></option>
            <?php endforeach; ?>
        </select>
        <noscript><button type="submit">Ver</button></noscript>
    </form>

    <?php if (isset($comunidades[$ccaa])): ?>
        <section class="tarjeta">
            <h2>Predicción para hoy: <?= e($comunidades[$ccaa][0]) ?></h2>
            <?php if ($prediccion): ?>
                <pre class="prediccion"><?= e($prediccion) ?></pre>
            <?php else: ?>
                <p class="error">No se pudo obtener la predicción. Inténtalo más tarde.</p>
            <?php endif; ?>
        </section>

        <?php if ($observacion): ?>
            <section class="tarjeta">
                <h2>Ahora en <?= e($observacion['ubi'] ?? '') ?></h2>
                <dl class="datos">
                    <dt>Temperatura</dt><dd><?= e($observacion['ta'] ?? '–') ?> °C</dd>
                    <dt>Humedad</dt><dd><?= e($observacion['hr'] ?? '–') ?> %</dd>
                    <dt>Viento</dt><dd><?= e($observacion['vv'] ?? '–') ?> m/s</dd>
                    <dt>Precipitación</dt><dd><?= e($observacion['prec'] ?? '–') ?> mm</dd>
                </dl>
                <p class="nota">Medida: <?= e($observacion['fint'] ?? '') ?> (hora UTC)</p>
            </section>
        <?php endif; ?>
    <?php endif; ?>

    <footer>Fuente: AEMET <br>Hecho por Daniel Bernad Peñuelas</footer>
</main>
</body>
</html>
