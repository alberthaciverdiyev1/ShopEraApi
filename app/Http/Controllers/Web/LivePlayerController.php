<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The page the app's WebView loads instead of building the embed itself.
 *
 * Since late 2025 YouTube refuses to configure an embedded player it cannot
 * attribute to a host — it wants a real `Referer` and a matching `origin`, and
 * answers "Error 153 — video player configuration error" when it gets neither.
 * A WebView handed raw HTML (which is what every Flutter YouTube package does,
 * `loadHtmlString` with `baseUrl: https://www.youtube.com`) sends no referer at
 * all and claims to be youtube.com itself, so every video fails, embeddable or
 * not.
 *
 * Serving the same iframe from our own https origin fixes it at the source:
 * the referer is real, the origin matches, and YouTube authorises playback.
 * Nothing here is a workaround — this is the arrangement the embed API is
 * documented to expect.
 */
class LivePlayerController extends Controller
{
    /** YouTube ids are 11 chars today; the range leaves room without opening it up. */
    private const VIDEO_ID = '/^[A-Za-z0-9_-]{6,20}$/';

    public function show(Request $request, string $video)
    {
        abort_unless(preg_match(self::VIDEO_ID, $video) === 1, Response::HTTP_NOT_FOUND);

        $html = view('live.player', [
            'video' => $video,
            // Cover crops a 16:9 stream to fill a portrait box; contain keeps
            // the whole frame and lets the bars show. The app decides.
            'fit' => $request->query('fit') === 'cover' ? 'cover' : 'contain',
            // Kəsim hesabı mənbənin nisbətini bilməlidir — 9:16 yayımı 16:9
            // sayanda kadr tamam yanlış böyüyür.
            'ratio' => $request->query('ratio') === '9:16' ? '9:16' : '16:9',
            'controls' => $request->query('controls') === '0' ? 0 : 1,
            'autoplay' => $request->query('autoplay') === '0' ? 0 : 1,
            'muted' => $request->query('mute') === '1',
        ])->render();

        // Spelled out rather than left to the defaults: strip the referer and
        // the page stops working, so it should be visible next to the code
        // that depends on it.
        return response($html)->withHeaders([
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'X-Robots-Tag' => 'noindex, nofollow',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }
}
