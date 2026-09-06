<?php

namespace App\Services;

class DirectPrintService
{
    /**
     * Checks printer status, then opens the print URL in the default browser
     * on the server PC. The print layout page automatically triggers window.print()
     * which opens the normal print dialog.
     *
     * @param string $url The full URL to print (must be accessible by the server)
     * @return array ['status' => 'success|error', 'message' => '...']
     */
    public function print(string $url): array
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            return [
                'status' => 'error',
                'message' => 'الطباعة المباشرة مدعومة فقط على خوادم الويندوز.'
            ];
        }

        // ── Step 1: Check Printer Status via PowerShell ──────────────────
        $psScript = <<<'EOT'
$def = Get-WmiObject -Class Win32_Printer -Filter "Default='True'"
if ($null -eq $def) {
    Write-Output "NO_DEFAULT"
} else {
    $p = Get-Printer -Name $def.Name
    Write-Output $p.PrinterStatus
}
EOT;

        $tempScript = tempnam(sys_get_temp_dir(), 'ps_') . '.ps1';
        file_put_contents($tempScript, $psScript);
        $statusOutput = shell_exec("powershell.exe -ExecutionPolicy Bypass -NonInteractive -File \"" . $tempScript . "\"");
        @unlink($tempScript);

        $statusOutput = strtolower(trim($statusOutput ?? ''));

        if ($statusOutput === 'no_default' || $statusOutput === '') {
            return [
                'status' => 'error',
                'message' => 'لا توجد طابعة افتراضية (Default Printer) محددة على السيرفر.'
            ];
        }

        if (str_contains($statusOutput, 'offline')) {
            return [
                'status' => 'error',
                'message' => 'الطابعة غير متصلة (Offline) أو مغلقة. يرجى تشغيلها أو توصيلها.'
            ];
        }

        if (str_contains($statusOutput, 'error')
            || str_contains($statusOutput, 'paperout')
            || str_contains($statusOutput, 'paperjam')) {
            return [
                'status' => 'error',
                'message' => 'توجد مشكلة في الطابعة (ورق محشور، أو لا يوجد ورق، أو خطأ عام). يرجى فحص الطابعة.'
            ];
        }

        // ── Step 2: Open the print page in Edge on the server ───────────
        // The print layout already has window.print() auto-trigger on load,
        // so Edge will open the page and show the normal Windows print dialog.
        // We use cmd "start" to completely detach the process so it doesn't block PHP.
        $cmd = 'start "" msedge "' . $url . '"';

        try {
            pclose(popen($cmd, 'r'));
            return [
                'status' => 'success',
                'message' => 'تم التحقق من جاهزية الطابعة وفتح نافذة الطباعة على الكمبيوتر بنجاح! اضغط طباعة في نافذة الكمبيوتر.'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'فشل في فتح نافذة الطباعة: ' . $e->getMessage()
            ];
        }
    }
}
