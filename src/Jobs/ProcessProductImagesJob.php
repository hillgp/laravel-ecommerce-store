<?php

namespace LaravelEcommerce\Store\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use LaravelEcommerce\Store\Models\ProductImage;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;

class ProcessProductImagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $productImage;
    protected $sizes;

    /**
     * Create a new job instance.
     */
    public function __construct(ProductImage $productImage, array $sizes = null)
    {
        $this->productImage = $productImage;
        $this->sizes = $sizes ?? [
            'thumbnail' => ['width' => 300, 'height' => 300],
            'medium' => ['width' => 600, 'height' => 600],
            'large' => ['width' => 1200, 'height' => 1200],
        ];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->info("Processando imagens do produto ID: {$this->productImage->id}");

        try {
            $originalPath = $this->productImage->path;

            if (!Storage::exists($originalPath)) {
                throw new \Exception("Arquivo original não encontrado: {$originalPath}");
            }

            // Get original image
            $image = Image::make(Storage::path($originalPath));

            // Store original dimensions
            $this->productImage->update([
                'width' => $image->width(),
                'height' => $image->height(),
            ]);

            // Process different sizes
            foreach ($this->sizes as $sizeName => $dimensions) {
                $this->processImageSize($image, $sizeName, $dimensions);
            }

            $this->info("Imagens processadas com sucesso para produto ID: {$this->productImage->id}");

        } catch (\Exception $e) {
            $this->error("Erro ao processar imagens: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Process a specific image size.
     */
    protected function processImageSize($image, string $sizeName, array $dimensions): void
    {
        try {
            $processedImage = clone $image;

            // Resize image
            $processedImage->fit($dimensions['width'], $dimensions['height'], function ($constraint) {
                $constraint->upsize();
            });

            // Generate filename for this size
            $filename = $this->generateSizeFilename($sizeName);

            // Save processed image
            $path = "products/{$sizeName}/{$filename}";
            $fullPath = Storage::path($path);

            // Ensure directory exists
            Storage::makeDirectory(dirname($path));

            // Save image
            $processedImage->save($fullPath, 90);

            // Update model with path
            $this->productImage->update([
                "{$sizeName}_path" => $path,
            ]);

            $this->info("✓ {$sizeName} processado: {$path}");

        } catch (\Exception $e) {
            $this->error("Erro ao processar {$sizeName}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate filename for a specific size.
     */
    protected function generateSizeFilename(string $sizeName): string
    {
        $originalFilename = pathinfo($this->productImage->filename, PATHINFO_FILENAME);
        $extension = pathinfo($this->productImage->filename, PATHINFO_EXTENSION);

        return "{$originalFilename}-{$sizeName}.{$extension}";
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->error("ProcessProductImagesJob falhou para imagem ID {$this->productImage->id}: " . $exception->getMessage());

        // Log failure
        \Log::error('ProcessProductImagesJob failed', [
            'image_id' => $this->productImage->id,
            'imageable_type' => $this->productImage->imageable_type,
            'imageable_id' => $this->productImage->imageable_id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Mark image as failed
        $this->productImage->update([
            'metadata' => json_encode([
                'processing_failed' => true,
                'error' => $exception->getMessage(),
                'failed_at' => now(),
            ]),
        ]);
    }

    /**
     * Log info message.
     */
    protected function info(string $message): void
    {
        \Log::info($message);
        if (app()->runningInConsole()) {
            $this->output->writeln("<info>{$message}</info>");
        }
    }

    /**
     * Log error message.
     */
    protected function error(string $message): void
    {
        \Log::error($message);
        if (app()->runningInConsole()) {
            $this->output->writeln("<error>{$message}</error>");
        }
    }
}