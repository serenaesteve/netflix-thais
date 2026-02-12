<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');

$SITE_NAME = 'NetflixThais';
$TAGLINE   = 'Fotografía · Familia · Naturaleza & Animales';
$BASE_URL  = './';             
$CANONICAL = $BASE_URL;
$OG_IMAGE  = $BASE_URL . 'og.png';      
$FAVICON   = $BASE_URL . 'favicon.png'; 


$page = (string)($_GET['page'] ?? 'home');
$page = ($page === 'videos') ? 'videos' : 'home';


function h(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function safe_rel_path(string $path): string {
  $path = str_replace('\\', '/', $path);
  $path = ltrim($path, '/');
  if ($path === '' || str_contains($path, '..')) return '';
  return $path;
}
function str_lower(string $s): string {

  return strtolower($s);
}
function slug_id(string $s): string {
  $s = trim(str_lower($s));
  $s = preg_replace('~[^a-z0-9]+~', '-', $s) ?? '';
  $s = trim($s, '-');
  return $s !== '' ? ('sec-' . $s) : ('sec-' . bin2hex(random_bytes(4)));
}
function pick_cover(array $items): array {

  foreach ($items as $it) {
    $img = safe_rel_path((string)($it['image_file'] ?? ''));
    $th  = safe_rel_path((string)($it['thumbnail_file'] ?? ''));
    if ($img !== '' || $th !== '') {
      return [$img, $th];
    }
  }
  return ['', ''];
}


$jsonFile = __DIR__ . '/photos_sections.json';

$data = null;
if (is_file($jsonFile)) {
  $raw = file_get_contents($jsonFile);
  if ($raw !== false) {
    $tmp = json_decode($raw, true);
    if (is_array($tmp)) $data = $tmp;
  }
}

$sections = is_array($data['sections'] ?? null) ? $data['sections'] : [];


$normalizedSections = [];
$allPhotos = [];
$totalPhotos = 0;

foreach ($sections as $sec) {
  if (!is_array($sec)) continue;

  $title = (string)($sec['title'] ?? '');
  $title = trim($title) !== '' ? trim($title) : 'Sección';
  $id    = slug_id($title);

  $items = is_array($sec['items'] ?? null) ? $sec['items'] : [];

  $normItems = [];
  foreach ($items as $it) {
    if (!is_array($it)) continue;
    $t  = (string)($it['title'] ?? '');
    $d  = (string)($it['description'] ?? '');
    $imgRel = safe_rel_path((string)($it['image_file'] ?? ''));
    $thRel  = safe_rel_path((string)($it['thumbnail_file'] ?? ''));


    $useThumbRel = $thRel !== '' ? $thRel : $imgRel;

    $imgOk  = ($imgRel !== '' && is_file(__DIR__ . '/' . $imgRel)) ? $imgRel : '';
    $thOk   = ($useThumbRel !== '' && is_file(__DIR__ . '/' . $useThumbRel)) ? $useThumbRel : '';

    if ($imgOk === '' && $imgRel !== '' && is_file(__DIR__ . '/' . $imgRel)) $imgOk = $imgRel;
    if ($thOk === '' && $imgOk !== '') $thOk = $imgOk;


    if ($imgOk === '' && $thOk === '') continue;

    $item = [
      'title' => $t !== '' ? $t : 'Foto',
      'description' => $d,
      'image' => $imgOk !== '' ? $imgOk : $thOk,
      'thumb' => $thOk !== '' ? $thOk : $imgOk,
      'section_title' => $title,
      'section_id' => $id,
    ];
    $normItems[] = $item;
    $allPhotos[] = $item;
    $totalPhotos++;
  }

  $normalizedSections[] = [
    'id' => $id,
    'title' => $title,
    'items' => $normItems,
  ];
}

$DESCRIPTION_HOME   = "Portfolio de fotografía estilo Netflix: {$totalPhotos} fotos organizadas en secciones (Familia, Naturaleza & Animales).";
$DESCRIPTION_VIDEOS = "Todas las fotos en una sola página con buscador: {$totalPhotos} fotos.";
$canonical = ($page === 'videos') ? ($BASE_URL . '?page=videos') : $CANONICAL;
$metaDesc  = ($page === 'videos') ? $DESCRIPTION_VIDEOS : $DESCRIPTION_HOME;
$titleTag  = ($page === 'videos')
  ? ($SITE_NAME . ' · Todas las fotos · ' . $TAGLINE)
  : ($SITE_NAME . ' · ' . $TAGLINE);

$heroTitle = $SITE_NAME;
$heroDesc  = $TAGLINE;
$heroBg    = '';

foreach ($normalizedSections as $sec) {
  if (!empty($sec['items'])) {
    $heroTitle = (string)$sec['title'];
    $heroDesc  = "Explora la sección “{$sec['title']}” y desliza como Netflix.";
    $cover = $sec['items'][0]['image'] ?? '';
    if ($cover !== '') $heroBg = $cover;
    break;
  }
}


$ldSections = [];
foreach ($normalizedSections as $sec) {
  $img = '';
  if (!empty($sec['items'])) $img = $sec['items'][0]['image'] ?? '';
  $ldSections[] = [
    '@type' => 'Collection',
    'name' => $sec['title'],
    'url'  => $BASE_URL . '#'.$sec['id'],
    'image'=> $img ? (rtrim($BASE_URL,'/').'/'.ltrim($img,'/')) : $OG_IMAGE,
  ];
}
$schema = [
  '@context' => 'https://schema.org',
  '@graph' => [
    [
      '@type' => 'WebSite',
      'name' => $SITE_NAME,
      'url' => $BASE_URL,
      'description' => $DESCRIPTION_HOME,
      'inLanguage' => 'es',
    ],
    [
      '@type' => 'CollectionPage',
      'name' => $titleTag,
      'url' => $canonical,
      'description' => $metaDesc,
      'inLanguage' => 'es',
      'hasPart' => $ldSections,
    ]
  ]
];
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?= h($titleTag) ?></title>

  <meta name="description" content="<?= h($metaDesc) ?>">
  <meta name="robots" content="index,follow,max-image-preview:large,max-snippet:-1">
  <link rel="canonical" href="<?= h($canonical) ?>">
  <link rel="icon" href="<?= h($FAVICON) ?>">

  <meta property="og:type" content="website">
  <meta property="og:site_name" content="<?= h($SITE_NAME) ?>">
  <meta property="og:title" content="<?= h($titleTag) ?>">
  <meta property="og:description" content="<?= h($metaDesc) ?>">
  <meta property="og:url" content="<?= h($canonical) ?>">
  <meta property="og:image" content="<?= h($OG_IMAGE) ?>">

  <meta name="theme-color" content="#000000">
  <script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) ?></script>

  <style>
    :root{
      --bg:#000;
      --card:#111;
      --card2:#0c0c0c;
      --text:#fff;
      --muted:rgba(255,255,255,.72);
      --line:rgba(255,255,255,.08);
      --soft:rgba(255,255,255,.14);
      --soft2:rgba(255,255,255,.20);
      --accent: rgba(229,9,20,.95); 
      --accentSoft: rgba(229,9,20,.25);
      --radius:14px;
    }
    *{box-sizing:border-box}
    html,body{height:100%}
    body{
      margin:0;
      background:var(--bg);
      color:var(--text);
      font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
      overflow-x:hidden;
    }
    a{color:inherit}

    nav{
      width:320px;
      background:linear-gradient(180deg, #0a0a0a, #050505);
      position:fixed;
      top:0;left:-320px;height:100%;
      padding:18px 16px;
      z-index:1200;
      overflow:auto;
      border-right:1px solid var(--line);
      transition:left 280ms ease;
    }
    nav.open{left:0}
    .navTitle{font-weight:900;font-size:14px;letter-spacing:.08em;text-transform:uppercase;opacity:.9;margin-bottom:10px}
    .navMeta{font-size:12px;opacity:.82;margin-bottom:14px}
    .navList{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:8px}
    .navList a{
      display:block;text-decoration:none;
      padding:10px 10px;border-radius:12px;
      background:rgba(255,255,255,.06);
      border:1px solid rgba(255,255,255,.10);
      font-weight:800;font-size:13px;line-height:1.2;
      transition:transform .12s ease, background .12s ease, border-color .12s ease;
    }
    .navList a:hover{
      background:rgba(255,255,255,.10);
      border-color:rgba(255,255,255,.18);
      transform:translateY(-1px);
    }
    .navList small{display:block;margin-top:6px;opacity:.75;font-weight:700}


    header{
      position:fixed;top:0;left:0;right:0;
      height:74px;
      display:flex;align-items:center;justify-content:space-between;
      padding:14px 18px;
      z-index:1100;
      background:linear-gradient(180deg, rgba(0,0,0,.92), rgba(0,0,0,.65));
      border-bottom:1px solid var(--line);
      backdrop-filter: blur(10px);
    }
    .brand{display:flex;flex-direction:column;gap:2px}
    .brand .name{font-weight:950;letter-spacing:.02em}
    .brand .tag{font-size:12px;opacity:.8}
    .topActions{display:flex;align-items:center;gap:10px;flex-wrap:wrap;justify-content:flex-end}
    .btnTop{
      display:inline-flex;align-items:center;justify-content:center;
      padding:9px 12px;border-radius:12px;
      border:1px solid rgba(255,255,255,.14);
      background:rgba(255,255,255,.06);
      font-weight:900;font-size:13px;
      text-decoration:none;cursor:pointer;user-select:none;
      transition:background .12s ease, border-color .12s ease, transform .12s ease;
      white-space:nowrap;
    }
    .btnTop:hover{background:rgba(255,255,255,.10);border-color:rgba(255,255,255,.22);transform:translateY(-1px)}
    .btnTopPrimary{
      border-color: rgba(229,9,20,.55);
      background: rgba(229,9,20,.18);
    }
    .btnTopPrimary:hover{background:rgba(229,9,20,.26)}
    .hamburger{font-size:18px;padding:9px 14px}

    main{padding-top:86px;padding-bottom:26px}


    .hero{
      position:relative;
      width:100%;
      min-height:420px;
      border-bottom:1px solid var(--line);
      overflow:hidden;
      margin-bottom:18px;
    }
    .heroBg{
      position:absolute;inset:0;
      background:#0b0b0b;
      background-size:cover;background-position:center;
      transform:scale(1.03);
      filter:saturate(1.02) contrast(1.05);
    }
    .heroShade{
      position:absolute;inset:0;
      background:
        radial-gradient(75% 85% at 20% 25%, rgba(0,0,0,.35) 0%, rgba(0,0,0,.88) 60%, rgba(0,0,0,.98) 100%),
        linear-gradient(90deg, rgba(0,0,0,.90) 0%, rgba(0,0,0,.58) 55%, rgba(0,0,0,.25) 100%);
    }
    .heroInner{
      position:relative;
      min-height:420px;
      display:flex;align-items:flex-end;
      padding:26px 18px;
      max-width:1200px;
      margin:0 auto;
    }
    .heroKicker{
      font-size:12px;letter-spacing:.10em;text-transform:uppercase;
      opacity:.86;margin-bottom:10px;
    }
    .heroTitle{
      font-size:42px;line-height:1.02;font-weight:950;
      text-shadow:0 8px 40px rgba(0,0,0,.6);
      margin-bottom:10px;
    }
    .heroDesc{
      max-width:820px;
      font-size:14px;line-height:1.55;
      opacity:.92;
    }
    .heroActions{display:flex;gap:10px;flex-wrap:wrap;margin-top:16px}
    .btn{
      display:inline-flex;align-items:center;justify-content:center;
      padding:10px 14px;border-radius:12px;
      border:1px solid rgba(255,255,255,.14);
      background:rgba(255,255,255,.06);
      font-weight:900;font-size:13px;
      cursor:pointer;user-select:none;text-decoration:none;
      transition:background .12s ease, border-color .12s ease, transform .12s ease;
    }
    .btn:hover{background:rgba(255,255,255,.10);border-color:rgba(255,255,255,.22);transform:translateY(-1px)}
    .btnPrimary{
      border-color: rgba(229,9,20,.65);
      background: rgba(229,9,20,.22);
    }
    .btnPrimary:hover{background: rgba(229,9,20,.30)}
    @media (max-width:720px){
      .heroTitle{font-size:34px}
      .hero{min-height:380px}
      .heroInner{min-height:380px}
    }


    section.row{
      margin:18px auto 26px auto;
      max-width:1300px;
      padding:0 10px;
      scroll-margin-top:96px;
    }
    .rowHead{
      display:flex;align-items:baseline;justify-content:space-between;
      padding:0 8px;margin-bottom:10px;gap:10px;
    }
    .rowTitle{
      font-weight:950;font-size:18px;letter-spacing:.01em;
    }
    .rowMeta{
      font-size:12px;opacity:.78;white-space:nowrap;
    }

    .rail{
      position:relative;
      border-radius:16px;
    }
    .track{
      display:flex;gap:14px;
      overflow:hidden;
      padding:8px 6px 14px 6px;
    }
    .strip{
      display:flex;gap:14px;
      transform:translateX(0px);
      transition:transform 420ms cubic-bezier(.2,.8,.2,1);
      will-change:transform;
    }

    .tile{
      width:280px;height:158px;
      border-radius:14px;
      overflow:hidden;
      background:linear-gradient(180deg, #101010, #0b0b0b);
      border:1px solid rgba(255,255,255,.10);
      cursor:pointer;
      position:relative;
      flex:0 0 auto;
      box-shadow:0 14px 40px rgba(0,0,0,.45);
      transition:transform .18s ease, border-color .18s ease, box-shadow .18s ease;
    }
    .tile:hover{
      transform:scale(1.04);
      border-color:rgba(255,255,255,.22);
      box-shadow:0 18px 60px rgba(0,0,0,.62);
      z-index:2;
    }
    .tileImg{
      position:absolute;inset:0;
      background:#111;
      background-size:cover;background-position:center;
      filter:saturate(1.02);
      transform:scale(1.02);
    }
    .tileShade{
      position:absolute;inset:0;
      background:linear-gradient(0deg, rgba(0,0,0,.86) 0%, rgba(0,0,0,.10) 68%, rgba(0,0,0,0) 100%);
    }
    .tileMeta{
      position:absolute;left:10px;right:10px;bottom:10px;
      display:flex;flex-direction:column;gap:6px;
      text-shadow:0 2px 10px rgba(0,0,0,.8);
    }
    .tileTitle{
      font-weight:900;font-size:13px;line-height:1.2;
      display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;
      overflow:hidden;
    }
    .tileSub{font-size:12px;opacity:.80;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

    .arrow{
      position:absolute;top:50%;transform:translateY(-50%);
      width:44px;height:44px;border-radius:999px;
      display:flex;align-items:center;justify-content:center;
      background:rgba(0,0,0,.45);
      border:1px solid rgba(255,255,255,.16);
      cursor:pointer;user-select:none;
      opacity:.32;
      transition:opacity .14s ease, background .14s ease, transform .14s ease;
      z-index:5;
    }
    .arrow:hover{opacity:.98;background:rgba(0,0,0,.65);transform:translateY(-50%) scale(1.03)}
    .arrow.left{left:8px}
    .arrow.right{right:8px}
    .arrow span{font-size:22px;line-height:1}
    .arrow[aria-disabled="true"]{opacity:.10;pointer-events:none}

    @media (max-width:900px){
      .tile{width:240px;height:138px}
    }
    @media (max-width:600px){
      .tile{width:210px;height:120px}
      .arrow{display:none} 
      .track{overflow-x:auto}
      .track::-webkit-scrollbar{height:10px}
      .track::-webkit-scrollbar-thumb{background:rgba(255,255,255,.14);border-radius:999px}
      .strip{transition:none}
    }


    .videosWrap{max-width:1300px;margin:0 auto;padding:0 12px}
    .searchBar{
      display:flex;gap:10px;align-items:center;flex-wrap:wrap;
      margin:10px 0 16px 0;
    }
    .searchInput{
      flex:1 1 340px;
      background:rgba(255,255,255,.06);
      border:1px solid rgba(255,255,255,.14);
      border-radius:14px;
      padding:12px 12px;
      color:white;font-weight:850;outline:none;
    }
    .searchInput::placeholder{color:rgba(255,255,255,.55);font-weight:750}
    .searchMeta{font-size:12px;opacity:.82;white-space:nowrap}

    .grid{
      display:grid;
      grid-template-columns: repeat(6, minmax(0, 1fr));
      gap:12px;
      padding-bottom:16px;
    }
    @media (max-width: 1400px){ .grid{ grid-template-columns: repeat(5, minmax(0,1fr)); } }
    @media (max-width: 1150px){ .grid{ grid-template-columns: repeat(4, minmax(0,1fr)); } }
    @media (max-width: 900px){ .grid{ grid-template-columns: repeat(3, minmax(0,1fr)); } }
    @media (max-width: 620px){ .grid{ grid-template-columns: repeat(2, minmax(0,1fr)); } }
    @media (max-width: 420px){ .grid{ grid-template-columns: repeat(1, minmax(0,1fr)); } }

    .gridItem{
      position:relative;border-radius:14px;overflow:hidden;
      background:#0f0f0f;border:1px solid rgba(255,255,255,.10);
      cursor:pointer;min-height:160px;
      box-shadow:0 14px 44px rgba(0,0,0,.45);
      transition:transform .16s ease, border-color .16s ease, box-shadow .16s ease;
    }
    .gridItem:hover{transform:scale(1.02);border-color:rgba(255,255,255,.22);box-shadow:0 18px 60px rgba(0,0,0,.62)}
    .gridThumb{position:absolute;inset:0;background:#111;background-size:cover;background-position:center;transform:scale(1.02)}
    .gridShade{position:absolute;inset:0;background:linear-gradient(0deg, rgba(0,0,0,.88) 0%, rgba(0,0,0,.18) 68%, rgba(0,0,0,.08) 100%)}
    .gridMeta{position:absolute;left:10px;right:10px;bottom:10px;display:flex;flex-direction:column;gap:6px;text-shadow:0 2px 10px rgba(0,0,0,.8)}
    .gridTitle{font-weight:900;font-size:13px;line-height:1.2;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
    .gridSub{font-size:12px;opacity:.82;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}


    .modalOverlay{
      position:fixed;inset:0;
      background:rgba(0,0,0,.78);
      display:none;
      align-items:center;justify-content:center;
      z-index:3000;padding:20px;
    }
    .modalOverlay.open{display:flex}
    .modalCard{
      width:min(980px, 96vw);
      background:linear-gradient(180deg, #0c0c0c, #070707);
      border:1px solid rgba(255,255,255,.14);
      border-radius:16px;
      overflow:hidden;
      box-shadow:0 26px 90px rgba(0,0,0,.70);
      display:flex;
    }
    .modalMedia{width:min(560px, 58vw);background:#111;flex:0 0 auto}
    .modalMedia img{width:100%;height:100%;max-height:520px;object-fit:cover;display:block;background:#111}
    .modalBody{padding:16px 16px 14px 16px;display:flex;flex-direction:column;gap:10px;flex:1 1 auto;min-width:0}
    .modalTop{display:flex;align-items:flex-start;justify-content:space-between;gap:10px}
    .modalTitle{font-size:18px;font-weight:950;line-height:1.2;overflow:hidden;text-overflow:ellipsis}
    .modalMetaLine{font-size:12px;opacity:.75;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .modalDesc{font-size:13px;line-height:1.5;opacity:.92;max-height:220px;overflow:auto;padding-right:6px}
    .modalDesc::-webkit-scrollbar{width:8px}
    .modalDesc::-webkit-scrollbar-thumb{background:rgba(255,255,255,.14);border-radius:999px}
    .modalActions{display:flex;gap:10px;margin-top:auto;padding-top:6px;flex-wrap:wrap}
    .closeBtn{
      width:36px;height:36px;border-radius:999px;
      display:flex;align-items:center;justify-content:center;
      border:1px solid rgba(255,255,255,.16);
      background:rgba(255,255,255,.06);
      cursor:pointer;user-select:none;
      transition:background .12s ease,border-color .12s ease,transform .12s ease;
      flex:0 0 auto;
    }
    .closeBtn:hover{background:rgba(255,255,255,.10);border-color:rgba(255,255,255,.24);transform:translateY(-1px)}
    @media (max-width:820px){
      .modalCard{flex-direction:column}
      .modalMedia{width:100%}
      .modalMedia img{max-height:320px}
    }


    .empty{
      max-width:1000px;margin:30px auto;padding:14px 12px;
      border:1px solid rgba(255,255,255,.10);
      background:rgba(255,255,255,.05);
      border-radius:14px;
      opacity:.92;
    }
    footer{
      max-width:1300px;
      margin:0 auto;
      padding:14px 12px 26px 12px;
      border-top:1px solid var(--line);
      color:rgba(255,255,255,.70);
      font-size:12px;
    }
  </style>
</head>

<body>
  <nav aria-label="Secciones">
    <div class="navTitle">Secciones</div>
    <div class="navMeta">
      <?= (int)count($normalizedSections) ?> secciones · <?= (int)$totalPhotos ?> fotos
    </div>

    <?php if (!$normalizedSections): ?>
      <div class="navMeta">No hay secciones. Crea <b>photos_sections.json</b>.</div>
    <?php else: ?>
      <ul class="navList">
        <?php foreach ($normalizedSections as $sec): ?>
          <li>
            <a href="#<?= h($sec['id']) ?>" data-scroll="#<?= h($sec['id']) ?>">
              <?= h($sec['title']) ?>
              <small><?= (int)count($sec['items']) ?> fotos</small>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </nav>

  <header>
    <div class="brand">
      <div class="name"><?= h($SITE_NAME) ?></div>
      <div class="tag"><?= h($TAGLINE) ?></div>
    </div>

    <div class="topActions">
      <a class="btnTop <?= $page==='home'?'btnTopPrimary':'' ?>" href="<?= h($BASE_URL) ?>?page=home">Inicio</a>
      <a class="btnTop <?= $page==='videos'?'btnTopPrimary':'' ?>" href="<?= h($BASE_URL) ?>?page=videos">Todas</a>
      <div class="btnTop hamburger" id="hamburger" role="button" tabindex="0" aria-label="Abrir menú">☰</div>
    </div>
  </header>

  <main>
    <?php if (!$normalizedSections): ?>
      <div class="empty">
        No se pudo leer <b><?= h(basename($jsonFile)) ?></b> o no tiene secciones.<br>
        Ruta esperada: <code><?= h($jsonFile) ?></code>
      </div>
    <?php else: ?>

      <?php if ($page === 'home'): ?>
        <section class="hero" id="hero" aria-label="Portada">
          <div class="heroBg" style="<?= $heroBg ? 'background-image:url('.h($heroBg).');' : '' ?>"></div>
          <div class="heroShade"></div>
          <div class="heroInner">
            <div>
              <div class="heroKicker">Portfolio fotográfico</div>
              <div class="heroTitle"><?= h($heroTitle) ?></div>
              <div class="heroDesc"><?= h($heroDesc) ?></div>
              <div class="heroActions">
                <a class="btn btnPrimary" href="<?= h($BASE_URL) ?>?page=videos">Ver todas las fotos</a>
                <?php if (!empty($normalizedSections[0]['id'])): ?>
                  <div class="btn" data-scroll-to="#<?= h($normalizedSections[0]['id']) ?>">Bajar a secciones</div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </section>

        <?php foreach ($normalizedSections as $sec): ?>
          <section class="row" id="<?= h($sec['id']) ?>" aria-label="Sección <?= h($sec['title']) ?>">
            <div class="rowHead">
              <div class="rowTitle"><?= h($sec['title']) ?></div>
              <div class="rowMeta"><?= (int)count($sec['items']) ?> fotos</div>
            </div>

            <?php if (empty($sec['items'])): ?>
              <div class="empty">No hay fotos en esta sección.</div>
            <?php else: ?>
              <div class="rail" data-rail>
                <div class="arrow left" data-left aria-label="Anterior"><span>‹</span></div>
                <div class="arrow right" data-right aria-label="Siguiente"><span>›</span></div>

                <div class="track" data-track>
                  <div class="strip" data-strip>
                    <?php foreach ($sec['items'] as $it): ?>
                      <?php
                        $thumb = (string)$it['thumb'];
                        $img   = (string)$it['image'];
                        $t     = (string)$it['title'];
                        $d     = (string)$it['description'];
                      ?>
                      <article class="tile"
                        data-title="<?= h($t) ?>"
                        data-desc="<?= h($d) ?>"
                        data-image="<?= h($img) ?>"
                        data-section="<?= h($sec['title']) ?>"
                        role="button"
                        aria-label="Abrir foto: <?= h($t) ?>"
                      >
                        <div class="tileImg" style="background-image:url('<?= h($thumb) ?>')"></div>
                        <div class="tileShade"></div>
                        <div class="tileMeta">
                          <div class="tileTitle"><?= h($t) ?></div>
                          <div class="tileSub"><?= h($sec['title']) ?></div>
                        </div>
                      </article>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
            <?php endif; ?>
          </section>
        <?php endforeach; ?>

      <?php else: ?>
        <div class="videosWrap" aria-label="Todas las fotos">
          <h2 style="margin:10px 6px 10px 6px;font-weight:950;font-size:22px;">Todas las fotos</h2>

          <div class="searchBar">
            <input id="q" class="searchInput" type="search" placeholder="Buscar por título o sección…" autocomplete="off">
            <div class="searchMeta">
              <span id="countVisible"><?= (int)count($allPhotos) ?></span> / <?= (int)count($allPhotos) ?>
            </div>
          </div>

          <div class="grid" id="grid">
            <?php foreach ($allPhotos as $p): ?>
              <?php
                $thumb = (string)$p['thumb'];
                $img   = (string)$p['image'];
                $t     = (string)$p['title'];
                $secT  = (string)$p['section_title'];
                $search = str_lower($t . ' ' . $secT);
              ?>
              <article class="gridItem"
                data-title="<?= h($t) ?>"
                data-desc="<?= h((string)$p['description']) ?>"
                data-image="<?= h($img) ?>"
                data-section="<?= h($secT) ?>"
                data-search="<?= h($search) ?>"
                role="button"
                aria-label="Abrir foto: <?= h($t) ?>"
              >
                <div class="gridThumb" style="background-image:url('<?= h($thumb) ?>')"></div>
                <div class="gridShade"></div>
                <div class="gridMeta">
                  <div class="gridTitle"><?= h($t) ?></div>
                  <div class="gridSub"><?= h($secT) ?></div>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

    <?php endif; ?>
  </main>

  <footer>
    <?= h($SITE_NAME) ?> · <?= h($TAGLINE) ?> · Fotos: <?= (int)$totalPhotos ?>
  </footer>


  <div class="modalOverlay" id="modalOverlay" aria-hidden="true">
    <div class="modalCard" id="modalCard" role="dialog" aria-modal="true" aria-label="Foto">
      <div class="modalMedia">
        <img id="modalImg" alt="Foto" src="" loading="lazy" decoding="async">
      </div>
      <div class="modalBody">
        <div class="modalTop">
          <div style="min-width:0;">
            <div class="modalTitle" id="modalTitle"></div>
            <div class="modalMetaLine" id="modalSection"></div>
          </div>
          <div class="closeBtn" id="modalClose" aria-label="Cerrar" title="Cerrar">✕</div>
        </div>

        <div class="modalDesc" id="modalDesc"></div>

        <div class="modalActions">
          <a class="btn btnPrimary" id="modalOpenImg" href="#" target="_blank" rel="noopener">Abrir imagen</a>
          <div class="btn" id="modalCancel">Cerrar</div>
        </div>
      </div>
    </div>
  </div>

  <noscript>
    <div class="empty">Activa JavaScript para el modal, menú y buscador.</div>
  </noscript>

  <script>

    const nav = document.querySelector('nav');
    const hamb = document.getElementById('hamburger');
    let navOpen = false;

    function toggleNav(){
      navOpen = !navOpen;
      nav.classList.toggle('open', navOpen);
      hamb.textContent = navOpen ? '🗙' : '☰';
    }
    hamb.addEventListener('click', toggleNav);
    hamb.addEventListener('keydown', (e)=>{
      if(e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggleNav(); }
    });


    document.querySelectorAll('nav a[data-scroll]').forEach(a=>{
      a.addEventListener('click', (e)=>{
        const targetSel = a.getAttribute('data-scroll');
        if(!targetSel) return;

        if (window.location.search.includes('page=videos')) return; 

        const sec = document.querySelector(targetSel);
        if(sec){
          e.preventDefault();
          sec.scrollIntoView({behavior:'smooth', block:'start'});
          if(navOpen) toggleNav();
        }
      });
    });


    document.querySelectorAll('[data-scroll-to]').forEach(b=>{
      b.addEventListener('click', ()=>{
        const t = b.getAttribute('data-scroll-to');
        const sec = t ? document.querySelector(t) : null;
        if(sec) sec.scrollIntoView({behavior:'smooth', block:'start'});
      });
    });


    const overlay = document.getElementById('modalOverlay');
    const card = document.getElementById('modalCard');
    const mImg = document.getElementById('modalImg');
    const mTitle = document.getElementById('modalTitle');
    const mSection = document.getElementById('modalSection');
    const mDesc = document.getElementById('modalDesc');
    const mClose = document.getElementById('modalClose');
    const mCancel = document.getElementById('modalCancel');
    const mOpen = document.getElementById('modalOpenImg');

    function openModal({title, desc, image, section}){
      mTitle.textContent = title || 'Foto';
      mSection.textContent = section ? ('Sección: ' + section) : '';
      const d = (desc || '').trim();
      mDesc.textContent = d ? d : '—';
      mImg.src = image || '';
      mOpen.href = image || '#';

      overlay.classList.add('open');
      overlay.setAttribute('aria-hidden','false');
      mClose.focus?.();
    }
    function closeModal(){
      overlay.classList.remove('open');
      overlay.setAttribute('aria-hidden','true');
      mImg.removeAttribute('src');
      mOpen.href = '#';
    }

    document.querySelectorAll('[data-image][data-title]').forEach(el=>{
      el.addEventListener('click', ()=>{
        const title = el.getAttribute('data-title') || '';
        const desc  = el.getAttribute('data-desc') || '';
        const image = el.getAttribute('data-image') || '';
        const section = el.getAttribute('data-section') || '';
        if(!image) return;
        openModal({title, desc, image, section});
      });
    });

    mClose.addEventListener('click', closeModal);
    mCancel.addEventListener('click', closeModal);
    overlay.addEventListener('click', (e)=>{ if(e.target === overlay) closeModal(); });
    card.addEventListener('click', (e)=> e.stopPropagation());
    document.addEventListener('keydown', (e)=>{ if(e.key === 'Escape' && overlay.classList.contains('open')) closeModal(); });


    function clamp(n, min, max){ return Math.max(min, Math.min(max, n)); }

    document.querySelectorAll('[data-rail]').forEach(rail=>{
      const track = rail.querySelector('[data-track]');
      const strip = rail.querySelector('[data-strip]');
      const left  = rail.querySelector('[data-left]');
      const right = rail.querySelector('[data-right]');

      let x = 0;

      function maxScroll(){
        if(!track || !strip) return 0;
        const max = strip.scrollWidth - track.clientWidth;
        return Math.max(0, max);
      }

      function updateArrows(){
        const max = maxScroll();
        if(left)  left.setAttribute('aria-disabled', x <= 0 ? 'true' : 'false');
        if(right) right.setAttribute('aria-disabled', x >= max ? 'true' : 'false');
      }

      function render(){
        if(strip) strip.style.transform = 'translateX(' + (-x) + 'px)';
        updateArrows();
      }

      function step(){

        return Math.max(260, Math.floor(track.clientWidth * 0.9));
      }

      left?.addEventListener('click', ()=>{
        x = clamp(x - step(), 0, maxScroll());
        render();
      });
      right?.addEventListener('click', ()=>{
        x = clamp(x + step(), 0, maxScroll());
        render();
      });

      window.addEventListener('resize', ()=>{
        x = clamp(x, 0, maxScroll());
        render();
      });

      render();
    });

    const q = document.getElementById('q');
    const grid = document.getElementById('grid');
    const countVisible = document.getElementById('countVisible');

    function applyFilter(){
      if(!q || !grid) return;
      const needle = (q.value || '').trim().toLowerCase();
      let shown = 0;
      grid.querySelectorAll('[data-search]').forEach(item=>{
        const hay = (item.getAttribute('data-search') || '');
        const ok = !needle || hay.includes(needle);
        item.style.display = ok ? '' : 'none';
        if(ok) shown++;
      });
      if(countVisible) countVisible.textContent = String(shown);
    }

    if(q){
      q.addEventListener('input', applyFilter);
      applyFilter();
    }
  </script>
</body>
</html>

