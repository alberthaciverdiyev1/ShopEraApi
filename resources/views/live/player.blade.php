<!DOCTYPE html>
<html lang="az">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<meta name="referrer" content="strict-origin-when-cross-origin">
<meta name="robots" content="noindex, nofollow">
<title>Teymur Store</title>
<style>
  html, body { margin: 0; height: 100%; background: #000; overflow: hidden; }
  #stage { position: fixed; inset: 0; overflow: hidden; background: #000; }
  /* contain: the iframe is the box and YouTube fits the frame inside it.
     cover: the iframe is grown past the box by script and centred, so the
     frame fills the screen and the overflow is clipped. */
  #player { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
            width: 100%; height: 100%; border: 0; }
  #fallback { position: absolute; inset: 0; display: none; align-items: center;
              justify-content: center; color: #8a8a8a; background: #000;
              font: 14px -apple-system, Roboto, sans-serif; text-align: center; padding: 24px; }
</style>
</head>
<body>
<div id="stage">
  <iframe id="player" allow="autoplay; encrypted-media; picture-in-picture; fullscreen"
          referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
  <div id="fallback">Yayım hazırda oynadıla bilmir</div>
</div>

<script id="cfg" type="application/json">{!! json_encode([
    'video' => $video,
    'fit' => $fit,
    'ratio' => $ratio,
    'controls' => $controls,
    'autoplay' => $autoplay,
    'muted' => $muted,
    'origin' => rtrim(config('app.url'), '/'),
], JSON_UNESCAPED_SLASHES) !!}</script>

@verbatim
<script>
(function () {
  var CFG = JSON.parse(document.getElementById('cfg').textContent);
  var RATIO = CFG.ratio === '9:16' ? 9 / 16 : 16 / 9;
  var frame = document.getElementById('player');
  var player = null;

  function post(payload) {
    try {
      if (window.LivePlayer && window.LivePlayer.postMessage) {
        window.LivePlayer.postMessage(JSON.stringify(payload));
      }
    } catch (e) { /* running in a plain browser, nobody is listening */ }
  }

  // Only "cover" needs measuring — "contain" is just a full-size iframe and
  // YouTube letterboxes inside it, which also handles a portrait source.
  function resize() {
    if (CFG.fit !== 'cover') {
      frame.style.width = '100%';
      frame.style.height = '100%';
      return;
    }
    var vw = window.innerWidth, vh = window.innerHeight;
    var w, h;
    if (vw / vh > RATIO) { w = vw; h = vw / RATIO; } else { h = vh; w = vh * RATIO; }
    frame.style.width = Math.ceil(w) + 'px';
    frame.style.height = Math.ceil(h) + 'px';
  }

  // The iframe follows the box on its own, but the player inside it does not:
  // it measures once and keeps that size. An Android WebView is often still
  // narrow when the page loads, so the video ends up a small rectangle
  // floating in a full-width frame. setSize is how the API is told to re-fit.
  function fit() {
    resize();
    if (!player || !player.setSize) return;
    var w = frame.clientWidth || window.innerWidth;
    var h = frame.clientHeight || window.innerHeight;
    if (w > 0 && h > 0) player.setSize(w, h);
  }

  window.addEventListener('resize', fit);
  window.addEventListener('orientationchange', fit);
  resize();

  // Kadrlamanı dəyişmək üçün: səhifəni yenidən yükləsək, video əvvəldən
  // başlayardı, ona görə tətbiq bunu çağırır.
  window.liveFit = function (value) {
    CFG.fit = value === 'cover' ? 'cover' : 'contain';
    fit();
  };

  var params = [
    'enablejsapi=1',
    'origin=' + encodeURIComponent(CFG.origin),
    'playsinline=1',
    'rel=0',
    'iv_load_policy=3',
    'modestbranding=1',
    'controls=' + CFG.controls,
    'autoplay=' + CFG.autoplay,
    'mute=' + (CFG.muted ? 1 : 0)
  ];
  frame.src = 'https://www.youtube.com/embed/' + encodeURIComponent(CFG.video) + '?' + params.join('&');

  var api = document.createElement('script');
  api.src = 'https://www.youtube.com/iframe_api';
  document.head.appendChild(api);

  window.onYouTubeIframeAPIReady = function () {
    player = new YT.Player('player', {
      events: {
        onReady: function () {
          post({ type: 'ready' });
          // The box the player measured on load is often not the box it ends
          // up in, so re-fit now and once more after the layout has settled.
          fit();
          setTimeout(fit, 900);
          setTimeout(fit, 2500);
          if (!CFG.autoplay) return;
          player.playVideo();
          // Autoplay with sound is refused unless the host allows it. Give it
          // a moment, and if nothing started, fall back to muted playback and
          // tell the app so it can offer an unmute control.
          setTimeout(function () {
            if (player.getPlayerState && player.getPlayerState() !== YT.PlayerState.PLAYING) {
              player.mute();
              player.playVideo();
              post({ type: 'muted', value: true });
            }
          }, 1200);
        },
        onStateChange: function (e) { post({ type: 'state', value: e.data }); },
        onError: function (e) {
          post({ type: 'error', code: e.data });
          document.getElementById('fallback').style.display = 'flex';
        }
      }
    });

    window.livePlayer = {
      play: function () { player.playVideo(); },
      pause: function () { player.pauseVideo(); },
      mute: function () { player.mute(); },
      unmute: function () { player.unMute(); player.setVolume(100); }
    };
  };
})();
</script>
@endverbatim
</body>
</html>
