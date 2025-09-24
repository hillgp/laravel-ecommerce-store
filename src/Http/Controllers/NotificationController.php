<?php

namespace LaravelEcommerce\Store\Http\Controllers;

use LaravelEcommerce\Store\Models\Notification;
use LaravelEcommerce\Store\Models\NotificationTemplate;
use LaravelEcommerce\Store\Models\NotificationSetting;
use LaravelEcommerce\Store\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
        $this->middleware('auth');
    }

    /**
     * Display a listing of notifications.
     */
    public function index(Request $request): View
    {
        $query = Notification::with(['recipient']);

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $notifications = $query->orderBy('created_at', 'desc')->paginate(20);

        $stats = $this->notificationService->getNotificationStats();

        return view('store::notifications.index', compact('notifications', 'stats'));
    }

    /**
     * Show the form for creating a new notification.
     */
    public function create(): View
    {
        $templates = NotificationTemplate::where('is_active', true)->get();
        $customers = \LaravelEcommerce\Store\Models\Customer::all();
        $orders = \LaravelEcommerce\Store\Models\Order::all();

        return view('store::notifications.create', compact('templates', 'customers', 'orders'));
    }

    /**
     * Store a newly created notification.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'type' => 'required|string|max:50',
            'channel' => 'required|in:email,sms,push,database',
            'recipient_type' => 'required|in:customer,order,admin',
            'recipient_id' => 'required|integer',
            'title' => 'nullable|string|max:255',
            'content' => 'required|string',
            'scheduled_at' => 'nullable|date|after:now',
            'priority' => 'in:normal,high,low',
        ]);

        try {
            $notification = $this->notificationService->createNotification($request->all());

            if ($this->notificationService->dispatchNotification($notification)) {
                return redirect()->route('store.notifications.index')
                    ->with('success', 'Notificação criada e enviada com sucesso!');
            } else {
                return redirect()->back()
                    ->with('error', 'Erro ao enviar notificação. Verifique os logs para mais detalhes.')
                    ->withInput();
            }
        } catch (\Exception $e) {
            Log::error('Failed to create notification: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Erro ao criar notificação: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified notification.
     */
    public function show(Notification $notification): View
    {
        $notification->load(['recipient']);

        return view('store::notifications.show', compact('notification'));
    }

    /**
     * Retry failed notification.
     */
    public function retry(Notification $notification): RedirectResponse
    {
        if ($notification->status !== 'failed') {
            return redirect()->back()
                ->with('error', 'Apenas notificações com falha podem ser reenviadas.');
        }

        try {
            if ($this->notificationService->dispatchNotification($notification)) {
                return redirect()->back()
                    ->with('success', 'Notificação reenviada com sucesso!');
            } else {
                return redirect()->back()
                    ->with('error', 'Erro ao reenviar notificação.');
            }
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao reenviar notificação: ' . $e->getMessage());
        }
    }

    /**
     * Cancel scheduled notification.
     */
    public function cancel(Notification $notification): RedirectResponse
    {
        if ($notification->status !== 'pending' || !$notification->scheduled_at) {
            return redirect()->back()
                ->with('error', 'Apenas notificações pendentes e agendadas podem ser canceladas.');
        }

        try {
            $notification->update(['status' => 'cancelled']);

            return redirect()->back()
                ->with('success', 'Notificação cancelada com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao cancelar notificação: ' . $e->getMessage());
        }
    }

    /**
     * Delete notification.
     */
    public function destroy(Notification $notification): RedirectResponse
    {
        try {
            $notification->delete();

            return redirect()->back()
                ->with('success', 'Notificação excluída com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao excluir notificação: ' . $e->getMessage());
        }
    }

    /**
     * Process pending notifications.
     */
    public function processPending(): RedirectResponse
    {
        try {
            $processed = $this->notificationService->processPendingNotifications();

            return redirect()->back()
                ->with('success', "{$processed} notificações processadas com sucesso!");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao processar notificações: ' . $e->getMessage());
        }
    }

    /**
     * Retry all failed notifications.
     */
    public function retryFailed(): RedirectResponse
    {
        try {
            $retried = $this->notificationService->retryFailedNotifications();

            return redirect()->back()
                ->with('success', "{$retried} notificações com falha foram reenviadas!");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao reenviar notificações: ' . $e->getMessage());
        }
    }

    /**
     * Send test notification.
     */
    public function sendTest(Request $request): RedirectResponse
    {
        $request->validate([
            'channel' => 'required|in:email,sms,push',
            'recipient' => 'required|string',
        ]);

        try {
            if ($this->notificationService->sendTestNotification($request->channel, $request->recipient)) {
                return redirect()->back()
                    ->with('success', 'Notificação de teste enviada com sucesso!');
            } else {
                return redirect()->back()
                    ->with('error', 'Erro ao enviar notificação de teste.');
            }
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao enviar notificação de teste: ' . $e->getMessage());
        }
    }

    /**
     * Show notification templates.
     */
    public function templates(): View
    {
        $templates = NotificationTemplate::orderBy('type')->orderBy('channel')->get();

        return view('store::notifications.templates', compact('templates'));
    }

    /**
     * Show form to create notification template.
     */
    public function createTemplate(): View
    {
        return view('store::notifications.template-form');
    }

    /**
     * Store notification template.
     */
    public function storeTemplate(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:notification_templates,code',
            'type' => 'required|string|max:50',
            'channel' => 'required|in:email,sms,push,database',
            'title' => 'nullable|string|max:255',
            'content' => 'required|string',
            'variables' => 'nullable|array',
            'locale' => 'required|string|size:5',
            'is_active' => 'boolean',
        ]);

        try {
            $errors = $this->notificationService->validateNotificationTemplate($request->all());

            if (!empty($errors)) {
                return redirect()->back()
                    ->withErrors($errors)
                    ->withInput();
            }

            $this->notificationService->createNotificationTemplate($request->all());

            return redirect()->route('store.notifications.templates')
                ->with('success', 'Template de notificação criado com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao criar template: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show form to edit notification template.
     */
    public function editTemplate(NotificationTemplate $template): View
    {
        return view('store::notifications.template-form', compact('template'));
    }

    /**
     * Update notification template.
     */
    public function updateTemplate(Request $request, NotificationTemplate $template): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:notification_templates,code,' . $template->id,
            'type' => 'required|string|max:50',
            'channel' => 'required|in:email,sms,push,database',
            'title' => 'nullable|string|max:255',
            'content' => 'required|string',
            'variables' => 'nullable|array',
            'locale' => 'required|string|size:5',
            'is_active' => 'boolean',
        ]);

        try {
            $errors = $this->notificationService->validateNotificationTemplate($request->all());

            if (!empty($errors)) {
                return redirect()->back()
                    ->withErrors($errors)
                    ->withInput();
            }

            if ($this->notificationService->updateNotificationTemplate($template->code, $request->all())) {
                return redirect()->route('store.notifications.templates')
                    ->with('success', 'Template de notificação atualizado com sucesso!');
            } else {
                return redirect()->back()
                    ->with('error', 'Erro ao atualizar template.')
                    ->withInput();
            }
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao atualizar template: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Delete notification template.
     */
    public function destroyTemplate(NotificationTemplate $template): RedirectResponse
    {
        try {
            $template->delete();

            return redirect()->back()
                ->with('success', 'Template de notificação excluído com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao excluir template: ' . $e->getMessage());
        }
    }

    /**
     * Show notification settings.
     */
    public function settings(): View
    {
        $settings = $this->notificationService->getNotificationSettings();

        return view('store::notifications.settings', compact('settings'));
    }

    /**
     * Update notification settings.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'required',
        ]);

        try {
            foreach ($request->settings as $setting) {
                $this->notificationService->setNotificationSetting(
                    $setting['key'],
                    $setting['value']
                );
            }

            return redirect()->back()
                ->with('success', 'Configurações de notificação atualizadas com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao atualizar configurações: ' . $e->getMessage());
        }
    }

    /**
     * Get notification statistics as JSON.
     */
    public function stats(): JsonResponse
    {
        $stats = $this->notificationService->getNotificationStats();

        return response()->json($stats);
    }

    /**
     * Cleanup old notifications.
     */
    public function cleanup(Request $request): RedirectResponse
    {
        $request->validate([
            'days' => 'required|integer|min:1|max:365',
        ]);

        try {
            $deleted = $this->notificationService->cleanupOldNotifications($request->days);

            return redirect()->back()
                ->with('success', "{$deleted} notificações antigas foram removidas!");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao limpar notificações: ' . $e->getMessage());
        }
    }
}