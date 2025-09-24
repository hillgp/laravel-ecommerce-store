<?php

namespace LaravelEcommerce\Store\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentGatewayConfig extends Model
{
    use HasFactory;

    /**
     * Nome da tabela
     */
    protected $table = 'payment_gateway_configs';

    /**
     * Campos preenchíveis
     */
    protected $fillable = [
        'gateway',
        'environment',
        'key',
        'value',
        'type',
        'description',
        'is_public',
        'is_required',
        'validation_rules',
        'meta_data',
    ];

    /**
     * Campos que devem ser convertidos para tipos específicos
     */
    protected $casts = [
        'is_public' => 'boolean',
        'is_required' => 'boolean',
        'validation_rules' => 'array',
        'meta_data' => 'array',
    ];

    /**
     * Campos que devem ser ocultos na serialização
     */
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * Tipos de valor suportados
     */
    const TYPES = [
        'string' => 'Texto',
        'boolean' => 'Booleano',
        'number' => 'Número',
        'json' => 'JSON',
        'password' => 'Senha',
        'url' => 'URL',
        'email' => 'Email',
    ];

    /**
     * Gateways suportados
     */
    const GATEWAYS = [
        'stripe' => 'Stripe',
        'mercadopago' => 'Mercado Pago',
        'pagseguro' => 'PagSeguro',
        'paypal' => 'PayPal',
        'manual' => 'Manual',
    ];

    /**
     * Ambientes suportados
     */
    const ENVIRONMENTS = [
        'sandbox' => 'Sandbox',
        'production' => 'Produção',
    ];

    /**
     * Scope para configurações por gateway
     */
    public function scopeByGateway($query, string $gateway)
    {
        return $query->where('gateway', $gateway);
    }

    /**
     * Scope para configurações por ambiente
     */
    public function scopeByEnvironment($query, string $environment)
    {
        return $query->where('environment', $environment);
    }

    /**
     * Scope para configurações públicas
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope para configurações obrigatórias
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Scope para configurações por tipo
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Obtém o valor convertido baseado no tipo
     */
    public function getConvertedValueAttribute()
    {
        if ($this->value === null) {
            return null;
        }

        return match ($this->type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'number' => is_numeric($this->value) ? (strpos($this->value, '.') !== false ? (float) $this->value : (int) $this->value) : 0,
            'json' => json_decode($this->value, true),
            default => $this->value,
        };
    }

    /**
     * Define o valor convertendo baseado no tipo
     */
    public function setConvertedValueAttribute($value): void
    {
        $this->value = match ($this->type) {
            'boolean' => $value ? 'true' : 'false',
            'number' => (string) $value,
            'json' => json_encode($value),
            default => (string) $value,
        };
    }

    /**
     * Obtém o tipo formatado
     */
    public function getFormattedTypeAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /**
     * Obtém o gateway formatado
     */
    public function getFormattedGatewayAttribute(): string
    {
        return self::GATEWAYS[$this->gateway] ?? $this->gateway;
    }

    /**
     * Obtém o ambiente formatado
     */
    public function getFormattedEnvironmentAttribute(): string
    {
        return self::ENVIRONMENTS[$this->environment] ?? $this->environment;
    }

