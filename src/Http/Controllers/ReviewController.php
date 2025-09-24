<?php

namespace LaravelEcommerce\Store\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use LaravelEcommerce\Store\Models\Product;
use LaravelEcommerce\Store\Models\ProductReview;
use LaravelEcommerce\Store\Models\ReviewVote;
use LaravelEcommerce\Store\Services\ReviewService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    protected ReviewService $reviewService;

    public function __construct(ReviewService $reviewService)
    {
        $this->reviewService = $reviewService;
        $this->middleware('auth:sanctum')->only(['store', 'update', 'destroy', 'vote']);
    }

    /**
     * Exibe as avaliações de um produto
     */
    public function index(Request $request, Product $product): View
    {
        $filters = $request->only(['rating', 'verified', 'status']);
        $perPage = $request->get('per_page', 10);

        $reviews = $this->reviewService->getProductReviews($product, $filters, $perPage);
        $stats = $this->reviewService->getReviewStats($product);

        return view('store::reviews.index', compact('product', 'reviews', 'stats', 'filters'));
    }

    /**
     * Exibe o formulário para criar uma nova avaliação
     */
    public function create(Product $product): View
    {
        $customerId = Auth::id();

        if (!$this->reviewService->canReviewProduct($customerId, $product->id)) {
            abort(403, 'Você não pode avaliar este produto.');
        }

        return view('store::reviews.create', compact('product'));
    }

    /**
     * Armazena uma nova avaliação
     */
    public function store(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        $customerId = Auth::id();

        if (!$this->reviewService->canReviewProduct($customerId, $product->id)) {
            return $this->respondWithError('Você não pode avaliar este produto.', 403);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'comment' => 'nullable|string|max:2000',
            'pros' => 'nullable|string|max:1000',
            'cons' => 'nullable|string|max:1000',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'videos.*' => 'nullable|mimes:mp4,mov,avi|max:10240',
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator);
        }

        try {
            $data = [
                'product_id' => $product->id,
                'customer_id' => $customerId,
                'rating' => $request->rating,
                'title' => $request->title,
                'comment' => $request->comment,
                'pros' => $request->pros,
                'cons' => $request->cons,
                'status' => 'pending', // Todas as avaliações começam como pendentes
                'is_verified_purchase' => true, // Assumindo que é verificada
            ];

            // Upload de imagens
            if ($request->hasFile('images')) {
                $images = [];
                foreach ($request->file('images') as $image) {
                    $path = $image->store('reviews', 'public');
                    $images[] = basename($path);
                }
                $data['images'] = $images;
            }

            // Upload de vídeos
            if ($request->hasFile('videos')) {
                $videos = [];
                foreach ($request->file('videos') as $video) {
                    $path = $video->store('reviews', 'public');
                    $videos[] = basename($path);
                }
                $data['videos'] = $videos;
            }

            $review = $this->reviewService->createReview($data);

            if ($request->expectsJson()) {
                return $this->respondWithSuccess('Avaliação criada com sucesso!', $review);
            }

            return redirect()->route('store.reviews.show', $review)
                           ->with('success', 'Avaliação enviada com sucesso! Aguarde aprovação.');

        } catch (\Exception $e) {
            return $this->respondWithError('Erro ao criar avaliação: ' . $e->getMessage());
        }
    }

    /**
     * Exibe uma avaliação específica
     */
    public function show(ProductReview $review): View
    {
        $review->load(['product', 'customer', 'votes.customer']);
        return view('store::reviews.show', compact('review'));
    }

    /**
     * Exibe o formulário para editar uma avaliação
     */
    public function edit(ProductReview $review): View
    {
        $customerId = Auth::id();

        if ($review->customer_id !== $customerId) {
            abort(403, 'Você não pode editar esta avaliação.');
        }

        if (!$review->canBeEdited()) {
            abort(403, 'Esta avaliação não pode ser editada.');
        }

        $review->load('product');
        return view('store::reviews.edit', compact('review'));
    }

    /**
     * Atualiza uma avaliação
     */
    public function update(Request $request, ProductReview $review): JsonResponse|RedirectResponse
    {
        $customerId = Auth::id();

        if ($review->customer_id !== $customerId) {
            return $this->respondWithError('Você não pode editar esta avaliação.', 403);
        }

        if (!$review->canBeEdited()) {
            return $this->respondWithError('Esta avaliação não pode ser editada.', 403);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'comment' => 'nullable|string|max:2000',
            'pros' => 'nullable|string|max:1000',
            'cons' => 'nullable|string|max:1000',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'videos.*' => 'nullable|mimes:mp4,mov,avi|max:10240',
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator);
        }

        try {
            $data = [
                'rating' => $request->rating,
                'title' => $request->title,
                'comment' => $request->comment,
                'pros' => $request->pros,
                'cons' => $request->cons,
            ];

            // Upload de novas imagens
            if ($request->hasFile('images')) {
                // Remove imagens antigas
                if ($review->images) {
                    foreach ($review->images as $image) {
                        Storage::disk('public')->delete('reviews/' . $image);
                    }
                }

                $images = [];
                foreach ($request->file('images') as $image) {
                    $path = $image->store('reviews', 'public');
                    $images[] = basename($path);
                }
                $data['images'] = $images;
            }

            // Upload de novos vídeos
            if ($request->hasFile('videos')) {
                // Remove vídeos antigos
                if ($review->videos) {
                    foreach ($review->videos as $video) {
                        Storage::disk('public')->delete('reviews/' . $video);
                    }
                }

                $videos = [];
                foreach ($request->file('videos') as $video) {
                    $path = $video->store('reviews', 'public');
                    $videos[] = basename($path);
                }
                $data['videos'] = $videos;
            }

            $updatedReview = $this->reviewService->updateReview($review, $data);

            if ($request->expectsJson()) {
                return $this->respondWithSuccess('Avaliação atualizada com sucesso!', $updatedReview);
            }

            return redirect()->route('store.reviews.show', $updatedReview)
                           ->with('success', 'Avaliação atualizada com sucesso!');

        } catch (\Exception $e) {
            return $this->respondWithError('Erro ao atualizar avaliação: ' . $e->getMessage());
        }
    }

    /**
     * Remove uma avaliação
     */
    public function destroy(ProductReview $review): JsonResponse|RedirectResponse
    {
        $customerId = Auth::id();

        if ($review->customer_id !== $customerId && !Auth::user()->hasRole('admin')) {
            return $this->respondWithError('Você não pode excluir esta avaliação.', 403);
        }

        if (!$review->canBeDeleted()) {
            return $this->respondWithError('Esta avaliação não pode ser excluída.', 403);
        }

        try {
            // Remove arquivos de mídia
            if ($review->images) {
                foreach ($review->images as $image) {
                    Storage::disk('public')->delete('reviews/' . $image);
                }
            }

            if ($review->videos) {
                foreach ($review->videos as $video) {
                    Storage::disk('public')->delete('reviews/' . $video);
                }
            }

            $this->reviewService->deleteReview($review);

            if (request()->expectsJson()) {
                return $this->respondWithSuccess('Avaliação excluída com sucesso!');
            }

            return redirect()->back()->with('success', 'Avaliação excluída com sucesso!');

        } catch (\Exception $e) {
            return $this->respondWithError('Erro ao excluir avaliação: ' . $e->getMessage());
        }
    }

    /**
     * Vota em uma avaliação (útil/não útil)
     */
    public function vote(Request $request, ProductReview $review): JsonResponse
    {
        $customerId = Auth::id();

        if (!$review->canCustomerVote($customerId)) {
            return $this->respondWithError('Você não pode votar nesta avaliação.', 403);
        }

        $validator = Validator::make($request->all(), [
            'is_helpful' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator);
        }

        try {
            $vote = $review->addVote($customerId, $request->is_helpful);
            $review->refresh();

            return $this->respondWithSuccess('Voto registrado com sucesso!', [
                'vote' => $vote,
                'review_stats' => $review->stats,
            ]);

        } catch (\Exception $e) {
            return $this->respondWithError('Erro ao registrar voto: ' . $e->getMessage());
        }
    }

    /**
     * Remove um voto de uma avaliação
     */
    public function removeVote(ProductReview $review): JsonResponse
    {
        $customerId = Auth::id();

        try {
            $removed = $review->removeVote($customerId);
            $review->refresh();

            if (!$removed) {
                return $this->respondWithError('Voto não encontrado.', 404);
            }

            return $this->respondWithSuccess('Voto removido com sucesso!', [
                'review_stats' => $review->stats,
            ]);

        } catch (\Exception $e) {
            return $this->respondWithError('Erro ao remover voto: ' . $e->getMessage());
        }
    }

    /**
     * Lista avaliações pendentes (apenas para administradores)
     */
    public function pending(Request $request): View
    {
        $this->middleware('role:admin');

        $perPage = $request->get('per_page', 20);
        $reviews = $this->reviewService->getPendingReviews($perPage);

        return view('store::reviews.pending', compact('reviews'));
    }

    /**
     * Aprova uma avaliação (apenas para administradores)
     */
    public function approve(ProductReview $review): JsonResponse|RedirectResponse
    {
        $this->middleware('role:admin');

        try {
            $this->reviewService->approveReview($review);

            if (request()->expectsJson()) {
                return $this->respondWithSuccess('Avaliação aprovada com sucesso!', $review->fresh());
            }

            return redirect()->back()->with('success', 'Avaliação aprovada com sucesso!');

        } catch (\Exception $e) {
            return $this->respondWithError('Erro ao aprovar avaliação: ' . $e->getMessage());
        }
    }

    /**
     * Rejeita uma avaliação (apenas para administradores)
     */
    public function reject(Request $request, ProductReview $review): JsonResponse|RedirectResponse
    {
        $this->middleware('role:admin');

        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->respondWithValidationErrors($validator);
        }

        try {
            $this->reviewService->rejectReview($review, $request->reason);

            if (request()->expectsJson()) {
                return $this->respondWithSuccess('Avaliação rejeitada com sucesso!', $review->fresh());
            }

            return redirect()->back()->with('success', 'Avaliação rejeitada com sucesso!');

        } catch (\Exception $e) {
            return $this->respondWithError('Erro ao rejeitar avaliação: ' . $e->getMessage());
        }
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