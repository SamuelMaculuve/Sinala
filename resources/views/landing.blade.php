<!doctype html>
<html lang="pt-MZ">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sinala — Gestão de presenças, assinaturas e pagamentos digitais</title>
  <meta name="description" content="Plataforma para ONGs criarem eventos, registarem participantes, recolherem assinaturas digitais no tablet e controlarem pagamentos e subsídios sem papel.">
  <meta name="keywords" content="gestão de presenças, assinatura digital, eventos para ONGs, lista de presença digital, pagamentos de subsídios, formações, workshops, Moçambique">
  <meta name="author" content="Sinala">
  <meta name="robots" content="index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1">
  <meta name="theme-color" content="#111210">
  <link rel="canonical" href="{{ url('/') }}">
  <link rel="alternate" hreflang="pt-MZ" href="{{ url('/') }}">
  <link rel="alternate" hreflang="x-default" href="{{ url('/') }}">
  <link rel="icon" href="{{ asset('landing/favicon.svg') }}" type="image/svg+xml">
  <link rel="manifest" href="{{ asset('site.webmanifest') }}">
  <meta property="og:type" content="website">
  <meta property="og:locale" content="pt_MZ">
  <meta property="og:site_name" content="Sinala">
  <meta property="og:title" content="Sinala — Gestão de presenças sem papel">
  <meta property="og:description" content="Eventos, participantes, assinaturas digitais e pagamentos numa plataforma simples para organizações que trabalham no terreno.">
  <meta property="og:url" content="{{ url('/') }}">
  <meta property="og:image" content="{{ asset('landing/og-sinala.png') }}">
  <meta property="og:image:width" content="1200"><meta property="og:image:height" content="630"><meta property="og:image:alt" content="Sinala — presenças digitais para eventos e formações">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="Sinala — Gestão de presenças sem papel">
  <meta name="twitter:description" content="Recolha assinaturas digitais e controle presenças e pagamentos no tablet.">
  <meta name="twitter:image" content="{{ asset('landing/og-sinala.png') }}">
  <script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
  <link rel="stylesheet" href="{{ asset('landing/styles.css') }}">
