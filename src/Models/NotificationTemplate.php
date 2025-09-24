<?php

namespace LaravelEcommerce\Store\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationTemplate extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nome da tabela
     */
    protected $table = 'notification_templates';

    /**
     * Campos preenchíveis
     */
    protected $fillable = [
        'name',
        'code',
        'type',
        'channel',
        'subject',
        'content',
        'html_content',
        'variables',
        'locale',
        'is_active',
        'configuration',
        'description',
    ];

    /**
     * Campos que devem ser convertidos para tipos específicos
     */
    protected $casts = [
        'name' => 'string',
        'code' => 'string',
        'type' => 'string',
        'channel' => 'string',
        'subject' => 'string',
        'content' => 'string',
        'html_content' => 'string',
        'variables' => 'array',
        'locale' => 'string',
        'is_active' => 'boolean',
        'configuration' => 'array',
        'description' => 'string',
    ];

    /**
     * Campos que devem ser ocultos na serialização
     */
    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Scope para templates ativos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para templates por tipo
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope para templates por canal
     */
    public function scopeByChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Scope para templates por código
     */
    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }

    /**
     * Scope para templates por idioma
     */
    public function scopeByLocale($query, string $locale)
    {
        return $query->where('locale', $locale);
    }

    /**
     * Verifica se o template está ativo
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Processa o template com variáveis
     */
    public function process(array $variables = []): array
    {
        $processedSubject = $this->subject;
        $processedContent = $this->content;
        $processedHtmlContent = $this->html_content;

        foreach ($variables as $key => $value) {
            $placeholder = '{{' . $key . '}}';

            if ($processedSubject) {
                $processedSubject = str_replace($placeholder, $value, $processedSubject);
            }

            if ($processedContent) {
                $processedContent = str_replace($placeholder, $value, $processedContent);
            }

            if ($processedHtmlContent) {
                $processedHtmlContent = str_replace($placeholder, $value, $processedHtmlContent);
            }
        }

        return [
            'subject' => $processedSubject,
            'content' => $processedContent,
            'html_content' => $processedHtmlContent,
        ];
    }

    /**
     * Valida se todas as variáveis necessárias estão presentes
     */
    public function validateVariables(array $variables): array
    {
        $errors = [];
        $requiredVariables = $this->variables ?? [];

        foreach ($requiredVariables as $variable) {
            if (!isset($variables[$variable['key']])) {
                $errors[] = "Variável obrigatória '{$variable['key']}' não fornecida";
            }
        }

        return $errors;
    }

    /**
     * Obtém informações para exibição
     */
    public function getDisplayInfoAttribute(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'type' => $this->type,
            'channel' => $this->channel,
            'subject' => $this->subject,
            'content' => $this->content,
            'html_content' => $this->html_content,
            'variables' => $this->variables,
            'locale' => $this->locale,
            'is_active' => $this->is_active,
            'configuration' => $this->configuration,
            'description' => $this->description,
            'formatted_created_at' => $this->created_at->format('d/m/Y H:i'),
        ];
    }

    /**
     * Ativa o template
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Desativa o template
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Obtém configuração específica
     */
    public function getConfig(string $key, $default = null)
    {
        return $this->configuration[$key] ?? $default;
    }

    /**
     * Define configuração específica
     */
    public function setConfig(string $key, $value): void
    {
        $config = $this->configuration ?? [];
        $config[$key] = $value;
        $this->configuration = $config;
        $this->save();
    }

    /**
     * Obtém variáveis disponíveis formatadas
     */
    public function getFormattedVariablesAttribute(): array
    {
        $variables = $this->variables ?? [];

        return array_map(function ($variable) {
            return [
                'key' => $variable['key'] ?? '',
                'label' => $variable['label'] ?? ucfirst(str_replace('_', ' ', $variable['key'] ?? '')),
                'description' => $variable['description'] ?? '',
                'required' => $variable['required'] ?? false,
                'type' => $variable['type'] ?? 'string',
                'example' => $variable['example'] ?? '',
            ];
        }, $variables);
    }

    /**
     * Verifica se é um template de email
     */
    public function isEmail(): bool
    {
        return $this->channel === 'mail';
    }

    /**
     * Verifica se é um template SMS
     */
    public function isSMS(): bool
    {
        return $this->channel === 'sms';
    }

    /**
     * Verifica se é um template push
     */
    public function isPush(): bool
    {
        return $this->channel === 'push';
    }

    /**
     * Verifica se é um template de banco de dados
     */
    public function isDatabase(): bool
    {
        return $this->channel === 'database';
    }

    /**
     * Obtém preview do template
     */
    public function getPreviewAttribute(): array
    {
        // Dados de exemplo para preview
        $sampleData = [];
        foreach ($this->variables ?? [] as $variable) {
            $key = $variable['key'] ?? '';
            $sampleData[$key] = $variable['example'] ?? 'Exemplo de ' . $key;
        }

        return $this->process($sampleData);
    }

    /**
     * Duplica o template
     */
    public function duplicate(string $newCode = null): self
    {
        $data = $this->toArray();
        unset($data['id'], $data['code'], $data['created_at'], $data['updated_at']);

        if ($newCode) {
            $data['code'] = $newCode;
        } else {
            $data['code'] = $this->code . '_copy_' . time();
        }

        return self::create($data);
    }
}