<?php

namespace LaravelEcommerce\Store\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShippingZone extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nome da tabela
     */
    protected $table = 'shipping_zones';

    /**
     * Campos preenchíveis
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'type',
        'country_code',
        'state_code',
        'city',
        'zip_start',
        'zip_end',
        'zip_codes',
        'conditions',
        'is_active',
        'sort_order',
    ];

    /**
     * Campos que devem ser convertidos para tipos específicos
     */
    protected $casts = [
        'type' => 'string',
        'country_code' => 'string',
        'state_code' => 'string',
        'city' => 'string',
        'zip_start' => 'string',
        'zip_end' => 'string',
        'zip_codes' => 'array',
        'conditions' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
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
     * Tipos de zona disponíveis
     */
    const TYPES = [
        'country' => 'País',
        'state' => 'Estado',
        'city' => 'Cidade',
        'zip_range' => 'Faixa de CEP',
        'custom' => 'Personalizada',
    ];

    /**
     * Métodos de envio relacionados
     */
    public function methods(): BelongsToMany
    {
        return $this->belongsToMany(ShippingMethod::class, 'shipping_method_zones', 'shipping_zone_id', 'shipping_method_id');
    }

    /**
     * Scope para zonas ativas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para zonas por tipo
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope para zonas ordenadas
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Verifica se a zona está ativa
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Verifica se um endereço pertence a esta zona
     */
    public function containsAddress(array $address): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        switch ($this->type) {
            case 'country':
                return $address['country_code'] === $this->country_code;

            case 'state':
                return $address['country_code'] === $this->country_code &&
                       $address['state_code'] === $this->state_code;

            case 'city':
                return $address['country_code'] === $this->country_code &&
                       $address['state_code'] === $this->state_code &&
                       strtolower($address['city']) === strtolower($this->city);

            case 'zip_range':
                if (!isset($address['zip_code'])) {
                    return false;
                }
                return $this->isInZipRange($address['zip_code']);

            case 'custom':
                return $this->matchesCustomConditions($address);

            default:
                return false;
        }
    }

    /**
     * Verifica se um CEP está na faixa definida
     */
    protected function isInZipRange(string $zipCode): bool
    {
        // Remove caracteres não numéricos
        $zipCode = preg_replace('/\D/', '', $zipCode);

        if ($this->zip_codes && is_array($this->zip_codes)) {
            return in_array($zipCode, $this->zip_codes);
        }

        if ($this->zip_start && $this->zip_end) {
            $zipStart = preg_replace('/\D/', '', $this->zip_start);
            $zipEnd = preg_replace('/\D/', '', $this->zip_end);
            $zipCode = preg_replace('/\D/', '', $zipCode);

            return $zipCode >= $zipStart && $zipCode <= $zipEnd;
        }

        return false;
    }

    /**
     * Verifica se o endereço atende às condições personalizadas
     */
    protected function matchesCustomConditions(array $address): bool
    {
        if (!$this->conditions || !is_array($this->conditions)) {
            return true;
        }

        foreach ($this->conditions as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? 'equals';
            $value = $condition['value'] ?? null;

            if (!$field || $value === null) {
                continue;
            }

            $addressValue = $address[$field] ?? null;

            switch ($operator) {
                case 'equals':
                    if ($addressValue !== $value) {
                        return false;
                    }
                    break;

                case 'not_equals':
                    if ($addressValue === $value) {
                        return false;
                    }
                    break;

                case 'contains':
                    if (strpos(strtolower($addressValue), strtolower($value)) === false) {
                        return false;
                    }
                    break;

                case 'not_contains':
                    if (strpos(strtolower($addressValue), strtolower($value)) !== false) {
                        return false;
                    }
                    break;

                case 'starts_with':
                    if (strpos(strtolower($addressValue), strtolower($value)) !== 0) {
                        return false;
                    }
                    break;

                case 'ends_with':
                    if (substr(strtolower($addressValue), -strlen($value)) !== strtolower($value)) {
                        return false;
                    }
                    break;

                case 'in':
                    if (!in_array($addressValue, (array) $value)) {
                        return false;
                    }
                    break;

                case 'not_in':
                    if (in_array($addressValue, (array) $value)) {
                        return false;
                    }
                    break;
            }
        }

        return true;
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
            'description' => $this->description,
            'type' => $this->type,
            'type_label' => self::TYPES[$this->type] ?? $this->type,
            'country_code' => $this->country_code,
            'state_code' => $this->state_code,
            'city' => $this->city,
            'zip_start' => $this->zip_start,
            'zip_end' => $this->zip_end,
            'zip_codes' => $this->zip_codes,
            'conditions' => $this->conditions,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'formatted_created_at' => $this->created_at->format('d/m/Y H:i'),
        ];
    }

    /**
     * Ativa a zona de envio
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Desativa a zona de envio
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Obtém descrição da zona
     */
    public function getZoneDescriptionAttribute(): string
    {
        switch ($this->type) {
            case 'country':
                return 'País: ' . $this->country_code;
            case 'state':
                return 'Estado: ' . $this->state_code . ' (' . $this->country_code . ')';
            case 'city':
                return 'Cidade: ' . $this->city . ' - ' . $this->state_code;
            case 'zip_range':
                return 'CEP: ' . $this->zip_start . ' - ' . $this->zip_end;
            case 'custom':
                return 'Zona personalizada';
            default:
                return 'Zona não definida';
        }
    }

    /**
     * Adiciona CEP à lista
     */
    public function addZipCode(string $zipCode): void
    {
        $zipCodes = $this->zip_codes ?? [];
        $zipCodes[] = preg_replace('/\D/', '', $zipCode);
        $zipCodes = array_unique($zipCodes);
        sort($zipCodes);

        $this->zip_codes = array_values($zipCodes);
        $this->save();
    }

    /**
     * Remove CEP da lista
     */
    public function removeZipCode(string $zipCode): void
    {
        $zipCodes = $this->zip_codes ?? [];
        $zipCode = preg_replace('/\D/', '', $zipCode);

        $key = array_search($zipCode, $zipCodes);
        if ($key !== false) {
            unset($zipCodes[$key]);
            $this->zip_codes = array_values($zipCodes);
            $this->save();
        }
    }
}