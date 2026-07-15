<?php

namespace App\Filament\Resources\Invoices\Actions;

use App\Models\Invoice;
use App\Services\InvoiceService;
use Filament\Actions\Action;

class DownloadInvoiceAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'download_invoice';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Download PDF')
            ->icon('heroicon-m-arrow-down-tray')
            ->color('gray')
            ->action(function (Invoice $record) {
                $pdf = app(InvoiceService::class)->downloadPdf($record);

                return response()->streamDownload(
                    fn () => print $pdf->output(),
                    "invoice-{$record->invoice_number}.pdf",
                );
            });
    }
}