    /**
     * Verifica se o valor é válido baseado nas regras de validação
     */
    public function isValid(): bool
    {
        if (empty($this->validation_rules)) {
            return true;
        }

        $value = $this->converted_value;

        foreach ($this->validation_rules as $rule => $parameters) {
            if (!$this->validateRule($value, $rule, $parameters)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Valida uma regra específica
     */
    protected function validateRule($value, string $rule, $parameters): bool
    {
        return match ($rule) {
            'required' => !empty($value),
            'min' => is_string($value) ? strlen($value) >= $parameters : $value >= $parameters,
            'max' => is_string($value) ? strlen($value) <= $parameters : $value <= $parameters,
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'url' => filter_var($value, FILTER_VALIDATE_URL) !== false,
            'regex' => preg_match($parameters, $value) === 1,
            'in' => in_array($value, $parameters),
            default => true,
        };
    }

    /**
     * Obtém as regras de validação formatadas
     */
    public function getFormattedValidationRulesAttribute(): array
    {
        $formatted = [];

        foreach ($this->validation_rules as $rule => $parameters) {
            $formatted[$rule] = [
                'parameters' => $parameters,
                'description' => $this->getRuleDescription($rule, $parameters),
            ];
        }

        return $formatted;
    }

    /**
     * Obtém a descrição de uma regra de validação
     */
    protected function getRuleDescription(string $rule, $parameters): string
    {
        return match ($rule) {
            'required' => 'Campo obrigatório',
            'min' => "Valor mínimo: {$parameters}",
            'max' => "Valor máximo: {$parameters}",
            'email' => 'Deve ser um email válido',
            'url' => 'Deve ser uma URL válida',
            'regex' => 'Deve seguir o padrão especificado',
            'in' => 'Deve ser um dos seguintes valores: ' . implode(', ', $parameters),
            default => "Regra: {$rule}",
        };
    }

    /**
     * Obtém informações para exibição
     */
    public function getDisplayInfoAttribute(): array
    {
        return [
            'id' => $this->id,
            'gateway' => $this->gateway,
            'formatted_gateway' => $this->formatted_gateway,
            'environment' => $this->environment,
            'formatted_environment' => $this->formatted_environment,
            'key' => $this->key,
            'value' => $this->type === 'password' ? '********' : $this->value,
            'type' => $this->type,
            'formatted_type' => $this->formatted_type,
            'description' => $this->description,
            'is_public' => $this->is_public,
            'is_required' => $this->is_required,
            'is_valid' => $this->isValid(),
            'validation_rules' => $this->formatted_validation_rules,
            'meta_data' => $this->meta_data,
        ];
    }

    /**
     * Obtém estatísticas da configuração
     */
    public function getStatsAttribute(): array
    {
        return [
            'gateway' => $this->gateway,
            'environment' => $this->environment,
            'type' => $this->type,
            'is_public' => $this->is_public,
            'is_required' => $this->is_required,
            'has_value' => !empty($this->value),
            'is_valid' => $this->isValid(),
            'has_validation_rules' => !empty($this->validation_rules),
        ];
    }

    /**
     * Obtém metadados
     */
    public function getMeta(string $key)
    {
        return $this->meta_data[$key] ?? null;
    }

    /**
     * Define metadados
     */
    public function setMeta(string $key, $value): void
    {
        $metaData = $this->meta_data ?? [];
        $metaData[$key] = $value;
        $this->meta_data = $metaData;
        $this->save();
    }

    /**
     * Remove metadados
     */
    public function removeMeta(string $key): void
    {
        $metaData = $this->meta_data ?? [];
        unset($metaData[$key]);
        $this->meta_data = $metaData;
        $this->save();
    }

    /**
     * Verifica se a configuração pode ser editada
     */
    public function canBeEdited(): bool
    {
        return true; // Por enquanto todas podem ser editadas
    }

    /**
     * Verifica se a configuração pode ser deletada
     */
    public function canBeDeleted(): bool
    {
        return !$this->is_required;
    }

    /**
     * Obtém configurações de um gateway específico
     */
    public static function getGatewayConfig(string $gateway, string $environment = 'production'): array
    {
        return self::byGateway($gateway)
            ->byEnvironment($environment)
            ->get()
            ->pluck('converted_value', 'key')
            ->toArray();
    }

    /**
     * Define configurações de um gateway
     */
    public static function setGatewayConfig(string $gateway, string $environment, array $config): void
    {
        foreach ($config as $key => $value) {
            self::updateOrCreate(
                [
                    'gateway' => $gateway,
                    'environment' => $environment,
                    'key' => $key,
                ],
                [
                    'value' => $value,
                    'type' => self::inferType($value),
                ]
            );
        }
    }

    /**
     * Infere o tipo de um valor
     */
    protected static function inferType($value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        }

        if (is_numeric($value)) {
            return 'number';
        }

        if (is_array($value) || is_object($value)) {
            return 'json';
        }

        return 'string';
    }

    /**
     * Valida todas as configurações de um gateway
     */
    public static function validateGatewayConfig(string $gateway, string $environment): array
    {
        $configs = self::byGateway($gateway)
            ->byEnvironment($environment)
            ->required()
            ->get();

        $errors = [];

        foreach ($configs as $config) {
            if (!$config->isValid()) {
                $errors[] = "Configuração '{$config->key}' inválida";
            }
        }

        return $errors;
    }

    /**
     * Obtém configurações obrigatórias que estão faltando
     */
    public static function getMissingRequiredConfigs(string $gateway, string $environment): array
    {
        $requiredConfigs = self::byGateway($gateway)
            ->byEnvironment($environment)
            ->required()
            ->get();

        $missing = [];

        foreach ($requiredConfigs as $config) {
            if (empty($config->value)) {
                $missing[] = $config->key;
            }
        }

        return $missing;
    }
}