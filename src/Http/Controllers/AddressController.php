<?php

namespace LaravelEcommerceStore\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use LaravelEcommerceStore\Models\Customer;
use LaravelEcommerceStore\Models\CustomerAddress;
use LaravelEcommerceStore\Services\CorreiosService;

class AddressController extends Controller
{
    protected CorreiosService $correiosService;

    public function __construct(CorreiosService $correiosService)
    {
        $this->correiosService = $correiosService;
        $this->middleware('auth');
    }

    /**
     * Listar endereços do cliente.
     */
    public function index(Request $request): JsonResponse
    {
        $customer = Auth::user()->customer;

        $addresses = $customer->addresses()
            ->when($request->type, function ($query, $type) {
                return $query->where('type', $type);
            })
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'addresses' => $addresses->map(function ($address) {
                return [
                    'id' => $address->id,
                    'type' => $address->type,
                    'postal_code' => $address->formatted_postal_code,
                    'street' => $address->street,
                    'number' => $address->number,
                    'complement' => $address->complement,
                    'neighborhood' => $address->neighborhood,
                    'city' => $address->city,
                    'state' => $address->state,
                    'country' => $address->country,
                    'recipient_name' => $address->recipient_name,
                    'recipient_phone' => $address->recipient_phone,
                    'is_default' => $address->is_default,
                    'formatted_address' => $address->formatted_address,
                ];
            })
        ]);
    }

    /**
     * Criar novo endereço.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:delivery,billing',
            'postal_code' => 'required|string|size:9',
            'street' => 'required|string|max:255',
            'number' => 'required|string|max:20',
            'complement' => 'nullable|string|max:255',
            'neighborhood' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|size:2',
            'recipient_name' => 'nullable|string|max:255',
            'recipient_phone' => 'nullable|string|max:20',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $customer = Auth::user()->customer;

        // Se marcar como padrão, remover outros padrões do mesmo tipo
        if ($request->boolean('is_default')) {
            $customer->addresses()
                ->where('type', $request->type)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $address = $customer->addresses()->create([
            'type' => $request->type,
            'postal_code' => preg_replace('/\D/', '', $request->postal_code),
            'street' => $request->street,
            'number' => $request->number,
            'complement' => $request->complement,
            'neighborhood' => $request->neighborhood,
            'city' => $request->city,
            'state' => strtoupper($request->state),
            'recipient_name' => $request->recipient_name,
            'recipient_phone' => $request->recipient_phone,
            'is_default' => $request->boolean('is_default', false),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Endereço criado com sucesso',
            'address' => $this->formatAddressResponse($address)
        ], 201);
    }

    /**
     * Mostrar endereço específico.
     */
    public function show(int $id): JsonResponse
    {
        $customer = Auth::user()->customer;
        $address = $customer->addresses()->findOrFail($id);

        return response()->json([
            'success' => true,
            'address' => $this->formatAddressResponse($address)
        ]);
    }

    /**
     * Atualizar endereço.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'sometimes|in:delivery,billing',
            'postal_code' => 'sometimes|string|size:9',
            'street' => 'sometimes|string|max:255',
            'number' => 'sometimes|string|max:20',
            'complement' => 'nullable|string|max:255',
            'neighborhood' => 'sometimes|string|max:255',
            'city' => 'sometimes|string|max:255',
            'state' => 'sometimes|string|size:2',
            'recipient_name' => 'nullable|string|max:255',
            'recipient_phone' => 'nullable|string|max:20',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $customer = Auth::user()->customer;
        $address = $customer->addresses()->findOrFail($id);

        // Se marcar como padrão, remover outros padrões do mesmo tipo
        if ($request->has('is_default') && $request->boolean('is_default')) {
            $customer->addresses()
                ->where('type', $address->type)
                ->where('id', '!=', $address->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $updateData = $request->only([
            'type', 'street', 'number', 'complement', 'neighborhood',
            'city', 'state', 'recipient_name', 'recipient_phone', 'is_default'
        ]);

        if ($request->has('postal_code')) {
            $updateData['postal_code'] = preg_replace('/\D/', '', $request->postal_code);
        }

        if (isset($updateData['state'])) {
            $updateData['state'] = strtoupper($updateData['state']);
        }

        $address->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Endereço atualizado com sucesso',
            'address' => $this->formatAddressResponse($address)
        ]);
    }

    /**
     * Remover endereço.
     */
    public function destroy(int $id): JsonResponse
    {
        $customer = Auth::user()->customer;
        $address = $customer->addresses()->findOrFail($id);

        // Não permitir remover endereço padrão
        if ($address->is_default) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível remover endereço padrão'
            ], 422);
        }

        $address->delete();

        return response()->json([
            'success' => true,
            'message' => 'Endereço removido com sucesso'
        ]);
    }

    /**
     * Definir endereço como padrão.
     */
    public function setDefault(int $id): JsonResponse
    {
        $customer = Auth::user()->customer;
        $address = $customer->addresses()->findOrFail($id);

        $address->makeDefault();

        return response()->json([
            'success' => true,
            'message' => 'Endereço definido como padrão',
            'address' => $this->formatAddressResponse($address)
        ]);
    }

    /**
     * Consultar CEP.
     */
    public function consultarCep(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'cep' => 'required|string|size:9',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'CEP inválido',
                'errors' => $validator->errors()
            ], 422);
        }

        $cepData = $this->correiosService->consultarCep($request->cep);

        if (!$cepData) {
            return response()->json([
                'success' => false,
                'message' => 'CEP não encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'cep_data' => $cepData
        ]);
    }

    /**
     * Buscar CEPs por endereço.
     */
    public function buscarCepPorEndereco(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'estado' => 'required|string|size:2',
            'cidade' => 'required|string|max:255',
            'logradouro' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $ceps = $this->correiosService->buscarCepPorEndereco(
            $request->estado,
            $request->cidade,
            $request->logradouro
        );

        return response()->json([
            'success' => true,
            'ceps' => $ceps
        ]);
    }

    /**
     * Validar CEP.
     */
    public function validarCep(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'cep' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'CEP inválido',
                'errors' => $validator->errors()
            ], 422);
        }

        $isValid = $this->correiosService->validarCep($request->cep);

        return response()->json([
            'success' => true,
            'is_valid' => $isValid,
            'formatted_cep' => $isValid ? $this->correiosService->formatarCep($request->cep) : null
        ]);
    }

    /**
     * Formatar resposta de endereço.
     */
    protected function formatAddressResponse(CustomerAddress $address): array
    {
        return [
            'id' => $address->id,
            'type' => $address->type,
            'postal_code' => $address->formatted_postal_code,
            'street' => $address->street,
            'number' => $address->number,
            'complement' => $address->complement,
            'neighborhood' => $address->neighborhood,
            'city' => $address->city,
            'state' => $address->state,
            'country' => $address->country,
            'recipient_name' => $address->recipient_name,
            'recipient_phone' => $address->recipient_phone,
            'is_default' => $address->is_default,
            'formatted_address' => $address->formatted_address,
            'created_at' => $address->created_at,
            'updated_at' => $address->updated_at,
        ];
    }
}