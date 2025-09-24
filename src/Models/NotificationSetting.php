<?php

namespace LaravelEcommerce\Store\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    use HasFactory;

    /**
     * Nome da tabela
     */
    protected $table = 'notification_settings';

    /**
     * Campos preenchíveis
     */
    protected $fillable = [
        'type',
        'key',
        'value',
        'group',
        'description',
        'input_type',
        'options',
        'is_public',
        'sort_order',
    ];

    /**
     * Campos que devem ser convertidos para tipos específicos
     */
    protected $casts = [
        'type' => 'string',
        'key' => 'string',
        'value' => 'string',
        'group' => 'string',
        'description' => 'string',
        'input_type' => 'string',
        'options' => 'array',
        'is_public' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Campos que devem ser ocultos na serialização
     */
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * Tipos de input disponíveis
     */
    const INPUT_TYPES = [
        'text' => 'Texto',
        'textarea' => 'Área de Texto',
        'select' => 'Seleção',
        'radio' => 'Radio',
        'checkbox' => 'Checkbox',
        'boolean' => 'Sim/Não',
        'number' => 'Número',
        'email' => 'Email',
        'url' => 'URL',
        'password' => 'Senha',
        'color' => 'Cor',
        'file' => 'Arquivo',
    ];

    /**
     * Scope para configurações por tipo
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope para configurações por grupo
     */
    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Scope para configurações públicas
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope para configurações ordenadas
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('key');
    }

    /**
     * Obtém valor da configuração
     */
    public function getValue(string $default = null)
    {
        return $this->value ?? $default;
    }

    /**
     * Define valor da configuração
     */
    public function setValue($value): void
    {
        $this->update(['value' => $value]);
    }

    /**
     * Obtém opções formatadas
     */
    public function getFormattedOptionsAttribute(): array
    {
        $options = $this->options ?? [];

        return array_map(function ($option) {
            if (is_array($option)) {
                return [
                    'value' => $option['value'] ?? '',
                    'label' => $option['label'] ?? '',
                    'description' => $option['description'] ?? '',
                ];
            }

            return [
                'value' => $option,
                'label' => ucfirst(str_replace('_', ' ', $option)),
                'description' => '',
            ];
        }, $options);
    }

    /**
     * Obtém informações para exibição
     */
    public function getDisplayInfoAttribute(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'key' => $this->key,
            'value' => $this->value,
            'group' => $this->group,
            'description' => $this->description,
            'input_type' => $this->input_type,
            'input_type_label' => self::INPUT_TYPES[$this->input_type] ?? $this->input_type,
            'options' => $this->formatted_options,
            'is_public' => $this->is_public,
            'sort_order' => $this->sort_order,
            'formatted_created_at' => $this->created_at->format('d/m/Y H:i'),
        ];
    }

    /**
     * Verifica se é uma configuração pública
     */
    public function isPublic(): bool
    {
        return $this->is_public;
    }

    /**
     * Verifica se é uma configuração de email
     */
    public function isEmail(): bool
    {
        return $this->type === 'email';
    }

    /**
     * Verifica se é uma configuração SMS
     */
    public function isSMS(): bool
    {
        return $this->type === 'sms';
    }

    /**
     * Verifica se é uma configuração push
     */
    public function isPush(): bool
    {
        return $this->type === 'push';
    }

    /**
     * Verifica se é uma configuração geral
     */
    public function isGeneral(): bool
    {
        return $this->type === 'general';
    }

    /**
     * Obtém configuração como boolean
     */
    public function getBooleanValue(bool $default = false): bool
    {
        if ($this->value === null) {
            return $default;
        }

        return in_array(strtolower($this->value), ['true', '1', 'yes', 'on', 'enabled']);
    }

    /**
     * Obtém configuração como número
     */
    public function getNumericValue(float $default = 0): float
    {
        if ($this->value === null) {
            return $default;
        }

        return (float) $this->value;
    }

    /**
     * Obtém configuração como array
     */
    public function getArrayValue(array $default = []): array
    {
        if ($this->value === null) {
            return $default;
        }

        $decoded = json_decode($this->value, true);

        return is_array($decoded) ? $decoded : $default;
    }

    /**
     * Valida valor da configuração
     */
    public function validateValue($value): array
    {
        $errors = [];

        switch ($this->input_type) {
            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Email inválido';
                }
                break;

            case 'url':
                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                    $errors[] = 'URL inválida';
                }
                break;

            case 'number':
                if (!is_numeric($value)) {
                    $errors[] = 'Valor deve ser numérico';
                }
                break;

            case 'select':
            case 'radio':
                if ($this->options && !in_array($value, array_column($this->options, 'value'))) {
                    $errors[] = 'Opção inválida';
                }
                break;
        }

        return $errors;
    }

    /**
     * Obtém grupos de configuração disponíveis
     */
    public static function getAvailableGroups(): array
    {
        return [
            'general' => 'Geral',
            'email' => 'Email',
            'sms' => 'SMS',
            'push' => 'Push',
            'webhook' => 'Webhook',
            'api' => 'API',
            'security' => 'Segurança',
            'performance' => 'Performance',
        ];
    }

    /**
     * Obtém configurações por grupo
     */
    public static function getByGroup(string $group): array
    {
        return self::byGroup($group)
                  ->ordered()
                  ->get()
                  ->mapWithKeys(function ($setting) {
                      return [$setting->key => $setting->getValue()];
                  })
                  ->toArray();
    }

    /**
     * Define configuração
     */
    public static function set(string $key, $value, string $type = 'general'): void
    {
        self::updateOrCreate(
            ['type' => $type, 'key' => $key],
            ['value' => $value]
        );
    }

    /**
     * Obtém configuração
     */
    public static function get(string $key, $default = null, string $type = 'general')
    {
        $setting = self::where('type', $type)->where('key', $key)->first();

        return $setting ? $setting->getValue($default) : $default;
    }
}