</head>
<body>
  <a class="skip-link" href="#conteudo">Saltar para o conteúdo</a>
  <header class="site-header" id="inicio">
    <a class="brand" href="#inicio" aria-label="Sinala — início"><x-brand-logo /></a>
    <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="main-nav">
      <span class="sr-only">Abrir menu</span><i></i><i></i>
    </button>
    <nav class="main-nav" id="main-nav" aria-label="Navegação principal">
      <a href="#produto">Produto</a>
      <a href="#como-funciona">Como funciona</a>
      <a href="#planos">Planos</a>
      <a href="#faq">FAQ</a>
    </nav>
    <div class="header-actions">
      <a class="text-link" href="{{ route('login') }}">Entrar</a>
      <a class="button button-dark button-small" href="{{ route('register') }}">Começar grátis <span>↗</span></a>
    </div>
  </header>

  <main id="conteudo">
    <section class="hero" aria-labelledby="hero-title">
      <div class="hero-copy reveal">
        <p class="eyebrow"><span></span> Feito para equipas que estão no terreno</p>
        <h1 id="hero-title">Presenças sem papel.<br><em>Impacto</em> com provas.</h1>
        <p class="hero-text">Crie eventos, registe participantes, recolha assinaturas no tablet e controle pagamentos — tudo numa plataforma simples e segura.</p>
        <div class="hero-actions">
          <a class="button button-orange" href="{{ route('register') }}">Começar gratuitamente <span>↗</span></a>
          <button class="play-button" type="button" data-demo><span class="play-icon">▶</span> Ver como funciona</button>
        </div>
        <div class="hero-trust">
          <div class="avatar-stack" aria-hidden="true"><span>AM</span><span>JN</span><span>SF</span><span>+8</span></div>
          <p><strong>Organizações em movimento</strong><br>Mais controlo. Menos burocracia.</p>
        </div>
      </div>

      <div class="hero-visual reveal" aria-label="Pré-visualização do painel Sinala">
        <div class="orbit orbit-one"></div><div class="orbit orbit-two"></div>
        <div class="tablet">
          <div class="tablet-camera"></div>
          <div class="app-shell">
            <aside class="app-sidebar">
              <div class="mini-brand"><x-brand-logo class="h-7 w-auto" /></div>
              <nav aria-label="Pré-visualização">
                <span class="active"><b>⌂</b> Visão geral</span>
                <span><b>◇</b> Eventos</span>
                <span><b>♙</b> Participantes</span>
                <span><b>✓</b> Presenças</span>
                <span><b>↗</b> Pagamentos</span>
                <span><b>▤</b> Relatórios</span>
              </nav>
              <div class="mini-user"><span>AM</span><p>Ana Manuel<small>Gestora</small></p></div>
            </aside>
            <div class="app-main">
              <div class="app-topbar"><div><small>Bom dia, Ana</small><strong>Visão geral</strong></div><button>+ Novo evento</button></div>
              <div class="stat-grid">
                <article><small>Participantes</small><strong>1.284</strong><span>↗ 12%</span><i class="spark spark-a"></i></article>
                <article><small>Presenças</small><strong>926</strong><span>↗ 8%</span><i class="spark spark-b"></i></article>
                <article><small>Valor distribuído</small><strong>486k <sup>MT</sup></strong><span>↗ 18%</span><i class="spark spark-c"></i></article>
              </div>
              <div class="app-content-grid">
                <article class="event-card">
                  <div class="card-heading"><div><small>Evento em curso</small><h3>Formação de Liderança</h3></div><span class="live"><i></i> AO VIVO</span></div>
                  <div class="event-meta"><span>⌖ Maputo</span><span>◷ 08:00—16:00</span></div>
                  <div class="progress-label"><span>Presenças confirmadas</span><strong>68 / 80</strong></div>
                  <div class="progress"><i style="width:85%"></i></div>
                  <div class="event-actions"><button>Modo assinatura</button><button>Ver lista</button></div>
                </article>
                <article class="chart-card"><div class="card-heading"><div><small>Esta semana</small><h3>Participação</h3></div><b>•••</b></div><div class="donut"><span><strong>87%</strong><small>presença</small></span></div><div class="legend"><span><i></i> Presentes</span><span><i></i> Ausentes</span></div></article>
              </div>
              <div class="attendance-card">
                <div class="card-heading"><h3>Presenças recentes</h3><a href="#">Ver todas →</a></div>
                <div class="person-row"><span class="person-avatar coral">JM</span><p><strong>João Manuel</strong><small>Associação Horizonte</small></p><time>09:42</time><b class="checked">✓ Presente</b></div>
                <div class="person-row"><span class="person-avatar yellow">AC</span><p><strong>Amélia Cossa</strong><small>Fundação Mãos Unidas</small></p><time>09:38</time><b class="checked">✓ Presente</b></div>
              </div>
            </div>
          </div>
        </div>
        <div class="floating-card float-sign"><span>✓</span><p><strong>Assinatura guardada</strong><small>Sincronizada com segurança</small></p></div>
        <div class="floating-card float-online"><i></i> Online</div>
      </div>
    </section>

    <section class="logo-strip" aria-label="Tipos de organizações apoiadas">
      <p>Construído para quem transforma comunidades</p>
      <div><span>ONGs</span><span>Associações</span><span>Projectos sociais</span><span>Consultorias</span><span>Instituições</span></div>
    </section>

    <section class="intro section" id="produto">
      <div class="section-label">01 — O produto</div>
      <div class="intro-copy reveal">
        <p class="eyebrow"><span></span> Uma plataforma. Todo o evento.</p>
        <h2>Do primeiro nome à última <em>assinatura.</em></h2>
        <p>O Sinala liga a sua equipa, os participantes e os financiadores numa fonte única e fiável de informação.</p>
      </div>
      <div class="feature-grid">
        <article class="feature feature-orange reveal"><div class="feature-icon">✍</div><span>01</span><h3>Assine no tablet</h3><p>Uma experiência simples e intuitiva para recolher assinaturas com o dedo ou caneta.</p><div class="signature-demo"><p>João Manuel</p><svg viewBox="0 0 220 60" role="img" aria-label="Exemplo de assinatura"><path d="M6 47c24-44 20 22 42-8s-3 36 27 1c12-14 10 21 31 2 18-16 7 12 35 2 22-8 32-7 72-6"/></svg><small>Assinatura do participante</small></div></article>
        <article class="feature feature-dark reveal"><div class="feature-icon">◎</div><span>02</span><h3>Funciona no terreno</h3><p>Preparado para ligações instáveis. Continue a trabalhar e sincronize quando a rede regressar.</p><div class="sync-demo"><div class="pulse"></div><strong>7 registos</strong><small>a sincronizar</small><div class="sync-line"><i></i></div></div></article>
        <article class="feature feature-light reveal"><div class="feature-icon">▤</div><span>03</span><h3>Relatórios prontos</h3><p>Exporte presenças e pagamentos em PDF ou Excel, com a identidade da sua organização.</p><div class="report-demo"><div><span>S</span><p><small>RELATÓRIO</small><strong>Formação Comunitária</strong></p></div><i></i><i></i><i></i><button>PDF ↓</button></div></article>
      </div>
    </section>

    <section class="steps section" id="como-funciona">
      <div class="section-label light">02 — Como funciona</div>
      <div class="steps-head reveal"><p class="eyebrow light"><span></span> Simples por natureza</p><h2>Em campo, cada<br><em>toque conta.</em></h2></div>
      <div class="steps-grid">
        <article><strong>01</strong><div class="step-icon">＋</div><h3>Crie o evento</h3><p>Defina o local, as datas e importe a lista de participantes.</p></article>
        <article><strong>02</strong><div class="step-icon">⌕</div><h3>Encontre o nome</h3><p>No modo tablet, cada participante procura o seu nome.</p></article>
        <article><strong>03</strong><div class="step-icon">〰</div><h3>Assine</h3><p>A assinatura e a hora ficam associadas ao evento com segurança.</p></article>
        <article><strong>04</strong><div class="step-icon">↓</div><h3>Exporte</h3><p>Gere listas profissionais prontas para os seus relatórios.</p></article>
      </div>
    </section>

    <section class="impact section">
      <div class="impact-copy reveal"><p class="eyebrow"><span></span> Clareza para decidir</p><h2>Os números contam a história do seu <em>impacto.</em></h2><p>Veja o que está a acontecer em cada projecto, em tempo real. Sem folhas perdidas. Sem contas duplicadas. Sem dúvidas.</p><ul><li><span>✓</span> Presenças por dia e por participante</li><li><span>✓</span> Pagamentos confirmados com assinatura</li><li><span>✓</span> Indicadores por género, local e projecto</li></ul><a class="arrow-link" href="#planos">Explorar todas as funcionalidades <span>→</span></a></div>
      <div class="impact-panel reveal">
        <div class="impact-top"><span>Resumo de impacto</span><select aria-label="Período"><option>Últimos 30 dias</option></select></div>
        <div class="impact-numbers"><div><small>Taxa de presença</small><strong>92,4%</strong><span>↑ 4,8%</span></div><div><small>Participantes</small><strong>1.284</strong><span>↑ 12,2%</span></div></div>
        <div class="bar-chart" aria-label="Gráfico de participação semanal"><div style="--h:44%"><span>SEG</span></div><div style="--h:65%"><span>TER</span></div><div style="--h:54%"><span>QUA</span></div><div class="active" style="--h:88%"><b>248</b><span>QUI</span></div><div style="--h:72%"><span>SEX</span></div><div style="--h:40%"><span>SÁB</span></div><div style="--h:28%"><span>DOM</span></div></div>
      </div>
    </section>

    <section class="pricing section" id="planos">
      <div class="pricing-head reveal"><p class="eyebrow"><span></span> Planos transparentes</p><h2>Comece pequeno.<br><em>Cresça sem limites.</em></h2><p>Experimente gratuitamente. Faça upgrade quando a sua equipa estiver pronta.</p></div>
      <div class="pricing-grid">
        <article class="price-card"><p>FREE</p><h3>0 <small>MT</small></h3><span>Para começar sem compromisso.</span><a class="button button-outline" href="{{ route('register') }}">Criar conta grátis</a><ul><li>✓ Até 10 eventos</li><li>✓ Até 3 utilizadores</li><li>✓ 100 participantes/evento</li><li>✓ Presenças e pagamentos</li><li>✓ Assinaturas digitais</li><li>✓ Exportação PDF</li></ul></article>
        <article class="price-card featured"><div class="popular">MAIS ESCOLHIDO</div><p>PROFISSIONAL</p><h3>3.500 <small>MT / mês</small></h3><span>Para equipas com actividade regular.</span><a class="button button-orange" href="{{ route('register') }}">Começar agora <b>↗</b></a><ul><li>✓ 20 novos eventos/mês</li><li>✓ Participantes ilimitados</li><li>✓ Presenças e pagamentos</li><li>✓ PDF, Excel e QR Code</li><li>✓ Relatórios avançados</li></ul></article>
        <article class="price-card"><p>ORGANIZAÇÃO</p><h3>7.500 <small>MT / mês</small></h3><span>Para programas e equipas maiores.</span><a class="button button-outline" href="{{ route('register') }}">Escolher Organização</a><ul><li>✓ 100 novos eventos/mês</li><li>✓ Até 20 utilizadores</li><li>✓ Permissões e auditoria</li><li>✓ Relatórios consolidados</li></ul></article>
      </div>
    </section>

    <section class="faq section" id="faq">
      <div class="faq-head"><p class="eyebrow"><span></span> Perguntas frequentes</p><h2>Antes de<br><em>começar.</em></h2></div>
      <div class="accordion">
        <details><summary>Preciso de instalar alguma aplicação?<span>+</span></summary><p>Não. O Sinala funciona directamente no navegador do tablet ou computador e pode ser adicionado ao ecrã principal.</p></details>
        <details><summary>O que acontece se a internet falhar?<span>+</span></summary><p>Os registos podem ficar guardados no dispositivo como pendentes e são sincronizados quando a ligação regressar.</p></details>
        <details><summary>As assinaturas ficam seguras?<span>+</span></summary><p>Sim. Cada assinatura fica ligada ao participante, evento, data e hora, com controlo de acesso e registo de integridade.</p></details>
        <details><summary>Posso usar o logótipo da minha organização?<span>+</span></summary><p>Sim. O logótipo e os dados da organização aparecem automaticamente nos relatórios exportados.</p></details>
      </div>
    </section>

    <section class="final-cta">
      <div><p class="eyebrow light"><span></span> O próximo evento começa aqui</p><h2>Troque o papel<br>por <em>progresso.</em></h2><p>Configure a sua organização em poucos minutos.</p><a class="button button-orange" href="{{ route('register') }}">Começar gratuitamente <span>↗</span></a></div>
      <div class="cta-mark" aria-hidden="true"><x-brand-logo class="cta-logo" /></div>
    </section>
  </main>

  <footer><a class="brand" href="#inicio" aria-label="Sinala — início"><x-brand-logo /></a><p>Presenças que provam impacto.</p><div><a href="#produto">Produto</a><a href="#planos">Planos</a><a href="#faq">Ajuda</a></div><small>© 2026 Sinala. Feito em Moçambique.</small></footer>

  <dialog class="demo-modal" aria-labelledby="demo-title"><button class="modal-close" type="button" aria-label="Fechar">×</button><div class="demo-check">✓</div><h2 id="demo-title">Presença confirmada!</h2><p>João Manuel assinou a lista da Formação de Liderança.</p><div><span>Hoje, 09:42</span><span>Maputo</span></div><button class="button button-dark modal-ok" type="button">Continuar</button></dialog>
  <script src="{{ asset('landing/script.js') }}"></script>
</body>
</html>
