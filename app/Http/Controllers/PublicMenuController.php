<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class PublicMenuController extends Controller
{
    /**
     * Display the public product menu.
     */
    public function index(Request $request)
    {
        $categories = Category::with(['products' => function($q) {
            $q->where('is_active', true);
        }])->has('products')->get();
        
        // If a specific category is requested
        $selectedCategoryId = $request->get('category');
        
        $productsQuery = Product::where('is_active', true);
        if ($selectedCategoryId) {
            $productsQuery->where('category_id', $selectedCategoryId);
        }
        
        $products = $productsQuery->get();

        return view('menu.index', compact('categories', 'products', 'selectedCategoryId'));
    }

    /**
     * Generate and download PDF catalog.
     */
    public function downloadPdf(Request $request)
    {
        $categories = Category::with(['products' => function($q) {
            $q->where('is_active', true);
        }])->has('products')->get();

        $Arabic = new \Arphp\Glyphs();
        $hidePrices = $request->has('hide_prices') && $request->hide_prices == '1';
        
        // جلب أرقام الهواتف المدخلة، وإزالة أي حقول فارغة
        $phones = array_filter($request->input('phones', []));
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('menu.pdf', compact('categories', 'Arabic', 'hidePrices', 'phones'));
        
        return $pdf->download('كتالوج_المنتجات.pdf');
    }

    /**
     * Publish the menu to GitHub Pages.
     * Strategy: Upload images separately, then upload a lightweight HTML that references them.
     */
    public function publishToGitHub()
    {
        $token = config('services.github.token');
        $username = config('services.github.username');

        if (!$token || !$username) {
            return back()->with('error', 'يرجى إعداد GITHUB_ACCESS_TOKEN و GITHUB_USERNAME في ملف .env أولاً.');
        }

        try {
            // Remove PHP execution time limit for this long-running operation
            set_time_limit(0);
            ini_set('max_execution_time', 0);

            $repoName = 'menu';
            $apiBase  = 'https://api.github.com';
            $headers  = [
                'Authorization' => 'token ' . $token,
                'Accept'        => 'application/vnd.github.v3+json',
                'User-Agent'    => 'ProFactory-App',
            ];

            // ─── 1. Ensure repo exists ───────────────────────────────────────
            $repoCheck = Http::timeout(30)->withHeaders($headers)
                ->get("{$apiBase}/repos/{$username}/{$repoName}");

            if ($repoCheck->status() === 404) {
                $createRepo = Http::timeout(30)->withHeaders($headers)
                    ->post("{$apiBase}/user/repos", [
                        'name'        => $repoName,
                        'description' => 'ProFactory Public Menu',
                        'private'     => false,
                        'auto_init'   => true,
                    ]);

                // 422 = already exists (from a previous attempt), just continue
                if (!$createRepo->successful() && $createRepo->status() !== 422) {
                    return back()->with('error', 'فشل إنشاء المستودع: ' . $createRepo->body());
                }
                sleep(3);

                Http::timeout(30)->withHeaders($headers)
                    ->post("{$apiBase}/repos/{$username}/{$repoName}/pages", [
                        'source' => ['branch' => 'main', 'path' => '/'],
                    ]);
            }

            // ─── 2. Fetch existing repo tree to avoid duplicate uploads ──────
            $existingFiles = [];
            $treeResponse = Http::timeout(30)->withHeaders($headers)
                ->get("{$apiBase}/repos/{$username}/{$repoName}/git/trees/main?recursive=1");

            if ($treeResponse->successful()) {
                foreach ($treeResponse->json('tree', []) as $item) {
                    if ($item['type'] === 'blob') {
                        $existingFiles[$item['path']] = $item['sha'];
                    }
                }
            }

            // ─── 3. Upload product images to GitHub (only if missing or changed) ──
            $products   = \App\Models\Product::where('is_active', true)->get();
            $imageUrlMap = []; // local_path => github_pages_url

            foreach ($products as $product) {
                if (!$product->image_path) continue;

                $localPath = public_path($product->image_path);
                if (!\Illuminate\Support\Facades\File::exists($localPath)) continue;

                // Normalise: strip leading slash from image_path, replace backslashes
                $ghPath = 'images/' . ltrim(str_replace('\\', '/', $product->image_path), '/');
                $ghPath = preg_replace('#/+#', '/', $ghPath); // collapse double slashes

                $imgData   = \Illuminate\Support\Facades\File::get($localPath);
                
                // Calculate Git Blob SHA to check if file already exists with same content
                $localSha = sha1("blob " . strlen($imgData) . "\0" . $imgData);

                if (isset($existingFiles[$ghPath]) && $existingFiles[$ghPath] === $localSha) {
                    // File exists and is identical
                    $imageUrlMap[$product->image_path] = "https://{$username}.github.io/{$repoName}/{$ghPath}";
                    continue;
                }

                $b64Content = base64_encode($imgData);

                $putPayload = [
                    'message' => 'upload image ' . basename($localPath),
                    'content' => $b64Content,
                ];
                if (isset($existingFiles[$ghPath])) {
                    $putPayload['sha'] = $existingFiles[$ghPath]; // Update existing
                }

                $uploadImg = Http::timeout(60)->withHeaders($headers)
                    ->put("{$apiBase}/repos/{$username}/{$repoName}/contents/{$ghPath}", $putPayload);

                if ($uploadImg->successful()) {
                    $imageUrlMap[$product->image_path] = "https://{$username}.github.io/{$repoName}/{$ghPath}";
                }
            }

            // ─── 3. Render HTML with remote image URLs (no base64 in HTML) ──
            $categories      = Category::with(['products' => function ($q) {
                $q->where('is_active', true);
            }])->has('products')->get();
            $selectedCategoryId = null;
            $isStaticExport  = true;
            $staticImageUrlMap = $imageUrlMap;

            $html = view('menu.index', compact(
                'categories', 'products', 'selectedCategoryId', 'isStaticExport', 'staticImageUrlMap'
            ))->render();

            // ─── 4. Upload index.html ────────────────────────────────────────
            $sha = $existingFiles['index.html'] ?? null;

            $uploadPayload = [
                'message' => 'تحديث المنيو ' . now()->format('Y-m-d H:i:s'),
                'content' => base64_encode($html),
            ];
            if ($sha) $uploadPayload['sha'] = $sha;

            $upload = Http::timeout(120)->withHeaders($headers)
                ->put("{$apiBase}/repos/{$username}/{$repoName}/contents/index.html", $uploadPayload);

            if ($upload->successful()) {
                // Ensure Pages is activated now that branch 'main' exists
                $pagesCheck = Http::timeout(30)->withHeaders($headers)
                    ->post("{$apiBase}/repos/{$username}/{$repoName}/pages", [
                        'source' => ['branch' => 'main', 'path' => '/'],
                    ]);

                $pagesUrl = "https://{$username}.github.io/{$repoName}/";
                return back()->with('success',
                    'تم النشر بنجاح! الرابط الثابت (قد يستغرق دقيقة لأول مرة):<br><br>' .
                    '<a href="' . $pagesUrl . '" target="_blank" class="btn btn-dark border-secondary px-4 fw-bold fs-5">' . $pagesUrl . '</a>'
                );
            }

            return back()->with('error', 'حدث خطأ أثناء رفع index.html: ' . $upload->body());

        } catch (\Exception $e) {
            return back()->with('error', 'حدث خطأ غير متوقع أثناء المعالجة: ' . $e->getMessage());
        }
    }
}
