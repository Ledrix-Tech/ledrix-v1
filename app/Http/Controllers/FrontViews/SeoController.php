<?php

namespace App\Http\Controllers\FrontViews;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $base = rtrim(config('app.url'), '/');
        $entries = config('seo.sitemap', []);
        $lastmod = now()->toAtomString();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($entries as $entry) {
            $loc = htmlspecialchars($base . ($entry['path'] === '/' ? '/' : $entry['path']), ENT_XML1);
            $xml .= "  <url>\n";
            $xml .= "    <loc>{$loc}</loc>\n";
            $xml .= "    <lastmod>{$lastmod}</lastmod>\n";
            $xml .= '    <changefreq>' . ($entry['changefreq'] ?? 'monthly') . "</changefreq>\n";
            $xml .= '    <priority>' . ($entry['priority'] ?? '0.5') . "</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(): Response
    {
        $base = rtrim(config('app.url'), '/');
        $lines = ['User-agent: *', 'Allow: /'];

        foreach (config('seo.robots_disallow', []) as $path) {
            $lines[] = 'Disallow: ' . rtrim($path, '/') . '/';
        }

        $lines[] = '';
        $lines[] = 'Sitemap: ' . $base . '/sitemap.xml';

        return response(implode("\n", $lines) . "\n", 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
