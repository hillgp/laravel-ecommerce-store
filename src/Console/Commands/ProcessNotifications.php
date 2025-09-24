<?php

namespace LaravelEcommerce\Store\Console\Commands;

use LaravelEcommerce\Store\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessNotifications extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'store:notifications:process
                            {--limit=50 : Number of notifications to process}
                            {--force : Force processing of all pending notifications}';

    /**
     * The console command description.
     */
    protected $description = 'Process pending notifications';

    protected NotificationService $notificationService;

    /**
     * Create a new command instance.
     */
    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $limit = $this->option('limit');
        $force = $this->option('force');

        $this->info('Processando notificações pendentes...');

        try {
            // Process pending notifications
            $processed = $this->notificationService->processPendingNotifications();

            // Retry failed notifications
            if ($force) {
                $retried = $this->notificationService->retryFailedNotifications();
                $this->info("{$retried} notificações com falha foram reenviadas.");
            }

            // Cleanup old notifications
            $cleaned = $this->notificationService->cleanupOldNotifications(30);
            if ($cleaned > 0) {
                $this->info("{$cleaned} notificações antigas foram removidas.");
            }

            // Get statistics
            $stats = $this->notificationService->getNotificationStats();

            $this->info("Processamento concluído!");
            $this->table(
                ['Estatística', 'Valor'],
                [
                    ['Processadas', $processed],
                    ['Total', $stats['total']],
                    ['Pendentes', $stats['pending']],
                    ['Enviadas', $stats['sent']],
                    ['Com Falha', $stats['failed']],
                    ['Hoje', $stats['today']],
                ]
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Erro ao processar notificações: ' . $e->getMessage());
            Log::error('Notification processing failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}