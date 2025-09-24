<?php

namespace LaravelEcommerce\Store\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use LaravelEcommerce\Store\Models\Coupon;
use LaravelEcommerce\Store\Services\CouponService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CouponController extends Controller
{
    protected CouponService $couponService;

    public function __construct(CouponService $couponService)
    {
        $this->couponService = $couponService;
        $this->middleware('auth:sanctum')->only(['store', 'update', 'destroy']);
        $this->middleware('role:admin')->only(['create', 'edit', 'update', 'destroy', 'index', 'show']);
    }

    /**
     * Lista todos os cupons (Admin)
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['type', 'search']);
        $perPage = $request->get('per_page', 15);

        $coupons = $this->couponService->getActiveCoupons($filters, $perPage);
        $stats = $this->couponService->getCouponStats();

        return view('store::coupons.index', compact('coupons', 'stats', 'filters'));
    }

    /**
     * Exibe formulário para criar cupom (Admin)
     */
    public function create(): View
    {
        return view('store::coupons.create');
    }

    /**
     * Armazena novo cupom (Admin)
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'nullable|string|max:50|unique:coupons,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => ['required', Rule::in(['fixed', 'percentage', 'free_shipping'])],
            'value' => 'required|numeric|min:0.01',
            'minimum_amount' => 'nullable|numeric|min:0',
            'maximum_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_per_customer' => 'required|integer|min:1',
            'starts_at' => 'nullable|date|after:now',
            'expires_at' => 'nullable|date|after:starts_at',
            'is_active' => 'boolean',
            'first_purchase_only' => 'boolean',
            'combine_with_others' => 'boolean',
            'applicable_categories' => 'nullable|array',
            'applicable_products' => 'nullable|array',
            'applicable_brands' => 'nullable|array',
            'excluded_categories' => 'nullable|array',
            'excluded_products' => 'nullable|array',
            'excluded_brands' => 'nullable|array',
            'customer_groups' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator);
        }

        try {
            $coupon = $this->couponService->createCoupon($request->all());

            if ($request->expectsJson()) {
                return $this->respondWithSuccess('Cupom criado com sucesso!', $coupon);
            }

            return redirect()->route('store.coupons.show', $coupon)
                           ->with('success', 'Cupom criado com sucesso!');

        } catch (\Exception $e) {
            return $this->respondWithError('Erro ao criar cupom: ' . $e->getMessage());
        }
    }

    /**
     * Exibe um cupom específico (Admin)
     */
    public function show(Coupon $coupon): View
    {
        $coupon->load(['usages.customer', 'usages.order']);
        $stats = $coupon->stats;

        return view('store::coupons.show', compact('coupon', 'stats'));
    }

    /**
     * Exibe formulário para editar cupom (Admin)
     */
    public function edit(Coupon $coupon): View
    {
        return view('store::coupons.edit', compact('coupon'));
    }

    /**
     * Atualiza cupom (Admin)
     */
    public function update(Request $request, Coupon $coupon): JsonResponse|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => ['nullable', 'string', 'max:50', Rule::unique('coupons', 'code')->ignore($coupon->id)],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => ['required', Rule::in(['fixed', 'percentage', 'free_shipping'])],
            'value' => 'required|numeric|min:0.01',
            'minimum_amount' => 'nullable|numeric|min:0',
            'maximum_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_per_customer' => 'required|integer|min:1',
            'starts_at' => 'nullable|date|after:now',
            'expires_at' => 'nullable|date|after:starts_at',
            'is_active' => 'boolean',
            'first_purchase_only' => 'boolean',
            'combine_with_others' => 'boolean',
            'applicable_categories' => 'nullable|array',
            'applicable_products' => 'nullable|array',
            'applicable_brands' => 'nullable|array',
            'excluded_categories' => 'nullable|array',
            'excluded_products' => 'nullable|array',
            'excluded_brands' => 'nullable|array',
            'customer_groups' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator);
        }

        try {
            $updatedCoupon = $this->couponService->updateCoupon($coupon, $request->all());

            if ($request->expectsJson()) {
                return $this->respondWithSuccess('Cupom atualizado com sucesso!', $updatedCoupon);
            }

            return redirect()->route('store.coupons.show', $updatedCoupon)
                           ->with('success', 'Cupom atualizado com sucesso!');

        } catch (\Exception $e) {
            return $this->respondWithError('Erro ao atualizar cupom: ' . $e->getMessage());
        }
    }

    /**
     * Exclui cupom (Admin)
     */
    public function destroy(Coupon $coupon): JsonResponse|RedirectResponse
    {
        try {
            $this->couponService->deleteCoupon($coupon);

            if (request()->expectsJson()) {
                return $this->respondWithSuccess('Cupom excluído com sucesso!');
            }

            return redirect()->route('store.coupons.index')
                           ->with('success', 'Cupom excluído com sucesso!');

        } catch (\Exception $e) {
            return $this->respondWithError('Erro ao excluir cupom: ' . $e->getMessage());
        }
    }

    /**
     * Aplica cupom ao carrinho (Cliente)
     */
    public function apply(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:50',
            'cart_data' => 'required|array',
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator);
        }

        try {
            $customerId = auth()->id();
            $result = $this->couponService->applyCoupon(
                $request->code,
                $request->cart_data,
                $customerId
            );

            if ($result['success']) {
                return $this->respondWithSuccess($result['message'], [
                    'coupon' => $result['coupon'],
                    'discount' => $result['discount'],
                ]);
            } else {
                return $this->respondWithError($result['message'], 400);
            }

        } catch (\Exception $e) {
            return $this->respondWithError('Erro ao aplicar cupom: ' . $e->getMessage());
        }
    }

    /**
     * Remove cupom do carrinho (Cliente)
     */
    public function remove(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator);
        }

        try {
            $result = $this->couponService->removeCoupon($request->code);

            return $this->respondWithSuccess($result['message']);

        } catch (\Exception $e) {
            return $this->respondWithError('Erro ao remover cupom: ' . $e->getMessage());
        }
    }

    /**
     * Verifica se cupom é válido (Cliente)
     */
    public function validateCoupon(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:50',
            'cart_data' => 'required|array',
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator);
        }

        try {
            $customerId = auth()->id();
            $coupon = $this->couponService->getValidCoupon(
                $request->code,
                $request->cart_data,
                $customerId
            );

            if ($coupon) {
                $discount = $this->couponService->calculateDiscount($coupon, $request->cart_data);

                return $this->respondWithSuccess('Cupom válido!', [
                    'coupon' => $coupon,
                    'discount' => $discount,
                ]);
            } else {
                return $this->respondWithError('Cupom inválido ou não aplicável', 400);
            }

        } catch (\Exception $e) {
            return $this->respondWithError('Erro ao validar cupom: ' . $e->getMessage());
        }
    }

    /**
     * Lista cupons aplicáveis (Cliente)
     */
    public function applicable(Request $request): JsonResponse
    {
        try {
            $customerId = auth()->id();
            $cartData = $request->get('cart_data', []);

            $coupons = $this->couponService->getApplicableCoupons($cartData, $customerId);

            return $this->respondWithSuccess('Cupons aplicáveis encontrados!', $coupons);

        } catch (\Exception $e) {
            return $this->respondWithError('Erro ao buscar cupons: ' . $e->getMessage());
        }
    }

    /**
     * Ativa cupom (Admin)
     */
    public function activate(Coupon $coupon): JsonResponse|RedirectResponse
    {
        try {
            $this->couponService->activateCoupon($coupon);

            if (request()->expectsJson()) {
                return $this->respondWithSuccess('Cupom ativado com sucesso!', $coupon->fresh());
            }

            return redirect()->back()->with('success', 'Cupom ativado com sucesso!');

        } catch (\Exception $e) {
            return $this->respondWithError('Erro ao ativar cupom: ' . $e->getMessage());
        }
    }

    /**
     * Desativa cupom (Admin)
     */
    public function deactivate(Coupon $coupon): JsonResponse|RedirectResponse
    {
        try {
            $this->couponService->deactivateCoupon($coupon);

            if (request()->expectsJson()) {
                return $this->respondWithSuccess('Cupom desativado com sucesso!', $coupon->fresh());
            }

            return redirect()->back()->with('success', 'Cupom desativado com sucesso!');

        } catch (\Exception $e) {
            return $this->respondWithError('Erro ao desativar cupom: ' . $e->getMessage());
        }
    }

    /**
     * Duplica cupom (Admin)
     */
    public function duplicate(Coupon $coupon): JsonResponse|RedirectResponse
    {
        try {
            $newCoupon = $this->couponService->duplicateCoupon($coupon);

            if (request()->expectsJson()) {
                return $this->respondWithSuccess('Cupom duplicado com sucesso!', $newCoupon);
            }

            return redirect()->route('store.coupons.edit', $newCoupon)
                           ->with('success', 'Cupom duplicado com sucesso!');

        } catch (\Exception $e) {
            return $this->respondWithError('Erro ao duplicar cupom: ' . $e->getMessage());
        }
    }

    /**
     * Relatório de uso de cupons (Admin)
     */
    public function usageReport(Request $request): View
    {
        $filters = $request->only(['coupon_id', 'customer_id', 'date_from', 'date_to']);
        $perPage = $request->get('per_page', 20);

        $usages = $this->couponService->getUsageReport($filters, $perPage);

        return view('store::coupons.report', compact('usages', 'filters'));
    }

    /**
     * Histórico de uso do cliente (Cliente)
     */
    public function usageHistory(Request $request): View
    {
        $perPage = $request->get('per_page', 10);
        $usages = $this->couponService->getCustomerUsageHistory(auth()->id(), $perPage);

        return view('store::coupons.history', compact('usages'));
    }

    /**
     * Resposta de sucesso padronizada
     */
    protected function respondWithSuccess(string $message, $data = null): JsonResponse
    {
        $response = ['success' => true, 'message' => $message];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response);
    }

    /**
     * Resposta de erro padronizada
     */
    protected function respondWithError(string $message, int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }

    /**
     * Resposta com erros de validação
     */
    protected function respondWithValidationErrors(Validator $validator): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Erros de validação.',
            'errors' => $validator->errors(),
        ], 422);
    }
}